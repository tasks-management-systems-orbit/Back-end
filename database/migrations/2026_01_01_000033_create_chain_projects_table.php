<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chain_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->unique(['chain_id', 'project_id']);
            $table->index(['chain_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chain_projects');
    }
};
