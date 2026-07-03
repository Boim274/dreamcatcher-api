<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::active()->with(['category', 'images', 'sizes', 'colors']);

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

        $products = $query->ordered()
            ->orderByDesc('created_at')
            ->paginate(12);

        return response()->json([
            'products' => $products,
        ]);
    }

    public function show($id)
    {
        $product = Product::active()
            ->with(['category', 'images', 'sizes', 'colors'])
            ->findOrFail($id);

        return response()->json([
            'product' => $product,
        ]);
    }
}
