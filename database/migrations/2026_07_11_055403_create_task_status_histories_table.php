<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_status_id')->nullable()->constrained('task_statuses')->nullOnDelete();
            $table->foreignId('to_status_id')->constrained('task_statuses')->cascadeOnDelete();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamps();

            $table->index(['task_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_status_histories');
    }
};
