<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Design;
use App\Services\CloudinaryService;
use App\Services\ConversationTitleService;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(
        private ConversationTitleService $titleService,
        private CloudinaryService $cloudinaryService
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'sort' => 'nullable|in:newest,oldest',
            'trashed' => 'nullable|in:0,1,true,false',
        ]);

        $user = $request->user();
        $trashed = filter_var($validated['trashed'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $conversations = Conversation::forUser($user->id)
            ->where('status', $trashed ? 'trashed' : 'active')
            ->search($validated['search'] ?? null)
            ->ordered($validated['sort'] ?? 'newest')
            ->get()
            ->map(function (Conversation $c) {
                $last = $c->messages()->latest()->first();
                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'emoji' => $c->emoji,
                    'pinned' => $c->pinned,
                    'status' => $c->status,
                    'created_at' => $c->created_at,
                    'updated_at' => $c->updated_at,
                    'trashed_at' => $c->trashed_at,
                    'last_message' => $last ? $this->stripAssistantBlocks($last->content) : null,
                    'last_role' => $last?->role,
                    'message_count' => $c->messages()->count(),
                ];
            });

        return response()->json([
            'conversations' => $conversations,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'prompt' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();
        $title = $validated['title'] ?? null;
        if (empty($title)) {
            $title = $this->titleService->generate($validated['prompt'] ?? '');
        }

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'user_session' => $request->input('user_session'),
            'title' => $title,
            'pinned' => false,
            'status' => 'active',
        ]);

        return response()->json([
            'conversation' => $conversation,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $conversation = $this->owned($request, $id);

        return response()->json([
            'conversation' => $conversation,
            'messages' => $conversation->messages()->orderBy('id', 'asc')->get()->map(fn ($m) => [
                'role' => $m->role,
                'text' => $m->content,
                'design_id' => $m->design_id,
                'image_url' => $m->image_url,
                'created_at' => $m->created_at,
            ]),
            'designs' => $conversation->designs()->orderBy('id', 'asc')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'emoji' => 'nullable|string|max:16',
            'pinned' => 'nullable|boolean',
        ]);

        $conversation = $this->owned($request, $id);

        $conversation->update(array_filter([
            'title' => $validated['title'] ?? null,
            'emoji' => $validated['emoji'] ?? null,
            'pinned' => array_key_exists('pinned', $validated) ? $validated['pinned'] : null,
        ], fn ($v) => $v !== null));

        return response()->json([
            'conversation' => $conversation->fresh(),
        ]);
    }

    public function duplicate(Request $request, $id)
    {
        $conversation = $this->owned($request, $id);

        $copy = Conversation::create([
            'user_id' => $conversation->user_id,
            'user_session' => $conversation->user_session,
            'title' => $conversation->title . ' (Salinan)',
            'emoji' => $conversation->emoji,
            'pinned' => false,
            'status' => 'active',
        ]);

        foreach ($conversation->messages()->orderBy('id', 'asc')->get() as $message) {
            $copy->messages()->create([
                'user_id' => $message->user_id,
                'user_session' => $message->user_session,
                'role' => $message->role,
                'content' => $message->content,
            ]);
        }

        $idMap = [];
        foreach ($conversation->designs()->orderBy('id', 'asc')->get() as $design) {
            $cloned = $copy->designs()->create([
                'user_id' => $design->user_id,
                'user_session' => $design->user_session,
                'prompt' => $design->prompt,
                'image_url' => $design->image_url,
                'cloudinary_public_id' => $design->cloudinary_public_id,
                'color_count' => $design->color_count,
                'has_gradient' => $design->has_gradient,
                'has_shadow' => $design->has_shadow,
                'contrast_score' => $design->contrast_score,
                'is_valid' => $design->is_valid,
                'design_type' => $design->design_type,
                'kind' => $design->kind,
                'version' => $design->version,
                'setting' => $design->setting,
                'validation_message' => $design->validation_message,
            ]);
            $idMap[$design->id] = $cloned->id;
        }

        // Remap parent_id so the version chain inside the copy stays intact.
        if (!empty($idMap)) {
            foreach ($copy->designs as $clonedDesign) {
                if ($clonedDesign->parent_id && isset($idMap[$clonedDesign->parent_id])) {
                    $clonedDesign->update(['parent_id' => $idMap[$clonedDesign->parent_id]]);
                }
            }
        }

        return response()->json([
            'conversation' => $copy->fresh(),
        ], 201);
    }

    public function destroy(Request $request, $id)
    {
        $conversation = $this->owned($request, $id);
        $conversation->update([
            'status' => 'trashed',
            'trashed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Conversation dipindahkan ke sampah.',
        ]);
    }

    public function restore(Request $request, $id)
    {
        $conversation = $this->owned($request, $id);
        $conversation->update([
            'status' => 'active',
            'trashed_at' => null,
        ]);

        return response()->json([
            'conversation' => $conversation->fresh(),
        ]);
    }

    public function forceDestroy(Request $request, $id)
    {
        $conversation = $this->owned($request, $id);

        foreach ($conversation->designs as $design) {
            $this->deleteDesign($design);
        }
        $conversation->messages()->delete();
        $conversation->delete();

        return response()->json([
            'message' => 'Conversation dihapus permanen.',
        ]);
    }

    private function owned(Request $request, $id): Conversation
    {
        return Conversation::forUser($request->user()->id)->findOrFail($id);
    }

    // Removes internal fenced JSON blocks (```json, ```questions, or an
    // unlabeled fence whose body is a JSON object) so protocol payloads like
    // the { "status": "ready", ... } contract never leak into user-visible
    // previews. Mirrors stripAssistantBlocks in the frontend.
    private function stripAssistantBlocks(string $text): string
    {
        $result = preg_replace_callback(
            '/```(?:json|questions)?\s*([\s\S]*?)```/i',
            function ($match) {
                $body = trim($match[1] ?? '');
                if ($body === '' || ($body[0] !== '{' || substr($body, -1) !== '}')) {
                    return $match[0];
                }
                return '';
            },
            $text
        );

        if ($result === null) {
            $result = $text;
        }

        return trim(preg_replace('/\n{3,}/', "\n\n", $result) ?? $result);
    }

    private function deleteDesign(Design $design): void
    {
        foreach ($design->children as $child) {
            $this->deleteDesign($child);
        }

        if ($design->cloudinary_public_id) {
            $this->cloudinaryService->delete($design->cloudinary_public_id);
        }

        $design->delete();
    }
}
