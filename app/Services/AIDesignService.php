<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIDesignService
{
    private string $apiKey;
    private string $model;
    private string $referer;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', env('OPENAI_API_KEY', ''));
        $this->model = config('services.openai.model', 'krea/krea-2-medium');
        $this->referer = env('APP_URL', 'http://localhost:8000');
    }

    public function editDesign(string $prompt, string $sourceImageUrl, int $count = 1, ?string $negativePrompt = null, bool $appendSuffix = true): array
    {
        if (empty($this->apiKey) || $this->mockMode()) {
            return $this->generateMockDesigns($prompt, $count);
        }

        $model = config('services.openai.edit_model', env('OPENAI_IMAGE_EDIT_MODEL', 'google/gemini-3.1-flash-lite-image'));

        $instruction = $prompt;
        if ($appendSuffix) {
            $instruction .= $this->printRequirementSuffix();
        }
        if (!empty($negativePrompt)) {
            $instruction .= ' Negative: ' . $negativePrompt;
        }

        // Unified Image API shape: prompt is a plain string and the source image
        // is passed as a top-level input_references entry (image-to-image).
        // Send the source as a base64 data URL so OpenRouter never needs to
        // dereference an external URL (it rejects non-public ones otherwise).
        $source = $this->toBase64DataUrl($sourceImageUrl);
        if ($source === null) {
            Log::error('AI Design edit failed: could not read source image ' . $sourceImageUrl);
            return [];
        }

        $payload = [
            'model' => $model,
            'prompt' => $instruction,
            'input_references' => [
                ['type' => 'image_url', 'image_url' => ['url' => $source]],
            ],
            'n' => $count,
        ];

        return $this->postImages($payload, $prompt, $count);
    }

    public function enhance(string $sourceImageUrl, int $count = 1): array
    {
        $prompt = 'Enhance and upscale this t-shirt design to be high quality and print-ready.'
            . 'Improve sharpness, color consistency and edge detail, keep the exact same design,'
            . 'layout and content unchanged, transparent background preferred, no added text or watermark.';

        return $this->editDesign($prompt, $sourceImageUrl, $count, null, false);
    }

    private function toBase64DataUrl(string $imageUrl): ?string
    {
        try {
            if (str_starts_with($imageUrl, 'data:')) {
                return $imageUrl;
            }

            $response = Http::timeout(30)->get($imageUrl);
            if (!$response->successful()) {
                return null;
            }

            $body = $response->body();
            if (empty($body)) {
                return null;
            }

            $mime = $response->header('Content-Type') ?: 'image/png';
            if (str_contains($mime, ';')) {
                $mime = explode(';', $mime)[0];
            }

            return 'data:' . $mime . ';base64,' . base64_encode($body);
        } catch (\Exception $e) {
            Log::error('AI Design source image fetch failed: ' . $e->getMessage());
            return null;
        }
    }

    private function printRequirementSuffix(): string
    {
        return ' Create a professional t-shirt printing design, high contrast, clean edges, print-ready.';
    }

    private function postImages(array $payload, string $fallbackPrompt, int $count): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'HTTP-Referer' => $this->referer,
                'Content-Type' => 'application/json',
            ])
                ->timeout(120)
                ->post('https://openrouter.ai/api/v1/images', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $urls = [];
                foreach (($data['data'] ?? []) as $item) {
                    if (!empty($item['url'])) {
                        $urls[] = $item['url'];
                    } elseif (!empty($item['b64_json'])) {
                        $mime = !empty($item['media_type']) ? $item['media_type'] : 'image/png';
                        $urls[] = 'data:' . $mime . ';base64,' . $item['b64_json'];
                    }
                }
                if (!empty($urls)) {
                    return $urls;
                }
            }

            Log::error('OpenRouter image error: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('AI Design generation failed: ' . $e->getMessage());
        }

        // Real API failure must surface as an error, not a bogus mock image,
        // otherwise the edit/enhance result silently drifts from the source.
        if ($this->mockMode()) {
            return $this->generateMockDesigns($fallbackPrompt, $count);
        }

        return [];
    }

    private function mockMode(): bool
    {
        return filter_var(env('AI_DESIGN_MOCK', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function generate(string $prompt, int $count = 1, ?string $negativePrompt = null): array
    {
        if (empty($this->apiKey) || $this->mockMode()) {
            Log::warning('OpenAI API key not configured');
            return $this->generateMockDesigns($prompt, $count);
        }

        try {
            $payload = [
                'model' => $this->model,
                'prompt' => $prompt,
                'n' => $count,
                'size' => '1024x1024',
                'response_format' => 'url',
            ];
            if (!empty($negativePrompt)) {
                $payload['negative_prompt'] = $negativePrompt;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'HTTP-Referer' => $this->referer,
                'Content-Type' => 'application/json',
            ])
                ->timeout(120)
                ->post('https://openrouter.ai/api/v1/images/generations', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $urls = [];
                foreach (($data['data'] ?? []) as $item) {
                    if (!empty($item['url'])) {
                        $urls[] = $item['url'];
                    } elseif (!empty($item['b64_json'])) {
                        $mime = !empty($item['media_type']) ? $item['media_type'] : 'image/png';
                        $urls[] = 'data:' . $mime . ';base64,' . $item['b64_json'];
                    }
                }
                if (!empty($urls)) {
                    return $urls;
                }
            }

            Log::error('OpenRouter image error: ' . $response->body());
            return $this->generateMockDesigns($prompt, $count);
        } catch (\Exception $e) {
            Log::error('AI Design generation failed: ' . $e->getMessage());
            return $this->generateMockDesigns($prompt, $count);
        }
    }

    private function generateMockDesigns(string $prompt, int $count): array
    {
        $results = [];
        for ($i = 0; $i < $count; $i++) {
            $results[] = $this->generateMockDesign($prompt . ' variant ' . ($i + 1));
        }
        return $results;
    }

    private function generateMockDesign(string $prompt): ?string
    {
        $seed = crc32($prompt);
        $colors = ['4285F4', 'EA4335', '34A853', 'FBBC05'];
        $color = $colors[$seed % count($colors)];

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512">';
        $svg .= '<rect width="512" height="512" fill="#' . $color . '"/>';
        $svg .= '<circle cx="256" cy="200" r="100" fill="white" opacity="0.9"/>';
        $svg .= '<rect x="156" y="300" width="200" height="80" rx="20" fill="white" opacity="0.9"/>';
        $svg .= '<text x="256" y="260" font-family="Arial" font-size="24" fill="' . $color . '" text-anchor="middle">DC</text>';
        $svg .= '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
