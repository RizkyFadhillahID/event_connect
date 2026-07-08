<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\LandingContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLandingTest extends TestCase
{
    use RefreshDatabase;

    // TC-041: Lihat konten landing page publik
    public function test_tc041_lihat_konten_landing_page(): void
    {
        // Seed some landing content
        LandingContent::create(['key_name' => 'hero', 'title' => 'Welcome', 'content' => 'Hero content']);
        LandingContent::create(['key_name' => 'features', 'title' => 'Features', 'content' => 'Feature list']);

        $response = $this->getJson('/api/landing/contents');
        $response->assertStatus(200);
        // Should return object keyed by key_name
        $data = $response->json();
        $this->assertArrayHasKey('hero', $data);
        $this->assertArrayHasKey('features', $data);
    }

    // TC-042: Kirim pesan kontak
    public function test_tc042_kirim_pesan_kontak(): void
    {
        $response = $this->postJson('/api/landing/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Pertanyaan',
            'message' => 'Saya ingin bertanya tentang layanan.',
        ]);
        $response->assertStatus(201)
                 ->assertJsonFragment(['message' => 'Pesan Anda berhasil dikirim ke Admin. Terima kasih!']);

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    // TC-043: Lihat FAQ publik
    public function test_tc043_lihat_faq_publik(): void
    {
        Faq::create(['question' => 'Apa itu EventConnect?', 'answer' => 'Platform manajemen event.', 'order_number' => 1]);
        Faq::create(['question' => 'Bagaimana cara daftar?', 'answer' => 'Melalui halaman registrasi.', 'order_number' => 2]);

        $response = $this->getJson('/api/landing/faqs');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(2, $data);
    }
}
