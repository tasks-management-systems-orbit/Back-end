<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('to_project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('from_task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('to_task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('transferred_by')->constrained('users')->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('transferred_at')->useCurrent();
            $table->timestamps();

            $table->index('task_id');
            $table->index('from_project_id');
            $table->index('to_project_id');
            $table->index('transferred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_transfers');
    }
};
