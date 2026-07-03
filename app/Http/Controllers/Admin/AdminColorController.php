<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Color;
use Illuminate\Http\Request;

class AdminColorController extends Controller
{
    public function index(Request $request)
    {
        $query = Color::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('hex_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $colors = $query->orderBy('sort_order')->orderBy('name')->paginate(15);

        return response()->json([
            'colors' => $colors,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hex_code' => 'required|string|max:7',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $color = Color::create($validated);

        return response()->json([
            'message' => 'Warna berhasil ditambahkan',
            'color' => $color,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $color = Color::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'hex_code' => 'sometimes|string|max:7',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $color->update($validated);

        return response()->json([
            'message' => 'Warna berhasil diperbarui',
            'color' => $color->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $color = Color::findOrFail($id);
        $color->delete();

        return response()->json([
            'message' => 'Warna berhasil dihapus',
        ]);
    }
}
