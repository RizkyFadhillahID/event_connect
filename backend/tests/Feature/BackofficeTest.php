<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\LandingContent;
use App\Models\Organization;
use App\Models\PlatformAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BackofficeTest extends TestCase
{
    use RefreshDatabase;

    protected PlatformAdmin $platformAdmin;
    protected string $boToken;

    private function resetAuth(string $defaultGuard = 'web'): void
    {
        $this->flushHeaders();
        Auth::guard('web')->logout();
        Auth::forgetGuards();
        Auth::shouldUse($defaultGuard);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->platformAdmin = PlatformAdmin::create([
            'name' => 'Test Platform Owner',
            'email' => 'owner@platform.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        // Login
        $this->resetAuth('web');
        $response = $this->postJson('/api/backoffice/login', [
            'email' => 'owner@platform.com',
            'password' => 'password123',
        ]);
        $this->boToken = $response->json('token');
    }

    // TC-087: Logout Platform Admin
    public function test_tc087_logout_platform_admin(): void
    {
        // Create a separate admin to logout (don't logout main admin)
        $admin2 = PlatformAdmin::create([
            'name' => 'Admin 2', 'email' => 'admin2@platform.com',
            'password' => Hash::make('password123'), 'role' => 'admin', 'status' => 'active',
        ]);
        $this->resetAuth('web');
        $r = $this->postJson('/api/backoffice/login', ['email' => 'admin2@platform.com', 'password' => 'password123']);
        $token2 = $r->json('token');

        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $token2)
                         ->postJson('/api/backoffice/logout');
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Berhasil logout.']);

        // Verify token is invalid
        $this->resetAuth('platform');
        $verify = $this->withHeader('Authorization', 'Bearer ' . $token2)
                       ->getJson('/api/backoffice/me');
        $verify->assertStatus(401);
    }

    // TC-088: Lihat profil Platform Admin
    public function test_tc088_lihat_profil_platform_admin(): void
    {
        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->getJson('/api/backoffice/me');
        $response->assertStatus(200)
                 ->assertJsonFragment(['email' => 'owner@platform.com']);
    }

    // TC-089: Lihat dashboard backoffice
    public function test_tc089_lihat_dashboard_backoffice(): void
    {
        // Create some data for dashboard
        Organization::create([
            'name' => 'Org 1', 'slug' => 'org-1', 'email' => 'org1@test.com',
            'status' => 'active', 'plan' => 'free', 'max_users' => 5, 'max_events' => 3,
        ]);

        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->getJson('/api/backoffice/dashboard');
        $response->assertStatus(200)
                 ->assertJsonStructure(['total_organizations', 'active_organizations', 'total_users', 'total_events']);
    }

    // TC-090: Lihat daftar organisasi
    public function test_tc090_lihat_daftar_organisasi(): void
    {
        Organization::create([
            'name' => 'Org A', 'slug' => 'org-a', 'email' => 'orga@test.com',
            'status' => 'active', 'plan' => 'business', 'max_users' => 25, 'max_events' => 50,
        ]);

        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->getJson('/api/backoffice/organizations');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    // TC-091: Lihat detail organisasi
    public function test_tc091_lihat_detail_organisasi(): void
    {
        $org = Organization::create([
            'name' => 'Detail Org', 'slug' => 'detail-org', 'email' => 'detail@test.com',
            'status' => 'active', 'plan' => 'business', 'max_users' => 25, 'max_events' => 50,
        ]);

        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->getJson('/api/backoffice/organizations/' . $org->id);
        $response->assertStatus(200)
                 ->assertJsonStructure(['organization', 'stats']);
    }

    // TC-092: Buat organisasi dari backoffice
    public function test_tc092_buat_organisasi_dari_backoffice(): void
    {
        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->postJson('/api/backoffice/organizations', [
                             'name' => 'New BO Org',
                             'email' => 'newborg@test.com',
                             'phone' => '08222222222',
                             'address' => 'Jakarta',
                             'plan' => 'business',
                             'admin_name' => 'BO Org Admin',
                             'admin_email' => 'borgadmin@test.com',
                             'admin_password' => 'password123',
                         ]);
        $response->assertStatus(201)
                 ->assertJsonFragment(['message' => 'Organisasi berhasil dibuat.']);
    }

    // TC-093: Update data organisasi
    public function test_tc093_update_data_organisasi(): void
    {
        $org = Organization::create([
            'name' => 'Update Org', 'slug' => 'update-org', 'email' => 'update@test.com',
            'status' => 'active', 'plan' => 'free', 'max_users' => 5, 'max_events' => 3,
        ]);

        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->putJson('/api/backoffice/organizations/' . $org->id, [
                             'name' => 'Updated Org Name',
                             'email' => 'update@test.com',
                             'plan' => 'business',
                             'max_users' => 25,
                             'max_events' => 50,
                             'status' => 'active',
                         ]);
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Organisasi berhasil diupdate.']);
    }

    // TC-094: Hapus (deaktivasi) organisasi
    public function test_tc094_hapus_organisasi(): void
    {
        $org = Organization::create([
            'name' => 'Delete Org', 'slug' => 'delete-org', 'email' => 'delete@test.com',
            'status' => 'active', 'plan' => 'free', 'max_users' => 5, 'max_events' => 3,
        ]);

        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->deleteJson('/api/backoffice/organizations/' . $org->id);
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Organisasi dinonaktifkan.']);
    }

    // TC-095: Lihat daftar platform admin
    public function test_tc095_lihat_daftar_platform_admin(): void
    {
        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->getJson('/api/backoffice/admins');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    // TC-096: Buat platform admin baru
    public function test_tc096_buat_platform_admin_baru(): void
    {
        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->postJson('/api/backoffice/admins', [
                             'name' => 'New Support Admin',
                             'email' => 'support@platform.com',
                             'password' => 'password123',
                             'role' => 'support',
                             'status' => 'active',
                         ]);
        $response->assertStatus(201)
                 ->assertJsonFragment(['message' => 'Admin platform berhasil dibuat.']);
    }

    // TC-097: Update data platform admin
    public function test_tc097_update_data_platform_admin(): void
    {
        $admin = PlatformAdmin::create([
            'name' => 'Edit Admin', 'email' => 'edit@platform.com',
            'password' => Hash::make('password123'), 'role' => 'admin', 'status' => 'active',
        ]);

        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->putJson('/api/backoffice/admins/' . $admin->id, [
                             'name' => 'Edited Admin Name',
                             'email' => 'edit@platform.com',
                             'role' => 'support',
                             'status' => 'active',
                         ]);
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Admin platform berhasil diupdate.']);
    }

    // TC-098: Hapus platform admin
    public function test_tc098_hapus_platform_admin(): void
    {
        $admin = PlatformAdmin::create([
            'name' => 'Delete Admin', 'email' => 'deladmin@platform.com',
            'password' => Hash::make('password123'), 'role' => 'admin', 'status' => 'active',
        ]);

        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->deleteJson('/api/backoffice/admins/' . $admin->id);
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Admin platform berhasil dihapus.']);
    }

    // TC-099: Lihat konten landing (backoffice)
    public function test_tc099_lihat_konten_landing_backoffice(): void
    {
        LandingContent::create(['key_name' => 'hero', 'title' => 'Welcome', 'content' => 'Content']);

        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->getJson('/api/backoffice/landing-contents');
        $response->assertStatus(200);
    }

    // TC-100: Update konten landing
    public function test_tc100_update_konten_landing(): void
    {
        LandingContent::create(['key_name' => 'hero', 'title' => 'Welcome', 'content' => 'Old content']);

        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->putJson('/api/backoffice/landing-contents/hero', [
                             'title' => 'Updated Welcome',
                             'content' => 'New content here',
                         ]);
        $response->assertStatus(200);
    }

    // TC-101: Lihat daftar pesan kontak
    public function test_tc101_lihat_daftar_pesan_kontak(): void
    {
        ContactMessage::create([
            'name' => 'Visitor', 'email' => 'visitor@test.com',
            'subject' => 'Hello', 'message' => 'Test message',
        ]);

        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->getJson('/api/backoffice/contact-messages');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    // TC-102: Lihat detail pesan kontak
    public function test_tc102_lihat_detail_pesan_kontak(): void
    {
        $msg = ContactMessage::create([
            'name' => 'Visitor', 'email' => 'visitor@test.com',
            'subject' => 'Detail Test', 'message' => 'Detailed message',
        ]);

        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->getJson('/api/backoffice/contact-messages/' . $msg->id);
        $response->assertStatus(200)
                 ->assertJsonFragment(['subject' => 'Detail Test']);
    }

    // TC-103: Hapus pesan kontak
    public function test_tc103_hapus_pesan_kontak(): void
    {
        $msg = ContactMessage::create([
            'name' => 'Visitor', 'email' => 'visitor@test.com',
            'subject' => 'Delete Test', 'message' => 'To be deleted',
        ]);

        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->deleteJson('/api/backoffice/contact-messages/' . $msg->id);
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Pesan contact us berhasil dihapus.']);
    }

    // TC-104: Lihat daftar FAQ (backoffice)
    public function test_tc104_lihat_daftar_faq_backoffice(): void
    {
        Faq::create(['question' => 'Q1', 'answer' => 'A1', 'order_number' => 1]);
        Faq::create(['question' => 'Q2', 'answer' => 'A2', 'order_number' => 2]);

        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->getJson('/api/backoffice/faqs');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
    }

    // TC-105: Buat FAQ baru
    public function test_tc105_buat_faq_baru(): void
    {
        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->postJson('/api/backoffice/faqs', [
                             'question' => 'Bagaimana cara registrasi?',
                             'answer' => 'Kunjungi halaman registrasi dan isi formulir.',
                             'order_number' => 1,
                         ]);
        $response->assertStatus(201)
                 ->assertJsonFragment(['message' => 'FAQ baru berhasil ditambahkan.']);
    }

    // TC-106: Update FAQ
    public function test_tc106_update_faq(): void
    {
        $faq = Faq::create(['question' => 'Old Q', 'answer' => 'Old A', 'order_number' => 1]);

        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->putJson('/api/backoffice/faqs/' . $faq->id, [
                             'question' => 'Updated Question',
                             'answer' => 'Updated Answer',
                         ]);
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'FAQ berhasil diperbarui.']);
    }

    // TC-107: Hapus FAQ
    public function test_tc107_hapus_faq(): void
    {
        $faq = Faq::create(['question' => 'Delete Q', 'answer' => 'Delete A', 'order_number' => 1]);

        $this->resetAuth('platform');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->boToken)
                         ->deleteJson('/api/backoffice/faqs/' . $faq->id);
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'FAQ berhasil dihapus.']);
    }
}
