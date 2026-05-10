<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\Service;
use Illuminate\Http\Request;

class AdminPortfolioController extends Controller
{
    public function index()
    {
        $portfolios = Portfolio::with('service')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'portfolios' => $portfolios,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image_url' => 'required|string|max:500',
            'service_id' => 'nullable|exists:services,id',
            'description' => 'nullable|string',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $portfolio = Portfolio::create($validated);

        return response()->json([
            'message' => 'Portfolio created successfully',
            'portfolio' => $portfolio,
        ], 201);
    }

    public function show($id)
    {
        $portfolio = Portfolio::with('service')->findOrFail($id);

        return response()->json([
            'portfolio' => $portfolio,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'image_url' => 'sometimes|string|max:500',
            'service_id' => 'nullable|exists:services,id',
            'description' => 'nullable|string',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $portfolio = Portfolio::findOrFail($id);
        $portfolio->update($validated);

        return response()->json([
            'message' => 'Portfolio updated successfully',
            'portfolio' => $portfolio->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $portfolio = Portfolio::findOrFail($id);
        $portfolio->delete();

        return response()->json([
            'message' => 'Portfolio deleted successfully',
        ]);
    }
}