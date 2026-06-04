<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create default organization
        $orgId = DB::table('organizations')->insertGetId([
            'name'       => 'Default Organization',
            'slug'       => 'default',
            'email'      => 'default@eventconnect.com',
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Assign all existing records to default organization
        DB::table('users')->whereNull('organization_id')->update(['organization_id' => $orgId]);
        DB::table('events')->whereNull('organization_id')->update(['organization_id' => $orgId]);
        DB::table('inventories')->whereNull('organization_id')->update(['organization_id' => $orgId]);

        // 3. Make organization_id NOT NULL
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable(false)->change();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable(false)->change();
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        // Make columns nullable again
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->change();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->change();
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->change();
        });

        // Delete default organization
        DB::table('organizations')->where('slug', 'default')->delete();
    }
};
