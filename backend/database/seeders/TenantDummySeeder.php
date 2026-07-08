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

class TenantDummySeeder extends Seeder
{
    public function run(): void
    {
        // 0. Clean up existing data for this tenant if it exists (allows re-running the seeder cleanly)
        $existingOrg = Organization::where('slug', 'mahakarya-event')->first();
        if ($existingOrg) {
            $existingOrg->delete();
        }

        // 1. Create Tenant (Organization)
        $org = Organization::create([
            'name' => 'PT Mahakarya Event Nusantara',
            'slug' => 'mahakarya-event',
            'email' => 'info@mahakaryaevent.com',
            'phone' => '02188997766',
            'address' => 'Jl. Jenderal Sudirman No. 45, Kav. 21, Jakarta Selatan',
            'status' => 'active',
            'plan' => 'enterprise', // Set enterprise to support 30+ users
        ]);

        // 2. Create 30 Users with Random Roles
        // We will create 1 Superadmin, 4 Project Managers, and 25 Staff members
        $users = [];

        // 2.1. Superadmin (Owner)
        $superadmin = User::create([
            'organization_id' => $org->id,
            'name' => 'Rizky Fadhillah',
            'email' => 'owner@mahakaryaevent.com',
            'phone' => '081299887766',
            'role' => 'superadmin',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);
        $users[] = $superadmin;

        // 2.2. Project Managers (4)
        $pmData = [
            ['name' => 'Budi Utomo', 'email' => 'budi.pm@mahakaryaevent.com', 'phone' => '081311223344'],
            ['name' => 'Siti Rahmawati', 'email' => 'siti.pm@mahakaryaevent.com', 'phone' => '081322334455'],
            ['name' => 'Dedi Hermawan', 'email' => 'dedi.pm@mahakaryaevent.com', 'phone' => '081333445566'],
            ['name' => 'Amanda Lestari', 'email' => 'amanda.pm@mahakaryaevent.com', 'phone' => '081344556677'],
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

        // 2.3. Staff (25)
        $staffNames = [
            'Adi Nugroho', 'Bambang Susilo', 'Candra Wijaya', 'Dewi Sartika', 'Eko Prasetyo',
            'Fitriani', 'Guntur Wibowo', 'Hendra Setiawan', 'Indah Cahyani', 'Joko Purwanto',
            'Kartika Sari', 'Lukman Hakim', 'Megawati', 'Novianto', 'Oki Rahardjo',
            'Pratiwi', 'Qori Aina', 'Rudi Hartono', 'Slamet Riyadi', 'Tri Wahyuni',
            'Utami', 'Vicky Prasetyo', 'Wahyu Hidayat', 'Yayan Ruhiyan', 'Zainal Abidin'
        ];

        $staffs = [];
        foreach ($staffNames as $index => $name) {
            $emailName = strtolower(str_replace(' ', '.', $name));
            $staff = User::create([
                'organization_id' => $org->id,
                'name' => $name,
                'email' => $emailName . '@mahakaryaevent.com',
                'phone' => '0857' . str_pad($index + 1, 8, '0', STR_PAD_LEFT),
                'role' => 'staff',
                'status' => 'active',
                'password' => Hash::make('password123'),
            ]);
            $staffs[] = $staff;
            $users[] = $staff;
        }

        // 3. Create Inventory Items
        $inventoriesData = [
            [
                'item_name' => 'Sound System 5000W',
                'serial_number' => 'SS-5K-001',
                'total_quantity' => 5,
                'available_quantity' => 5,
                'ownership' => 'owned',
                'default_rent_price' => null,
                'status' => 'ready',
                'notes' => 'Paket sound system aktif lengkap dengan mixer 16 channel.'
            ],
            [
                'item_name' => 'Wireless Microphone Shure',
                'serial_number' => 'WM-SH-002',
                'total_quantity' => 30, // Increased from 15
                'available_quantity' => 30, // Increased from 15
                'ownership' => 'owned',
                'default_rent_price' => null,
                'status' => 'ready',
                'notes' => 'Shure SVX24/PG58 UHF Wireless System.'
            ],
            [
                'item_name' => 'LED Projector 5000 Lumens',
                'serial_number' => 'LP-50-003',
                'total_quantity' => 5,
                'available_quantity' => 5,
                'ownership' => 'owned',
                'default_rent_price' => null,
                'status' => 'ready',
                'notes' => 'Epson EB-2250U Full HD Projector.'
            ],
            [
                'item_name' => 'Stage Rigging 8x6m',
                'serial_number' => 'SR-86-004',
                'total_quantity' => 2,
                'available_quantity' => 2,
                'ownership' => 'owned',
                'default_rent_price' => null,
                'status' => 'ready',
                'notes' => 'Rangka panggung aluminium tinggi 1.5m.'
            ],
            [
                'item_name' => 'Lighting Moving Head',
                'serial_number' => 'LM-MH-005',
                'total_quantity' => 16,
                'available_quantity' => 16,
                'ownership' => 'owned',
                'default_rent_price' => null,
                'status' => 'ready',
                'notes' => 'Moving Head Beam 230W.'
            ],
            [
                'item_name' => 'Kursi Lipat Futura',
                'serial_number' => 'KL-FU-006',
                'total_quantity' => 300,
                'available_quantity' => 300,
                'ownership' => 'owned',
                'default_rent_price' => null,
                'status' => 'ready',
                'notes' => 'Kursi futura merah standar.'
            ],
            [
                'item_name' => 'Genset 100 kVA',
                'serial_number' => 'GS-10-007',
                'total_quantity' => 5, // Increased from 3
                'available_quantity' => 5, // Increased from 3
                'ownership' => 'rented',
                'default_rent_price' => 1500000.00,
                'status' => 'ready',
                'notes' => 'Genset silent sewa dari PT Sumber Energi.'
            ],
            [
                'item_name' => 'AC Portable 5 PK',
                'serial_number' => 'AC-PO-008',
                'total_quantity' => 8,
                'available_quantity' => 8,
                'ownership' => 'rented',
                'default_rent_price' => 500000.00,
                'status' => 'ready',
                'notes' => 'AC standing sewa dari CV Dingin Jaya.'
            ],
            [
                'item_name' => 'Handy Talky (HT) Baofeng',
                'serial_number' => 'HT-BF-009',
                'total_quantity' => 80, // Increased from 40
                'available_quantity' => 80, // Increased from 40
                'ownership' => 'owned',
                'default_rent_price' => null,
                'status' => 'ready',
                'notes' => 'Baofeng UV-5R dual band UHF/VHF.'
            ],
            [
                'item_name' => 'Backdrop Frame 3x4m',
                'serial_number' => 'BF-34-010',
                'total_quantity' => 6,
                'available_quantity' => 6,
                'ownership' => 'owned',
                'default_rent_price' => null,
                'status' => 'ready',
                'notes' => 'Frame besi portable knock-down.'
            ],
        ];

        $inventories = [];
        foreach ($inventoriesData as $data) {
            $inventories[] = Inventory::create(array_merge($data, ['organization_id' => $org->id]));
        }

        // 4. Create 5 Events
        $eventsData = [
            [
                'name' => 'Mandiri Fun Run 2026',
                'description' => 'Event lari santai 5K dan 10K bersama Bank Mandiri untuk meningkatkan pola hidup sehat masyarakat Jakarta.',
                'location' => 'Lapangan Parkir Timur Senayan, Jakarta',
                'start_date' => '2026-07-15',
                'end_date' => '2026-07-15',
                'start_time' => '05:00',
                'end_time' => '12:00',
                'status' => 'active',
                'budget' => 150000000.00,
                'category' => 'Sports',
                'expected_participants' => 1200,
                'pm_index' => 0, // Budi Utomo
            ],
            [
                'name' => 'National Seminar: Gen-Z Career Path 2026',
                'description' => 'Seminar nasional membahas peluang karir di industri kreatif dan teknologi bagi generasi Z.',
                'location' => 'Auditorium Perpustakaan Nasional, Jakarta',
                'start_date' => '2026-07-22',
                'end_date' => '2026-07-22',
                'start_time' => '09:00',
                'end_time' => '16:00',
                'status' => 'active',
                'budget' => 45000000.00,
                'category' => 'Seminar',
                'expected_participants' => 500,
                'pm_index' => 1, // Siti Rahmawati
            ],
            [
                'name' => 'Pameran UMKM Jakarta Kreatif 2026',
                'description' => 'Pameran produk unggulan UMKM binaan Pemprov DKI Jakarta selama 3 hari berturut-turut.',
                'location' => 'Hall B, Jakarta Convention Center',
                'start_date' => '2026-08-10',
                'end_date' => '2026-08-12',
                'start_time' => '10:00',
                'end_time' => '21:00',
                'status' => 'draft',
                'budget' => 300000000.00,
                'category' => 'Exhibition',
                'expected_participants' => 3000,
                'pm_index' => 2, // Dedi Hermawan
            ],
            [
                'name' => 'Gala Dinner & Corporate Anniversary PT Antam',
                'description' => 'Malam perayaan hari ulang tahun PT Antam Tbk yang dihadiri oleh jajaran direksi dan tamu kehormatan.',
                'location' => 'Grand Ballroom Hotel Mulia, Jakarta',
                'start_date' => '2026-07-30',
                'end_date' => '2026-07-30',
                'start_time' => '18:00',
                'end_time' => '22:00',
                'status' => 'active',
                'budget' => 250000000.00,
                'category' => 'Gala Dinner',
                'expected_participants' => 400,
                'pm_index' => 3, // Amanda Lestari
            ],
            [
                'name' => 'Konser Musik Akustik Senja 2026',
                'description' => 'Konser musik indie akustik outdoor di sore hari menampilkan beberapa musisi lokal terkemuka.',
                'location' => 'Amphitheater Taman Ismail Marzuki, Jakarta',
                'start_date' => '2026-06-20', // Past date relative to June 30, 2026
                'end_date' => '2026-06-20',
                'start_time' => '16:00',
                'end_time' => '22:00',
                'status' => 'completed',
                'budget' => 80000000.00,
                'category' => 'Concert',
                'expected_participants' => 800,
                'pm_index' => 0, // Budi Utomo
            ],
        ];

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

            // Pick 7 unique staffs for this event to simulate realistic team assignment
            $eventStaffs = array_slice($staffs, ($index * 5) % count($staffs), 7);
            foreach ($eventStaffs as $sKey => $staff) {
                $roleInEvent = $eventPersonnelRoles[$sKey] ?? 'Team Member';
                $syncData[$staff->id] = [
                    'role_in_event' => $roleInEvent,
                    'notes' => 'Ditugaskan ke divisi ' . explode(' ', $roleInEvent)[0],
                ];
            }
            $event->personnel()->sync($syncData);

            // 5. Seed Event Logistics (from Inventory)
            // Logistic PIC is the one assigned as 'Logistic Officer' or the first staff
            $logisticPic = $eventStaffs[3] ?? $eventStaffs[0];

            $borrowedDate = Carbon::parse($event->start_date)->subDay()->toDateString();
            $returnedDate = $event->status === 'completed' ? $event->end_date->toDateString() : null;
            $returnStatus = $event->status === 'completed' ? 'complete' : null;

            // List of items to borrow
            $logisticsToCreate = [
                [
                    'inventory_id' => $inventories[0]->id, // Sound System
                    'quantity' => $event->category === 'Concert' ? 3 : 1,
                    'rent_cost' => 0.00,
                    'notes' => 'Gunakan kabel XLR cadangan.'
                ],
                [
                    'inventory_id' => $inventories[1]->id, // Mics
                    'quantity' => $event->category === 'Seminar' ? 6 : 4,
                    'rent_cost' => 0.00,
                    'notes' => 'Pastikan baterai penuh.'
                ],
                [
                    'inventory_id' => $inventories[8]->id, // HT
                    'quantity' => 12,
                    'rent_cost' => 0.00,
                    'notes' => 'Dibagikan saat briefing pagi.'
                ],
                [
                    'inventory_id' => $inventories[6]->id, // Genset
                    'quantity' => 1,
                    'rent_cost' => $inventories[6]->default_rent_price,
                    'notes' => 'Sewa genset silent untuk backup listrik.'
                ]
            ];

            foreach ($logisticsToCreate as $logData) {
                $el = EventLogistic::create([
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

                // Decrement available quantity if the item is not yet returned (active/draft events)
                if (is_null($returnedDate)) {
                    $inv = Inventory::find($logData['inventory_id']);
                    $inv->decrement('available_quantity', $logData['quantity']);
                }
            }

            // 6. Seed Tasks & Workflows (Parent-Child)
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
                    // Assign subtask to staff who has the matching role
                    $assigneeId = $assignedPm->id;
                    foreach ($syncData as $uId => $pData) {
                        if ($pData['role_in_event'] === $sTask['role']) {
                            $assigneeId = $uId;
                            break;
                        }
                    }

                    // Subtask status
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
            if ($event->category === 'Sports') {
                $rundownItems = [
                    ['time_s' => '05:00', 'time_e' => '05:30', 'title' => 'Briefing Panitia & Final Check', 'cat' => 'technical', 'pic_role' => 'Project Manager', 'loc' => 'Gate Start'],
                    ['time_s' => '05:30', 'time_e' => '06:15', 'title' => 'Registrasi Ulang & Pengambilan Race Pack', 'cat' => 'registration', 'pic_role' => 'Registration PIC', 'loc' => 'Meja Registrasi'],
                    ['time_s' => '06:15', 'time_e' => '06:30', 'title' => 'Opening Ceremony & Sambutan Direksi', 'cat' => 'ceremony', 'pic_role' => 'Event Coordinator', 'loc' => 'Panggung Utama'],
                    ['time_s' => '06:30', 'time_e' => '08:30', 'title' => 'Flag Off & Sesi Lari (5K & 10K)', 'cat' => 'technical', 'pic_role' => 'Stage Manager', 'loc' => 'Rute Lari'],
                    ['time_s' => '08:30', 'time_e' => '09:30', 'title' => 'Hiburan Live Band & Doorprize', 'cat' => 'talent', 'pic_role' => 'Stage Manager', 'loc' => 'Panggung Utama'],
                    ['time_s' => '09:30', 'time_e' => '10:00', 'title' => 'Pengumuman Pemenang & Penyerahan Medali', 'cat' => 'ceremony', 'pic_role' => 'Project Manager', 'loc' => 'Panggung Utama'],
                    ['time_s' => '10:00', 'time_e' => '10:30', 'title' => 'Closing & Foto Bersama', 'cat' => 'ceremony', 'pic_role' => 'Event Coordinator', 'loc' => 'Panggung Utama'],
                    ['time_s' => '10:30', 'time_e' => '12:00', 'title' => 'Clean Up & Bongkar Panggung', 'cat' => 'logistics', 'pic_role' => 'Logistic Officer', 'loc' => 'Seluruh Venue'],
                ];
            } elseif ($event->category === 'Seminar') {
                $rundownItems = [
                    ['time_s' => '08:00', 'time_e' => '09:00', 'title' => 'Registrasi Ulang & Coffee Morning', 'cat' => 'registration', 'pic_role' => 'Registration PIC', 'loc' => 'Foyer Auditorium'],
                    ['time_s' => '09:00', 'time_e' => '09:15', 'title' => 'Opening & Lagu Indonesia Raya', 'cat' => 'ceremony', 'pic_role' => 'Event Coordinator', 'loc' => 'Dalam Auditorium'],
                    ['time_s' => '09:15', 'time_e' => '10:30', 'title' => 'Keynote Speech: Peluang Karir Masa Depan', 'cat' => 'talent', 'pic_role' => 'Liaison Officer (LO)', 'loc' => 'Panggung Utama'],
                    ['time_s' => '10:30', 'time_e' => '12:00', 'title' => 'Sesi Panelis: Tech & Creative Industry', 'cat' => 'talent', 'pic_role' => 'Stage Manager', 'loc' => 'Panggung Utama'],
                    ['time_s' => '12:00', 'time_e' => '13:00', 'title' => 'ISHOMA (Istirahat, Sholat, Makan)', 'cat' => 'logistics', 'pic_role' => 'Event Coordinator', 'loc' => 'Ruang Makan'],
                    ['time_s' => '13:00', 'time_e' => '15:00', 'title' => 'Workshop Interaktif & Q&A Session', 'cat' => 'talent', 'pic_role' => 'Stage Manager', 'loc' => 'Panggung Utama'],
                    ['time_s' => '15:00', 'time_e' => '15:30', 'title' => 'Penyerahan Plakat & Foto Bersama', 'cat' => 'ceremony', 'pic_role' => 'Project Manager', 'loc' => 'Panggung Utama'],
                    ['time_s' => '15:30', 'time_e' => '16:00', 'title' => 'Closing & Pembagian Sertifikat Elektronik', 'cat' => 'registration', 'pic_role' => 'Registration PIC', 'loc' => 'Foyer Auditorium'],
                ];
            } else {
                // Default fallback rundown for other categories
                $rundownItems = [
                    ['time_s' => '15:00', 'time_e' => '16:00', 'title' => 'Briefing & Sound Check', 'cat' => 'technical', 'pic_role' => 'Sound Engineer', 'loc' => 'Stage'],
                    ['time_s' => '16:00', 'time_e' => '17:00', 'title' => 'Open Gate & Registrasi Tamu', 'cat' => 'registration', 'pic_role' => 'Registration PIC', 'loc' => 'Pintu Masuk'],
                    ['time_s' => '17:00', 'time_e' => '18:30', 'title' => 'Acara Pembuka & Sambutan', 'cat' => 'ceremony', 'pic_role' => 'Event Coordinator', 'loc' => 'Stage'],
                    ['time_s' => '18:30', 'time_e' => '19:30', 'title' => 'Istirahat & Makan Malam / Break', 'cat' => 'logistics', 'pic_role' => 'Logistic Officer', 'loc' => 'Area Katering'],
                    ['time_s' => '19:30', 'time_e' => '21:30', 'title' => 'Sesi Utama / Live Performance', 'cat' => 'talent', 'pic_role' => 'Stage Manager', 'loc' => 'Stage'],
                    ['time_s' => '21:30', 'time_e' => '22:00', 'title' => 'Closing & Evaluasi Panitia Onsite', 'cat' => 'ceremony', 'pic_role' => 'Project Manager', 'loc' => 'Stage'],
                ];
            }

            foreach ($rundownItems as $rIdx => $rItem) {
                // Find PIC ID based on role
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
                    'description' => 'Pelaksanaan agenda ' . $rItem['title'] . ' untuk event ' . $event->name,
                    'category' => $rItem['cat'],
                    'start_time' => $rItem['time_s'],
                    'end_time' => $rItem['time_e'],
                    'duration_minutes' => $duration,
                    'status' => $rundownStatus,
                    'pic_id' => $picId,
                    'location_note' => $rItem['loc'],
                    'notes' => 'Harap tepat waktu sesuai koordinasi HT.',
                    'order_number' => $rIdx,
                    'started_at' => $rundownStatus === 'completed' ? $startDateTime : null,
                    'ended_at' => $rundownStatus === 'completed' ? $endDateTime : null,
                    'delay_minutes' => 0,
                    'created_by' => $assignedPm->id,
                ]);
            }

            // 8. Seed Budget Allocations & Expenses (Financial details)
            if ($event->status !== 'draft') {
                // Budget Allocation
                $alloc1 = EventBudgetAllocation::create([
                    'event_id' => $event->id,
                    'category' => 'Production',
                    'allocated_amount' => $event->budget * 0.50, // 50%
                    'notes' => 'Alokasi panggung, sound system, lighting, dan rigging.'
                ]);
                $alloc2 = EventBudgetAllocation::create([
                    'event_id' => $event->id,
                    'category' => 'Talent & MC',
                    'allocated_amount' => $event->budget * 0.25, // 25%
                    'notes' => 'Fee pengisi acara, pembicara, dan Master of Ceremony.'
                ]);
                $alloc3 = EventBudgetAllocation::create([
                    'event_id' => $event->id,
                    'category' => 'Logistics & Catering',
                    'allocated_amount' => $event->budget * 0.15, // 15%
                    'notes' => 'Konsumsi panitia, katering VIP, sewa AC portable, genset.'
                ]);
                $alloc4 = EventBudgetAllocation::create([
                    'event_id' => $event->id,
                    'category' => 'Marketing & Permits',
                    'allocated_amount' => $event->budget * 0.10, // 10%
                    'notes' => 'Izin keramaian, cetak banner, iklan media sosial.'
                ]);

                // Create some expenses
                EventExpense::create([
                    'event_id' => $event->id,
                    'user_id' => $assignedPm->id,
                    'category' => 'Production',
                    'title' => 'DP Sewa Gedung / Lokasi',
                    'amount' => $event->budget * 0.20,
                    'vendor_name' => 'Pengelola Kawasan Senayan / Hotel',
                    'payment_method' => 'transfer',
                    'payment_status' => 'paid',
                    'spent_at' => Carbon::parse($event->start_date)->subDays(25)->toDateString(),
                    'notes' => 'Bukti transfer dilampirkan.'
                ]);

                EventExpense::create([
                    'event_id' => $event->id,
                    'user_id' => $logisticPic->id,
                    'category' => 'Logistics & Catering',
                    'title' => 'Uang Muka Sewa Genset Silent',
                    'amount' => $inventories[6]->default_rent_price,
                    'vendor_name' => 'PT Sumber Energi',
                    'payment_method' => 'transfer',
                    'payment_status' => 'paid',
                    'spent_at' => Carbon::parse($event->start_date)->subDays(12)->toDateString(),
                    'notes' => 'Sewa Genset 100 kVA.'
                ]);

                EventExpense::create([
                    'event_id' => $event->id,
                    'user_id' => $eventStaffs[0]->id,
                    'category' => 'Logistics & Catering',
                    'title' => 'Snack Box & Air Mineral Briefing',
                    'amount' => 350000.00,
                    'vendor_name' => 'Catering Selera Kita',
                    'payment_method' => 'cash',
                    'payment_status' => 'paid',
                    'spent_at' => Carbon::parse($event->start_date)->toDateString(),
                    'notes' => 'Konsumsi panitia saat briefing pagi.'
                ]);
            }
        }
    }
}
