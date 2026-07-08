<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Faq::truncate();

        $faqs = [
            [
                'question' => 'Apakah EventConnect gratis untuk dicoba?',
                'answer' => 'Ya! EventConnect menyediakan pendaftaran mandiri (self-signup) gratis untuk Event Organizer baru. Anda akan langsung mendapatkan organisasi dengan kuota gratis uji coba (seperti 1 event aktif dan 5 anggota tim) tanpa perlu kartu kredit.',
                'order_number' => 1,
            ],
            [
                'question' => 'Bagaimana cara meningkatkan kuota jumlah event atau anggota tim?',
                'answer' => 'Anda dapat menghubungi administrator platform EventConnect melalui formulir "Hubungi Kami" di halaman ini untuk melakukan peningkatan kapasitas kuota (upgrade plan) sesuai dengan kebutuhan operasional Event Organizer Anda.',
                'order_number' => 2,
            ],
            [
                'question' => 'Apakah data rundown dan anggaran event kami aman?',
                'answer' => 'Tentu saja. EventConnect dibangun menggunakan arsitektur keamanan multi-tenant. Ini berarti data organisasi Anda terisolasi secara logis dari organisasi lain, menjamin kerahasiaan rundown, data anggaran, dan inventaris logistik Anda.',
                'order_number' => 3,
            ],
            [
                'question' => 'Peran (role) apa saja yang tersedia dalam aplikasi manajemen event?',
                'answer' => 'Platform kami menyediakan 3 peran global: Superadmin (pemilik EO), Project Manager (PM - pengelola proyek event), dan Staff (personel pelaksana). Selain itu, Anda dapat memetakan peran operasional event yang detail seperti Rundown Coordinator, PIC Panggung, atau Tim Teknis saat menugaskan personel ke suatu event.',
                'order_number' => 4,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }
    }
}
