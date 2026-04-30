<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users');
            $table->foreignId('reported_project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('reason', 255);
            $table->text('details')->nullable();
            $table->timestamps();

            $table->unique(['reporter_id', 'reported_project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_reports');
    }
};
