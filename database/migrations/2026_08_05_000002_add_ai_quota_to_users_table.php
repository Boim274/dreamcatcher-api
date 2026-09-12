<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('generate_used')->default(0)->after('address');
            $table->unsignedInteger('generate_limit')->default(3)->after('generate_used');
            $table->unsignedInteger('edit_used')->default(0)->after('generate_limit');
            $table->unsignedInteger('edit_limit')->default(3)->after('edit_used');
            $table->timestamp('last_generate_at')->nullable()->after('edit_limit');
            $table->timestamp('last_edit_at')->nullable()->after('last_generate_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'generate_used',
                'generate_limit',
                'edit_used',
                'edit_limit',
                'last_generate_at',
                'last_edit_at',
            ]);
        });
    }
};