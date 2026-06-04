<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed Platform Admin first
        $this->call(PlatformAdminSeeder::class);
        $this->call(LandingContentSeeder::class);

        // Get default organization
        $org = \App\Models\Organization::where('slug', 'default')->first();
        $orgId = $org ? $org->id : 1;

        // Create Superadmin
        $superadmin = User::create([
            'organization_id' => $orgId,
            'name' => 'Super Admin',
            'email' => 'superadmin@eventconnect.com',
            'phone' => '081234567890',
            'role' => 'superadmin',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        // Create Project Manager
        $pm = User::create([
            'organization_id' => $orgId,
            'name' => 'Budi Santoso',
            'email' => 'pm@eventconnect.com',
            'phone' => '081234567891',
            'role' => 'project_manager',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        // Create other team members
        $usersData = [
            ['name' => 'Siti Rahayu',      'email' => 'planner@eventconnect.com',      'role' => 'staff', 'phone' => '081234567892'],
            ['name' => 'Ahmad Fauzi',      'email' => 'promo@eventconnect.com',         'role' => 'staff', 'phone' => '081234567893'],
            ['name' => 'Diana Putri',      'email' => 'partner@eventconnect.com',       'role' => 'staff', 'phone' => '081234567894'],
            ['name' => 'Rizky Maulana',    'email' => 'budget@eventconnect.com',        'role' => 'staff', 'phone' => '081234567895'],
            ['name' => 'Eka Fitriani',     'email' => 'ops@eventconnect.com',           'role' => 'staff', 'phone' => '081234567896'],
            ['name' => 'Farhan Hidayat',   'email' => 'creative@eventconnect.com',      'role' => 'staff', 'phone' => '081234567897'],
            ['name' => 'Gina Marlina',     'email' => 'rundown@eventconnect.com',       'role' => 'staff', 'phone' => '081234567898'],
            ['name' => 'Hendra Wijaya',    'email' => 'talent@eventconnect.com',        'role' => 'staff', 'phone' => '081234567899'],
            ['name' => 'Indah Permata',    'email' => 'registration@eventconnect.com',  'role' => 'staff', 'phone' => '081234567900'],
            ['name' => 'Joko Susilo',      'email' => 'tech@eventconnect.com',          'role' => 'staff', 'phone' => '081234567901'],
            ['name' => 'Kartika Dewi',     'email' => 'doc@eventconnect.com',           'role' => 'staff', 'phone' => '081234567902'],
            ['name' => 'Luhut Pangaribuan', 'email' => 'lo@eventconnect.com',            'role' => 'staff', 'phone' => '081234567903'],
        ];

        $createdUsers = [];
        foreach ($usersData as $userData) {
            $createdUsers[] = User::create([
                'organization_id' => $orgId,
                'name'     => $userData['name'],
                'email'    => $userData['email'],
                'phone'    => $userData['phone'],
                'role'     => $userData['role'],
                'status'   => 'active',
                'password' => Hash::make('password123'),
            ]);
        }

        // Create dummy events
        $eventsData = [
            [
                'name' => 'Festival Budaya Nusantara 2026',
                'description' => 'Festival budaya tahunan yang menampilkan keberagaman seni dan budaya Indonesia dari Sabang sampai Merauke.',
                'location' => 'Lapangan Banteng, Jakarta Pusat',
                'start_date' => '2026-06-15',
                'end_date' => '2026-06-17',
                'start_time' => '08:00',
                'end_time' => '22:00',
                'status' => 'active',
                'budget' => 500000000,
                'category' => 'Cultural',
                'expected_participants' => 5000,
                'created_by' => $pm->id,
            ],
            [
                'name' => 'Tech Summit Indonesia 2026',
                'description' => 'Konferensi teknologi terbesar di Indonesia dengan pembicara dari perusahaan teknologi global.',
                'location' => 'Jakarta Convention Center',
                'start_date' => '2026-07-20',
                'end_date' => '2026-07-22',
                'start_time' => '09:00',
                'end_time' => '18:00',
                'status' => 'draft',
                'budget' => 750000000,
                'category' => 'Conference',
                'expected_participants' => 2000,
                'created_by' => $pm->id,
            ],
            [
                'name' => 'Gala Dinner Alumni Akbar',
                'description' => 'Malam gala dinner tahunan para alumni Universitas Darma Persada.',
                'location' => 'Hotel Grand Sahid Jaya, Jakarta',
                'start_date' => '2026-08-10',
                'end_date' => '2026-08-10',
                'start_time' => '18:00',
                'end_time' => '23:00',
                'status' => 'draft',
                'budget' => 200000000,
                'category' => 'Gala Dinner',
                'expected_participants' => 500,
                'created_by' => $pm->id,
            ],
            [
                'name' => 'Seminar Kewirausahaan Muda',
                'description' => 'Seminar inspiratif untuk para pengusaha muda Indonesia bersama mentor-mentor berpengalaman.',
                'location' => 'Auditorium Universitas Indonesia, Depok',
                'start_date' => '2026-05-05',
                'end_date' => '2026-05-05',
                'start_time' => '08:00',
                'end_time' => '17:00',
                'status' => 'completed',
                'budget' => 50000000,
                'category' => 'Seminar',
                'expected_participants' => 800,
                'created_by' => $pm->id,
            ],
            [
                'name' => 'Konser Amal Peduli Anak',
                'description' => 'Konser musik amal untuk penggalangan dana bagi anak-anak kurang mampu di Indonesia.',
                'location' => 'Istora Senayan, Jakarta',
                'start_date' => '2026-09-25',
                'end_date' => '2026-09-25',
                'start_time' => '16:00',
                'end_time' => '22:00',
                'status' => 'draft',
                'budget' => 300000000,
                'category' => 'Concert',
                'expected_participants' => 10000,
                'created_by' => $pm->id,
            ],
        ];

        $personnelRoles = ['Event Coordinator', 'Promotion Lead', 'Budget Manager', 'Operations Head', 'Creative Director', 'Talent Handler', 'Rundown PIC', 'Registration PIC'];

        foreach ($eventsData as $eventData) {
            $event = Event::create(array_merge($eventData, ['organization_id' => $orgId]));

            $syncData = [];
            foreach (array_slice($createdUsers, 0, 6) as $i => $user) {
                $syncData[$user->id] = [
                    'role_in_event' => $personnelRoles[$i] ?? 'Team Member',
                    'notes' => 'Ditugaskan ke ' . $event->name,
                ];
            }
            $syncData[$pm->id] = [
                'role_in_event' => 'Project Manager',
                'notes' => 'Penanggung jawab utama project',
            ];
            $event->personnel()->sync($syncData);
        }
    }
}
