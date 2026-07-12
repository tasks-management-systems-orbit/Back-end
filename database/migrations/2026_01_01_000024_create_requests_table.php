<?php

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
            $table->foreignId('project_id')->constrained()->restrictOnDelete();

            $table->enum('type', ['join_request', 'invitation'])->default('join_request');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->text('message')->nullable();
            $table->string('role', 32)->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Index to look up requests by sender + project + type quickly
            // (the original unique constraint was dropped because it prevented
            //  re-sending after a previous request was rejected/expired)
            $table->index(['sender_id', 'project_id', 'type'], 'idx_requests_sender_project_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
