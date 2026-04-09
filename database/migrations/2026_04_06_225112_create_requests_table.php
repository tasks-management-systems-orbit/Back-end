<?php
// database/migrations/[timestamp]_create_requests_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['join_request', 'invitation'])->default('join_request');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->text('message')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Unique constraint to prevent duplicate requests
            $table->unique(['sender_id', 'project_id', 'type'], 'unique_pending_request');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
