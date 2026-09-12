<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE designs MODIFY design_type ENUM('ai_generated','uploaded','chat_upload') NOT NULL DEFAULT 'uploaded'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE designs MODIFY design_type ENUM('ai_generated','uploaded') NOT NULL DEFAULT 'uploaded'");
    }
};