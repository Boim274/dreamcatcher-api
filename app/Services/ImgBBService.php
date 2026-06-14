<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImgBBService
{
    private string $apiKey;
    private string $uploadUrl = 'https://api.imgbb.com/1/upload';

    public function __construct()
    {
        $this->apiKey = config('services.imgbb.key', '');
    }

    public function upload(UploadedFile $file): ?string
    {
        if (empty($this->apiKey)) {
            Log::error('ImgBB API key not configured');
            return null;
        }

        try {
            $base64 = base64_encode(file_get_contents($file->getRealPath()));

            $response = Http::post($this->uploadUrl, [
                'key' => $this->apiKey,
                'image' => $base64,
            ]);

            if ($response->successful()) {
                $data = $response->json('data');
                return $data['url'] ?? null;
            }

            Log::error('ImgBB upload failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('ImgBB upload error: ' . $e->getMessage());
            return null;
        }
    }

    public function uploadFromContent(string $content, string $filename = 'image.png'): ?string
    {
        if (empty($this->apiKey)) {
            Log::error('ImgBB API key not configured');
            return null;
        }

        try {
            $base64 = base64_encode($content);

            $response = Http::post($this->uploadUrl, [
                'key' => $this->apiKey,
                'image' => $base64,
                'name' => pathinfo($filename, PATHINFO_FILENAME),
            ]);

            if ($response->successful()) {
                $data = $response->json('data');
                return $data['url'] ?? null;
            }

            Log::error('ImgBB upload failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('ImgBB upload error: ' . $e->getMessage());
            return null;
        }
    }
}
