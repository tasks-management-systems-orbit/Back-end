<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropUnique('unique_pending_request');

            $table->index(['sender_id', 'project_id', 'type'], 'idx_requests_sender_project_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropIndex('idx_requests_sender_project_type');

            $table->unique(['sender_id', 'project_id', 'type'], 'unique_pending_request');
        });
    }
};
