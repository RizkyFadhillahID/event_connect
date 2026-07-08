<?php

namespace Database\Seeders;

use App\Models\ContactMessage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ContactMessageSeeder extends Seeder
{
    public function run(): void
    {
        // Clean up existing contact messages
        ContactMessage::truncate();

        $messages = [
            [
                'name' => 'Andi Wijaya',
                'email' => 'andi.wijaya@gmail.com',
                'subject' => 'Tanya Paket Enterprise untuk 100+ User',
                'message' => "Halo tim EventConnect,\n\nSaya dari EO Mahakarya Sukses Utama di Surabaya. Kami sedang menangani festival budaya skala besar dan membutuhkan sistem kolaborasi untuk sekitar 120 panitia lapangan. Apakah ada penawaran khusus atau diskon untuk paket Enterprise tahunan? Mohon kirimkan proposal harganya.\n\nTerima kasih,\nAndi Wijaya",
                'created_at' => Carbon::now()->subDays(5),
            ],
            [
                'name' => 'Siti Aisyah',
                'email' => 'siti.aisyah@corporate-event.id',
                'subject' => 'Integrasi Pembayaran Tiket Mandiri',
                'message' => "Selamat pagi,\n\nApakah platform EventConnect mendukung integrasi custom payment gateway untuk modul registrasi tamu & peserta secara lokal? Perusahaan kami memiliki kebijakan internal untuk hanya menggunakan Bank Mandiri Virtual Account sebagai penerimaan dana.\n\nSalam,\nSiti Aisyah",
                'created_at' => Carbon::now()->subDays(4),
            ],
            [
                'name' => 'Budi Pratama',
                'email' => 'budi.pratama@techfest.or.id',
                'subject' => 'Error Saat Upload Logo Organisasi',
                'message' => "Halo,\n\nSaya sedang mencoba membuat akun demo untuk organisasi kami dan ingin mengganti logo organisasi di menu pengaturan tenant. Namun, setiap kali saya mengunggah file PNG ukuran 3MB, selalu muncul error 'Request Entity Too Large'. Apakah ada batasan maksimal ukuran file? Mohon panduannya.\n\nBudi Pratama",
                'created_at' => Carbon::now()->subDays(3),
            ],
            [
                'name' => 'Clara Anastasia',
                'email' => 'clara.anastasia@outlook.com',
                'subject' => 'Kemitraan Vendor Perlengkapan Panggung',
                'message' => "Selamat siang,\n\nKami dari CV Panggung Nusantara, penyedia jasa rental rigging, sound system, dan lighting di area Jabodetabek. Kami ingin menanyakan apakah ada program kemitraan agar jasa rental kami bisa masuk ke daftar katalog/vendor rekomendasi bagi para EO yang mendaftar di EventConnect?\n\nSalam sukses,\nClara",
                'created_at' => Carbon::now()->subDays(2),
            ],
            [
                'name' => 'Rian Hidayat',
                'email' => 'rian.hidayat@outlook.com',
                'subject' => 'Pertanyaan Batas Event untuk Paket Free',
                'message' => "Halo admin,\n\nSaya melihat di halaman landing page untuk paket Free dibatasi maksimal 3 event. Yang ingin saya tanyakan, apakah batas 3 event itu adalah batas akumulasi selamanya, atau batas event aktif secara bersamaan? Jika event yang sudah selesai saya hapus/arsipkan, apakah slot kuota event kami akan bertambah kembali?\n\nTerima kasih.",
                'created_at' => Carbon::now()->subDay(),
            ],
            [
                'name' => 'Dewi Lestari',
                'email' => 'dewi.lestari@creative-eo.co.id',
                'subject' => 'Permintaan Demo Sistem Fitur Reverb Chat',
                'message' => "Halo tim sales EventConnect,\n\nKami dari Creative EO sangat tertarik dengan fitur kolaborasi real-time chat yang didukung Reverb di aplikasi ini. Apakah kami bisa menjadwalkan sesi demo online (via Zoom) bersama perwakilan tim teknis Anda minggu ini untuk melihat demo interaktif produk ini?\n\nSalam,\nDewi Lestari",
                'created_at' => Carbon::now()->subHours(5),
            ]
        ];

        foreach ($messages as $data) {
            ContactMessage::create(array_merge($data, [
                'updated_at' => $data['created_at']
            ]));
        }
    }
}
