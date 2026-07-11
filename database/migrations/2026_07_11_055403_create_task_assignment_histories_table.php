<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_assignment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->enum('action', ['assigned', 'unassigned']);
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            $table->index(['task_id', 'assigned_at']);
            $table->index(['user_id', 'task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignment_histories');
    }
};
