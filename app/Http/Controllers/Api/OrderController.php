<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
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

            // Optional auth: resolve user from token if present
            $userId = null;
            if ($request->bearerToken()) {
                $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());
                if ($token) {
                    $userId = $token->tokenable->id;
                }
            }

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
                'status' => 'waiting_payment',
                'user_id' => $userId,
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
                    'design_type' => $item['design_type'] ?? 'uploaded',
                    'design_id' => $item['design_id'] ?? null,
                    'design_notes' => $item['design_notes'] ?? null,
                ]);
            }

            DB::commit();

            $order->load('items.service', 'items.design');

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

        $order = Order::with(['items.service', 'payments'])
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

        $orders = Order::with(['items.service'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, $id)
    {
        $order = Order::with(['items.service', 'items.design', 'payments', 'user'])
            ->findOrFail($id);

        $user = $request->user();
        if ($user && !$user->isAdmin() && $order->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized. You can only view your own orders.',
            ], 403);
        }

        return response()->json([
            'order' => $order,
        ]);
    }

    public function showByCode($orderCode)
    {
        $order = Order::with(['items.service', 'items.design', 'payments', 'user'])
            ->where('order_code', $orderCode)
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'order' => $order,
        ]);
    }
}
