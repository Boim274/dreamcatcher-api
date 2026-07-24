<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\StatusHistory;
use Illuminate\Validation\ValidationException;

class OrderWorkflowService
{
    const VALID_TRANSITIONS = [
        'waiting_payment'    => ['waiting_verification', 'cancelled'],
        'waiting_verification' => ['processing', 'ready_for_pickup', 'cancelled'],
        'processing'         => ['ready_for_pickup'],
        'ready_for_pickup'   => ['completed'],
        'completed'          => [],
        'cancelled'          => [],
        'cancel_requested'   => ['cancelled', 'waiting_payment'],
    ];

    public function transitionStatus(Order $order, string $newStatus, ?string $notes = null): Order
    {
        $allowed = self::VALID_TRANSITIONS[$order->status] ?? [];

        if (!in_array($newStatus, $allowed)) {
            throw ValidationException::withMessages([
                'status' => ["Transisi status dari '{$order->status}' ke '{$newStatus}' tidak diizinkan."],
            ]);
        }

        $order->update(['status' => $newStatus]);

        StatusHistory::create([
            'order_id' => $order->id,
            'status' => $newStatus,
            'notes' => $notes ?? "Status berubah dari {$order->status} ke {$newStatus}",
            'created_by' => 'admin',
        ]);

        return $order->fresh();
    }

    public function verifyFullPayment(Order $order, Payment $payment, ?string $notes = null): Order
    {
        $payment->update([
            'payment_status' => 'paid',
            'verified_by' => request()->user()->id,
            'verified_at' => now(),
            'notes' => $notes ?? $payment->notes,
        ]);

        $totalPaid = (float) $payment->amount;
        $existingPaid = (float) $order->paid_amount;
        $newPaid = $existingPaid + $totalPaid;
        $remaining = max(0, (float) $order->total_price - $newPaid);

        $order->update([
            'payment_status' => 'paid',
            'paid_amount' => $newPaid,
            'remaining_amount' => $remaining,
            'status' => 'processing',
        ]);

        StatusHistory::create([
            'order_id' => $order->id,
            'status' => 'processing',
            'notes' => 'Pembayaran lunas — pesanan langsung diproses',
            'created_by' => 'admin',
        ]);

        return $order->fresh();
    }

    public function verifyDpPayment(Order $order, Payment $payment, ?string $notes = null): Order
    {
        $payment->update([
            'payment_status' => 'dp',
            'verified_by' => request()->user()->id,
            'verified_at' => now(),
            'notes' => $notes ?? $payment->notes,
        ]);

        $totalPaid = (float) $payment->amount;
        $existingPaid = (float) $order->paid_amount;
        $newPaid = $existingPaid + $totalPaid;
        $remaining = max(0, (float) $order->total_price - $newPaid);

        $order->update([
            'payment_status' => 'dp',
            'paid_amount' => $newPaid,
            'remaining_amount' => $remaining,
            'status' => 'processing',
        ]);

        StatusHistory::create([
            'order_id' => $order->id,
            'status' => 'processing',
            'notes' => 'Pembayaran DP (Rp ' . number_format($totalPaid, 0, ',', '.') . ') — pesanan langsung diproses',
            'created_by' => 'admin',
        ]);

        return $order->fresh();
    }

    public function verifyPelunasan(Order $order, Payment $payment, ?string $notes = null): Order
    {
        $payment->update([
            'payment_status' => 'paid',
            'verified_by' => request()->user()->id,
            'verified_at' => now(),
            'notes' => $notes ?? $payment->notes,
        ]);

        $totalPaid = (float) $payment->amount;
        $existingPaid = (float) $order->paid_amount;
        $newPaid = $existingPaid + $totalPaid;

        $order->update([
            'payment_status' => 'paid',
            'paid_amount' => $newPaid,
            'remaining_amount' => 0,
        ]);

        StatusHistory::create([
            'order_id' => $order->id,
            'status' => $order->status,
            'notes' => 'Pelunasan diterima — pembayaran lunas',
            'created_by' => 'admin',
        ]);

        return $order->fresh();
    }

    public function markAsWaitingVerification(Order $order): Order
    {
        $order->update([
            'payment_status' => 'waiting_verification',
            'status' => 'waiting_verification',
        ]);

        StatusHistory::create([
            'order_id' => $order->id,
            'status' => 'waiting_verification',
            'notes' => 'Bukti pembayaran diupload — menunggu verifikasi',
            'created_by' => 'system',
        ]);

        return $order->fresh();
    }

    public function rejectPayment(Order $order, Payment $payment, string $reason): Order
    {
        $payment->update([
            'payment_status' => 'rejected',
            'verified_by' => request()->user()->id,
            'verified_at' => now(),
            'notes' => $reason,
        ]);

        $previousOrderStatus = $order->status === 'waiting_verification' ? 'waiting_payment' : $order->status;

        $order->update([
            'payment_status' => 'rejected',
            'status' => $previousOrderStatus,
        ]);

        StatusHistory::create([
            'order_id' => $order->id,
            'status' => $previousOrderStatus,
            'notes' => 'Pembayaran ditolak: ' . $reason,
            'created_by' => 'admin',
        ]);

        return $order->fresh();
    }
}
