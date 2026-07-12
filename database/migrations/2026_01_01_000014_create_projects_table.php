<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->enum('status', ['active', 'paused', 'completed'])->default('active');
            $table->enum('visibility', ['private', 'public'])->default('private');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('chain_id')->nullable()->constrained('chains')->nullOnDelete();
            $table->boolean('allow_join_requests')->default(false);
            $table->boolean('allow_commit')->default(true);
            $table->boolean('allow_reactions')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('created_by');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
