<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['order', 'verifier']);

        if ($request->has('status')) {
            $query->where('payment_status', $request->status);
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
        $payment->update([
            'payment_status' => 'verified',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'notes' => $validated['notes'] ?? $payment->notes,
        ]);

        $order = $payment->order;
        $order->update(['status' => 'paid']);

        return response()->json([
            'message' => 'Payment verified successfully',
            'payment' => $payment->fresh(['order', 'verifier']),
        ]);
    }

    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $payment = Payment::findOrFail($id);
        $payment->update([
            'payment_status' => 'rejected',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'notes' => $validated['notes'],
        ]);

        return response()->json([
            'message' => 'Payment rejected',
            'payment' => $payment->fresh(['order', 'verifier']),
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