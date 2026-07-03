<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Size;
use Illuminate\Http\Request;

class AdminSizeController extends Controller
{
    public function index(Request $request)
    {
        $query = Size::with('service');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('dimensions', 'like', "%{$search}%");
            });
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        $sizes = $query->orderBy('sort_order')->orderBy('name')->paginate(15);

        return response()->json([
            'sizes' => $sizes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'dimensions' => 'nullable|string|max:255',
            'service_id' => 'nullable|exists:services,id',
            'sort_order' => 'integer',
        ]);

        $size = Size::create($validated);

        return response()->json([
            'message' => 'Ukuran berhasil ditambahkan',
            'size' => $size->load('service'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $size = Size::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'dimensions' => 'nullable|string|max:255',
            'service_id' => 'nullable|exists:services,id',
            'sort_order' => 'integer',
        ]);

        $size->update($validated);

        return response()->json([
            'message' => 'Ukuran berhasil diperbarui',
            'size' => $size->fresh()->load('service'),
        ]);
    }

    public function destroy($id)
    {
        $size = Size::findOrFail($id);
        $size->delete();

        return response()->json([
            'message' => 'Ukuran berhasil dihapus',
        ]);
    }
}
