<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Models\Event;
use App\Models\Inventory;
use App\Models\EventLogistic;
use App\Models\Task;
use App\Models\EventRundown;
use App\Models\EventBudgetAllocation;
use App\Models\EventExpense;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class TwoTenantsDummySeeder extends Seeder
{
    public function run(): void
    {
        // 0. Clean up existing data for these two tenants if they exist
        Organization::whereIn('slug', ['aurora-creative', 'sinergi-convex'])->get()->each(function($org) {
            $org->delete();
        });

        // 1. Seed PT Aurora Creative Planner
        $this->seedAurora();

        // 2. Seed CV Sinergi Karya Convex
        $this->seedSinergi();
    }

    private function seedAurora(): void
    {
        // 1. Create Organization
        $org = Organization::create([
            'name' => 'PT Aurora Creative Planner',
            'slug' => 'aurora-creative',
            'email' => 'contact@auroraplanner.com',
            'phone' => '02177889900',
            'address' => 'Gedung Menara Karya Lt. 12, Kuningan, Jakarta Selatan',
            'status' => 'active',
            'plan' => 'enterprise',
        ]);

        // 2. Create Users (1 Superadmin, 4 PM, 25 Staff = 30 Users)
        $users = [];

        // 2.1. Superadmin (Owner)
        $superadmin = User::create([
            'organization_id' => $org->id,
            'name' => 'Aurora Clarissa',
            'email' => 'owner@auroraplanner.com',
            'phone' => '081211112222',
            'role' => 'superadmin',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);
        $users[] = $superadmin;

        // 2.2. PMs (4)
        $pmData = [
            ['name' => 'Bima Sakti', 'email' => 'bima.pm@auroraplanner.com', 'phone' => '081222223333'],
            ['name' => 'Cinta Laura', 'email' => 'cinta.pm@auroraplanner.com', 'phone' => '081233334444'],
            ['name' => 'Dodi Mulyadi', 'email' => 'dodi.pm@auroraplanner.com', 'phone' => '081244445555'],
            ['name' => 'Elisa Putri', 'email' => 'elisa.pm@auroraplanner.com', 'phone' => '081255556666'],
        ];

        $pms = [];
        foreach ($pmData as $data) {
            $pm = User::create([
                'organization_id' => $org->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'role' => 'project_manager',
                'status' => 'active',
                'password' => Hash::make('password123'),
            ]);
            $pms[] = $pm;
            $users[] = $pm;
        }

        // 2.3. Staffs (25)
        $staffNames = [
            'Indri Hapsari', 'Jaka Tarub', 'Kevin Sanjaya', 'Larasati', 'Miko Angelo',
            'Nia Ramadhani', 'Oscar Lawalata', 'Poppy Bunga', 'Ryan Hidayat', 'Susi Susanti',
            'Teguh Prakoso', 'Ulfa Dwiyanti', 'Valen Tino', 'Wawan Setiawan', 'Yeni Wahid',
            'Zaki Rahmad', 'Aldo Saputra', 'Bella Saphira', 'Citra Kirana', 'Dani Ahmad',
            'Eva Celia', 'Ferdy Sambo', 'Gita Gutawa', 'Hari Mukti', 'Iwan Fals'
        ];

        $staffs = [];
        foreach ($staffNames as $index => $name) {
            $emailName = strtolower(str_replace(' ', '.', $name));
            $staff = User::create([
                'organization_id' => $org->id,
                'name' => $name,
                'email' => $emailName . '@auroraplanner.com',
                'phone' => '0878' . str_pad($index + 1, 8, '0', STR_PAD_LEFT),
                'role' => 'staff',
                'status' => 'active',
                'password' => Hash::make('password123'),
            ]);
            $staffs[] = $staff;
            $users[] = $staff;
        }

        // 3. Create Inventories
        $inventoriesData = [
            ['item_name' => 'Line Array Speaker 10000W', 'serial_number' => 'LA-10K-A1', 'total_quantity' => 4, 'available_quantity' => 4, 'ownership' => 'owned', 'default_rent_price' => null, 'status' => 'ready', 'notes' => 'Speaker konser utama.'],
            ['item_name' => 'LED Wall Screen P3.9', 'serial_number' => 'LW-P39-A2', 'total_quantity' => 3, 'available_quantity' => 3, 'ownership' => 'owned', 'default_rent_price' => null, 'status' => 'ready', 'notes' => 'LED display resolusi tinggi untuk backdrop panggung.'],
            ['item_name' => 'Stage Platform 12x8m', 'serial_number' => 'SP-128-A3', 'total_quantity' => 2, 'available_quantity' => 2, 'ownership' => 'owned', 'default_rent_price' => null, 'status' => 'ready', 'notes' => 'Rangka besi panggung modular.'],
            ['item_name' => 'Moving Head Spotlight', 'serial_number' => 'MH-SL-A4', 'total_quantity' => 24, 'available_quantity' => 24, 'ownership' => 'owned', 'default_rent_price' => null, 'status' => 'ready', 'notes' => 'Pencahayaan beam panggung konser.'],
            ['item_name' => 'Smoke Machine Concert', 'serial_number' => 'SM-CC-A5', 'total_quantity' => 4, 'available_quantity' => 4, 'ownership' => 'owned', 'default_rent_price' => null, 'status' => 'ready', 'notes' => 'Mesin asap efek panggung.'],
            ['item_name' => 'Wireless In-Ear Monitor', 'serial_number' => 'WI-EM-A6', 'total_quantity' => 12, 'available_quantity' => 12, 'ownership' => 'owned', 'default_rent_price' => null, 'status' => 'ready', 'notes' => 'Sennheiser G4 In-Ear system.'],
            ['item_name' => 'Genset Silent 150 kVA', 'serial_number' => 'GS-15-A7', 'total_quantity' => 3, 'available_quantity' => 3, 'ownership' => 'rented', 'default_rent_price' => 2000000.00, 'status' => 'ready', 'notes' => 'Sewa genset kapasitas besar PT Power.'],
            ['item_name' => 'Barricade Fence 100m', 'serial_number' => 'BF-10-A8', 'total_quantity' => 2, 'available_quantity' => 2, 'ownership' => 'owned', 'default_rent_price' => null, 'status' => 'ready', 'notes' => 'Pagar pengaman depan panggung.'],
            ['item_name' => 'Walkie Talkie Motorola', 'serial_number' => 'WT-MO-A9', 'total_quantity' => 60, 'available_quantity' => 60, 'ownership' => 'owned', 'default_rent_price' => null, 'status' => 'ready', 'notes' => 'Motorola GP338 UHF.'],
            ['item_name' => 'Sofa VIP Event', 'serial_number' => 'SF-VIP-A10', 'total_quantity' => 10, 'available_quantity' => 10, 'ownership' => 'rented', 'default_rent_price' => 250000.00, 'status' => 'ready', 'notes' => 'Sewa sofa tamu penting VIP CV Furnitur.'],
        ];

        $inventories = [];
        foreach ($inventoriesData as $data) {
            $inventories[] = Inventory::create(array_merge($data, ['organization_id' => $org->id]));
        }

        // 4. Create 6 Events
        $eventsData = [
            [
                'name' => 'Gala Launching Astra EV 2026',
                'description' => 'Malam peluncuran produk mobil listrik terbaru dari Astra dengan konsep futuristic dan live show.',
                'location' => 'Grand Ballroom Ritz Carlton Pacific Place, Jakarta',
                'start_date' => '2026-08-05', 'end_date' => '2026-08-05', 'start_time' => '18:00', 'end_time' => '22:00',
                'status' => 'active', 'budget' => 500000000.00, 'category' => 'Corporate', 'expected_participants' => 350,
                'pm_index' => 0, // Bima Sakti
            ],
            [
                'name' => 'Sunset Sounds Indie Festival 2026',
                'description' => 'Festival musik indie outdoor tahunan dengan penampilan band-band lokal ternama di kala senja.',
                'location' => 'Ecopark Ancol, Jakarta Utara',
                'start_date' => '2026-08-20', 'end_date' => '2026-08-20', 'start_time' => '15:00', 'end_time' => '23:00',
                'status' => 'active', 'budget' => 250000000.00, 'category' => 'Concert', 'expected_participants' => 1500,
                'pm_index' => 1, // Cinta Laura
            ],
            [
                'name' => 'Wedding of Chelsea & Glenn',
                'description' => 'Resepsi pernikahan eksklusif dengan tema floral forest garden.',
                'location' => 'Plataran Cilandak, Jakarta Selatan',
                'start_date' => '2026-09-05', 'end_date' => '2026-09-05', 'start_time' => '17:00', 'end_time' => '21:00',
                'status' => 'active', 'budget' => 180000000.00, 'category' => 'Social', 'expected_participants' => 400,
                'pm_index' => 2, // Dodi Mulyadi
            ],
            [
                'name' => 'Gamer Con Indonesia 2026',
                'description' => 'Konvensi dan kompetisi esport terbesar, menghadirkan developer game lokal dan internasional.',
                'location' => 'Hall A-B, Kartika Expo Center, Jakarta',
                'start_date' => '2026-10-10', 'end_date' => '2026-10-12', 'start_time' => '10:00', 'end_time' => '21:00',
                'status' => 'draft', 'budget' => 400000000.00, 'category' => 'Exhibition', 'expected_participants' => 4000,
                'pm_index' => 3, // Elisa Putri
            ],
            [
                'name' => 'Launching Brand Fashion Hijab Chic',
                'description' => 'Peragaan busana dan launching koleksi pakaian muslim modern musim panas.',
                'location' => 'Senayan City Main Atrium, Jakarta',
                'start_date' => '2026-06-15', 'end_date' => '2026-06-15', 'start_time' => '14:00', 'end_time' => '18:00',
                'status' => 'completed', 'budget' => 90000000.00, 'category' => 'Brand Activation', 'expected_participants' => 200,
                'pm_index' => 0, // Bima Sakti
            ],
            [
                'name' => 'Corporate Gathering PT Telkom',
                'description' => 'Malam kebersamaan karyawan PT Telkom dengan berbagai macam games, doorprize, dan performance.',
                'location' => 'Hotel Mulia Senayan, Jakarta',
                'start_date' => '2026-08-25', 'end_date' => '2026-08-25', 'start_time' => '18:00', 'end_time' => '22:00',
                'status' => 'active', 'budget' => 150000000.00, 'category' => 'Corporate', 'expected_participants' => 500,
                'pm_index' => 1, // Cinta Laura
            ]
        ];

        $this->seedCommonEventData($org, $users, $pms, $staffs, $inventories, $eventsData);
    }

    private function seedSinergi(): void
    {
        // 1. Create Organization
        $org = Organization::create([
            'name' => 'CV Sinergi Karya Convex',
            'slug' => 'sinergi-convex',
            'email' => 'info@sinergiconvex.com',
            'phone' => '02188992211',
            'address' => 'Rukan Grand Galaxy City Blok RRG No. 18, Bekasi',
            'status' => 'active',
            'plan' => 'enterprise',
        ]);

        // 2. Create Users (1 Superadmin, 4 PM, 25 Staff = 30 Users)
        $users = [];

        // 2.1. Superadmin (Owner)
        $superadmin = User::create([
            'organization_id' => $org->id,
            'name' => 'Hendra Pratama',
            'email' => 'owner@sinergiconvex.com',
            'phone' => '081311112222',
            'role' => 'superadmin',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);
        $users[] = $superadmin;

        // 2.2. PMs (4)
        $pmData = [
            ['name' => 'Agus Rahardjo', 'email' => 'agus.pm@sinergiconvex.com', 'phone' => '081322223333'],
            ['name' => 'Fitri Handayani', 'email' => 'fitri.pm@sinergiconvex.com', 'phone' => '081333334444'],
            ['name' => 'Galih Pratama', 'email' => 'galih.pm@sinergiconvex.com', 'phone' => '081344445555'],
            ['name' => 'Hilda Lestari', 'email' => 'hilda.pm@sinergiconvex.com', 'phone' => '081355556666'],
        ];

        $pms = [];
        foreach ($pmData as $data) {
            $pm = User::create([
                'organization_id' => $org->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'role' => 'project_manager',
                'status' => 'active',
                'password' => Hash::make('password123'),
            ]);
            $pms[] = $pm;
            $users[] = $pm;
        }

        // 2.3. Staffs (25)
        $staffNames = [
            'Irfan Bachdim', 'Julia Perez', 'Kiki Fatmala', 'Luthfi Hakim', 'Mega Utami',
            'Naufal Azhar', 'Olla Ramlan', 'Panji Petualang', 'Rina Nose', 'Setio Budi',
            'Tari Lestari', 'Ujang Ronda', 'Vera Kebaya', 'Wira Yudha', 'Yulia Rahman',
            'Zulfikar Ali', 'Aris Setiawan', 'Betty Nurhayati', 'Cecep Supriadi', 'Dina Mariana',
            'Eka Saputra', 'Fajar Alamsyah', 'Galuh Candra', 'Heru Kartiko', 'Indra Bekti'
        ];

        $staffs = [];
        foreach ($staffNames as $index => $name) {
            $emailName = strtolower(str_replace(' ', '.', $name));
            $staff = User::create([
                'organization_id' => $org->id,
                'name' => $name,
                'email' => $emailName . '@sinergiconvex.com',
                'phone' => '0819' . str_pad($index + 1, 8, '0', STR_PAD_LEFT),
                'role' => 'staff',
                'status' => 'active',
                'password' => Hash::make('password123'),
            ]);
            $staffs[] = $staff;
            $users[] = $staff;
        }

        // 3. Create Inventories
        $inventoriesData = [
            ['item_name' => 'LED Projector 7000 Lumens', 'serial_number' => 'LP-7K-B1', 'total_quantity' => 6, 'available_quantity' => 6, 'ownership' => 'owned', 'default_rent_price' => null, 'status' => 'ready', 'notes' => 'Proyektor terang untuk seminar siang.'],
            ['item_name' => 'Wireless Interpreter System', 'serial_number' => 'WI-IS-B2', 'total_quantity' => 4, 'available_quantity' => 4, 'ownership' => 'owned', 'default_rent_price' => null, 'status' => 'ready', 'notes' => 'Set alat penerjemah bahasa 50 receiver.'],
            ['item_name' => 'Presentation Clicker Pointer', 'serial_number' => 'PC-PT-B3', 'total_quantity' => 10, 'available_quantity' => 10, 'ownership' => 'owned', 'default_rent_price' => null, 'status' => 'ready', 'notes' => 'Logitech Spotlight clicker.'],
            ['item_name' => 'Registration Desk Counter', 'serial_number' => 'RD-CT-B4', 'total_quantity' => 8, 'available_quantity' => 8, 'ownership' => 'owned', 'default_rent_price' => null, 'status' => 'ready', 'notes' => 'Meja loket registrasi portable.'],
            ['item_name' => 'Flipchart Stand & Board', 'serial_number' => 'FC-SB-B5', 'total_quantity' => 10, 'available_quantity' => 10, 'ownership' => 'owned', 'default_rent_price' => null, 'status' => 'ready', 'notes' => 'Papan tulis kecil dengan penyangga.'],
            ['item_name' => 'Clip-On Mic Sennheiser', 'serial_number' => 'CO-MS-B6', 'total_quantity' => 12, 'available_quantity' => 12, 'ownership' => 'owned', 'default_rent_price' => null, 'status' => 'ready', 'notes' => 'Microphone clip-on presentasi.'],
            ['item_name' => 'Genset Silent 80 kVA', 'serial_number' => 'GS-08-B7', 'total_quantity' => 4, 'available_quantity' => 4, 'ownership' => 'rented', 'default_rent_price' => 1200000.00, 'status' => 'ready', 'notes' => 'Sewa genset untuk cadangan listrik AC.'],
            ['item_name' => 'AC Standing 5 PK', 'serial_number' => 'AC-ST-B8', 'total_quantity' => 12, 'available_quantity' => 12, 'ownership' => 'rented', 'default_rent_price' => 450000.00, 'status' => 'ready', 'notes' => 'Sewa pendingin ruangan CV Dingin.'],
            ['item_name' => 'HT Baofeng UV-82', 'serial_number' => 'HT-BF-B9', 'total_quantity' => 50, 'available_quantity' => 50, 'ownership' => 'owned', 'default_rent_price' => null, 'status' => 'ready', 'notes' => 'Handy Talky panitia.'],
            ['item_name' => 'Backdrop Frame 4x3m', 'serial_number' => 'BF-43-B10', 'total_quantity' => 6, 'available_quantity' => 6, 'ownership' => 'owned', 'default_rent_price' => null, 'status' => 'ready', 'notes' => 'Frame besi banner backdrop.'],
        ];

        $inventories = [];
        foreach ($inventoriesData as $data) {
            $inventories[] = Inventory::create(array_merge($data, ['organization_id' => $org->id]));
        }

        // 4. Create 6 Events
        $eventsData = [
            [
                'name' => 'Indonesia Healthcare Summit 2026',
                'description' => 'Konferensi medis nasional membahas kebijakan kesehatan dan digitalisasi layanan medis pasca-pandemi.',
                'location' => 'Jakarta Convention Center (JCC), Senayan',
                'start_date' => '2026-08-15', 'end_date' => '2026-08-16', 'start_time' => '08:00', 'end_time' => '17:00',
                'status' => 'active', 'budget' => 120000000.00, 'category' => 'Seminar', 'expected_participants' => 600,
                'pm_index' => 0, // Agus Rahardjo
            ],
            [
                'name' => 'Jakarta International Education Fair 2026',
                'description' => 'Pameran pendidikan internasional yang diikuti oleh 80+ universitas luar negeri terkemuka.',
                'location' => 'Balai Kartini Exhibition Hall, Jakarta',
                'start_date' => '2026-09-10', 'end_date' => '2026-09-11', 'start_time' => '10:00', 'end_time' => '18:00',
                'status' => 'active', 'budget' => 280000000.00, 'category' => 'Exhibition', 'expected_participants' => 2500,
                'pm_index' => 1, // Fitri Handayani
            ],
            [
                'name' => 'Annual Business Conference 2026',
                'description' => 'Konferensi bisnis tahunan menghadirkan pakar ekonomi dan pemimpin perusahaan global.',
                'location' => 'Shangri-La Hotel Ballroom, Jakarta',
                'start_date' => '2026-08-30', 'end_date' => '2026-08-30', 'start_time' => '09:00', 'end_time' => '17:00',
                'status' => 'active', 'budget' => 150000000.00, 'category' => 'Conference', 'expected_participants' => 450,
                'pm_index' => 2, // Galih Pratama
            ],
            [
                'name' => 'National Technology Forum 2026',
                'description' => 'Forum diskusi regulasi pemanfaatan AI (Kecerdasan Buatan) di pemerintahan dan industri.',
                'location' => 'Auditorium Kementerian Kominfo, Jakarta',
                'start_date' => '2026-10-05', 'end_date' => '2026-10-05', 'start_time' => '09:00', 'end_time' => '16:00',
                'status' => 'draft', 'budget' => 80000000.00, 'category' => 'Conference', 'expected_participants' => 300,
                'pm_index' => 3, // Hilda Lestari
            ],
            [
                'name' => 'Seminar Kepemimpinan Mahasiswa Nasional',
                'description' => 'Seminar pembekalan kepemimpinan bagi ketua BEM seluruh universitas di Indonesia.',
                'location' => 'Graha Sabha Pramana UGM, Yogyakarta',
                'start_date' => '2026-06-10', 'end_date' => '2026-06-10', 'start_time' => '08:00', 'end_time' => '15:00',
                'status' => 'completed', 'budget' => 35000000.00, 'category' => 'Seminar', 'expected_participants' => 800,
                'pm_index' => 0, // Agus Rahardjo
            ],
            [
                'name' => 'Symposium of Cardiology 2026',
                'description' => 'Simposium spesialis jantung Indonesia untuk pembaruan teknik bedah toraks kardiak.',
                'location' => 'Hotel DoubleTree by Hilton, Cikini, Jakarta',
                'start_date' => '2026-09-20', 'end_date' => '2026-09-21', 'start_time' => '08:00', 'end_time' => '16:30',
                'status' => 'active', 'budget' => 95000000.00, 'category' => 'Seminar', 'expected_participants' => 250,
                'pm_index' => 1, // Fitri Handayani
            ]
        ];

        $this->seedCommonEventData($org, $users, $pms, $staffs, $inventories, $eventsData);
    }

    private function seedCommonEventData(Organization $org, array $users, array $pms, array $staffs, array $inventories, array $eventsData): void
    {
        $eventPersonnelRoles = [
            'Event Coordinator',
            'Stage Manager',
            'Liaison Officer (LO)',
            'Logistic Officer',
            'Registration PIC',
            'Media & Documentation PIC',
            'Sound Engineer',
            'Sponsor Coordinator'
        ];

        foreach ($eventsData as $index => $eData) {
            $assignedPm = $pms[$eData['pm_index']];

            $event = Event::create([
                'organization_id' => $org->id,
                'name' => $eData['name'],
                'description' => $eData['description'],
                'location' => $eData['location'],
                'start_date' => $eData['start_date'],
                'end_date' => $eData['end_date'],
                'start_time' => $eData['start_time'],
                'end_time' => $eData['end_time'],
                'status' => $eData['status'],
                'budget' => $eData['budget'],
                'category' => $eData['category'],
                'expected_participants' => $eData['expected_participants'],
                'created_by' => $assignedPm->id,
            ]);

            // Assign personnel (1 PM + 7 Staff)
            $syncData = [
                $assignedPm->id => [
                    'role_in_event' => 'Project Manager',
                    'notes' => 'Penanggung jawab utama project',
                ]
            ];

            // Pick 7 unique staffs for this event
            $eventStaffs = array_slice($staffs, ($index * 5) % count($staffs), 7);
            foreach ($eventStaffs as $sKey => $staff) {
                $roleInEvent = $eventPersonnelRoles[$sKey] ?? 'Team Member';
                $syncData[$staff->id] = [
                    'role_in_event' => $roleInEvent,
                    'notes' => 'Ditugaskan ke divisi ' . explode(' ', $roleInEvent)[0],
                ];
            }
            $event->personnel()->sync($syncData);

            // 5. Seed Event Logistics
            $logisticPic = $eventStaffs[3] ?? $eventStaffs[0];
            $borrowedDate = Carbon::parse($event->start_date)->subDay()->toDateString();
            $returnedDate = $event->status === 'completed' ? $event->end_date->toDateString() : null;
            $returnStatus = $event->status === 'completed' ? 'complete' : null;

            // Pick inventories to borrow depending on categories
            $isConcertOrCreative = in_array($event->category, ['Concert', 'Brand Activation', 'Social', 'Corporate']);
            
            $logisticsToCreate = [];
            if ($isConcertOrCreative) {
                $logisticsToCreate = [
                    ['inventory_id' => $inventories[0]->id, 'quantity' => $event->category === 'Concert' ? 2 : 1, 'rent_cost' => 0.00, 'notes' => 'Cek kelayakan kabel & mixer.'],
                    ['inventory_id' => $inventories[1]->id, 'quantity' => 6, 'rent_cost' => 0.00, 'notes' => 'Pasang baterai cadangan.'],
                    ['inventory_id' => $inventories[8]->id, 'quantity' => 15, 'rent_cost' => 0.00, 'notes' => 'Bagi HT saat briefing.'],
                    ['inventory_id' => $inventories[6]->id, 'quantity' => 1, 'rent_cost' => $inventories[6]->default_rent_price, 'notes' => 'Backup listrik panggung.']
                ];
            } else { // Seminar, Conference, Exhibition
                $logisticsToCreate = [
                    ['inventory_id' => $inventories[0]->id, 'quantity' => 1, 'rent_cost' => 0.00, 'notes' => 'Setup proyektor dan mic.'],
                    ['inventory_id' => $inventories[1]->id, 'quantity' => 8, 'rent_cost' => 0.00, 'notes' => 'Gunakan clip-on jika ada.'],
                    ['inventory_id' => $inventories[8]->id, 'quantity' => 10, 'rent_cost' => 0.00, 'notes' => 'Komunikasi tim registrasi.'],
                    ['inventory_id' => $inventories[6]->id, 'quantity' => 1, 'rent_cost' => $inventories[6]->default_rent_price, 'notes' => 'Backup listrik utama AC.']
                ];
            }

            foreach ($logisticsToCreate as $logData) {
                EventLogistic::create([
                    'event_id' => $event->id,
                    'inventory_id' => $logData['inventory_id'],
                    'user_id' => $logisticPic->id,
                    'quantity' => $logData['quantity'],
                    'rent_cost' => $logData['rent_cost'],
                    'borrowed_at' => $borrowedDate,
                    'returned_at' => $returnedDate,
                    'return_status' => $returnStatus,
                    'notes' => $logData['notes']
                ]);

                // Decrement stock in database if logistics are active (not yet returned)
                if (is_null($returnedDate)) {
                    $inv = Inventory::find($logData['inventory_id']);
                    $inv->decrement('available_quantity', $logData['quantity']);
                }
            }

            // 6. Seed Tasks & Workflows
            $taskPhases = [
                [
                    'title' => 'Fase 1: Inisiasi & Perencanaan',
                    'desc' => 'Tahapan awal penyusunan konsep, perizinan, dan anggaran.',
                    'days_offset' => -30,
                    'subtasks' => [
                        ['title' => 'Penyusunan konsep acara & RAB final', 'desc' => 'Membuat konsep kreatif, rundown garis besar, dan rencana anggaran biaya.', 'role' => 'Event Coordinator', 'priority' => 'high'],
                        ['title' => 'Pengurusan surat izin keramaian & keamanan', 'desc' => 'Mengajukan izin resmi ke Polsek/Polres, Dishub, dan pengelola lokasi.', 'role' => 'Liaison Officer (LO)', 'priority' => 'urgent'],
                        ['title' => 'Penyusunan & pengiriman proposal sponsorship', 'desc' => 'Menghubungi calon sponsor potensial dan menindaklanjuti proposal.', 'role' => 'Sponsor Coordinator', 'priority' => 'medium'],
                    ]
                ],
                [
                    'title' => 'Fase 2: Persiapan Teknis & Vendor',
                    'desc' => 'Tahapan pemesanan venue, panggung, dekorasi, talent, dan perlengkapan.',
                    'days_offset' => -15,
                    'subtasks' => [
                        ['title' => 'Finalisasi kontrak talent & pembicara VIP', 'desc' => 'Menandatangani MoU dengan pengisi acara, MC, dan pembicara.', 'role' => 'Liaison Officer (LO)', 'priority' => 'high'],
                        ['title' => 'Booking venue & pembayaran uang muka (DP)', 'desc' => 'Mengamankan lokasi acara dan melunasi DP sewa gedung/tempat.', 'role' => 'Project Manager', 'priority' => 'urgent'],
                        ['title' => 'Finalisasi sewa alat & logistik tambahan', 'desc' => 'Memastikan semua alat dari inventaris internal siap dan menyewa genset/AC.', 'role' => 'Logistic Officer', 'priority' => 'medium'],
                    ]
                ],
                [
                    'title' => 'Fase 3: Pelaksanaan & Hari H',
                    'desc' => 'Fase krusial selama eksekusi di lapangan.',
                    'days_offset' => 0,
                    'subtasks' => [
                        ['title' => 'Briefing panitia & koordinasi pagi hari H', 'desc' => 'Briefing akhir seluruh divisi sebelum gate dibuka untuk memastikan koordinasi.', 'role' => 'Project Manager', 'priority' => 'high'],
                        ['title' => 'Manajemen registrasi & check-in peserta', 'desc' => 'Melayani registrasi onsite, penukaran tiket, dan pembagian kit peserta.', 'role' => 'Registration PIC', 'priority' => 'medium'],
                        ['title' => 'Stage management & pengawasan rundown', 'desc' => 'Mengontrol jalannya acara di atas panggung sesuai dengan jadwal waktu.', 'role' => 'Stage Manager', 'priority' => 'urgent'],
                    ]
                ],
                [
                    'title' => 'Fase 4: Pasca-Event & Evaluasi',
                    'desc' => 'Tahap pembongkaran, pelaporan, dan penutupan project.',
                    'days_offset' => 3,
                    'subtasks' => [
                        ['title' => 'Bongkar muat (load-out) & pengembalian logistik', 'desc' => 'Mengembalikan inventaris ke gudang dan memastikan alat sewa diambil vendor.', 'role' => 'Logistic Officer', 'priority' => 'high'],
                        ['title' => 'Penyusunan Laporan Pertanggungjawaban (LPJ) Keuangan', 'desc' => 'Mengumpulkan kuitansi pengeluaran dan menyusun laporan laba rugi event.', 'role' => 'Event Coordinator', 'priority' => 'medium'],
                        ['title' => 'Rapat evaluasi akhir & pembubaran panitia', 'desc' => 'Membahas performa acara, feedback peserta, dan memberikan apresiasi tim.', 'role' => 'Project Manager', 'priority' => 'low'],
                    ]
                ],
            ];

            foreach ($taskPhases as $pIdx => $phase) {
                // Determine phase status based on event status
                $phaseStatus = 'pending';
                if ($event->status === 'completed') {
                    $phaseStatus = 'completed';
                } elseif ($event->status === 'active') {
                    if ($pIdx === 0) $phaseStatus = 'completed';
                    elseif ($pIdx === 1) $phaseStatus = 'completed';
                    elseif ($pIdx === 2) $phaseStatus = 'in_progress';
                }

                $parentTask = Task::create([
                    'title' => $phase['title'],
                    'description' => $phase['desc'],
                    'event_id' => $event->id,
                    'assigned_to' => $assignedPm->id,
                    'created_by' => $assignedPm->id,
                    'parent_task_id' => null,
                    'priority' => 'medium',
                    'status' => $phaseStatus,
                    'due_date' => Carbon::parse($event->start_date)->addDays($phase['days_offset'])->toDateString(),
                    'category' => 'Management',
                    'order' => $pIdx,
                    'completed_at' => $phaseStatus === 'completed' ? Carbon::parse($event->start_date)->addDays($phase['days_offset']) : null,
                ]);

                foreach ($phase['subtasks'] as $sIdx => $sTask) {
                    $assigneeId = $assignedPm->id;
                    foreach ($syncData as $uId => $pData) {
                        if ($pData['role_in_event'] === $sTask['role']) {
                            $assigneeId = $uId;
                            break;
                        }
                    }

                    $subtaskStatus = 'pending';
                    if ($event->status === 'completed') {
                        $subtaskStatus = 'completed';
                    } elseif ($event->status === 'active') {
                        if ($pIdx < 2) $subtaskStatus = 'completed';
                        elseif ($pIdx === 2) {
                            $subtaskStatus = $sIdx === 0 ? 'completed' : 'in_progress';
                        }
                    }

                    Task::create([
                        'title' => $sTask['title'],
                        'description' => $sTask['desc'],
                        'event_id' => $event->id,
                        'assigned_to' => $assigneeId,
                        'created_by' => $assignedPm->id,
                        'parent_task_id' => $parentTask->id,
                        'priority' => $sTask['priority'],
                        'status' => $subtaskStatus,
                        'due_date' => Carbon::parse($event->start_date)->addDays($phase['days_offset'])->toDateString(),
                        'category' => explode(' ', $sTask['role'])[0],
                        'order' => $sIdx,
                        'completed_at' => $subtaskStatus === 'completed' ? Carbon::parse($event->start_date)->addDays($phase['days_offset']) : null,
                    ]);
                }
            }

            // 7. Seed Rundown / Timeline (6-8 items)
            $rundownItems = [];
            if ($isConcertOrCreative) {
                $rundownItems = [
                    ['time_s' => '13:00', 'time_e' => '14:30', 'title' => 'Briefing Panitia & Sound Check Konser', 'cat' => 'technical', 'pic_role' => 'Sound Engineer', 'loc' => 'Main Stage'],
                    ['time_s' => '14:30', 'time_e' => '15:30', 'title' => 'Registrasi Ulang & Penukaran Gelang Tiket', 'cat' => 'registration', 'pic_role' => 'Registration PIC', 'loc' => 'Ticket Booth'],
                    ['time_s' => '15:30', 'time_e' => '16:00', 'title' => 'Open Gate & Background Music', 'cat' => 'technical', 'pic_role' => 'Stage Manager', 'loc' => 'Venue Area'],
                    ['time_s' => '16:00', 'time_e' => '18:00', 'title' => 'Live Performance Sesi Sore (Band Lokal)', 'cat' => 'talent', 'pic_role' => 'Stage Manager', 'loc' => 'Main Stage'],
                    ['time_s' => '18:00', 'time_e' => '19:00', 'title' => 'Break Maghrib & Layanan Tenant Makanan', 'cat' => 'logistics', 'pic_role' => 'Event Coordinator', 'loc' => 'Food Court Area'],
                    ['time_s' => '19:00', 'time_e' => '21:30', 'title' => 'Performance Artis Utama & Penutupan', 'cat' => 'talent', 'pic_role' => 'Liaison Officer (LO)', 'loc' => 'Main Stage'],
                    ['time_s' => '21:30', 'time_e' => '22:00', 'title' => 'Closing Ceremony & Kembang Api', 'cat' => 'ceremony', 'pic_role' => 'Project Manager', 'loc' => 'Main Stage'],
                    ['time_s' => '22:00', 'time_e' => '23:30', 'title' => 'Load Out Logistik & Pengosongan Area', 'cat' => 'logistics', 'pic_role' => 'Logistic Officer', 'loc' => 'Main Stage'],
                ];
            } else { // Seminar, Conference, Exhibition
                $rundownItems = [
                    ['time_s' => '08:00', 'time_e' => '09:00', 'title' => 'Registrasi Ulang Peserta & Coffee Break', 'cat' => 'registration', 'pic_role' => 'Registration PIC', 'loc' => 'Lobby Utama'],
                    ['time_s' => '09:00', 'time_e' => '09:30', 'title' => 'Opening Ceremony & Keynote Speech Utama', 'cat' => 'ceremony', 'pic_role' => 'Project Manager', 'loc' => 'Main Hall'],
                    ['time_s' => '09:30', 'time_e' => '11:45', 'title' => 'Sesi Panelis & Diskusi Tanya Jawab (Q&A)', 'cat' => 'talent', 'pic_role' => 'Stage Manager', 'loc' => 'Main Hall Stage'],
                    ['time_s' => '11:45', 'time_e' => '13:00', 'title' => 'Istirahat, Sholat, & Makan Siang (Ishoma)', 'cat' => 'logistics', 'pic_role' => 'Event Coordinator', 'loc' => 'Dining Area'],
                    ['time_s' => '13:00', 'time_e' => '15:30', 'title' => 'Sesi Paralel (Workshop & Networking)', 'cat' => 'talent', 'pic_role' => 'Liaison Officer (LO)', 'loc' => 'Classrooms'],
                    ['time_s' => '15:30', 'time_e' => '16:00', 'title' => 'Rapat Pleno, Pembagian Sertifikat, & Closing', 'cat' => 'ceremony', 'pic_role' => 'Event Coordinator', 'loc' => 'Main Hall'],
                ];
            }

            foreach ($rundownItems as $rIdx => $rItem) {
                $picId = $assignedPm->id;
                foreach ($syncData as $uId => $pData) {
                    if ($pData['role_in_event'] === $rItem['pic_role']) {
                        $picId = $uId;
                        break;
                    }
                }

                $rundownStatus = 'pending';
                if ($event->status === 'completed') {
                    $rundownStatus = 'completed';
                } elseif ($event->status === 'active' && $rIdx < 2) {
                    $rundownStatus = 'completed';
                }

                $startDateTime = Carbon::parse($event->start_date->toDateString() . ' ' . $rItem['time_s']);
                $endDateTime = Carbon::parse($event->start_date->toDateString() . ' ' . $rItem['time_e']);
                $duration = $startDateTime->diffInMinutes($endDateTime);

                EventRundown::create([
                    'event_id' => $event->id,
                    'event_date' => $event->start_date,
                    'title' => $rItem['title'],
                    'description' => 'Jadwal rundown ' . $rItem['title'] . ' untuk event ' . $event->name,
                    'category' => $rItem['cat'],
                    'start_time' => $rItem['time_s'],
                    'end_time' => $rItem['time_e'],
                    'duration_minutes' => $duration,
                    'status' => $rundownStatus,
                    'pic_id' => $picId,
                    'location_note' => $rItem['loc'],
                    'notes' => 'Ikuti petunjuk panggung secara disiplin.',
                    'order_number' => $rIdx,
                    'started_at' => $rundownStatus === 'completed' ? $startDateTime : null,
                    'ended_at' => $rundownStatus === 'completed' ? $endDateTime : null,
                    'delay_minutes' => 0,
                    'created_by' => $assignedPm->id,
                ]);
            }

            // 8. Seed Budget Allocations & Expenses
            if ($event->status !== 'draft') {
                EventBudgetAllocation::create([
                    'event_id' => $event->id,
                    'category' => 'Production & Venue',
                    'allocated_amount' => $event->budget * 0.60,
                    'notes' => 'Sewa ballroom, sound lighting, dekorasi, stage.'
                ]);
                EventBudgetAllocation::create([
                    'event_id' => $event->id,
                    'category' => 'Catering & Logistics',
                    'allocated_amount' => $event->budget * 0.25,
                    'notes' => 'Konsumsi peserta dan panitia.'
                ]);
                EventBudgetAllocation::create([
                    'event_id' => $event->id,
                    'category' => 'Marketing & Permits',
                    'allocated_amount' => $event->budget * 0.15,
                    'notes' => 'Izin keramaian dan promosi.'
                ]);

                EventExpense::create([
                    'event_id' => $event->id,
                    'user_id' => $assignedPm->id,
                    'category' => 'Production & Venue',
                    'title' => 'DP Sewa Venue Utama',
                    'amount' => $event->budget * 0.30,
                    'vendor_name' => 'Management Venue / Hotel',
                    'payment_method' => 'transfer',
                    'payment_status' => 'paid',
                    'spent_at' => Carbon::parse($event->start_date)->subDays(20)->toDateString(),
                    'notes' => 'Pembayaran lunas uang muka.'
                ]);
            }
        }
    }
}
