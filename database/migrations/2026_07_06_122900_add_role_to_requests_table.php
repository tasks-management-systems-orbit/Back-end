<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add the `role` column to the `requests` table so invitations can store
     * the role the sender wants the invitee to have when accepted
     * ('user' or 'observer').
     */
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->string('role', 32)->nullable()->after('message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
