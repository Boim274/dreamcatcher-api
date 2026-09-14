<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use App\Models\StatusHistory;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => $request->delivery_method === 'delivery' ? 'required|string' : 'nullable|string',
            'delivery_method' => 'required|in:pickup,delivery',
            'notes' => 'nullable|string',
            'description' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'required|exists:services,id',
            'items.*.product_name' => 'nullable|string|max:255',
            'items.*.size' => 'nullable|string|max:50',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.selected_color' => 'nullable|string|max:50',
            'items.*.sablon_type' => 'nullable|string|max:50',
            'items.*.service_option' => 'nullable|string|max:255',
            'items.*.design_type' => 'nullable|in:ai_generated,uploaded',
            'items.*.design_id' => 'nullable|exists:designs,id',
            'items.*.design_notes' => 'nullable|string',
            'items.*.print_area' => 'nullable|string|max:50',
            'items.*.print_size' => 'nullable|string|max:20',
        ]);

        DB::beginTransaction();

        try {
            $orderCode = Order::generateOrderCode();

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['unit_price'] * $item['quantity'];
            }

            $shippingCost = $validated['delivery_method'] === 'delivery' ? 15000 : 0;
            $totalPrice = $subtotal + $shippingCost;

            // Service orders require an authenticated user so every order is
            // tied to its owner.
            $user = $request->user();

            $order = Order::create([
                'order_code' => $orderCode,
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'] ?? null,
                'phone' => $validated['phone'],
                'address' => $validated['address'] ?? 'Ambil di toko',
                'delivery_method' => $validated['delivery_method'],
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total_price' => $totalPrice,
                'notes' => $validated['notes'] ?? null,
                'description' => $validated['description'] ?? null,
                'status' => 'waiting_payment',
                'payment_status' => 'pending',
                'paid_amount' => 0,
                'remaining_amount' => $totalPrice,
                'user_id' => $user->id,
            ]);

            foreach ($validated['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'service_id' => $item['service_id'],
                    'product_name' => $item['product_name'] ?? $item['service_id'],
                    'size' => $item['size'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['unit_price'] * $item['quantity'],
                    'selected_color' => $item['selected_color'] ?? null,
                    'sablon_type' => $item['sablon_type'] ?? null,
                    'design_type' => $item['design_type'] ?? 'uploaded',
                    'design_id' => $item['design_id'] ?? null,
                    'design_notes' => $item['design_notes'] ?? null,
                    'print_area' => $item['print_area'] ?? null,
                    'print_size' => $item['print_size'] ?? null,
                ]);
            }

            DB::commit();

            $order->load('items.service', 'items.design', 'items.product.images');

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order,
                'payment_info' => [
                    'dp_amount' => round($totalPrice * 0.5),
                    'full_amount' => $totalPrice,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create order: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function track(Request $request)
    {
        $validated = $request->validate([
            'order_code' => 'required|string|max:20',
            'phone' => 'required|string|max:20',
        ]);

        $order = Order::with(['items.service', 'items.design', 'items.product.images', 'payments', 'testimonial'])
            ->where('order_code', $validated['order_code'])
            ->where('phone', $validated['phone'])
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found. Please check your order code and phone number.',
            ], 404);
        }

        return response()->json([
            'order' => $order,
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Order::with(['items.service', 'items.design', 'items.product.images', 'payments', 'testimonial'])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if (!empty($user->email)) {
                    $q->orWhere('customer_email', $user->email);
                }
                if (!empty($user->phone)) {
                    $q->orWhere('phone', $user->phone);
                }
            });

        if ($request->has('status')) {
            $statuses = $request->input('status');
            if (is_array($statuses)) {
                $query->whereIn('status', $statuses);
            } else {
                $query->where('status', $statuses);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($iq) use ($search) {
                        $iq->where('product_name', 'like', "%{$search}%")
                            ->orWhereHas('product', function ($pq) use ($search) {
                                $pq->where('name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('service', function ($sq) use ($search) {
                                $sq->where('name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $perPage = (int) $request->input('per_page', 10);
        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $userTestimonial = $user ? Testimonial::where('user_id', $user->id)->first() : null;
        if ($userTestimonial) {
            $orders->getCollection()->transform(function ($order) use ($userTestimonial) {
                $order->user_testimonial = $userTestimonial;
                return $order;
            });
        }

        return response()->json([
            'orders' => $orders,
            'user_testimonial' => $userTestimonial,
        ]);
    }

    public function myStats(Request $request)
    {
        $user = $request->user();

        $activeStatuses = [
            'pending',
            'waiting_payment',
            'waiting_verification',
            'ready_to_process',
            'processing',
            'ready_for_pickup',
            'cancel_requested',
        ];

        $baseQuery = Order::where(function ($q) use ($user) {
            $q->where('user_id', $user->id);
            if (!empty($user->email)) {
                $q->orWhere('customer_email', $user->email);
            }
            if (!empty($user->phone)) {
                $q->orWhere('phone', $user->phone);
            }
        });

        $total = (clone $baseQuery)->count();
        $active = (clone $baseQuery)->whereIn('status', $activeStatuses)->count();
        $completed = (clone $baseQuery)->where('status', 'completed')->count();
        $cancelled = (clone $baseQuery)->where('status', 'cancelled')->count();

        return response()->json([
            'stats' => [
                'total' => $total,
                'active' => $active,
                'completed' => $completed,
                'cancelled' => $cancelled,
            ],
        ]);
    }

    public function show(Request $request, $id)
    {
        $order = Order::with(['items.service', 'items.design', 'items.product.images', 'payments', 'testimonial', 'user'])
            ->findOrFail($id);

        $user = $request->user();
        if ($user && !$user->isAdmin() && $order->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized. You can only view your own orders.',
            ], 403);
        }

        $userTestimonial = $order->user_id ? Testimonial::where('user_id', $order->user_id)->first() : null;
        $order->user_testimonial = $userTestimonial;

        return response()->json([
            'order' => $order,
            'user_testimonial' => $userTestimonial,
        ]);
    }

    public function showByCode($orderCode)
    {
        $order = Order::with(['items.service', 'items.design', 'items.product.images', 'payments', 'testimonial', 'user'])
            ->where('order_code', $orderCode)
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found',
            ], 404);
        }

        $userTestimonial = $order->user_id ? Testimonial::where('user_id', $order->user_id)->first() : null;
        $order->user_testimonial = $userTestimonial;

        return response()->json([
            'order' => $order,
            'user_testimonial' => $userTestimonial,
        ]);
    }

    public function requestCancel(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $user = $request->user();
        if (!$user || ($order->user_id !== $user->id && !$user->isAdmin())) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if (!in_array($order->status, ['pending', 'waiting_payment'])) {
            return response()->json([
                'message' => 'Pesanan hanya bisa dibatalkan sebelum pembayaran atau dalam status pending',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $order->update(['status' => 'cancel_requested']);

            StatusHistory::create([
                'order_id' => $order->id,
                'status' => 'cancel_requested',
                'notes' => $validated['reason'] ?? 'User meminta pembatalan',
                'created_by' => 'user',
            ]);

            DB::commit();

            $order->load('items.service');

            return response()->json([
                'message' => 'Permintaan pembatalan berhasil dikirim. Menunggu konfirmasi admin.',
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memproses permintaan pembatalan: ' . $e->getMessage(),
            ], 500);
        }
    }
}
