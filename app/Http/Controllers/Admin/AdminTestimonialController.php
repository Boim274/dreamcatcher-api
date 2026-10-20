<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class AdminTestimonialController extends Controller
{
    public function __construct(
        private CloudinaryService $cloudinaryService,
    ) {}

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

        $testimonials = $query->latest()->paginate(15);

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
            $uploadResult = $this->cloudinaryService->upload($request->file('image'));
            $validated['image_url'] = $uploadResult['url'];
            $validated['cloudinary_public_id'] = $uploadResult['public_id'];
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
            if ($testimonial->cloudinary_public_id) {
                $this->cloudinaryService->delete($testimonial->cloudinary_public_id);
            }
            $uploadResult = $this->cloudinaryService->upload($request->file('image'));
            $validated['image_url'] = $uploadResult['url'];
            $validated['cloudinary_public_id'] = $uploadResult['public_id'];
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

        if ($testimonial->cloudinary_public_id) {
            $this->cloudinaryService->delete($testimonial->cloudinary_public_id);
        }

        $testimonial->delete();

        return response()->json([
            'message' => 'Testimoni berhasil dihapus',
        ]);
    }
}
