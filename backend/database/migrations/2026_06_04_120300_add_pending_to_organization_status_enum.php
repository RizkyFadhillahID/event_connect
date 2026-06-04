<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE organizations MODIFY COLUMN status ENUM('active', 'suspended', 'inactive', 'pending') DEFAULT 'active'");
        } else {
            Schema::table('organizations', function (Blueprint $table) {
                $table->string('status')->default('active')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            // Set pending status to suspended/inactive first to avoid truncation errors on rollback
            DB::table('organizations')->where('status', 'pending')->update(['status' => 'inactive']);
            DB::statement("ALTER TABLE organizations MODIFY COLUMN status ENUM('active', 'suspended', 'inactive') DEFAULT 'active'");
        } else {
            DB::table('organizations')->where('status', 'pending')->update(['status' => 'inactive']);
            Schema::table('organizations', function (Blueprint $table) {
                $table->string('status')->default('active')->change();
            });
        }
    }
};
