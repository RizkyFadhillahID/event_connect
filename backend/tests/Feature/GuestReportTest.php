<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GuestReportTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected User $pm;
    protected User $staff1;
    protected Event $event;
    protected string $pmToken;
    protected int $guestId;

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
            'name' => 'Test Org', 'slug' => 'test-org', 'email' => 'org@test.com',
            'status' => 'active', 'plan' => 'business', 'max_users' => 25, 'max_events' => 50,
        ]);

        $this->pm = User::create([
            'organization_id' => $this->org->id, 'name' => 'PM',
            'email' => 'pm@test.com', 'password' => Hash::make('password123'),
            'role' => 'project_manager', 'status' => 'active',
        ]);

        $this->staff1 = User::create([
            'organization_id' => $this->org->id, 'name' => 'Staff',
            'email' => 'staff@test.com', 'password' => Hash::make('password123'),
            'role' => 'staff', 'status' => 'active',
        ]);

        // Login PM
        $this->resetAuth('web');
        $r = $this->postJson('/api/login', ['email' => 'pm@test.com', 'password' => 'password123']);
        $this->pmToken = $r->json('token');

        // Create event
        $this->resetAuth('sanctum');
        $eventResp = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                          ->postJson('/api/events', [
                              'name' => 'Guest Event', 'description' => 'Desc',
                              'location' => 'Jakarta', 'start_date' => '2026-09-01',
                              'end_date' => '2026-09-02', 'start_time' => '09:00',
                              'end_time' => '18:00', 'status' => 'draft',
                              'budget' => 20000000, 'category' => 'Seminar',
                              'expected_participants' => 100,
                              'personnel' => [
                                  ['user_id' => $this->staff1->id, 'role_in_event' => 'coordinator', 'notes' => 'Lead'],
                              ],
                          ]);
        $this->event = Event::find($eventResp->json('id'));

        // Register a guest
        $this->resetAuth('sanctum');
        $guestResp = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                          ->postJson("/api/events/{$this->event->id}/guests", [
                              'name' => 'Dr. Jane Smith',
                              'email' => 'jane@example.com',
                              'phone' => '08111222333',
                              'category' => 'vip',
                              'rsvp_status' => 'attending',
                          ]);
        $this->guestId = $guestResp->json('id');

        // Create a report
        $this->resetAuth('sanctum');
        $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
             ->postJson("/api/events/{$this->event->id}/report", [
                 'evaluation_notes' => 'Good event overall.',
                 'recommendations' => 'Need better venue next time.',
             ]);
    }

    // TC-083
    public function test_tc083_lihat_daftar_tamu_event(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->getJson("/api/events/{$this->event->id}/guests");
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEmpty($data);
    }

    // TC-084
    public function test_tc084_update_data_tamu(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->putJson("/api/events/{$this->event->id}/guests/{$this->guestId}", [
                             'name' => 'Dr. Jane Smith Updated',
                             'category' => 'vvip',
                             'rsvp_status' => 'attending',
                         ]);
        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Dr. Jane Smith Updated']);
    }

    // TC-085
    public function test_tc085_hapus_tamu(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->deleteJson("/api/events/{$this->event->id}/guests/{$this->guestId}");
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Tamu berhasil dihapus.']);
    }

    // TC-086
    public function test_tc086_hapus_laporan_evaluasi(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->deleteJson("/api/events/{$this->event->id}/report");
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Laporan evaluasi berhasil dihapus/direset.']);
    }
}
