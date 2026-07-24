<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function __construct(
        private OrderWorkflowService $workflowService
    ) {}

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
            'status' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $order = Order::findOrFail($id);

        $updatedOrder = $this->workflowService->transitionStatus(
            $order,
            $validated['status'],
            $validated['notes'] ?? null
        );

        return response()->json([
            'message' => 'Status pesanan berhasil diperbarui',
            'order' => $updatedOrder,
        ]);
    }

    public function getOrderStats()
    {
        return response()->json([
            'total_orders' => Order::count(),
            'waiting_payment' => Order::where('status', 'waiting_payment')->count(),
            'waiting_verification' => Order::where('status', 'waiting_verification')->count(),
            'ready_to_process' => Order::where('status', 'ready_to_process')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'ready_for_pickup' => Order::where('status', 'ready_for_pickup')->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
            'cancel_requested' => Order::where('status', 'cancel_requested')->count(),
        ]);
    }
}
