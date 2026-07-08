<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EventCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected User $admin;
    protected User $pm;
    protected User $staff1;
    protected User $staff2;
    protected string $adminToken;
    protected string $pmToken;
    protected string $staffToken;
    protected Event $event;

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
            'name' => 'Test Organization', 'slug' => 'test-org',
            'email' => 'org@test.com', 'status' => 'active',
            'plan' => 'business', 'max_users' => 25, 'max_events' => 50,
        ]);

        $this->admin = User::create([
            'organization_id' => $this->org->id, 'name' => 'Admin',
            'email' => 'admin@test.com', 'password' => Hash::make('password123'),
            'role' => 'superadmin', 'status' => 'active',
        ]);

        $this->pm = User::create([
            'organization_id' => $this->org->id, 'name' => 'PM',
            'email' => 'pm@test.com', 'password' => Hash::make('password123'),
            'role' => 'project_manager', 'status' => 'active',
        ]);

        $this->staff1 = User::create([
            'organization_id' => $this->org->id, 'name' => 'Staff 1',
            'email' => 'staff1@test.com', 'password' => Hash::make('password123'),
            'role' => 'staff', 'status' => 'active',
        ]);

        $this->staff2 = User::create([
            'organization_id' => $this->org->id, 'name' => 'Staff 2',
            'email' => 'staff2@test.com', 'password' => Hash::make('password123'),
            'role' => 'staff', 'status' => 'active',
        ]);

        // Create an event via PM
        $this->resetAuth('web');
        $r = $this->postJson('/api/login', ['email' => 'pm@test.com', 'password' => 'password123']);
        $this->pmToken = $r->json('token');

        $this->resetAuth('sanctum');
        $eventResp = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                          ->postJson('/api/events', [
                              'name' => 'Test Event', 'description' => 'A test event',
                              'location' => 'Jakarta', 'start_date' => '2026-09-01',
                              'end_date' => '2026-09-03', 'start_time' => '09:00',
                              'end_time' => '18:00', 'status' => 'draft',
                              'budget' => 100000000, 'category' => 'Conference',
                              'expected_participants' => 500,
                              'personnel' => [
                                  ['user_id' => $this->staff1->id, 'role_in_event' => 'coordinator', 'notes' => 'Main coordinator'],
                                  ['user_id' => $this->staff2->id, 'role_in_event' => 'technical_team', 'notes' => 'Tech support'],
                              ],
                          ]);
        $this->event = Event::find($eventResp->json('id'));

        // Get admin and staff tokens
        $this->resetAuth('web');
        $r = $this->postJson('/api/login', ['email' => 'admin@test.com', 'password' => 'password123']);
        $this->adminToken = $r->json('token');

        $this->resetAuth('web');
        $r = $this->postJson('/api/login', ['email' => 'staff1@test.com', 'password' => 'password123']);
        $this->staffToken = $r->json('token');
    }

    // TC-051: Lihat daftar event
    public function test_tc051_lihat_daftar_event(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->staffToken)
                         ->getJson('/api/events');
        $response->assertStatus(200);
    }

    // TC-052: Lihat detail event
    public function test_tc052_lihat_detail_event(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->staffToken)
                         ->getJson('/api/events/' . $this->event->id);
        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Test Event']);
    }

    // TC-053: Update event
    public function test_tc053_update_event(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->putJson('/api/events/' . $this->event->id, [
                             'name' => 'Updated Event Name',
                             'description' => 'Updated description',
                             'location' => 'Surabaya',
                             'start_date' => '2026-09-01',
                             'end_date' => '2026-09-03',
                             'start_time' => '10:00',
                             'end_time' => '17:00',
                             'status' => 'active',
                             'budget' => 120000000,
                             'category' => 'Expo',
                             'expected_participants' => 600,
                             'personnel' => [
                                 ['user_id' => $this->staff1->id, 'role_in_event' => 'coordinator', 'notes' => 'Lead'],
                             ],
                         ]);
        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Updated Event Name']);
    }

    // TC-054: Hapus event
    public function test_tc054_hapus_event(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->deleteJson('/api/events/' . $this->event->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('events', ['id' => $this->event->id]);
    }

    // TC-055: Lihat daftar user untuk penugasan
    public function test_tc055_lihat_daftar_user_untuk_penugasan(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->getJson('/api/users-list');
        $response->assertStatus(200);
    }

    // TC-056: Lihat dashboard keuangan
    public function test_tc056_lihat_dashboard_keuangan(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->getJson('/api/dashboard/financials');
        $response->assertStatus(200);
    }
}
