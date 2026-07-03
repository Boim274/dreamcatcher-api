<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestimonialController extends Controller
{
    public function index()
    {
        $testimonials = Testimonial::active()
            ->ordered()
            ->get();

        return response()->json([
            'testimonials' => $testimonials,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $order = Order::findOrFail($validated['order_id']);

        if ($order->status !== 'completed') {
            return response()->json(['message' => 'Pesanan belum selesai'], 400);
        }

        if (Testimonial::where('order_id', $order->id)->exists()) {
            return response()->json(['message' => 'Anda sudah memberikan testimoni untuk pesanan ini'], 400);
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('testimonials', 'public');
            $imageUrl = 'storage/' . $path;
        }

        $testimonial = Testimonial::create([
            'customer_name' => $order->customer_name,
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? null,
            'image_url' => $imageUrl,
            'order_id' => $order->id,
            'is_active' => false,
        ]);

        return response()->json([
            'message' => 'Terima kasih! Testimoni Anda akan ditinjau oleh admin sebelum tampil di halaman utama.',
            'testimonial' => $testimonial,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $testimonial = Testimonial::findOrFail($id);

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $imageUrl = $testimonial->image_url;
        if ($request->hasFile('image')) {
            if ($testimonial->image_url && Storage::disk('public')->exists(str_replace('storage/', '', $testimonial->image_url))) {
                Storage::disk('public')->delete(str_replace('storage/', '', $testimonial->image_url));
            }
            $path = $request->file('image')->store('testimonials', 'public');
            $imageUrl = 'storage/' . $path;
        }

        $testimonial->update([
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? null,
            'image_url' => $imageUrl,
            'is_active' => false,
        ]);

        return response()->json([
            'message' => 'Testimoni berhasil diperbarui dan akan ditinjau kembali oleh admin.',
            'testimonial' => $testimonial->fresh(),
        ]);
    }
}
