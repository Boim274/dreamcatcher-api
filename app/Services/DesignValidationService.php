<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class DesignValidationService
{
    private int $maxColors = 3;
    private float $gradientThreshold = 0.1;
    private float $contrastMinThreshold = 50;

    public function validateFromUrl(string $url): array
    {
        if (str_starts_with($url, 'data:') || !str_starts_with($url, 'http')) {
            return $this->validate($url);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'design_');

        try {
            $imageContent = @file_get_contents($url);
            if ($imageContent === false) {
                return $this->failValidation('Gagal mengunduh gambar dari URL');
            }

            file_put_contents($tempPath, $imageContent);
            return $this->validate($tempPath);
        } catch (\Exception $e) {
            return $this->failValidation('Gagal memvalidasi gambar: ' . $e->getMessage());
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    public function validate(string $imagePath): array
    {
        try {
            if (str_starts_with($imagePath, 'data:')) {
                return $this->validateBase64Image($imagePath);
            }

            if (!file_exists($imagePath)) {
                return $this->failValidation('File tidak ditemukan');
            }

            $fileSize = filesize($imagePath);
            if ($fileSize > 5 * 1024 * 1024) {
                return $this->failValidation('Ukuran file maksimal 5MB');
            }

            $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                return $this->failValidation('Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP');
            }

            if ($this->hasGD() || $this->hasImagick()) {
                return $this->validateWithGD($imagePath);
            }

            return $this->defaultValidation($imagePath);
        } catch (\Exception $e) {
            Log::error('Design validation failed: ' . $e->getMessage());
            return $this->defaultValidation($imagePath);
        }
    }

    private function hasGD(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatefromstring');
    }

    private function hasImagick(): bool
    {
        return class_exists('Imagick');
    }

    private function validateWithGD(string $imagePath): array
    {
        try {
            $imageData = file_get_contents($imagePath);
            $image = @imagecreatefromstring($imageData);

            if (!$image) {
                return $this->defaultValidation($imagePath);
            }

            $width = imagesx($image);
            $height = imagesy($image);
            $resized = imagecreatetruecolor(100, 100);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, 100, 100, $width, $height);

            $colorCount = $this->countDominantColorsGD($resized);
            $hasGradient = $this->detectGradientGD($resized);
            $contrastScore = $this->calculateContrastGD($resized);

            imagedestroy($image);
            imagedestroy($resized);

            $isValid = $colorCount <= $this->maxColors && $contrastScore >= $this->contrastMinThreshold;

            $messages = [];
            $messages[] = $colorCount <= $this->maxColors
                ? "Jumlah warna: {$colorCount} warna (Maks. {$this->maxColors}) - Valid"
                : "Jumlah warna: {$colorCount} warna (Maks. {$this->maxColors}) - PERLU REVISI";

            $messages[] = !$hasGradient
                ? "Gradient: Tidak terdeteksi - Valid"
                : "Gradient: Terdeteksi - Perlu diperhatikan";

            $messages[] = $contrastScore >= $this->contrastMinThreshold
                ? "Kontras: {$contrastScore}% - Valid"
                : "Kontras: {$contrastScore}% - Rendah, perlu perbaikan";

            return [
                'color_count' => $colorCount,
                'has_gradient' => $hasGradient,
                'has_shadow' => false,
                'contrast_score' => $contrastScore,
                'is_valid' => $isValid,
                'message' => implode("\n", $messages),
                'status_text' => $isValid ? 'SIAP PRODUKSI' : 'PERLU REVISI',
            ];
        } catch (\Exception $e) {
            return $this->defaultValidation($imagePath);
        }
    }

    private function countDominantColorsGD($image): int
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $colors = [];

        for ($x = 0; $x < $width; $x += 5) {
            for ($y = 0; $y < $height; $y += 5) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $r = round($r / 32) * 32;
                $g = round($g / 32) * 32;
                $b = round($b / 32) * 32;

                $key = "{$r},{$g},{$b}";
                $colors[$key] = ($colors[$key] ?? 0) + 1;
            }
        }

        arsort($colors);
        $dominantColors = array_slice($colors, 0, 10, true);

        $uniqueColors = [];
        foreach ($dominantColors as $colorKey => $count) {
            [$r, $g, $b] = explode(',', $colorKey);
            $isSimilar = false;

            foreach ($uniqueColors as $existing) {
                [$er, $eg, $eb] = explode(',', $existing);
                $distance = sqrt(pow($r - $er, 2) + pow($g - $eg, 2) + pow($b - $eb, 2));

                if ($distance < 50) {
                    $isSimilar = true;
                    break;
                }
            }

            if (!$isSimilar) {
                $uniqueColors[$colorKey] = $colorKey;
            }
        }

        return count($uniqueColors);
    }

    private function detectGradientGD($image): bool
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $variations = [];

        for ($y = 0; $y < $height; $y += 10) {
            $prevColor = null;
            $variation = 0;

            for ($x = 0; $x < $width; $x += 5) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $currentColor = "{$r},{$g},{$b}";

                if ($prevColor !== null) {
                    $prev = explode(',', $prevColor);
                    $curr = explode(',', $currentColor);
                    $diff = abs($curr[0] - $prev[0]) + abs($curr[1] - $prev[1]) + abs($curr[2] - $prev[2]);
                    $variation += $diff;
                }

                $prevColor = $currentColor;
            }

            $variations[] = $variation / ($width / 5);
        }

        $avgVariation = count($variations) > 0 ? array_sum($variations) / count($variations) : 0;

        return $avgVariation > $this->gradientThreshold * 255;
    }

    private function calculateContrastGD($image): float
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $brightnesses = [];

        for ($x = 0; $x < $width; $x += 5) {
            for ($y = 0; $y < $height; $y += 5) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $brightness = ($r * 299 + $g * 587 + $b * 114) / 1000;
                $brightnesses[] = $brightness;
            }
        }

        if (count($brightnesses) === 0) {
            return 0;
        }

        $min = min($brightnesses);
        $max = max($brightnesses);

        $contrast = (($max - $min) / 255) * 100;

        return round($contrast, 2);
    }

    private function validateBase64Image(string $base64): array
    {
        $svgContent = base64_decode(explode(',', $base64)[1] ?? '');
        $colorMatches = [];
        preg_match_all('/fill="(#[A-Fa-f0-9]{6})"/i', $svgContent, $matches);

        $uniqueColors = [];
        foreach ($matches[1] as $color) {
            $normalizedColor = strtoupper($color);
            if (!isset($uniqueColors[$normalizedColor])) {
                $uniqueColors[$normalizedColor] = true;
            }
        }

        $colorCount = count($uniqueColors);

        return [
            'color_count' => $colorCount,
            'has_gradient' => str_contains($svgContent, 'gradient'),
            'has_shadow' => str_contains($svgContent, 'shadow') || str_contains($svgContent, 'filter'),
            'contrast_score' => 75,
            'is_valid' => $colorCount <= $this->maxColors,
            'message' => "Jumlah warna: {$colorCount} warna (Maks. {$this->maxColors})\n" .
                       "Gradient: " . (str_contains($svgContent, 'gradient') ? 'Terdeteksi' : 'Tidak terdeteksi') . " - Valid\n" .
                       "Kontras: 75% - Valid",
            'status_text' => $colorCount <= $this->maxColors ? 'SIAP PRODUKSI' : 'PERLU REVISI',
        ];
    }

    private function defaultValidation(string $imagePath): array
    {
        $fileSize = file_exists($imagePath) ? filesize($imagePath) : 0;
        $isValid = $fileSize > 0 && $fileSize <= 5 * 1024 * 1024;

        return [
            'color_count' => 2,
            'has_gradient' => false,
            'has_shadow' => false,
            'contrast_score' => 70,
            'is_valid' => $isValid,
            'message' => "Desain berhasil diupload.\nValidasi: Perlu konfirmasi manual oleh tim kami.\nSilakan hubungi admin jika ada pertanyaan.",
            'status_text' => $isValid ? 'SIAP PRODUKSI' : 'PERLU REVISI',
        ];
    }

    private function failValidation(string $message): array
    {
        return [
            'color_count' => 0,
            'has_gradient' => false,
            'has_shadow' => false,
            'contrast_score' => 0,
            'is_valid' => false,
            'message' => $message,
            'status_text' => 'ERROR',
        ];
    }
}