<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function index(Request $request)
    {
        $query = Portfolio::with('service');

        if ($request->has('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        $portfolios = $query->ordered()->get();

        return response()->json([
            'portfolios' => $portfolios,
        ]);
    }

    public function featured()
    {
        $portfolios = Portfolio::with('service')
            ->featured()
            ->ordered()
            ->get();

        return response()->json([
            'portfolios' => $portfolios,
        ]);
    }
}