<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE project_users DROP CONSTRAINT project_users_role_check');
        DB::statement("ALTER TABLE project_users ADD CONSTRAINT project_users_role_check CHECK (role::text = ANY (ARRAY['owner'::character varying, 'manager'::character varying, 'member'::character varying, 'user'::character varying, 'observer'::character varying]::text[]))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE project_users DROP CONSTRAINT project_users_role_check');
        DB::statement("ALTER TABLE project_users ADD CONSTRAINT project_users_role_check CHECK (role::text = ANY (ARRAY['owner'::character varying, 'manager'::character varying, 'user'::character varying, 'observer'::character varying]::text[]))");
    }
};
