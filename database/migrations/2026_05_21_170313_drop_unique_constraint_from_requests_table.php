<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('requests', function (Blueprint $table) {
            $table->dropIndex('unique_pending_request');
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->index(['sender_id', 'project_id', 'type'], 'idx_requests_sender_project_type');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('requests', function (Blueprint $table) {
            $table->dropIndex('idx_requests_sender_project_type');
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->unique(['sender_id', 'project_id', 'type'], 'unique_pending_request');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};