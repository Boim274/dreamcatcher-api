<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('designs', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('user_id')
                ->index()
                ->constrained('designs')
                ->nullOnDelete();

            $table->unsignedInteger('version')->default(1)->after('parent_id');
            $table->enum('kind', ['generated', 'uploaded', 'enhanced', 'edit'])
                ->default('generated')
                ->after('design_type');

            $table->json('setting')->nullable()->after('validation_message');
        });
    }

    public function down(): void
    {
        Schema::table('designs', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
        });
        Schema::table('designs', function (Blueprint $table) {
            $table->dropColumn(['parent_id', 'version', 'kind', 'setting']);
        });
    }
};