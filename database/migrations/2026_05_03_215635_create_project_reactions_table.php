<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('reaction_type', ['like', 'love', 'dislike']);
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
            $table->index(['project_id', 'reaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_reactions');
    }
};
