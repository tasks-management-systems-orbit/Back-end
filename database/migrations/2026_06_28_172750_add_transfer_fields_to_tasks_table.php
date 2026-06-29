<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('can_be_assigned');
            $table->foreignId('transferred_from_task_id')
                ->nullable()
                ->constrained('tasks')
                ->nullOnDelete()
                ->after('is_archived');
            $table->foreignId('transferred_to_task_id')
                ->nullable()
                ->constrained('tasks')
                ->nullOnDelete()
                ->after('transferred_from_task_id');

            $table->index('is_archived');
            $table->index('transferred_from_task_id');
            $table->index('transferred_to_task_id');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['transferred_from_task_id']);
            $table->dropForeign(['transferred_to_task_id']);
            $table->dropColumn(['is_archived', 'transferred_from_task_id', 'transferred_to_task_id']);
        });
    }
};