<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AIDesignRecommendationService;
use Illuminate\Http\Request;

class AIDesignRecommendationController extends Controller
{
    public function __construct(
        private AIDesignRecommendationService $recommendationService
    ) {}

    public function index()
    {
        return response()->json([
            'catalog' => $this->recommendationService->catalog(),
        ]);
    }

    public function suggestions(Request $request)
    {
        $shirtColor = $request->input('shirt_color');

        return response()->json([
            'suggestions' => $this->recommendationService->smartSuggestions($shirtColor),
        ]);
    }
}
