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

    public function upload(UploadedFile $file): ?array
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
                return [
                    'url' => $response->json('secure_url'),
                    'public_id' => $response->json('public_id'),
                ];
            }

            Log::error('Cloudinary upload failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Cloudinary upload error: ' . $e->getMessage());
            return null;
        }
    }

    public function uploadFromContent(string $content, string $filename = 'image.png'): ?array
    {
        if (empty($this->apiKey) || empty($this->cloudName)) {
            Log::error('Cloudinary credentials not configured');
            return null;
        }

        try {
            $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($content) ?: 'image/png';
            $base64 = 'data:' . $mimeType . ';base64,' . base64_encode($content);

            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->post($this->uploadUrl, [
                    'file' => $base64,
                    'public_id' => pathinfo($filename, PATHINFO_FILENAME),
                    'folder' => 'dreamcatcher',
                ]);

            if ($response->successful()) {
                return [
                    'url' => $response->json('secure_url'),
                    'public_id' => $response->json('public_id'),
                ];
            }

            Log::error('Cloudinary upload failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Cloudinary upload error: ' . $e->getMessage());
            return null;
        }
    }

    public function delete(string $publicId): bool
    {
        if (empty($this->apiKey) || empty($this->apiSecret) || empty($this->cloudName)) {
            return false;
        }

        try {
            $timestamp = time();
            $signature = sha1('public_id=' . $publicId . '&timestamp=' . $timestamp . $this->apiSecret);

            $response = Http::post(
                "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/destroy",
                [
                    'public_id' => $publicId,
                    'timestamp' => $timestamp,
                    'api_key' => $this->apiKey,
                    'signature' => $signature,
                ]
            );

            return $response->successful() && $response->json('result') === 'ok';
        } catch (\Exception $e) {
            Log::error('Cloudinary delete error: ' . $e->getMessage());
            return false;
        }
    }

    public function extractPublicId(string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        // URL format: https://res.cloudinary.com/{cloud_name}/image/upload/v{version}/{public_id}.{ext}
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return null;
        }

        // Remove leading / and /image/upload/ prefix
        $path = ltrim($path, '/');
        $path = preg_replace('#^image/upload/(v\d+/)?#', '', $path);

        // Remove extension
        $path = preg_replace('/\.\w+$/', '', $path);

        return $path ?: null;
    }
}
