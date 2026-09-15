<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\Service;
use App\Services\CloudinaryService;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;

class AdminPortfolioController extends Controller
{
    public function __construct(
        private CloudinaryService $cloudinaryService,
        private ImageOptimizationService $imageOptimizationService,
    ) {}

    public function index(Request $request)
    {
        $query = Portfolio::with('service');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        if ($request->filled('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        $portfolios = $query->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'portfolios' => $portfolios,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'image_url' => 'nullable|required_without:image|string|max:500',
            'service_id' => 'nullable|exists:services,id',
            'description' => 'nullable|string',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($request->hasFile('image')) {
            $uploadResult = $this->imageOptimizationService->optimizeWebAsset($request->file('image'), 'portfolios');
            $validated['image_url'] = $uploadResult['url'];
            $validated['cloudinary_public_id'] = $uploadResult['cloudinary_public_id'] ?? null;
        }

        unset($validated['image']);
        $portfolio = Portfolio::create($validated);

        return response()->json([
            'message' => 'Portfolio created successfully',
            'portfolio' => $portfolio->load('service'),
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
        $portfolio = Portfolio::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'image_url' => 'nullable|string|max:500',
            'service_id' => 'nullable|exists:services,id',
            'description' => 'nullable|string',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($request->hasFile('image')) {
            if ($portfolio->cloudinary_public_id) {
                $this->cloudinaryService->delete($portfolio->cloudinary_public_id);
            }
            $uploadResult = $this->imageOptimizationService->optimizeWebAsset($request->file('image'), 'portfolios');
            $validated['image_url'] = $uploadResult['url'];
            $validated['cloudinary_public_id'] = $uploadResult['cloudinary_public_id'] ?? null;
        }

        unset($validated['image']);
        $portfolio->update($validated);

        return response()->json([
            'message' => 'Portfolio updated successfully',
            'portfolio' => $portfolio->fresh()->load('service'),
        ]);
    }

    public function destroy($id)
    {
        $portfolio = Portfolio::findOrFail($id);

        if ($portfolio->cloudinary_public_id) {
            $this->cloudinaryService->delete($portfolio->cloudinary_public_id);
        }

        $portfolio->delete();

        return response()->json([
            'message' => 'Portfolio deleted successfully',
        ]);
    }
}
