<?php

namespace Database\Seeders;

use App\Models\LandingContent;
use Illuminate\Database\Seeder;

class LandingContentSeeder extends Seeder
{
    public function run(): void
    {
        $contents = [
            [
                'key_name' => 'hero',
                'title' => 'Platform Manajemen Event Terintegrasi',
                'content' => 'Kelola rundown, logistik gudang global, alokasi anggaran, dan koordinasi tim secara instan dalam satu ekosistem multi-tenant premium.',
            ],
            [
                'key_name' => 'features',
                'title' => 'Fitur Unggulan EventConnect',
                'content' => json_encode([
                    [
                        'title' => 'Isolasi Multi-Tenant',
                        'desc' => 'Data organisasi dan tim Anda tersimpan aman serta terisolasi penuh dari pihak luar.'
                    ],
                    [
                        'title' => 'Kanban Task & Rundown',
                        'desc' => 'Pantau jadwal acara secara real-time lengkap dengan sistem dependency task & log audit.'
                    ],
                    [
                        'title' => 'Logistik Global',
                        'desc' => 'Kelola aset gudang EO secara terpusat, lengkap dengan pelacakan distribusi alat ke event aktif.'
                    ],
                    [
                        'title' => 'Chat Kolaborasi & Laporan',
                        'desc' => 'Komunikasi instan per-kegiatan dan hasilkan laporan evaluasi pasca-event secara otomatis.'
                    ]
                ]),
            ],
            [
                'key_name' => 'pricing',
                'title' => 'Pilihan Paket Layanan',
                'content' => json_encode([
                    [
                        'name' => 'Starter Plan',
                        'price' => 'Rp 0 / Free',
                        'desc' => 'Cocok untuk Event Organizer pemula.',
                        'specs' => ['Maksimal 5 User', 'Maksimal 3 Event Aktif', 'Akses Fitur Dasar', 'Penyimpanan 500MB']
                    ],
                    [
                        'name' => 'Business Plan',
                        'price' => 'Rp 299.000 / bln',
                        'desc' => 'Solusi terbaik untuk EO berkembang.',
                        'specs' => ['Maksimal 25 User', 'Maksimal 20 Event Aktif', 'Fitur Logistik & Rundown', 'Live Chat & Laporan Otomatis', 'Dukungan Email 24/7']
                    ],
                    [
                        'name' => 'Enterprise Plan',
                        'price' => 'Rp 799.000 / bln',
                        'desc' => 'Dukungan penuh untuk EO skala besar.',
                        'specs' => ['User & Event Tidak Terbatas', 'Prioritas Server & Keamanan', 'Fitur Kustomisasi Laporan', 'Dukungan PIC Utama', 'SLA 99.9%']
                    ]
                ]),
            ],
            [
                'key_name' => 'contact_info',
                'title' => 'Informasi Kontak Kami',
                'content' => json_encode([
                    'email' => 'support@eventconnect.com',
                    'phone' => '+62 821-4567-8901',
                    'address' => 'Gedung Cyber Plaza Lantai 12, Jl. Kuningan Mulia, Jakarta Selatan, Indonesia'
                ]),
            ]
        ];

        foreach ($contents as $c) {
            LandingContent::updateOrCreate(
                ['key_name' => $c['key_name']],
                ['title' => $c['title'], 'content' => $c['content']]
            );
        }
    }
}
