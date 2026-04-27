<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('project_id')->constrained('groups')->nullOnDelete();
            $table->foreignId('parent_task_id')->nullable()->after('group_id')->constrained('tasks')->cascadeOnDelete();
            $table->boolean('allow_subtasks')->default(false)->after('description');
            $table->boolean('auto_status')->default(false)->after('allow_subtasks');
            $table->boolean('can_be_assigned')->default(true)->after('auto_status');
            $table->foreignId('assigned_group_id')->nullable()->after('assigned_to')->constrained('groups')->nullOnDelete();
            $table->index(['project_id', 'group_id']);
            $table->index(['project_id', 'parent_task_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['parent_task_id']);
            $table->dropForeign(['assigned_group_id']);

            $table->dropColumn(['group_id', 'parent_task_id', 'allow_subtasks', 'auto_status', 'can_be_assigned', 'assigned_group_id']);

            $table->dropIndex(['project_id', 'group_id']);
            $table->dropIndex(['project_id', 'parent_task_id']);
        });
    }
};
