<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Color;

class ColorController extends Controller
{
    public function index()
    {
        $colors = Color::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'colors' => $colors,
        ]);
    }
}
