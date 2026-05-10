<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Services\AIDesignService;
use App\Services\DesignValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DesignController extends Controller
{
    public function __construct(
        private AIDesignService $aiDesignService,
        private DesignValidationService $validationService
    ) {}

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:1000',
            'user_session' => 'nullable|string|max:255',
        ]);

        $aiPrompt = $validated['prompt'] . '. Flat vector design, maximum 3 solid colors, no gradients, no shadows, high contrast, suitable for screen printing. Simple clean lines.';

        $imageUrl = $this->aiDesignService->generate($aiPrompt);

        if (!$imageUrl) {
            return response()->json([
                'message' => 'Failed to generate design. Please try again.',
            ], 500);
        }

        $localPath = $this->downloadAndSaveImage($imageUrl);

        $validation = $this->validationService->validate($localPath);

        $design = Design::create([
            'user_session' => $validated['user_session'] ?? Str::uuid()->toString(),
            'prompt' => $validated['prompt'],
            'image_url' => $localPath,
            'color_count' => $validation['color_count'],
            'has_gradient' => $validation['has_gradient'],
            'has_shadow' => $validation['has_shadow'],
            'contrast_score' => $validation['contrast_score'],
            'is_valid' => $validation['is_valid'],
            'design_type' => 'ai_generated',
            'validation_message' => $validation['message'],
        ]);

        return response()->json([
            'message' => 'Design generated successfully',
            'design' => $design,
            'validation' => $validation,
        ], 201);
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'user_session' => 'nullable|string|max:255',
        ]);

        $path = $request->file('image')->store('designs', 'public');

        $localPath = 'storage/' . $path;

        $fullPath = storage_path('app/public/' . $path);

        $validation = $this->validationService->validate($fullPath);

        $design = Design::create([
            'user_session' => $validated['user_session'] ?? Str::uuid()->toString(),
            'prompt' => $request->prompt ?? null,
            'image_url' => $localPath,
            'color_count' => $validation['color_count'],
            'has_gradient' => $validation['has_gradient'],
            'has_shadow' => $validation['has_shadow'],
            'contrast_score' => $validation['contrast_score'],
            'is_valid' => $validation['is_valid'],
            'design_type' => 'uploaded',
            'validation_message' => $validation['message'],
        ]);

        return response()->json([
            'message' => 'Design uploaded successfully',
            'design' => $design,
            'validation' => $validation,
        ], 201);
    }

    public function show($id)
    {
        $design = Design::findOrFail($id);

        return response()->json([
            'design' => $design,
        ]);
    }

    public function validateDesign($id)
    {
        $design = Design::findOrFail($id);

        if (!file_exists(storage_path('app/public/' . str_replace('storage/', '', $design->image_url)))) {
            return response()->json([
                'message' => 'Design image not found',
            ], 404);
        }

        $fullPath = storage_path('app/public/' . str_replace('storage/', '', $design->image_url));

        $validation = $this->validationService->validate($fullPath);

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

    private function downloadAndSaveImage(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'follow_location' => true,
            ],
        ]);

        $imageContent = @file_get_contents($url, false, $context);

        if ($imageContent === false) {
            return $url;
        }

        $filename = 'designs/' . Str::uuid() . '.png';
        $path = Storage::disk('public')->put($filename, $imageContent);

        if ($path) {
            return 'storage/' . $filename;
        }

        return $url;
    }
}