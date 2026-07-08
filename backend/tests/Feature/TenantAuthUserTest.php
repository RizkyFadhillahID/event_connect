<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantAuthUserTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected User $admin;
    protected User $pm;
    protected User $staff1;
    protected string $adminToken;

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

        $this->org = Organization::create([
            'name' => 'Test Organization',
            'slug' => 'test-organization',
            'email' => 'org@test.com',
            'phone' => '08111111111',
            'address' => 'Test Address',
            'status' => 'active',
            'plan' => 'business',
            'max_users' => 25,
            'max_events' => 50,
        ]);

        $this->admin = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $this->pm = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Test PM',
            'email' => 'pm@test.com',
            'password' => Hash::make('password123'),
            'role' => 'project_manager',
            'status' => 'active',
        ]);

        $this->staff1 = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Test Staff',
            'email' => 'staff@test.com',
            'password' => Hash::make('password123'),
            'role' => 'staff',
            'status' => 'active',
        ]);

        // Login admin to get token
        $this->resetAuth('web');
        $response = $this->postJson('/api/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);
        $this->adminToken = $response->json('token');
    }

    // TC-044: Logout tenant user
    public function test_tc044_logout_user(): void
    {
        // Login as PM to get a separate token
        $this->resetAuth('web');
        $resp = $this->postJson('/api/login', [
            'email' => 'pm@test.com',
            'password' => 'password123',
        ]);
        $pmToken = $resp->json('token');

        // Logout
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $pmToken)
                         ->postJson('/api/logout');
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Berhasil logout.']);

        // Verify token is now invalid
        $this->resetAuth('sanctum');
        $verifyResponse = $this->withHeader('Authorization', 'Bearer ' . $pmToken)
                               ->getJson('/api/me');
        $verifyResponse->assertStatus(401);
    }

    // TC-045: Lihat profil saya
    public function test_tc045_lihat_profil_saya(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
                         ->getJson('/api/me');
        $response->assertStatus(200)
                 ->assertJsonFragment(['email' => 'admin@test.com'])
                 ->assertJsonStructure(['id', 'name', 'email', 'role', 'organization']);
    }

    // TC-046: Update profil saya
    public function test_tc046_update_profil_saya(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
                         ->putJson('/api/profile', [
                             'name' => 'Updated Admin Name',
                             'email' => 'admin@test.com',
                             'phone' => '08999999999',
                         ]);
        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Updated Admin Name']);
    }

    // TC-047: Lihat daftar user
    public function test_tc047_lihat_daftar_user(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
                         ->getJson('/api/users');
        $response->assertStatus(200);
        // Paginated response has 'data' key
        $response->assertJsonStructure(['data']);
        // Should have 3 users (admin, pm, staff)
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    // TC-048: Lihat detail user
    public function test_tc048_lihat_detail_user(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
                         ->getJson('/api/users/' . $this->pm->id);
        $response->assertStatus(200)
                 ->assertJsonFragment(['email' => 'pm@test.com']);
    }

    // TC-049: Update data user
    public function test_tc049_update_data_user(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
                         ->putJson('/api/users/' . $this->staff1->id, [
                             'name' => 'Updated Staff Name',
                             'email' => 'staff@test.com',
                             'role' => 'staff',
                             'status' => 'active',
                         ]);
        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Updated Staff Name']);
    }

    // TC-050: Hapus user
    public function test_tc050_hapus_user(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
                         ->deleteJson('/api/users/' . $this->staff1->id);
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'User berhasil dihapus.']);

        $this->assertDatabaseMissing('users', ['id' => $this->staff1->id]);
    }
}
