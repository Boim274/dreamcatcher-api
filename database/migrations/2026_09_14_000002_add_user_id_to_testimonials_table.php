<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });

        // Backfill user_id from orders
        $orders = DB::table('orders')->whereNotNull('user_id')->get();
        foreach ($orders as $order) {
            DB::table('testimonials')
                ->where('order_id', $order->id)
                ->whereNull('user_id')
                ->update(['user_id' => $order->user_id]);
        }

        // Deduplicate any historical testimonials with same user_id before applying unique constraint
        // Keep the latest testimonial (highest ID) for each user_id
        $duplicates = DB::table('testimonials')
            ->select('user_id', DB::raw('MAX(id) as latest_id'), DB::raw('COUNT(*) as total'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('testimonials')
                ->where('user_id', $duplicate->user_id)
                ->where('id', '!=', $duplicate->latest_id)
                ->delete();
        }

        // Add UNIQUE constraint on user_id
        Schema::table('testimonials', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
