<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StatusHistory;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['items.service', 'user']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'orders' => $orders,
        ]);
    }

    public function show($id)
    {
        $order = Order::with([
            'items.service',
            'items.design',
            'payments.verifier',
            'user',
        ])->findOrFail($id);

        return response()->json([
            'order' => $order,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,waiting_payment,paid,processed,completed,cancelled,cancel_requested',
            'notes' => 'nullable|string|max:500',
        ]);

        $order = Order::findOrFail($id);
        $oldStatus = $order->status;
        $order->update(['status' => $validated['status']]);

        StatusHistory::create([
            'order_id' => $order->id,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => 'admin',
        ]);

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order->fresh(),
        ]);
    }

    public function getOrderStats()
    {
        return response()->json([
            'total_orders' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'waiting_payment' => Order::where('status', 'waiting_payment')->count(),
            'paid' => Order::where('status', 'paid')->count(),
            'processed' => Order::where('status', 'processed')->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
            'cancel_requested' => Order::where('status', 'cancel_requested')->count(),
        ]);
    }
}