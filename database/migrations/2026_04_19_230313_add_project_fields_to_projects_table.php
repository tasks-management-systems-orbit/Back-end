<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('image')->nullable()->after('description');
            $table->enum('status', ['active', 'paused', 'completed'])->default('active')->after('image');
            $table->enum('visibility', ['private', 'public'])->default('private')->after('status');
            $table->timestamp('start_date')->nullable()->after('visibility');
            $table->timestamp('end_date')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['image', 'status', 'visibility', 'start_date', 'end_date']);
        });
    }
};
