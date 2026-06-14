<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\ImgBBService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private ImgBBService $imgbbService
    ) {}

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:transfer_bank,cash,qris',
            'amount' => 'required|numeric|min:0',
            'payment_type' => 'required|in:dp,full,pelunasan',
            'payment_proof' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'notes' => 'nullable|string',
        ]);

        $order = Order::findOrFail($validated['order_id']);

        $paymentProofUrl = null;
        if ($request->hasFile('payment_proof')) {
            $paymentProofUrl = $this->imgbbService->upload($request->file('payment_proof'));
        }

        $payment = Payment::create([
            'order_id' => $validated['order_id'],
            'payment_method' => $validated['payment_method'],
            'payment_proof' => $paymentProofUrl,
            'amount' => $validated['amount'],
            'payment_type' => $validated['payment_type'],
            'payment_status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Payment uploaded successfully. Please wait for verification.',
            'payment' => $payment,
        ], 201);
    }

    public function show($orderId)
    {
        $payments = Payment::where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'payments' => $payments,
        ]);
    }
}
