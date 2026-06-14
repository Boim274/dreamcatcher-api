<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    private string $cloudName;
    private string $apiKey;
    private string $apiSecret;
    private string $uploadUrl;

    public function __construct()
    {
        $this->cloudName = config('services.cloudinary.cloud_name', '');
        $this->apiKey = config('services.cloudinary.api_key', '');
        $this->apiSecret = config('services.cloudinary.api_secret', '');
        $this->uploadUrl = "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload";
    }

    public function upload(UploadedFile $file): ?string
    {
        if (empty($this->apiKey) || empty($this->cloudName)) {
            Log::error('Cloudinary credentials not configured');
            return null;
        }

        try {
            $base64 = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));

            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->post($this->uploadUrl, [
                    'file' => $base64,
                    'folder' => 'dreamcatcher',
                ]);

            if ($response->successful()) {
                return $response->json('secure_url');
            }

            Log::error('Cloudinary upload failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Cloudinary upload error: ' . $e->getMessage());
            return null;
        }
    }

    public function uploadFromContent(string $content, string $filename = 'image.png'): ?string
    {
        if (empty($this->apiKey) || empty($this->cloudName)) {
            Log::error('Cloudinary credentials not configured');
            return null;
        }

        try {
            $mimeType = mime_content_type($filename) ?: 'image/png';
            $base64 = 'data:' . $mimeType . ';base64,' . base64_encode($content);

            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->post($this->uploadUrl, [
                    'file' => $base64,
                    'public_id' => pathinfo($filename, PATHINFO_FILENAME),
                    'folder' => 'dreamcatcher',
                ]);

            if ($response->successful()) {
                return $response->json('secure_url');
            }

            Log::error('Cloudinary upload failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Cloudinary upload error: ' . $e->getMessage());
            return null;
        }
    }
}
