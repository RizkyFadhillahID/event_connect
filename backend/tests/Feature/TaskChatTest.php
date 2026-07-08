<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TaskChatTest extends TestCase
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
                              'name' => 'Test Event', 'description' => 'Desc',
                              'location' => 'Jakarta', 'start_date' => '2026-09-01',
                              'end_date' => '2026-09-03', 'start_time' => '09:00',
                              'end_time' => '18:00', 'status' => 'draft',
                              'budget' => 50000000, 'category' => 'Conference',
                              'expected_participants' => 200,
                              'personnel' => [
                                  ['user_id' => $this->staff1->id, 'role_in_event' => 'coordinator', 'notes' => 'Lead'],
                                  ['user_id' => $this->staff2->id, 'role_in_event' => 'technical_team', 'notes' => 'Tech'],
                              ],
                          ]);
        $this->event = Event::find($eventResp->json('id'));

        // Create task
        $this->resetAuth('sanctum');
        $taskResp = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->postJson('/api/tasks', [
                             'title' => 'Test Task', 'description' => 'Task description',
                             'event_id' => $this->event->id, 'assigned_to' => $this->staff1->id,
                             'priority' => 'high', 'status' => 'pending', 'due_date' => '2026-08-30',
                             'category' => 'technical',
                         ]);
        $this->task = Task::find($taskResp->json('id'));

        // Add a comment on the task
        $this->resetAuth('web');
        $r = $this->postJson('/api/login', ['email' => 'staff1@test.com', 'password' => 'password123']);
        $this->staff1Token = $r->json('token');

        $this->resetAuth('sanctum');
        $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
             ->postJson("/api/tasks/{$this->task->id}/comments", [
                 'comment' => 'Test comment on task.',
             ]);
    }

    // TC-057
    public function test_tc057_lihat_daftar_semua_task(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->getJson('/api/tasks');
        $response->assertStatus(200);
    }

    // TC-058
    public function test_tc058_lihat_detail_task(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->getJson('/api/tasks/' . $this->task->id);
        $response->assertStatus(200)
                 ->assertJsonFragment(['title' => 'Test Task']);
    }

    // TC-059
    public function test_tc059_update_task(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->putJson('/api/tasks/' . $this->task->id, [
                             'title' => 'Updated Task Title',
                             'description' => 'Updated description',
                             'event_id' => $this->event->id,
                             'assigned_to' => $this->staff1->id,
                             'priority' => 'urgent',
                             'status' => 'in_progress',
                             'due_date' => '2026-08-31',
                             'category' => 'technical',
                         ]);
        $response->assertStatus(200)
                 ->assertJsonFragment(['title' => 'Updated Task Title']);
    }

    // TC-060
    public function test_tc060_hapus_task(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->deleteJson('/api/tasks/' . $this->task->id);
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Task berhasil dihapus.']);
    }

    // TC-061
    public function test_tc061_lihat_komentar_task(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
                         ->getJson('/api/tasks/' . $this->task->id . '/comments');
        $response->assertStatus(200);
    }

    // TC-062
    public function test_tc062_lihat_task_per_event(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->getJson('/api/events/' . $this->event->id . '/tasks');
        $response->assertStatus(200);
    }

    // TC-063
    public function test_tc063_lihat_personel_event(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->getJson('/api/events/' . $this->event->id . '/personnel');
        $response->assertStatus(200);
    }

    // TC-064
    public function test_tc064_lihat_pesan_chat_event(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
                         ->getJson('/api/events/' . $this->event->id . '/chat');
        $response->assertStatus(200);
    }

    // TC-065
    public function test_tc065_kirim_pesan_chat_event(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
                         ->postJson('/api/events/' . $this->event->id . '/chat', [
                             'message' => 'Halo tim, persiapan sudah ready?',
                         ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('chat_messages', [
            'event_id' => $this->event->id,
            'message' => 'Halo tim, persiapan sudah ready?',
        ]);
    }
}
