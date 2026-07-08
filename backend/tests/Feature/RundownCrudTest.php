<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventRundown;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RundownCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected User $pm;
    protected User $staff1;
    protected User $staff2;
    protected Event $event;
    protected Task $task;
    protected string $pmToken;
    protected string $staff1Token;
    protected int $rundown1Id;
    protected int $rundown2Id;

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
            'organization_id' => $this->org->id, 'name' => 'Staff 1',
            'email' => 'staff1@test.com', 'password' => Hash::make('password123'),
            'role' => 'staff', 'status' => 'active',
        ]);

        $this->staff2 = User::create([
            'organization_id' => $this->org->id, 'name' => 'Staff 2',
            'email' => 'staff2@test.com', 'password' => Hash::make('password123'),
            'role' => 'staff', 'status' => 'active',
        ]);

        // Login PM
        $this->resetAuth('web');
        $r = $this->postJson('/api/login', ['email' => 'pm@test.com', 'password' => 'password123']);
        $this->pmToken = $r->json('token');

        // Create event with personnel
        $this->resetAuth('sanctum');
        $eventResp = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                          ->postJson('/api/events', [
                              'name' => 'Rundown Event', 'description' => 'Test',
                              'location' => 'Jakarta', 'start_date' => '2026-09-01',
                              'end_date' => '2026-09-02', 'start_time' => '08:00',
                              'end_time' => '18:00', 'status' => 'draft',
                              'budget' => 30000000, 'category' => 'Conference',
                              'expected_participants' => 100,
                              'personnel' => [
                                  ['user_id' => $this->staff1->id, 'role_in_event' => 'Event Coordinator', 'notes' => 'Lead'],
                                  ['user_id' => $this->staff2->id, 'role_in_event' => 'technical_team', 'notes' => 'Tech'],
                              ],
                          ]);
        $this->event = Event::find($eventResp->json('id'));

        // Login Staff 1 (Event Coordinator)
        $this->resetAuth('web');
        $r = $this->postJson('/api/login', ['email' => 'staff1@test.com', 'password' => 'password123']);
        $this->staff1Token = $r->json('token');

        // Create rundown items
        $this->resetAuth('sanctum');
        $rd1 = $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
                    ->postJson("/api/events/{$this->event->id}/rundowns", [
                        'event_date' => '2026-09-01', 'title' => 'Opening',
                        'description' => 'Opening ceremony', 'category' => 'ceremony',
                        'start_time' => '09:00', 'end_time' => '10:00',
                        'pic_id' => $this->staff1->id, 'location_note' => 'Main Hall',
                        'notes' => 'Important', 'order_number' => 1,
                    ]);
        $this->rundown1Id = $rd1->json('data.id');

        $this->resetAuth('sanctum');
        $rd2 = $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
                    ->postJson("/api/events/{$this->event->id}/rundowns", [
                        'event_date' => '2026-09-01', 'title' => 'Technical Setup',
                        'description' => 'Sound check', 'category' => 'technical',
                        'start_time' => '08:00', 'end_time' => '09:00',
                        'pic_id' => $this->staff2->id, 'location_note' => 'Stage',
                        'notes' => 'Before opening', 'order_number' => 2,
                    ]);
        $this->rundown2Id = $rd2->json('data.id');

        // Create a task
        $this->task = Task::create([
            'title' => 'Test Task',
            'description' => 'Task description',
            'event_id' => $this->event->id,
            'assigned_to' => $this->staff1->id,
            'created_by' => $this->pm->id,
            'priority' => 'high',
            'status' => 'pending',
            'due_date' => '2026-08-30',
            'category' => 'technical',
        ]);
    }

    // TC-074
    public function test_tc074_lihat_daftar_rundown_event(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
                         ->getJson("/api/events/{$this->event->id}/rundowns");
        $response->assertStatus(200);
    }

    // TC-075
    public function test_tc075_lihat_tanggal_rundown_event(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
                         ->getJson("/api/events/{$this->event->id}/rundown-dates");
        $response->assertStatus(200);
    }

    // TC-076
    public function test_tc076_lihat_statistik_rundown_event(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
                         ->getJson("/api/events/{$this->event->id}/rundown-stats");
        $response->assertStatus(200);
    }

    // TC-077
    public function test_tc077_lihat_detail_rundown(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
                         ->getJson("/api/rundowns/{$this->rundown1Id}");
        $response->assertStatus(200)
                 ->assertJsonFragment(['title' => 'Opening']);
    }

    // TC-078
    public function test_tc078_update_item_rundown(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
                         ->putJson("/api/rundowns/{$this->rundown1Id}", [
                             'event_date' => '2026-09-01',
                             'title' => 'Grand Opening Updated',
                             'description' => 'Updated ceremony',
                             'category' => 'ceremony',
                             'start_time' => '09:30',
                             'end_time' => '10:30',
                             'pic_id' => $this->staff1->id,
                             'location_note' => 'Main Hall Updated',
                             'notes' => 'Updated notes',
                             'order_number' => 1,
                         ]);
        $response->assertStatus(200);
    }

    // TC-079
    public function test_tc079_hapus_item_rundown(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
                         ->deleteJson("/api/rundowns/{$this->rundown2Id}");
        $response->assertStatus(200);
    }

    // TC-080
    public function test_tc080_lihat_log_perubahan_rundown(): void
    {
        // First update the rundown to generate a log entry
        $this->resetAuth('sanctum');
        $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
             ->putJson("/api/rundowns/{$this->rundown1Id}", [
                 'event_date' => '2026-09-01', 'title' => 'Opening Modified',
                 'description' => 'Modified', 'category' => 'ceremony',
                 'start_time' => '09:00', 'end_time' => '10:00',
                 'pic_id' => $this->staff1->id, 'order_number' => 1,
             ]);

        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
                         ->getJson("/api/rundowns/{$this->rundown1Id}/logs");
        $response->assertStatus(200);
    }

    // TC-081
    public function test_tc081_tambah_dependensi_rundown(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
                         ->postJson("/api/rundowns/{$this->rundown1Id}/dependencies", [
                             'task_id' => $this->task->id,
                         ]);
        $response->assertStatus(200);
    }

    // TC-082
    public function test_tc082_hapus_dependensi_rundown(): void
    {
        // First add dependency
        $this->resetAuth('sanctum');
        $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
             ->postJson("/api/rundowns/{$this->rundown1Id}/dependencies", [
                 'task_id' => $this->task->id,
             ]);

        // Then remove it
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
                         ->deleteJson("/api/rundowns/{$this->rundown1Id}/dependencies/{$this->task->id}");
        $response->assertStatus(200);
    }
}
