<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Size;

class SizeController extends Controller
{
    public function index()
    {
        $sizes = Size::with('service')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'sizes' => $sizes,
        ]);
    }
}
