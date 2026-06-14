<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FreeImageService
{
    private string $apiKey;
    private string $uploadUrl = 'https://freeimage.host/api/1/upload';

    public function __construct()
    {
        $this->apiKey = config('services.freeimage.key', '');
    }

    public function upload(UploadedFile $file): ?string
    {
        if (empty($this->apiKey)) {
            Log::error('FreeImage API key not configured');
            return null;
        }

        try {
            $base64 = base64_encode(file_get_contents($file->getRealPath()));

            $response = Http::post($this->uploadUrl, [
                'key' => $this->apiKey,
                'source' => $base64,
            ]);

            if ($response->successful()) {
                return $response->json('image.url');
            }

            Log::error('FreeImage upload failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('FreeImage upload error: ' . $e->getMessage());
            return null;
        }
    }

    public function uploadFromContent(string $content, string $filename = 'image.png'): ?string
    {
        if (empty($this->apiKey)) {
            Log::error('FreeImage API key not configured');
            return null;
        }

        try {
            $base64 = base64_encode($content);

            $response = Http::post($this->uploadUrl, [
                'key' => $this->apiKey,
                'source' => $base64,
            ]);

            if ($response->successful()) {
                return $response->json('image.url');
            }

            Log::error('FreeImage upload failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('FreeImage upload error: ' . $e->getMessage());
            return null;
        }
    }
}
