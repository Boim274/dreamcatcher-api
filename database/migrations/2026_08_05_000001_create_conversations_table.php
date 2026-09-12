<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->string('user_session', 255)->nullable();
            $table->string('title', 255)->default('Desain Baru');
            $table->string('emoji', 16)->nullable();
            $table->boolean('pinned')->default(false);
            $table->enum('status', ['active', 'trashed'])->default('active');
            $table->timestamp('trashed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->foreignId('conversation_id')->nullable()->after('user_id')->index()->constrained('conversations')->nullOnDelete();
        });

        Schema::table('designs', function (Blueprint $table) {
            $table->foreignId('conversation_id')->nullable()->after('user_id')->index()->constrained('conversations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('designs', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropColumn('conversation_id');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropColumn('conversation_id');
        });

        Schema::dropIfExists('conversations');
    }
};
