<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->enum('pricing_type', ['flat', 'tiered'])->default('flat')->after('is_active');
            $table->json('pricing_config')->nullable()->after('pricing_type');
            $table->json('options_config')->nullable()->after('pricing_config');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['pricing_type', 'pricing_config', 'options_config']);
        });
    }
};
