<?php

namespace Database\Seeders;

use App\Models\PlatformAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        PlatformAdmin::updateOrCreate(
            ['email' => 'admin@eventconnect.com'],
            [
                'name'     => 'Platform Owner',
                'password' => Hash::make('password123'),
                'role'     => 'owner',
                'status'   => 'active',
            ]
        );
    }
}
