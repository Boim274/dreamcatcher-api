<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIDesignService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', env('OPENAI_API_KEY', ''));
        $this->model = config('services.openai.model', 'dall-e-3');
    }

    public function generate(string $prompt): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('OpenAI API key not configured');
            return $this->generateMockDesign($prompt);
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post('https://api.openai.com/v1/images/generations', [
                    'model' => $this->model,
                    'prompt' => $prompt,
                    'n' => 1,
                    'size' => '1024x1024',
                    'response_format' => 'url',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'][0]['url'] ?? null;
            }

            Log::error('OpenAI API error: ' . $response->body());
            return $this->generateMockDesign($prompt);
        } catch (\Exception $e) {
            Log::error('AI Design generation failed: ' . $e->getMessage());
            return $this->generateMockDesign($prompt);
        }
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

        $base64 = 'data:image/svg+xml;base64,' . base64_encode($svg);
        return $base64;
    }
}