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
        Schema::disableForeignKeyConstraints();

        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name', 255)->nullable();
            $table->string('phone', 50)->unique()->nullable();
            $table->text('bio')->nullable();
            $table->string('job_title', 255)->nullable();
            $table->json('skills')->nullable()->comment('[\"PHP\", \"Laravel\", \"MySQL\"]');
            $table->string('avatar', 255)->nullable();
            $table->string('location', 255)->nullable()->comment('text from user');
            $table->string('twitter_url', 255)->nullable();
            $table->string('alternative_email', 255)->nullable();
            $table->string('github_url', 255)->nullable();
            $table->string('portfolio_url', 255)->nullable();
            $table->string('linkedin_url', 255)->nullable();
            $table->string('cv_url', 255)->nullable()->comment('file pdf less than 2MB');
            $table->string('language', 10)->nullable()->comment('language of the app');
            $table->enum('theme', ['light', 'dark'])->nullable();
            $table->boolean('is_public')->nullable()->comment('If his profile is public, people can send an invite, and he is able to accept or refuse');
            $table->integer('projects_count')->nullable();
            $table->integer('tasks_completed')->nullable();
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
