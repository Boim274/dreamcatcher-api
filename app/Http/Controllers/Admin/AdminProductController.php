<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Services\CloudinaryService;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    public function __construct(
        private CloudinaryService $cloudinaryService,
        private ImageOptimizationService $imageOptimizationService
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
        $this->prepareProductInput($request);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'category_id' => 'nullable|integer',
            'stock' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'sizes' => 'nullable|array',
            'sizes.*.name' => 'nullable|string|max:100',
            'sizes.*.stock' => 'nullable|integer|min:0',
            'colors' => 'nullable|array',
            'colors.*.name' => 'nullable|string|max:100',
            'colors.*.hex_code' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            \Illuminate\Support\Facades\Log::warning('Product store validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request' => $request->except(['images']),
            ]);
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $product = Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'base_price' => $validated['base_price'],
            'category_id' => $validated['category_id'] ?? null,
            'stock' => $validated['stock'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        if ($request->hasFile('images')) {
            $files = $request->file('images');
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $i => $file) {
                if ($file && $file->isValid()) {
                    try {
                        $uploadResult = $this->imageOptimizationService->optimizeWebAsset($file, 'products');
                        if ($uploadResult && !empty($uploadResult['url'])) {
                            $product->images()->create([
                                'image_url' => $uploadResult['url'],
                                'cloudinary_public_id' => $uploadResult['cloudinary_public_id'] ?? null,
                                'sort_order' => $i,
                            ]);
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Product image optimization error: ' . $e->getMessage());
                    }
                }
            }
        }

        if (!empty($validated['sizes'])) {
            foreach ($validated['sizes'] as $i => $size) {
                if (!empty(trim((string)($size['name'] ?? '')))) {
                    $product->sizes()->create([
                        'name' => trim((string)$size['name']),
                        'stock' => (int)($size['stock'] ?? 0),
                        'sort_order' => $i,
                    ]);
                }
            }
        }

        if (!empty($validated['colors'])) {
            foreach ($validated['colors'] as $i => $color) {
                if (!empty(trim((string)($color['name'] ?? '')))) {
                    $product->colors()->create([
                        'name' => trim((string)$color['name']),
                        'hex_code' => !empty($color['hex_code']) ? trim((string)$color['hex_code']) : '#111827',
                        'sort_order' => $i,
                    ]);
                }
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

        $this->prepareProductInput($request);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'sometimes|numeric|min:0',
            'category_id' => 'nullable|integer',
            'stock' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'existing_images' => 'nullable|array',
            'existing_images.*.id' => 'nullable|integer|exists:product_images,id',
            'existing_images.*.sort_order' => 'nullable|integer',
            'sizes' => 'nullable|array',
            'sizes.*.name' => 'nullable|string|max:100',
            'sizes.*.stock' => 'nullable|integer|min:0',
            'colors' => 'nullable|array',
            'colors.*.name' => 'nullable|string|max:100',
            'colors.*.hex_code' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            \Illuminate\Support\Facades\Log::warning('Product update validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request' => $request->except(['images']),
            ]);
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

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
                if ($file && $file->isValid()) {
                    try {
                        $uploadResult = $this->imageOptimizationService->optimizeWebAsset($file, 'products');
                        if ($uploadResult && !empty($uploadResult['url'])) {
                            $product->images()->create([
                                'image_url' => $uploadResult['url'],
                                'cloudinary_public_id' => $uploadResult['cloudinary_public_id'] ?? null,
                                'sort_order' => $maxSort + $i + 1,
                            ]);
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Product image update optimization error: ' . $e->getMessage());
                    }
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

    private function prepareProductInput(Request $request): void
    {
        $merge = [];

        if ($request->has('category_id')) {
            $catId = $request->category_id;
            if ($catId === '' || $catId === '0' || $catId === 'null' || $catId === null || $catId === 0) {
                $merge['category_id'] = null;
            } else {
                $idInt = (int) $catId;
                if (\App\Models\Category::where('id', $idInt)->exists()) {
                    $merge['category_id'] = $idInt;
                } else {
                    $merge['category_id'] = null;
                }
            }
        }

        if ($request->has('base_price')) {
            $price = $request->base_price;
            if (is_string($price)) {
                $cleanPrice = preg_replace('/[^0-9]/', '', $price);
                $merge['base_price'] = $cleanPrice !== '' ? (float) $cleanPrice : 0;
            }
        }

        if ($request->has('stock')) {
            $merge['stock'] = (int) preg_replace('/[^0-9]/', '', (string) $request->stock);
        }

        if ($request->has('sort_order')) {
            $merge['sort_order'] = (int) preg_replace('/[^0-9]/', '', (string) $request->sort_order);
        }

        if ($request->has('description') && $request->description === '') {
            $merge['description'] = null;
        }

        if ($request->has('sizes')) {
            $sizesData = $request->sizes;
            if (is_string($sizesData)) {
                $sizesData = json_decode($sizesData, true);
            }
            if (is_array($sizesData)) {
                $cleanedSizes = [];
                foreach ($sizesData as $s) {
                    if (is_array($s) && !empty(trim((string) ($s['name'] ?? '')))) {
                        $cleanedSizes[] = [
                            'name' => trim((string) $s['name']),
                            'stock' => (int) ($s['stock'] ?? 0),
                        ];
                    }
                }
                $merge['sizes'] = $cleanedSizes;
            }
        }

        if ($request->has('colors')) {
            $colorsData = $request->colors;
            if (is_string($colorsData)) {
                $colorsData = json_decode($colorsData, true);
            }
            if (is_array($colorsData)) {
                $cleanedColors = [];
                foreach ($colorsData as $c) {
                    if (is_array($c) && !empty(trim((string) ($c['name'] ?? '')))) {
                        $cleanedColors[] = [
                            'name' => trim((string) $c['name']),
                            'hex_code' => !empty($c['hex_code']) ? trim((string) $c['hex_code']) : '#111827',
                        ];
                    }
                }
                $merge['colors'] = $cleanedColors;
            }
        }

        if ($request->has('existing_images') && is_string($request->existing_images)) {
            $decoded = json_decode($request->existing_images, true);
            if (is_array($decoded)) {
                $merge['existing_images'] = $decoded;
            }
        }

        if ($request->hasFile('images')) {
            $files = $request->file('images');
            if (!is_array($files)) {
                $files = [$files];
            }
            $validFiles = [];
            foreach ($files as $f) {
                if ($f instanceof \Illuminate\Http\UploadedFile && $f->isValid()) {
                    $validFiles[] = $f;
                }
            }
            $request->files->set('images', $validFiles);
        }

        if ($request->has('is_active')) {
            $merge['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $request->is_active;
        }

        if (!empty($merge)) {
            $request->merge($merge);
        }
    }
}
