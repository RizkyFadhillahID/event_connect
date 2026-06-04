<?php

namespace Database\Seeders;

use App\Models\PlatformAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        PlatformAdmin::create([
            'name'     => 'Platform Owner',
            'email'    => 'admin@eventconnect.com',
            'password' => Hash::make('password123'),
            'role'     => 'owner',
            'status'   => 'active',
        ]);
    }
}
