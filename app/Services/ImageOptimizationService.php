<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageOptimizationService
{
    private CloudinaryService $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    /**
     * Validate image file rigorously for security and integrity.
     */
    public function validateImage(UploadedFile $file, int $maxKilobytes = 10240): array
    {
        if (!$file->isValid()) {
            return ['valid' => false, 'error' => 'File gambar tidak valid atau rusak saat diunggah.'];
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'image/gif'];
        $mime = $file->getMimeType();
        if (!in_array($mime, $allowedMimes, true)) {
            return ['valid' => false, 'error' => 'Format file tidak didukung. Harap gunakan format JPG, PNG, atau WebP.'];
        }

        if ($file->getSize() > ($maxKilobytes * 1024)) {
            $mb = round($maxKilobytes / 1024, 1);
            return ['valid' => false, 'error' => "Ukuran gambar melebihi batas maksimal {$mb} MB."];
        }

        $imageInfo = @getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            return ['valid' => false, 'error' => 'File yang diunggah bukan gambar yang valid.'];
        }

        return [
            'valid' => true,
            'width' => $imageInfo[0] ?? null,
            'height' => $imageInfo[1] ?? null,
            'mime' => $mime,
        ];
    }

    /**
     * Optimize Web Asset (Products, Portfolios, Testimonials, Banners, Avatars).
     * Creates:
     * - Optimized WebP main image (max 1400px width, quality 82)
     * - Medium WebP variant (max 800px width, quality 80)
     * - Thumbnail WebP variant (max 300px width, quality 75)
     */
    public function optimizeWebAsset(UploadedFile $file, string $folder = 'products'): array
    {
        $cloudResult = null;
        try {
            $cloudResult = $this->cloudinaryService->upload($file);
        } catch (\Throwable $e) {
            Log::info('Cloudinary upload skipped or failed, fallback to local storage: ' . $e->getMessage());
        }

        // If Cloudinary succeeded, return cloud URLs with automatic transformation helpers
        if ($cloudResult && !empty($cloudResult['url'])) {
            return [
                'original_url' => $cloudResult['url'],
                'url' => $this->transformCloudinary($cloudResult['url'], 'c_limit,w_1400,q_auto,f_auto'),
                'medium_url' => $this->transformCloudinary($cloudResult['url'], 'c_limit,w_800,q_auto,f_auto'),
                'thumbnail_url' => $this->transformCloudinary($cloudResult['url'], 'c_fill,w_300,h_300,g_auto,q_auto,f_auto'),
                'cloudinary_public_id' => $cloudResult['public_id'],
                'storage' => 'cloudinary',
            ];
        }

        // Otherwise process locally via PHP GD
        $baseName = Str::uuid()->toString();
        $disk = Storage::disk('public');
        $directory = "uploads/{$folder}";
        if (!$disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $sourcePath = $file->getRealPath();
        $sourceImage = $this->createImageResource($sourcePath, $file->getMimeType());

        if (!$sourceImage) {
            // Fallback: direct save if GD cannot process
            $path = $file->store($directory, 'public');
            $url = "storage/{$path}";
            return [
                'original_url' => $url,
                'url' => $url,
                'medium_url' => $url,
                'thumbnail_url' => $url,
                'cloudinary_public_id' => null,
                'storage' => 'local',
            ];
        }

        $origW = imagesx($sourceImage);
        $origH = imagesy($sourceImage);

        // 1. Optimized WebP Main (Max 1400px width)
        $mainImg = $this->resizeImage($sourceImage, $origW, $origH, 1400);
        $mainFileName = "{$directory}/{$baseName}.webp";
        $mainFullPath = $disk->path($mainFileName);
        imagewebp($mainImg, $mainFullPath, 82);
        imagedestroy($mainImg);

        // 2. Medium WebP (Max 800px width)
        $medImg = $this->resizeImage($sourceImage, $origW, $origH, 800);
        $medFileName = "{$directory}/{$baseName}-medium.webp";
        $medFullPath = $disk->path($medFileName);
        imagewebp($medImg, $medFullPath, 80);
        imagedestroy($medImg);

        // 3. Thumbnail WebP (Max 300px width)
        $thumbImg = $this->resizeImage($sourceImage, $origW, $origH, 300);
        $thumbFileName = "{$directory}/{$baseName}-thumb.webp";
        $thumbFullPath = $disk->path($thumbFileName);
        imagewebp($thumbImg, $thumbFullPath, 75);
        imagedestroy($thumbImg);

        imagedestroy($sourceImage);

        return [
            'original_url' => "storage/{$mainFileName}",
            'url' => "storage/{$mainFileName}",
            'medium_url' => "storage/{$medFileName}",
            'thumbnail_url' => "storage/{$thumbFileName}",
            'cloudinary_public_id' => null,
            'storage' => 'local',
        ];
    }

    /**
     * Process Production Design for Printing (Customer Design & AI Designs).
     * CRITICAL: Preserves original high-resolution graphic 100% untouched for printing/production.
     * Generates lightweight web preview and thumbnail variants for fast UI loading.
     */
    public function processProductionDesign(UploadedFile $file, string $folder = 'designs'): array
    {
        $cloudResult = null;
        try {
            $cloudResult = $this->cloudinaryService->upload($file);
        } catch (\Throwable $e) {
            Log::info('Cloudinary design upload fallback to local: ' . $e->getMessage());
        }

        if ($cloudResult && !empty($cloudResult['url'])) {
            return [
                'original_url' => $cloudResult['url'], // 100% full original for production
                'image_url' => $cloudResult['url'],
                'preview_url' => $this->transformCloudinary($cloudResult['url'], 'c_limit,w_800,q_auto,f_auto'),
                'thumbnail_url' => $this->transformCloudinary($cloudResult['url'], 'c_fill,w_250,h_250,g_auto,q_auto,f_auto'),
                'cloudinary_public_id' => $cloudResult['public_id'],
                'storage' => 'cloudinary',
            ];
        }

        $disk = Storage::disk('public');
        $directory = "uploads/{$folder}";
        if (!$disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $baseName = Str::uuid()->toString();
        $ext = $file->getClientOriginalExtension() ?: 'png';

        // 1. SAVE ORIGINAL FULL HIGH-RES ASSET FOR PRODUCTION PRINTING
        $origFileName = "{$directory}/{$baseName}-production.{$ext}";
        $disk->putFileAs($directory, $file, "{$baseName}-production.{$ext}");

        $sourcePath = $file->getRealPath();
        $sourceImage = $this->createImageResource($sourcePath, $file->getMimeType());

        if (!$sourceImage) {
            $origUrl = "storage/{$origFileName}";
            return [
                'original_url' => $origUrl,
                'image_url' => $origUrl,
                'preview_url' => $origUrl,
                'thumbnail_url' => $origUrl,
                'cloudinary_public_id' => null,
                'storage' => 'local',
            ];
        }

        $origW = imagesx($sourceImage);
        $origH = imagesy($sourceImage);

        // 2. Web Preview (Max 800px, preserves alpha transparency)
        $prevImg = $this->resizeImage($sourceImage, $origW, $origH, 800);
        $prevFileName = "{$directory}/{$baseName}-preview.webp";
        imagewebp($prevImg, $disk->path($prevFileName), 85);
        imagedestroy($prevImg);

        // 3. Web Thumbnail (Max 250px, preserves alpha transparency)
        $thumbImg = $this->resizeImage($sourceImage, $origW, $origH, 250);
        $thumbFileName = "{$directory}/{$baseName}-thumb.webp";
        imagewebp($thumbImg, $disk->path($thumbFileName), 78);
        imagedestroy($thumbImg);

        imagedestroy($sourceImage);

        return [
            'original_url' => "storage/{$origFileName}",
            'image_url' => "storage/{$origFileName}",
            'preview_url' => "storage/{$prevFileName}",
            'thumbnail_url' => "storage/{$thumbFileName}",
            'cloudinary_public_id' => null,
            'storage' => 'local',
        ];
    }

    /**
     * Helper: Transform Cloudinary URL dynamically.
     */
    public function transformCloudinary(string $url, string $transform): string
    {
        if (empty($url) || !str_contains($url, 'res.cloudinary.com')) {
            return $url;
        }

        if (str_contains($url, '/image/upload/')) {
            return str_replace('/image/upload/', "/image/upload/{$transform}/", $url);
        }

        return $url;
    }

    /**
     * Create image resource supporting JPEG, PNG, WebP with transparency preservation.
     */
    private function createImageResource(string $path, ?string $mime = null)
    {
        if (!$mime) {
            $info = @getimagesize($path);
            $mime = $info['mime'] ?? '';
        }

        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                return @imagecreatefromjpeg($path);
            case 'image/png':
                $img = @imagecreatefrompng($path);
                if ($img) {
                    imagealphablending($img, true);
                    imagesavealpha($img, true);
                }
                return $img;
            case 'image/webp':
                return @imagecreatefromwebp($path);
            default:
                return @imagecreatefromstring(file_get_contents($path));
        }
    }

    /**
     * Resize image proportionally while retaining PNG alpha transparency.
     */
    private function resizeImage($source, int $origW, int $origH, int $maxDim)
    {
        if ($origW <= $maxDim && $origH <= $maxDim) {
            $newW = $origW;
            $newH = $origH;
        } else {
            if ($origW > $origH) {
                $newW = $maxDim;
                $newH = (int) round(($origH / $origW) * $maxDim);
            } else {
                $newH = $maxDim;
                $newW = (int) round(($origW / $origH) * $maxDim);
            }
        }

        $newW = max(1, $newW);
        $newH = max(1, $newH);

        $target = imagecreatetruecolor($newW, $newH);

        // Preserve alpha transparency channel
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefilledrectangle($target, 0, 0, $newW, $newH, $transparent);
        imagealphablending($target, true);

        imagecopyresampled($target, $source, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        return $target;
    }
}
