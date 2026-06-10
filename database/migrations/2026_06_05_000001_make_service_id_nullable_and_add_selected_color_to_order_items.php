<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->change();
            $table->string('selected_color', 50)->nullable()->after('design_notes');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('service_id')->constrained()->change();
            $table->dropColumn('selected_color');
        });
    }
};
