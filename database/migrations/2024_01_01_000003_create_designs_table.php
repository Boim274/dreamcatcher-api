<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->string('user_session', 255)->nullable();
            $table->text('prompt')->nullable();
            $table->string('image_url', 500);
            $table->integer('color_count')->default(0);
            $table->boolean('has_gradient')->default(false);
            $table->boolean('has_shadow')->default(false);
            $table->decimal('contrast_score', 5, 2)->default(0);
            $table->boolean('is_valid')->default(false);
            $table->enum('design_type', ['ai_generated', 'uploaded'])->default('uploaded');
            $table->text('validation_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designs');
    }
};