<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Design;
use App\Services\AIChatService;
use App\Services\ChatContextService;
use App\Services\ConversationTitleService;
use Illuminate\Http\Request;

class AIChatController extends Controller
{
    private const MAX_HISTORY = 20;
    private const MAX_STORED = 40;

    public function __construct(
        private AIChatService $aiChatService,
        private ChatContextService $chatContextService,
        private ConversationTitleService $titleService
    ) {}

    public function chat(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'conversation_id' => 'nullable|integer',
            'design_id' => 'nullable|integer',
        ]);

        $message = $validated['message'];
        $user = $request->user();
        $session = $request->input('user_session');
        $conversationId = $validated['conversation_id'] ?? null;

        // Optional attached design (must belong to the user). When provided, the
        // AI receives its image so it can see/discuss the user's own design.
        $attachmentImageUrl = null;
        $attachmentDesignId = null;
        if (!empty($validated['design_id'])) {
            $design = Design::where('user_id', $user->id)->find($validated['design_id']);
            if ($design) {
                $attachmentImageUrl = $design->image_url;
                $attachmentDesignId = $design->id;
            }
        }

        // Auto-create a conversation when the client doesn't provide one yet.
        // Title is derived from the first prompt via a local heuristic.
        $conversation = null;
        if ($conversationId) {
            $conversation = Conversation::forUser($user->id)->find($conversationId);
        }
        if (!$conversation) {
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'user_session' => $session,
                'title' => $this->titleService->generate($message),
                'pinned' => false,
                'status' => 'active',
            ]);
            $conversationId = $conversation->id;
        }

        // Persist the user's message so the conversation survives reloads.
        ChatMessage::create([
            'user_id' => $user->id,
            'user_session' => $session,
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $message,
            'design_id' => $attachmentDesignId,
            'image_url' => $attachmentImageUrl,
        ]);

        // Build the conversation: recent history (if any) for this conversation.
        $history = ChatMessage::where('user_id', $user->id)
            ->where('conversation_id', $conversation->id)
            ->orderBy('id', 'desc')
            ->limit(self::MAX_HISTORY)
            ->get()
            ->reverse()
            ->map(fn (ChatMessage $m) => [
                'role' => $m->role,
                'content' => $m->content,
            ])
            ->values()
            ->toArray();

        // Inject the current database data as an additional system message.
        $context = $this->chatContextService->buildContext($message);
        $messages = [];
        if ($context !== '') {
            $messages[] = ['role' => 'system', 'content' => $context];
        }
        $messages = array_merge($messages, $history);

        $reply = $this->aiChatService->chat($messages, $attachmentImageUrl);

        ChatMessage::create([
            'user_id' => $user->id,
            'user_session' => $session,
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $reply,
        ]);

        // Keep the conversation bounded so old exchanges never bloat the prompt.
        $this->pruneUser($user->id);

        $conversation->touch();

        return response()->json([
            'reply' => $reply,
            'conversation' => $conversation->fresh(),
        ]);
    }

    public function history(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'nullable|integer',
        ]);

        $conversationId = $validated['conversation_id'] ?? null;

        // Legacy fallback: without a conversation_id, return the most recent
        // active conversation's messages (single-thread clients like Order Step4).
        if (!$conversationId) {
            $latest = Conversation::forUser($request->user()->id)
                ->where('status', 'active')
                ->latest()
                ->first();
            $conversationId = $latest?->id;
        }

        $query = ChatMessage::where('user_id', $request->user()->id);
        if ($conversationId) {
            $query->where('conversation_id', $conversationId);
        }

        $messages = $query
            ->orderBy('id', 'asc')
            ->limit(self::MAX_STORED)
            ->get()
            ->map(fn (ChatMessage $m) => [
                'role' => $m->role,
                'text' => $m->content,
                'design_id' => $m->design_id,
                'image_url' => $m->image_url,
                'created_at' => $m->created_at,
            ]);

        return response()->json([
            'messages' => $messages,
        ]);
    }

    private function pruneUser(int $userId): void
    {
        $ids = ChatMessage::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->pluck('id');

        if ($ids->count() <= self::MAX_STORED) {
            return;
        }

        ChatMessage::whereIn('id', $ids->slice(self::MAX_STORED)->all())->delete();
    }
}
