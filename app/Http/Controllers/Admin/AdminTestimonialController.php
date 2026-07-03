<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminTestimonialController extends Controller
{
    public function index(Request $request)
    {
        $query = Testimonial::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('review', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('min_rating')) {
            $query->where('rating', '>=', (int) $request->min_rating);
        }

        $testimonials = $query->orderBy('sort_order')->orderByDesc('created_at')->paginate(15);

        return response()->json([
            'testimonials' => $testimonials,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'image_url' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('testimonials', 'public');
            $validated['image_url'] = 'storage/' . $path;
        }

        unset($validated['image']);
        $testimonial = Testimonial::create($validated);

        return response()->json([
            'message' => 'Testimoni berhasil ditambahkan',
            'testimonial' => $testimonial,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $testimonial = Testimonial::findOrFail($id);

        $validated = $request->validate([
            'customer_name' => 'sometimes|string|max:255',
            'rating' => 'sometimes|integer|min:1|max:5',
            'review' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'image_url' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($request->hasFile('image')) {
            if ($testimonial->image_url && Storage::disk('public')->exists(str_replace('storage/', '', $testimonial->image_url))) {
                Storage::disk('public')->delete(str_replace('storage/', '', $testimonial->image_url));
            }
            $path = $request->file('image')->store('testimonials', 'public');
            $validated['image_url'] = 'storage/' . $path;
        }

        unset($validated['image']);
        $testimonial->update($validated);

        return response()->json([
            'message' => 'Testimoni berhasil diperbarui',
            'testimonial' => $testimonial->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $testimonial = Testimonial::findOrFail($id);

        if ($testimonial->image_url && Storage::disk('public')->exists(str_replace('storage/', '', $testimonial->image_url))) {
            Storage::disk('public')->delete(str_replace('storage/', '', $testimonial->image_url));
        }

        $testimonial->delete();

        return response()->json([
            'message' => 'Testimoni berhasil dihapus',
        ]);
    }
}
