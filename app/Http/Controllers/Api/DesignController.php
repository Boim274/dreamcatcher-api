<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Services\AIDesignService;
use App\Services\AIQuotaService;
use App\Services\DesignValidationService;
use App\Services\CloudinaryService;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DesignController extends Controller
{
    public function __construct(
        private AIDesignService $aiDesignService,
        private DesignValidationService $validationService,
        private CloudinaryService $cloudinaryService,
        private AIQuotaService $aiQuotaService,
        private ImageOptimizationService $imageOptimizationService
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $designs = Design::where('user_id', $request->user()->id)
            ->when($validated['conversation_id'] ?? null, fn ($q, $id) => $q->where('conversation_id', $id))
            ->latest()
            ->paginate($validated['per_page'] ?? 24);

        return response()->json([
            'designs' => $designs,
        ]);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:1000',
            'count' => 'nullable|integer|min:1|max:3',
            'negative_prompt' => 'nullable|string|max:2000',
            'conversation_id' => 'nullable|integer',
            'sablon_type' => 'nullable|string|max:50',
        ]);

        $user = $request->user();

        // Enforce the per-user generate quota BEFORE sending the request to the
        // AI model so no token is spent once the quota is exhausted.
        if (!$this->aiQuotaService->hasGenerateRemaining($user)) {
            return $this->quotaExceeded('generate');
        }

        // Idempotency: if the same prompt was already generated for this
        // conversation within the last 60 seconds, return the existing design
        // instead of creating a duplicate.
        $recentDesign = Design::where('user_id', $user->id)
            ->where('prompt', $validated['prompt'])
            ->where('conversation_id', $validated['conversation_id'] ?? null)
            ->where('created_at', '>=', now()->subSeconds(60))
            ->latest()
            ->first();

        if ($recentDesign) {
            return response()->json([
                'message' => 'Designs generated successfully',
                'designs' => [$recentDesign],
            ], 200);
        }

        // Each generate process produces exactly one image (1 request = 1 image).
        $aiPrompt = $validated['prompt'];
        $negativePrompt = $validated['negative_prompt'] ?? null;

        $imageUrls = $this->aiDesignService->generate($aiPrompt, 1, $negativePrompt);

        if (empty($imageUrls)) {
            return response()->json([
                'message' => 'Failed to generate designs. Please try again.',
            ], 500);
        }

        $designs = [];
        foreach ($imageUrls as $imageUrl) {
            $imageContent = @file_get_contents($imageUrl);
            if ($imageContent === false) {
                continue;
            }

            $filename = 'design-' . Str::uuid() . '.png';
            $uploadResult = $this->cloudinaryService->uploadFromContent($imageContent, $filename);

            if (!$uploadResult) {
                continue;
            }

            $validation = $this->validationService->validateFromUrl($uploadResult['url'], $validated['sablon_type'] ?? null);

            $design = Design::create([
                'user_id' => $user->id,
                'user_session' => $request->input('user_session'),
                'conversation_id' => $validated['conversation_id'] ?? null,
                'prompt' => $validated['prompt'],
                'image_url' => $uploadResult['url'],
                'cloudinary_public_id' => $uploadResult['public_id'],
                'color_count' => $validation['color_count'],
                'has_gradient' => $validation['has_gradient'],
                'has_shadow' => $validation['has_shadow'],
                'contrast_score' => $validation['contrast_score'],
                'is_valid' => $validation['is_valid'],
                'design_type' => 'ai_generated',
                'setting' => ['sablon_type' => $validated['sablon_type'] ?? null],
                'validation_message' => $validation['message'],
            ]);

            $designs[] = $design;
        }

        if (empty($designs)) {
            return response()->json([
                'message' => 'Failed to process generated designs.',
            ], 500);
        }

        $this->aiQuotaService->consumeGenerate($user);

        return response()->json([
            'message' => 'Designs generated successfully',
            'designs' => $designs,
        ], 201);
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'conversation_id' => 'nullable|integer',
            'from_chat' => 'nullable|boolean',
            'sablon_type' => 'nullable|string|max:50',
        ]);

        $user = $request->user();
        $file = $request->file('image');
        $uploadResult = $this->imageOptimizationService->processProductionDesign($file, 'designs');

        if (!$uploadResult || empty($uploadResult['image_url'])) {
            return response()->json([
                'message' => 'Failed to upload design to storage.',
            ], 500);
        }

        $sablonType = $validated['sablon_type'] ?? null;
        $validation = $this->validationService->validateFromUrl($uploadResult['image_url'], $sablonType);

        $designType = !empty($validated['from_chat']) ? 'chat_upload' : 'uploaded';

        $design = Design::create([
            'user_id' => $user->id,
            'user_session' => $request->input('user_session'),
            'conversation_id' => $validated['conversation_id'] ?? null,
            'prompt' => $request->prompt ?? null,
            'image_url' => $uploadResult['image_url'],
            'cloudinary_public_id' => $uploadResult['cloudinary_public_id'] ?? null,
            'color_count' => $validation['color_count'],
            'has_gradient' => $validation['has_gradient'],
            'has_shadow' => $validation['has_shadow'],
            'contrast_score' => $validation['contrast_score'],
            'is_valid' => $validation['is_valid'],
            'design_type' => $designType,
            'setting' => ['sablon_type' => $validated['sablon_type'] ?? null],
            'validation_message' => $validation['message'],
        ]);

        return response()->json([
            'message' => 'Design uploaded successfully',
            'design' => $design,
            'validation' => $validation,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $design = Design::where('user_id', $request->user()->id)->findOrFail($id);

        return response()->json([
            'design' => $design,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $design = Design::where('user_id', $request->user()->id)->findOrFail($id);

        $this->deleteDesign($design);

        return response()->json([
            'message' => 'Design deleted successfully',
        ]);
    }

    public function enhance(Request $request, $id)
    {
        $user = $request->user();
        $design = Design::where('user_id', $user->id)->findOrFail($id);

        if (!$this->editableDesign($design)) {
            return response()->json([
                'success' => false,
                'message' => 'Edit hanya berlaku untuk desain yang dihasilkan melalui AI Generate.',
            ], 422);
        }

        if (!$this->aiQuotaService->hasEditRemaining($user)) {
            return $this->quotaExceeded('edit');
        }

        $imageUrls = $this->aiDesignService->enhance($design->image_url);

        $sablonType = $request->input('sablon_type') ?? ($design->setting['sablon_type'] ?? null);

        $response = $this->storeResult($request, $design, $imageUrls, 'enhanced', null, $sablonType);

        if ($response->getStatusCode() === 201) {
            $this->aiQuotaService->consumeEdit($user);
        }

        return $response;
    }

    public function edit(Request $request, $id)
    {
        $validated = $request->validate([
            'instruction' => 'required|string|max:2000',
            'count' => 'nullable|integer|min:1|max:3',
            'sablon_type' => 'nullable|string|max:50',
        ]);

        $user = $request->user();
        $design = Design::where('user_id', $user->id)->findOrFail($id);

        if (!$this->editableDesign($design)) {
            return response()->json([
                'success' => false,
                'message' => 'Edit hanya berlaku untuk desain yang dihasilkan melalui AI Generate.',
            ], 422);
        }

        if (!$this->aiQuotaService->hasEditRemaining($user)) {
            return $this->quotaExceeded('edit');
        }

        // Each edit process produces exactly one image.
        $imageUrls = $this->aiDesignService->editDesign(
            $validated['instruction'],
            $design->image_url,
            1
        );

        $sablonType = $validated['sablon_type'] ?? ($design->setting['sablon_type'] ?? null);

        $response = $this->storeResult($request, $design, $imageUrls, 'edit', $validated['instruction'], $sablonType);

        if ($response->getStatusCode() === 201) {
            $this->aiQuotaService->consumeEdit($user);
        }

        return $response;
    }

    public function versions(Request $request, $id)
    {
        $user = $request->user();
        $design = Design::where('user_id', $user->id)->findOrFail($id);

        $root = $design;
        while ($root->parent && $root->parent->user_id === $user->id) {
            $root = $root->parent;
        }

        $versions = Design::where('user_id', $user->id)
            ->where(fn ($q) => $q->where('id', $root->id)->orWhere('parent_id', $root->id))
            ->orderBy('version')
            ->get();

        return response()->json([
            'root' => $root,
            'versions' => $versions,
        ]);
    }

    private function storeResult(Request $request, Design $parent, array $imageUrls, string $kind, ?string $instruction = null, ?string $sablonType = null)
    {
        if (empty($imageUrls)) {
            return response()->json([
                'message' => 'Failed to process design. Please try again.',
            ], 500);
        }

        $user = $request->user();
        $designs = [];
        $nextVersion = ($parent->version ?? 0) + 1;

        foreach ($imageUrls as $index => $imageUrl) {
            $imageContent = @file_get_contents($imageUrl);
            if ($imageContent === false) {
                continue;
            }

            $filename = 'design-' . Str::uuid() . '.png';
            $uploadResult = $this->cloudinaryService->uploadFromContent($imageContent, $filename);

            if (!$uploadResult) {
                continue;
            }

$validation = $this->validationService->validateFromUrl($uploadResult['url'], $validated['sablon_type'] ?? null);

            $design = Design::create([
                'user_id' => $user->id,
                'user_session' => $request->input('user_session'),
                'conversation_id' => $parent->conversation_id,
                'parent_id' => $parent->id,
                'version' => $nextVersion,
                'prompt' => $instruction ?? ($kind === 'enhanced' ? 'Enhanced version' : null),
                'image_url' => $uploadResult['url'],
                'cloudinary_public_id' => $uploadResult['public_id'],
                'color_count' => $validation['color_count'],
                'has_gradient' => $validation['has_gradient'],
                'has_shadow' => $validation['has_shadow'],
                'contrast_score' => $validation['contrast_score'],
                'is_valid' => $validation['is_valid'],
                'design_type' => $kind === 'enhanced' ? 'ai_generated' : ($parent->design_type ?? 'ai_generated'),
                'kind' => $kind,
                'setting' => ['requested_index' => $index, 'sablon_type' => $sablonType],
                'validation_message' => $validation['message'],
            ]);

            $designs[] = $design;
        }

        if (empty($designs)) {
            return response()->json([
                'message' => 'Failed to process generated designs.',
            ], 500);
        }

        return response()->json([
            'message' => 'Design processed successfully',
            'designs' => $designs,
        ], 201);
    }

    public function quota(Request $request)
    {
        return response()->json([
            'success' => true,
            'quota' => $this->aiQuotaService->getQuota($request->user()),
        ]);
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

    private function editableDesign(Design $design): bool
    {
        return in_array($design->design_type, ['ai_generated', 'chat_upload'], true);
    }

    private function quotaExceeded(string $type)
    {
        $message = $type === 'generate'
            ? 'Kuota AI Generate Anda telah habis. Setiap akun hanya dapat membuat maksimal 3 gambar.'
            : 'Kuota AI Edit Anda telah habis. Anda telah menggunakan seluruh kuota edit yang tersedia.';

        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }

    public function validateDesign(Request $request, $id)
    {
        $design = Design::where('user_id', $request->user()->id)->findOrFail($id);

        $sablonType = $request->input('sablon_type') ?? ($design->setting['sablon_type'] ?? null);

        $validation = $this->validationService->validateFromUrl($design->image_url, $sablonType);

        $design->update([
            'color_count' => $validation['color_count'],
            'has_gradient' => $validation['has_gradient'],
            'has_shadow' => $validation['has_shadow'],
            'contrast_score' => $validation['contrast_score'],
            'is_valid' => $validation['is_valid'],
            'validation_message' => $validation['message'],
        ]);

        return response()->json([
            'message' => 'Design re-validated successfully',
            'design' => $design->fresh(),
            'validation' => $validation,
        ]);
    }
}
