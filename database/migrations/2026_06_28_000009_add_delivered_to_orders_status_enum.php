<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','waiting_payment','paid','processed','delivered','completed','cancelled','cancel_requested') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','waiting_payment','paid','processed','completed','cancelled','cancel_requested') DEFAULT 'pending'");
    }
};
