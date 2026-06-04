<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventRundown;
use App\Models\Task;
use App\Models\User;
use App\Models\TaskComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComprehensiveCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $pm;
    private User $staff1;
    private User $staff2;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $orgId = DB::table('organizations')->where('slug', 'default')->value('id');

        // Create standard test users
        $this->superadmin = User::create([
            'organization_id' => $orgId,
            'name' => 'Super Admin Test',
            'email' => 'superadmin@eventconnect.com',
            'phone' => '08111111111',
            'role' => 'superadmin',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        $this->pm = User::create([
            'organization_id' => $orgId,
            'name' => 'Project Manager Test',
            'email' => 'pm@eventconnect.com',
            'phone' => '08222222222',
            'role' => 'project_manager',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        $this->staff1 = User::create([
            'organization_id' => $orgId,
            'name' => 'Staff One Test',
            'email' => 'staff1@eventconnect.com',
            'phone' => '08333333333',
            'role' => 'staff',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        $this->staff2 = User::create([
            'organization_id' => $orgId,
            'name' => 'Staff Two Test',
            'email' => 'staff2@eventconnect.com',
            'phone' => '08444444444',
            'role' => 'staff',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        // Create a standard test event
        $this->event = Event::create([
            'organization_id' => $orgId,
            'name' => 'Annual Gala Night 2026',
            'description' => 'Gala celebration event.',
            'location' => 'Grand Ballroom Mulia',
            'start_date' => '2026-12-10',
            'end_date' => '2026-12-12',
            'start_time' => '17:00',
            'end_time' => '23:00',
            'status' => 'active',
            'budget' => 50000000,
            'category' => 'Gala',
            'expected_participants' => 300,
            'created_by' => $this->pm->id,
        ]);

        // Register staff1 as event personnel (Rundown Coordinator)
        DB::table('event_personnel')->insert([
            'event_id' => $this->event->id,
            'user_id' => $this->staff1->id,
            'role_in_event' => 'Rundown Coordinator',
            'notes' => 'Head of rundown planning',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // =========================================================================
    // 1. AUTHENTICATION MODULE TESTS
    // =========================================================================

    public function test_authentication_flow(): void
    {
        // A. Login success
        $response = $this->postJson('/api/login', [
            'email' => 'superadmin@eventconnect.com',
            'password' => 'password123',
        ]);
        $response->assertStatus(200)
                 ->assertJsonStructure(['user', 'token']);

        // B. Login failure - wrong password
        $response = $this->postJson('/api/login', [
            'email' => 'superadmin@eventconnect.com',
            'password' => 'wrongpassword',
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);

        // C. Login failure - inactive account
        $this->staff1->update(['status' => 'inactive']);
        $response = $this->postJson('/api/login', [
            'email' => 'staff1@eventconnect.com',
            'password' => 'password123',
        ]);
        $response->assertStatus(403)
                 ->assertJsonFragment(['message' => 'Akun Anda tidak aktif.']);
        
        // Restore staff1 to active
        $this->staff1->update(['status' => 'active']);

        // D. Get currently logged in user profile (me)
        Sanctum::actingAs($this->superadmin);
        $response = $this->getJson('/api/me');
        $response->assertStatus(200)
                 ->assertJsonFragment(['email' => 'superadmin@eventconnect.com']);

        // E. Logout
        $response = $this->postJson('/api/logout');
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Berhasil logout.']);
    }

    // =========================================================================
    // 2. USERS MANAGEMENT MODULE TESTS (Superadmin only)
    // =========================================================================

    public function test_users_crud_as_superadmin(): void
    {
        Sanctum::actingAs($this->superadmin);

        // A. Create User (Valid)
        $response = $this->postJson('/api/users', [
            'name' => 'New Staff Member',
            'email' => 'newstaff@eventconnect.com',
            'phone' => '08999999912',
            'role' => 'staff',
            'status' => 'active',
            'password' => 'securePassword123',
        ]);
        $response->assertStatus(201)
                 ->assertJsonFragment(['email' => 'newstaff@eventconnect.com']);
        $newUserId = $response->json('id');

        // B. Create User (Invalid role validation)
        $response = $this->postJson('/api/users', [
            'name' => 'Illegal Role User',
            'email' => 'illegal@eventconnect.com',
            'role' => 'event_planner', // old legacy role, now rejected
            'status' => 'active',
            'password' => 'securePassword123',
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['role']);

        // C. List Users (Index & Filter & Search)
        $response = $this->getJson('/api/users?search=Staff&role=staff');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'current_page', 'total']);

        // D. Show User details
        $response = $this->getJson("/api/users/{$newUserId}");
        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'New Staff Member']);

        // E. Update User details
        $response = $this->putJson("/api/users/{$newUserId}", [
            'name' => 'New Staff Member Updated',
            'phone' => '08888888888',
        ]);
        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'New Staff Member Updated', 'phone' => '08888888888']);

        // F. Delete other user
        $response = $this->deleteJson("/api/users/{$newUserId}");
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'User berhasil dihapus.']);

        // G. Try to delete self (fails)
        $response = $this->deleteJson("/api/users/{$this->superadmin->id}");
        $response->assertStatus(422)
                 ->assertJsonFragment(['message' => 'Tidak dapat menghapus akun sendiri.']);
    }

    public function test_users_crud_unauthorized_for_non_superadmin(): void
    {
        // Acting as PM
        Sanctum::actingAs($this->pm);
        $response = $this->getJson('/api/users');
        $response->assertStatus(403);

        $response = $this->postJson('/api/users', [
            'name' => 'Unauthorized User',
            'email' => 'unauth@eventconnect.com',
            'role' => 'staff',
            'status' => 'active',
            'password' => 'password123',
        ]);
        $response->assertStatus(403);
    }

    // =========================================================================
    // 3. EVENTS MANAGEMENT MODULE TESTS (PM & Superadmin)
    // =========================================================================

    public function test_events_crud(): void
    {
        Sanctum::actingAs($this->pm);

        // A. Create Event (Valid with personnel)
        $response = $this->postJson('/api/events', [
            'name' => 'Product Launch Expo 2026',
            'description' => 'New product launch event.',
            'location' => 'JIExpo Kemayoran',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-03',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'status' => 'draft',
            'budget' => 250000000,
            'category' => 'Expo',
            'expected_participants' => 1000,
            'personnel' => [
                [
                    'user_id' => $this->staff1->id,
                    'role_in_event' => 'Logistics Head',
                    'notes' => 'Manage booth setup',
                ]
            ],
        ]);
        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'Product Launch Expo 2026']);
        $eventId = $response->json('id');

        // B. Create Event (Invalid: end date before start date)
        $response = $this->postJson('/api/events', [
            'name' => 'Broken Event',
            'location' => 'Online',
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-05',
            'status' => 'draft',
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['end_date']);

        // C. List Events
        $response = $this->getJson('/api/events?search=Launch&status=draft');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'total']);

        // D. Show Event details
        $response = $this->getJson("/api/events/{$eventId}");
        $response->assertStatus(200)
                 ->assertJsonFragment(['location' => 'JIExpo Kemayoran']);

        // E. Update Event (with synced personnel changes)
        $response = $this->putJson("/api/events/{$eventId}", [
            'location' => 'Grand Indonesia Hall',
            'personnel' => [
                [
                    'user_id' => $this->staff1->id,
                    'role_in_event' => 'Logistics Chief',
                    'notes' => 'Updated notes',
                ],
                [
                    'user_id' => $this->staff2->id,
                    'role_in_event' => 'Registration Lead',
                    'notes' => 'Handle registrations',
                ]
            ]
        ]);
        $response->assertStatus(200)
                 ->assertJsonFragment(['location' => 'Grand Indonesia Hall']);
        
        $this->assertEquals(2, DB::table('event_personnel')->where('event_id', $eventId)->count());

        // F. Delete Event (Create temp event then delete it)
        $tempEvent = Event::create([
            'organization_id' => $this->pm->organization_id,
            'name' => 'Temp Event',
            'location' => 'Temp Office',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
            'status' => 'draft',
            'created_by' => $this->pm->id,
        ]);
        $response = $this->deleteJson("/api/events/{$tempEvent->id}");
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Event berhasil dihapus.']);

        // G. Fetch all active users list for dropdown mapping
        $response = $this->getJson('/api/users-list');
        $response->assertStatus(200)
                 ->assertJsonStructure([['id', 'name', 'role', 'status']]);
    }

    public function test_events_crud_unauthorized_for_staff(): void
    {
        Sanctum::actingAs($this->staff1);

        // Staff can list and show events
        $response = $this->getJson('/api/events');
        $response->assertStatus(200);

        $response = $this->getJson("/api/events/{$this->event->id}");
        $response->assertStatus(200);

        // Staff cannot create events
        $response = $this->postJson('/api/events', [
            'name' => 'Gala Part 2',
            'location' => 'Mulia',
            'start_date' => '2026-12-13',
            'end_date' => '2026-12-14',
            'status' => 'draft',
        ]);
        $response->assertStatus(403);
    }

    // =========================================================================
    // 4. TASKS MANAGEMENT MODULE TESTS
    // =========================================================================

    public function test_tasks_crud(): void
    {
        Sanctum::actingAs($this->pm);

        // A. Create Task (Valid - assignee staff1 is personnel of the event)
        $response = $this->postJson('/api/tasks', [
            'title' => 'Set up Sound System',
            'description' => 'Deploy speakers and wireless microphones.',
            'event_id' => $this->event->id,
            'assigned_to' => $this->staff1->id,
            'priority' => 'high',
            'status' => 'pending',
            'due_date' => '2026-12-09',
            'category' => 'technical',
        ]);
        $response->assertStatus(201)
                 ->assertJsonFragment(['title' => 'Set up Sound System']);
        $taskId = $response->json('id');

        // B. Create Task (Invalid - assignee staff2 is NOT personnel of the event)
        $response = $this->postJson('/api/tasks', [
            'title' => 'Prepare catering',
            'description' => 'Select gala catering vendor.',
            'event_id' => $this->event->id,
            'assigned_to' => $this->staff2->id,
            'priority' => 'medium',
            'status' => 'pending',
        ]);
        $response->assertStatus(422)
                 ->assertJsonFragment(['message' => 'User yang ditugaskan bukan personel event ini.']);

        // C. List Tasks
        $response = $this->getJson('/api/tasks?priority=high&search=Sound');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'total']);

        // D. Show Task details
        $response = $this->getJson("/api/tasks/{$taskId}");
        $response->assertStatus(200)
                 ->assertJsonFragment(['title' => 'Set up Sound System']);

        // E. Update Task details (As PM - managers can edit anything)
        $response = $this->putJson("/api/tasks/{$taskId}", [
            'title' => 'Set up Sound System Pro',
            'priority' => 'urgent',
            'due_date' => '2026-12-10',
        ]);
        $response->assertStatus(200)
                 ->assertJsonFragment(['title' => 'Set up Sound System Pro', 'priority' => 'urgent']);

        // F. Quick status update (Change status to in_progress)
        $response = $this->patchJson("/api/tasks/{$taskId}/status", [
            'status' => 'in_progress',
        ]);
        $response->assertStatus(200)
                 ->assertJsonFragment(['status' => 'in_progress']);

        // G. Add comment
        $response = $this->postJson("/api/tasks/{$taskId}/comments", [
            'comment' => 'Checking audio drivers.',
        ]);
        $response->assertStatus(201)
                 ->assertJsonFragment(['comment' => 'Checking audio drivers.']);

        // H. Get comments
        $response = $this->getJson("/api/tasks/{$taskId}/comments");
        $response->assertStatus(200)
                 ->assertJsonStructure([['id', 'comment', 'user']]);

        // I. Get "My Tasks"
        Sanctum::actingAs($this->staff1);
        $response = $this->getJson('/api/my-tasks');
        $response->assertStatus(200)
                 ->assertJsonStructure([['id', 'title', 'event', 'creator']]);

        // J. Event Specific Tasks & Stats
        $response = $this->getJson("/api/events/{$this->event->id}/tasks");
        $response->assertStatus(200)
                 ->assertJsonStructure(['tasks', 'stats']);

        // K. Event Specific Personnel
        $response = $this->getJson("/api/events/{$this->event->id}/personnel");
        $response->assertStatus(200)
                 ->assertJsonStructure([['id', 'name', 'role', 'role_in_event']]);

        // L. Delete Task (PM only)
        Sanctum::actingAs($this->pm);
        $response = $this->deleteJson("/api/tasks/{$taskId}");
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Task berhasil dihapus.']);
    }

    public function test_tasks_scoping_and_staff_creator_limitations(): void
    {
        // 1. Add staff2 as personnel so they have access to event
        DB::table('event_personnel')->insert([
            'event_id' => $this->event->id,
            'user_id' => $this->staff2->id,
            'role_in_event' => 'Logistics Staff', // non-manager role
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create task created by staff2
        $task = Task::create([
            'title' => 'Staff Task',
            'description' => 'Created by staff',
            'event_id' => $this->event->id,
            'assigned_to' => $this->staff2->id,
            'priority' => 'low',
            'status' => 'pending',
            'created_by' => $this->staff2->id,
            'due_date' => '2026-12-10',
        ]);

        Sanctum::actingAs($this->staff2);

        // A. Staff creator CAN edit description and notes
        $response = $this->putJson("/api/tasks/{$task->id}", [
            'description' => 'Updated desc by staff',
            'notes' => 'Additional notes',
            'status' => 'in_progress',
        ]);
        $response->assertStatus(200);
        $this->assertEquals('Updated desc by staff', $task->fresh()->description);

        // B. Staff creator CANNOT edit metadata such as due_date or priority
        $response = $this->putJson("/api/tasks/{$task->id}", [
            'priority' => 'urgent',
        ]);
        $response->assertStatus(403)
                 ->assertJsonFragment(['message' => 'Anda tidak memiliki izin untuk mengubah detail utama tugas ini (seperti penerima, tenggat, prioritas).']);
    }

    // =========================================================================
    // 5. JADWAL ACARA & RUNDOWN TIMELINE MODULE TESTS
    // =========================================================================

    public function test_rundowns_crud(): void
    {
        Sanctum::actingAs($this->staff1); // Rundown Coordinator

        // A. Create Rundown Item (Valid - PIC staff1 is personnel of the event)
        $response = $this->postJson("/api/events/{$this->event->id}/rundowns", [
            'event_date' => '2026-12-10',
            'title' => 'VIP Welcoming Ceremony',
            'description' => 'Red carpet welcoming.',
            'category' => 'ceremony',
            'start_time' => '17:00',
            'end_time' => '18:00',
            'pic_id' => $this->staff1->id,
            'location_note' => 'Ballroom Entrance',
            'notes' => 'Requires media crew',
            'order_number' => 1,
        ]);
        $response->assertStatus(201)
                 ->assertJsonFragment(['title' => 'VIP Welcoming Ceremony']);
        $rundownId = $response->json('data.id');

        // B. Create Rundown Item (Invalid - PIC staff2 is NOT personnel of the event yet)
        $response = $this->postJson("/api/events/{$this->event->id}/rundowns", [
            'event_date' => '2026-12-10',
            'title' => 'VIP Dinner Reception',
            'category' => 'ceremony',
            'start_time' => '18:00',
            'end_time' => '19:00',
            'pic_id' => $this->staff2->id, // staff2 is not in event personnel
        ]);
        $response->assertStatus(422)
                 ->assertJsonFragment(['message' => 'PIC harus terdaftar sebagai personel event.']);

        // C. List Rundowns
        $response = $this->getJson("/api/events/{$this->event->id}/rundowns?category=ceremony");
        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'dates']);

        // D. Show Rundown details
        $response = $this->getJson("/api/rundowns/{$rundownId}");
        $response->assertStatus(200)
                 ->assertJsonFragment(['title' => 'VIP Welcoming Ceremony']);

        // E. Update Rundown item details
        $response = $this->putJson("/api/rundowns/{$rundownId}", [
            'location_note' => 'Main Gate Red Carpet',
        ]);
        $response->assertStatus(200)
                 ->assertJsonFragment(['location_note' => 'Main Gate Red Carpet']);

        // F. Add Dependency Task
        // Create a quick task for dependency
        $task = Task::create([
            'title' => 'Stage check',
            'event_id' => $this->event->id,
            'priority' => 'low',
            'status' => 'pending',
            'created_by' => $this->pm->id,
        ]);

        $response = $this->postJson("/api/rundowns/{$rundownId}/dependencies", [
            'task_id' => $task->id,
        ]);
        $response->assertStatus(200)
                 ->assertJsonFragment(['title' => 'Stage check']);

        // G. Remove Dependency Task
        $response = $this->deleteJson("/api/rundowns/{$rundownId}/dependencies/{$task->id}");
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Dependency berhasil dihapus.']);

        // H. Get Event Dates & stats
        $response = $this->getJson("/api/events/{$this->event->id}/rundown-dates");
        $response->assertStatus(200)
                 ->assertJsonFragment(['data' => ['2026-12-10']]);

        $response = $this->getJson("/api/events/{$this->event->id}/rundown-stats");
        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => ['total', 'pending', 'ready', 'live', 'delayed', 'completed', 'progress']]);

        // I. Fetch Rundown Audit Logs
        $response = $this->getJson("/api/rundowns/{$rundownId}/logs");
        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['id', 'action', 'new_status', 'user']]]);

        // J. Delete Rundown Item
        $response = $this->deleteJson("/api/rundowns/{$rundownId}");
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Rundown item berhasil dihapus.']);
    }

    public function test_rundown_status_category_restrictions_and_audit(): void
    {
        // 1. Add staff2 as event personnel with 'technical_team' role
        DB::table('event_personnel')->insert([
            'event_id' => $this->event->id,
            'user_id' => $this->staff2->id,
            'role_in_event' => 'technical_team',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create 'technical' and 'talent' rundown items
        $techRundown = EventRundown::create([
            'event_id' => $this->event->id,
            'event_date' => '2026-12-10',
            'title' => 'Soundcheck technical',
            'category' => 'technical',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'status' => 'pending',
            'created_by' => $this->pm->id,
        ]);

        $talentRundown = EventRundown::create([
            'event_id' => $this->event->id,
            'event_date' => '2026-12-10',
            'title' => 'Talent briefing stage',
            'category' => 'talent',
            'start_time' => '13:00',
            'end_time' => '14:00',
            'status' => 'pending',
            'created_by' => $this->pm->id,
        ]);

        Sanctum::actingAs($this->staff2);

        // A. Technical team CAN update technical rundown status
        $response = $this->patchJson("/api/rundowns/{$techRundown->id}/status", [
            'status' => 'ready',
        ]);
        $response->assertStatus(200)
                 ->assertJsonFragment(['status' => 'ready']);
        
        $this->assertEquals('ready', $techRundown->fresh()->status);

        // Check if logs are recorded
        $this->assertDatabaseHas('rundown_logs', [
            'rundown_id' => $techRundown->id,
            'action' => 'status_changed',
            'old_status' => 'pending',
            'new_status' => 'ready',
            'user_id' => $this->staff2->id,
        ]);

        // B. Technical team CANNOT update talent rundown status
        $response = $this->patchJson("/api/rundowns/{$talentRundown->id}/status", [
            'status' => 'ready',
        ]);
        $response->assertStatus(403)
                 ->assertJsonFragment(['message' => 'Anda tidak memiliki izin untuk mengubah status item ini.']);
    }
}
