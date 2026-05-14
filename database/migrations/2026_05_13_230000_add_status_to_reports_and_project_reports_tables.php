<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('status', 20)->default('open')->after('details');
        });

        Schema::table('project_reports', function (Blueprint $table) {
            $table->string('status', 20)->default('open')->after('details');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('project_reports', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
