<?php
// database/migrations/2024_01_15_000004_create_tasks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->foreignId('status_id')->constrained('task_statuses')->cascadeOnDelete();
            $table->enum('priority', ['urgent', 'high', 'medium', 'low'])->default('medium');
            $table->date('due_date')->nullable();  
            $table->integer('position')->default(0);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['project_id', 'status_id', 'position']);
            $table->index('assigned_to');
            $table->index('due_date');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
