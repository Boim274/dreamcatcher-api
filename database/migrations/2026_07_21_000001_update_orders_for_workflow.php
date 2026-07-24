<?php

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('paid_amount', 12, 2)->default(0)->after('total_price');
            $table->decimal('remaining_amount', 12, 2)->default(0)->after('paid_amount');
        });

        DB::statement("ALTER TABLE orders ADD payment_status ENUM('pending','waiting_verification','dp','paid','rejected') DEFAULT 'pending' AFTER status");

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','waiting_payment','waiting_verification','ready_to_process','processing','ready_for_pickup','paid','processed','delivered','completed','cancelled','cancel_requested') DEFAULT 'pending'");

        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_status ENUM('pending','waiting_verification','dp','paid','verified','rejected') DEFAULT 'pending'");

        $this->migrateExistingOrders();
    }

    private function migrateExistingOrders(): void
    {
        $statusMap = [
            'paid' => 'ready_to_process',
            'processed' => 'processing',
            'delivered' => 'ready_for_pickup',
        ];

        Order::whereIn('status', ['paid', 'processed', 'delivered'])->chunk(100, function ($orders) use ($statusMap) {
            foreach ($orders as $order) {
                $newStatus = $statusMap[$order->status] ?? $order->status;
                $totalPaid = (float) Payment::where('order_id', $order->id)
                    ->whereIn('payment_status', ['verified', 'paid'])
                    ->sum('amount');
                $remaining = max(0, (float) $order->total_price - $totalPaid);

                $paymentStatus = 'pending';
                if ($totalPaid > 0 && $remaining > 0) {
                    $paymentStatus = 'dp';
                } elseif ($totalPaid > 0 && $remaining <= 0) {
                    $paymentStatus = 'paid';
                }

                DB::table('orders')
                    ->where('id', $order->id)
                    ->update([
                        'status' => $newStatus,
                        'payment_status' => $paymentStatus,
                        'paid_amount' => $totalPaid,
                        'remaining_amount' => $remaining,
                    ]);
            }
        });

        Order::where('status', 'waiting_payment')
            ->where('payment_status', 'pending')
            ->chunk(100, function ($orders) {
                foreach ($orders as $order) {
                    $hasPendingPayments = Payment::where('order_id', $order->id)
                        ->where('payment_status', 'pending')
                        ->exists();

                    if ($hasPendingPayments) {
                        DB::table('orders')
                            ->where('id', $order->id)
                            ->update([
                                'payment_status' => 'waiting_verification',
                                'status' => 'waiting_verification',
                            ]);
                    }
                }
            });

        Order::whereNull('payment_status')->orWhere('payment_status', '')->update([
            'payment_status' => 'pending',
        ]);
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','waiting_payment','paid','processed','delivered','completed','cancelled','cancel_requested') DEFAULT 'pending'");

        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_status ENUM('pending','verified','rejected') DEFAULT 'pending'");

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'paid_amount', 'remaining_amount']);
        });
    }
};
