<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('task_statuses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->integer('position');
            $table->timestamps();
            $table->unique(['project_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_statuses');
    }
};
