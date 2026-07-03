<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductOrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.size' => 'nullable|string|max:50',
            'items.*.color' => 'nullable|string|max:50',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'delivery_method' => 'required|in:pickup,delivery',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $shippingCost = (int) (Setting::getValue('shipping_cost', 15000));
        $subtotal = 0;
        $orderItems = [];

        foreach ($validated['items'] as $item) {
            $product = Product::with('sizes')->findOrFail($item['product_id']);

            if ($item['quantity'] > $product->stock) {
                return response()->json([
                    'message' => "Insufficient stock for {$product->name}. Available: {$product->stock}",
                ], 422);
            }

            $unitPrice = (float) $product->base_price;
            $variantParts = [];

            if (!empty($item['size'])) {
                $variantParts[] = "Size: {$item['size']}";
                $sizeModel = $product->sizes->where('name', $item['size'])->first();
                if ($sizeModel && $item['quantity'] > $sizeModel->stock) {
                    return response()->json([
                        'message' => "Insufficient stock for {$product->name} (Size: {$item['size']}). Available: {$sizeModel->stock}",
                    ], 422);
                }
            }

            if (!empty($item['color'])) {
                $variantParts[] = "Color: {$item['color']}";
            }

            $subtotalItem = $unitPrice * $item['quantity'];
            $subtotal += $subtotalItem;

            $orderItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'size' => $item['size'] ?? null,
                'variant_label' => implode(', ', $variantParts),
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'subtotal' => $subtotalItem,
            ];
        }

        $shipping = $validated['delivery_method'] === 'delivery' ? $shippingCost : 0;
        $total = $subtotal + $shipping;

        $order = Order::create([
            'order_code' => 'PRD-' . strtoupper(Str::random(8)),
            'user_id' => $user->id,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'phone' => $validated['phone'],
            'address' => $validated['address'] ?? '',
            'delivery_method' => $validated['delivery_method'],
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping,
            'total_price' => $total,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        foreach ($orderItems as $item) {
            $orderItem = $order->items()->create($item);

            $product = Product::find($item['product_id']);
            if ($product) {
                $product->decrement('stock', $item['quantity']);
                if (!empty($item['size'])) {
                    $product->sizes()->where('name', $item['size'])->decrement('stock', $item['quantity']);
                }
            }
        }

        $order->statusHistory()->create([
            'status' => 'pending',
            'notes' => 'Product order created',
            'created_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order->load('items'),
        ], 201);
    }
}
