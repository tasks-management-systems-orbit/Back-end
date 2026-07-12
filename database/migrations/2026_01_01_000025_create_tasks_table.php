<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->restrictOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->restrictOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->boolean('allow_subtasks')->default(false);
            $table->boolean('auto_status')->default(false);
            $table->boolean('can_be_assigned')->default(true);
            $table->foreignId('status_id')->constrained('task_statuses')->restrictOnDelete();
            $table->enum('priority', ['urgent', 'high', 'medium', 'low'])->default('medium');
            $table->date('due_date')->nullable();
            $table->integer('position')->default(0);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->foreignId('transferred_from_task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('transferred_to_task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['project_id', 'status_id', 'position']);
            $table->index('assigned_to');
            $table->index('due_date');
            $table->index('priority');
            $table->index(['project_id', 'group_id']);
            $table->index(['project_id', 'parent_task_id']);
            $table->index('is_archived');
            $table->index('transferred_from_task_id');
            $table->index('transferred_to_task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
