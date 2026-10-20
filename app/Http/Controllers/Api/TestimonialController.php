<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Testimonial;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    public function __construct(
        private CloudinaryService $cloudinaryService,
    ) {}

    public function index()
    {
        $testimonials = Testimonial::active()
            ->latest()
            ->get();

        return response()->json([
            'testimonials' => $testimonials,
        ]);
    }

    public function myTestimonial(Request $request)
    {
        $user = $request->user('sanctum') ?? $request->user();
        if (!$user) {
            return response()->json(['testimonial' => null]);
        }

        $testimonial = Testimonial::where('user_id', $user->id)->first();

        return response()->json([
            'testimonial' => $testimonial,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'customer_name' => 'nullable|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'image_url' => 'nullable|string|max:1000',
        ]);

        $order = Order::with('user')->findOrFail($validated['order_id']);

        if ($order->status !== 'completed') {
            return response()->json(['message' => 'Pesanan belum selesai'], 400);
        }

        // Identify user_id from auth user or order's owner
        $userId = $request->user('sanctum')?->id ?? $request->user()?->id ?? $order->user_id;

        // Check if user already has an existing testimonial (1 User = 1 Testimonial)
        $existing = null;
        if ($userId) {
            $existing = Testimonial::where('user_id', $userId)->first();
        }
        if (!$existing) {
            $existing = Testimonial::where('order_id', $order->id)->first();
        }

        // ==========================================
        // 1. UPDATE EXISTING (If user already reviewed)
        // ==========================================
        if ($existing) {
            $imageUrl = $existing->image_url;
            $cloudinaryPublicId = $existing->cloudinary_public_id;

            if ($request->hasFile('image')) {
                if ($cloudinaryPublicId) {
                    $this->cloudinaryService->delete($cloudinaryPublicId);
                }
                $uploadResult = $this->cloudinaryService->upload($request->file('image'));
                $imageUrl = $uploadResult['url'];
                $cloudinaryPublicId = $uploadResult['public_id'];
            } elseif ($order->user?->avatar) {
                $imageUrl = $order->user->avatar;
            } elseif ($request->filled('image_url')) {
                $imageUrl = $request->input('image_url');
            }

            $customerName = $request->input('customer_name') 
                ?? $order->customer_name 
                ?? $order->user?->name 
                ?? $existing->customer_name;

            $existing->update([
                'user_id' => $userId ?? $existing->user_id,
                'order_id' => $order->id,
                'customer_name' => $customerName,
                'rating' => $validated['rating'],
                'review' => $validated['review'] ?? null,
                'image_url' => $imageUrl,
                'cloudinary_public_id' => $cloudinaryPublicId,
                'is_active' => true,
                'created_at' => now(),
            ]);

            return response()->json([
                'message' => 'Testimoni Anda berhasil diperbarui dan telah aktif.',
                'testimonial' => $existing->fresh(),
                'is_updated' => true,
            ], 200);
        }

        // ==========================================
        // 2. INSERT NEW (If first time for this user)
        // ==========================================
        $imageUrl = null;
        $cloudinaryPublicId = null;
        if ($request->hasFile('image')) {
            $uploadResult = $this->cloudinaryService->upload($request->file('image'));
            $imageUrl = $uploadResult['url'];
            $cloudinaryPublicId = $uploadResult['public_id'];
        } else {
            $imageUrl = $order->user?->avatar ?? $request->input('image_url');
        }

        $customerName = $request->input('customer_name') 
            ?? $order->customer_name 
            ?? $order->user?->name 
            ?? 'Pelanggan';

        $testimonial = Testimonial::create([
            'user_id' => $userId,
            'order_id' => $order->id,
            'customer_name' => $customerName,
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? null,
            'image_url' => $imageUrl,
            'cloudinary_public_id' => $cloudinaryPublicId,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Terima kasih! Testimoni Anda telah berhasil dibuat dan langsung aktif.',
            'testimonial' => $testimonial,
            'is_updated' => false,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $testimonial = Testimonial::with('order.user')->findOrFail($id);
        $user = $request->user('sanctum') ?? $request->user();

        $validated = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'customer_name' => 'nullable|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'image_url' => 'nullable|string|max:1000',
        ]);

        $imageUrl = $testimonial->image_url;
        $cloudinaryPublicId = $testimonial->cloudinary_public_id;
        if ($request->hasFile('image')) {
            if ($testimonial->cloudinary_public_id) {
                $this->cloudinaryService->delete($testimonial->cloudinary_public_id);
            }
            $uploadResult = $this->cloudinaryService->upload($request->file('image'));
            $imageUrl = $uploadResult['url'];
            $cloudinaryPublicId = $uploadResult['public_id'];
        } elseif (!$imageUrl) {
            $imageUrl = $testimonial->order?->user?->avatar ?? $user?->avatar ?? $request->input('image_url');
        }

        $customerName = $validated['customer_name']
            ?? $testimonial->order?->customer_name
            ?? $user?->name
            ?? $testimonial->customer_name;

        $testimonial->update([
            'order_id' => $validated['order_id'] ?? $testimonial->order_id,
            'user_id' => $testimonial->user_id ?? $user?->id ?? $testimonial->order?->user_id,
            'customer_name' => $customerName,
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? null,
            'image_url' => $imageUrl,
            'cloudinary_public_id' => $cloudinaryPublicId,
            'is_active' => true,
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Testimoni berhasil diperbarui dan langsung aktif.',
            'testimonial' => $testimonial->fresh(),
        ]);
    }
}

