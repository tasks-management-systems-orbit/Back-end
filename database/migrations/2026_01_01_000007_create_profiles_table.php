<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('profiles');

        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // Basic info
            $table->string('phone', 50)->unique()->nullable();
            $table->text('bio')->nullable();
            $table->string('job_title', 255)->nullable();
            $table->json('skills')->nullable();
            $table->string('avatar', 255)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('alternative_email', 255)->nullable();

            // Social links
            $table->string('twitter_url', 255)->nullable();
            $table->string('facebook_url', 255)->nullable();
            $table->string('instagram_url', 255)->nullable();
            $table->string('youtube_url', 255)->nullable();
            $table->string('github_url', 255)->nullable();
            $table->string('portfolio_url', 255)->nullable();
            $table->string('linkedin_url', 255)->nullable();
            $table->string('cv_url', 255)->nullable();

            // Preferences
            $table->enum('language', ['ar', 'en'])->default('ar');
            $table->enum('theme', ['light', 'dark'])->default('light');

            // Privacy settings
            $table->boolean('is_public')->default(false);
            $table->boolean('allow_messages')->default(false);
            $table->boolean('allow_invitation_requests')->default(false);

            // Statistics & reporting
            $table->integer('projects_count')->default(0);
            $table->integer('tasks_completed')->default(0);
            $table->integer('report_count')->default(0);

            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
