<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update any existing users whose role is not superadmin or project_manager to 'staff'
        DB::table('users')
            ->whereNotIn('role', ['superadmin', 'project_manager'])
            ->update(['role' => 'staff']);

        // 2. Change the enum 'role' column to a string column with default 'staff'
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 50)->default('staff')->change();
            });
        } else {
            DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'staff'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting is simplified to keeping varchar(50) since it is a superset of enum values.
    }
};
