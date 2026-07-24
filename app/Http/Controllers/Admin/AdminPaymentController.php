<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function __construct(
        private OrderWorkflowService $workflowService
    ) {}

    public function index(Request $request)
    {
        $query = Payment::with(['order', 'verifier']);

        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('order', function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'payments' => $payments,
        ]);
    }

    public function pending()
    {
        $payments = Payment::with(['order', 'verifier'])
            ->where('payment_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'payments' => $payments,
        ]);
    }

    public function verify(Request $request, $id)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $payment = Payment::findOrFail($id);
        $order = $payment->order;

        if ($payment->payment_status !== 'pending') {
            return response()->json(['message' => 'Pembayaran sudah diverifikasi sebelumnya'], 422);
        }

        $updatedOrder = $this->workflowService->verifyFullPayment(
            $order,
            $payment,
            $validated['notes'] ?? null
        );

        return response()->json([
            'message' => 'Pembayaran lunas berhasil diverifikasi. Pesanan siap diproses.',
            'payment' => $payment->fresh(['order', 'verifier']),
            'order' => $updatedOrder,
        ]);
    }

    public function verifyDp(Request $request, $id)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $payment = Payment::findOrFail($id);
        $order = $payment->order;

        if ($payment->payment_status !== 'pending') {
            return response()->json(['message' => 'Pembayaran sudah diverifikasi sebelumnya'], 422);
        }

        $updatedOrder = $this->workflowService->verifyDpPayment(
            $order,
            $payment,
            $validated['notes'] ?? null
        );

        return response()->json([
            'message' => 'Pembayaran DP berhasil diverifikasi. Pesanan siap diproses.',
            'payment' => $payment->fresh(['order', 'verifier']),
            'order' => $updatedOrder,
        ]);
    }

    public function verifyPelunasan(Request $request, $id)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $payment = Payment::findOrFail($id);
        $order = $payment->order;

        if ($payment->payment_status !== 'pending') {
            return response()->json(['message' => 'Pembayaran sudah diverifikasi sebelumnya'], 422);
        }

        $updatedOrder = $this->workflowService->verifyPelunasan(
            $order,
            $payment,
            $validated['notes'] ?? null
        );

        return response()->json([
            'message' => 'Pelunasan berhasil diverifikasi.',
            'payment' => $payment->fresh(['order', 'verifier']),
            'order' => $updatedOrder,
        ]);
    }

    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $payment = Payment::findOrFail($id);
        $order = $payment->order;

        if ($payment->payment_status !== 'pending') {
            return response()->json(['message' => 'Pembayaran sudah diverifikasi sebelumnya'], 422);
        }

        $updatedOrder = $this->workflowService->rejectPayment(
            $order,
            $payment,
            $validated['notes']
        );

        return response()->json([
            'message' => 'Pembayaran ditolak',
            'payment' => $payment->fresh(['order', 'verifier']),
            'order' => $updatedOrder,
        ]);
    }

    public function show($id)
    {
        $payment = Payment::with(['order', 'verifier'])
            ->findOrFail($id);

        return response()->json([
            'payment' => $payment,
        ]);
    }
}
