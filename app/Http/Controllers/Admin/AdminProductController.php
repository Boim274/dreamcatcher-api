<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    public function __construct(
        private CloudinaryService $cloudinaryService
    ) {}

    public function index(Request $request)
    {
        $query = Product::with(['category', 'images', 'sizes', 'colors']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $products = $query->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'stock' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'sizes' => 'nullable|array',
            'sizes.*.name' => 'required|string|max:50',
            'sizes.*.stock' => 'integer|min:0',
            'colors' => 'nullable|array',
            'colors.*.name' => 'required|string|max:50',
            'colors.*.hex_code' => 'nullable|string|max:7',
        ]);

        $product = Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'base_price' => $validated['base_price'],
            'category_id' => $validated['category_id'] ?? null,
            'stock' => $validated['stock'],
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $i => $file) {
                $uploadResult = $this->cloudinaryService->upload($file);
                if ($uploadResult) {
                    $product->images()->create([
                        'image_url' => $uploadResult['url'],
                        'cloudinary_public_id' => $uploadResult['public_id'],
                        'sort_order' => $i,
                    ]);
                }
            }
        }

        if ($request->filled('sizes')) {
            foreach ($request->sizes as $i => $size) {
                $product->sizes()->create([
                    'name' => $size['name'],
                    'stock' => $size['stock'] ?? 0,
                    'sort_order' => $i,
                ]);
            }
        }

        if ($request->filled('colors')) {
            foreach ($request->colors as $i => $color) {
                $product->colors()->create([
                    'name' => $color['name'],
                    'hex_code' => $color['hex_code'] ?? null,
                    'sort_order' => $i,
                ]);
            }
        }

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product->fresh()->load(['category', 'images', 'sizes', 'colors']),
        ], 201);
    }

    public function show($id)
    {
        $product = Product::with(['category', 'images', 'sizes', 'colors'])->findOrFail($id);

        return response()->json([
            'product' => $product,
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::with('images')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'sometimes|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'stock' => 'sometimes|integer|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'existing_images' => 'nullable|array',
            'existing_images.*.id' => 'nullable|integer|exists:product_images,id',
            'existing_images.*.sort_order' => 'integer',
            'sizes' => 'nullable|array',
            'sizes.*.name' => 'required|string|max:50',
            'sizes.*.stock' => 'integer|min:0',
            'colors' => 'nullable|array',
            'colors.*.name' => 'required|string|max:50',
            'colors.*.hex_code' => 'nullable|string|max:7',
        ]);

        $product->update([
            'name' => $validated['name'] ?? $product->name,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $product->description,
            'base_price' => $validated['base_price'] ?? $product->base_price,
            'category_id' => array_key_exists('category_id', $validated) ? $validated['category_id'] : $product->category_id,
            'stock' => $validated['stock'] ?? $product->stock,
            'is_active' => $validated['is_active'] ?? $product->is_active,
            'sort_order' => $validated['sort_order'] ?? $product->sort_order,
        ]);

        if ($request->has('existing_images')) {
            $keptIds = collect($request->existing_images)->pluck('id')->filter()->values();
            $deleted = $product->images()->whereNotIn('id', $keptIds)->get();
            foreach ($deleted as $img) {
                if ($img->cloudinary_public_id) {
                    $this->cloudinaryService->delete($img->cloudinary_public_id);
                }
                $img->delete();
            }
            foreach ($request->existing_images as $imgData) {
                if (!empty($imgData['id'])) {
                    $product->images()->where('id', $imgData['id'])->update([
                        'sort_order' => $imgData['sort_order'] ?? 0,
                    ]);
                }
            }
        }

        if ($request->hasFile('images')) {
            $maxSort = $product->images()->max('sort_order') ?? 0;
            foreach ($request->file('images') as $i => $file) {
                $uploadResult = $this->cloudinaryService->upload($file);
                if ($uploadResult) {
                    $product->images()->create([
                        'image_url' => $uploadResult['url'],
                        'cloudinary_public_id' => $uploadResult['public_id'],
                        'sort_order' => $maxSort + $i + 1,
                    ]);
                }
            }
        }

        if ($request->has('sizes')) {
            $product->sizes()->delete();
            foreach ($request->sizes as $i => $size) {
                $product->sizes()->create([
                    'name' => $size['name'],
                    'stock' => $size['stock'] ?? 0,
                    'sort_order' => $i,
                ]);
            }
        }

        if ($request->has('colors')) {
            $product->colors()->delete();
            foreach ($request->colors as $i => $color) {
                $product->colors()->create([
                    'name' => $color['name'],
                    'hex_code' => $color['hex_code'] ?? null,
                    'sort_order' => $i,
                ]);
            }
        }

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product->fresh()->load(['category', 'images', 'sizes', 'colors']),
        ]);
    }

    public function destroy($id)
    {
        $product = Product::with('images')->findOrFail($id);

        foreach ($product->images as $img) {
            if ($img->cloudinary_public_id) {
                $this->cloudinaryService->delete($img->cloudinary_public_id);
            }
        }

        $product->sizes()->delete();
        $product->colors()->delete();
        $product->images()->delete();
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }
}
