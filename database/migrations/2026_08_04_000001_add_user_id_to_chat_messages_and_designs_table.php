<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->string('user_session', 255)->nullable()->change();
            $table->foreignId('user_id')->nullable()->after('user_session')->index()->constrained()->nullOnDelete();
        });

        Schema::table('designs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('user_session')->index()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->string('user_session', 255)->change();
        });

        Schema::table('designs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};