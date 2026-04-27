<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete(); // cascadeOnDelete أفضل
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->foreignId('status_id')->nullable()->constrained('task_statuses')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->unique(['task_id', 'user_id']);

            $table->index(['user_id', 'status_id']);
            $table->index(['task_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignments');
    }
};
