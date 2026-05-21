<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventRundown;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleAccessTest extends TestCase
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

        // Create core users
        $this->superadmin = User::create([
            'name' => 'Test Super Admin',
            'email' => 'superadmin_test@eventconnect.com',
            'phone' => '12345678901',
            'role' => 'superadmin',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        $this->pm = User::create([
            'name' => 'Test PM',
            'email' => 'pm_test@eventconnect.com',
            'phone' => '12345678902',
            'role' => 'project_manager',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        $this->staff1 = User::create([
            'name' => 'Test Staff 1',
            'email' => 'staff1_test@eventconnect.com',
            'phone' => '12345678903',
            'role' => 'staff',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        $this->staff2 = User::create([
            'name' => 'Test Staff 2',
            'email' => 'staff2_test@eventconnect.com',
            'phone' => '12345678904',
            'role' => 'staff',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        // Create an event
        $this->event = Event::create([
            'name' => 'Test Event 2026',
            'description' => 'A test event description.',
            'location' => 'JCC Jakarta',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-17',
            'start_time' => '08:00',
            'end_time' => '22:00',
            'status' => 'active',
            'budget' => 10000000,
            'category' => 'Seminar',
            'expected_participants' => 500,
            'created_by' => $this->pm->id,
        ]);

        // Add staff1 as event personnel (Rundown Coordinator)
        DB::table('event_personnel')->insert([
            'event_id' => $this->event->id,
            'user_id' => $this->staff1->id,
            'role_in_event' => 'Rundown Coordinator',
            'notes' => 'Test Notes',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test UserController role validation (only superadmin, project_manager, staff)
     */
    public function test_user_controller_role_validation(): void
    {
        Sanctum::actingAs($this->superadmin);

        // 1. Success - valid role (staff)
        $response = $this->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'newuser@eventconnect.com',
            'phone' => '08999999999',
            'role' => 'staff',
            'status' => 'active',
            'password' => 'password123',
        ]);
        $response->assertStatus(201);

        // 2. Failure - invalid role (event_planner)
        $response = $this->postJson('/api/users', [
            'name' => 'Invalid Role User',
            'email' => 'invalidrole@eventconnect.com',
            'phone' => '08999999998',
            'role' => 'event_planner',
            'status' => 'active',
            'password' => 'password123',
        ]);
        $response->assertStatus(422);
    }

    /**
     * Test Task assignee restriction: cannot assign task to non-personnel
     */
    public function test_task_assignee_must_be_event_personnel(): void
    {
        Sanctum::actingAs($this->pm);

        // 1. Success - assigning to staff1 (who is registered personnel)
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task 1',
            'description' => 'Description 1',
            'event_id' => $this->event->id,
            'assigned_to' => $this->staff1->id,
            'priority' => 'high',
            'status' => 'pending',
        ]);
        $response->assertStatus(201);

        // 2. Failure - assigning to staff2 (who is NOT personnel)
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task 2',
            'description' => 'Description 2',
            'event_id' => $this->event->id,
            'assigned_to' => $this->staff2->id,
            'priority' => 'high',
            'status' => 'pending',
        ]);
        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'User yang ditugaskan bukan personel event ini.']);
    }

    /**
     * Test Task edit restriction for staff creator:
     * Staff creator can edit description, notes, status, but NOT sensitive metadata (due_date, assigned_to, etc.)
     */
    public function test_task_staff_creator_metadata_edit_restrictions(): void
    {
        // Add staff2 as event personnel (Promotion Lead - non-manager role)
        DB::table('event_personnel')->insert([
            'event_id' => $this->event->id,
            'user_id' => $this->staff2->id,
            'role_in_event' => 'Promotion Lead',
            'notes' => 'Promotion notes',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // staff2 creates a task
        $task = Task::create([
            'title' => 'Original Task Title',
            'description' => 'Original description',
            'event_id' => $this->event->id,
            'assigned_to' => $this->staff1->id,
            'priority' => 'low',
            'status' => 'pending',
            'due_date' => '2026-06-16',
            'created_by' => $this->staff2->id,
        ]);

        Sanctum::actingAs($this->staff2);

        // 1. Success - editing status, description, notes
        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Original Task Title',
            'description' => 'Updated description by staff',
            'notes' => 'New notes added',
            'status' => 'in_progress',
        ]);
        $response->assertStatus(200);
        $this->assertEquals('Updated description by staff', $task->fresh()->description);
        $this->assertEquals('in_progress', $task->fresh()->status);

        // 2. Failure - trying to change sensitive fields like due_date
        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Original Task Title',
            'due_date' => '2026-06-20',
        ]);
        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'Anda tidak memiliki izin untuk mengubah detail utama tugas ini (seperti penerima, tenggat, prioritas).']);

        // 3. Failure - trying to change assignee
        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Original Task Title',
            'assigned_to' => $this->superadmin->id, // not even personnel, but the assignee change itself is blocked
        ]);
        $response->assertStatus(403);
    }

    /**
     * Test Rundown management authorization: rundown coordinator vs non-manager
     */
    public function test_rundown_management_authorization(): void
    {
        // 1. Rundown Coordinator (staff1) can create rundown
        Sanctum::actingAs($this->staff1);

        $response = $this->postJson("/api/events/{$this->event->id}/rundowns", [
            'event_date' => '2026-06-15',
            'title' => 'Opening',
            'category' => 'ceremony',
            'start_time' => '08:00',
            'end_time' => '09:00',
            'order_number' => 1,
        ]);
        $response->assertStatus(201);
        $rundownId = $response->json('data.id');

        // 2. Non-manager (staff2 - who is not even in personnel) cannot delete or manage rundown
        Sanctum::actingAs($this->staff2);
        $response = $this->deleteJson("/api/rundowns/{$rundownId}");
        $response->assertStatus(403);

        // 3. Rundown Coordinator (staff1) can delete rundown
        Sanctum::actingAs($this->staff1);
        $response = $this->deleteJson("/api/rundowns/{$rundownId}");
        $response->assertStatus(200);
    }

    /**
     * Test Rundown status updates: category specific role restrictions
     */
    public function test_rundown_status_update_category_restrictions(): void
    {
        // Create rundown items for technical and talent
        $technicalRundown = EventRundown::create([
            'event_id' => $this->event->id,
            'event_date' => '2026-06-15',
            'title' => 'Soundcheck',
            'category' => 'technical',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'pending',
            'created_by' => $this->superadmin->id,
        ]);

        $talentRundown = EventRundown::create([
            'event_id' => $this->event->id,
            'event_date' => '2026-06-15',
            'title' => 'Talent Briefing',
            'category' => 'talent',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'pending',
            'created_by' => $this->superadmin->id,
        ]);

        // Add staff2 as technical_team personnel
        DB::table('event_personnel')->insert([
            'event_id' => $this->event->id,
            'user_id' => $this->staff2->id,
            'role_in_event' => 'technical_team',
            'notes' => 'Technical team notes',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->staff2);

        // 1. Success - Technical team updates technical category rundown
        $response = $this->patchJson("/api/rundowns/{$technicalRundown->id}/status", [
            'status' => 'ready',
        ]);
        $response->assertStatus(200);
        $this->assertEquals('ready', $technicalRundown->fresh()->status);

        // 2. Failure - Technical team updates talent category rundown
        $response = $this->patchJson("/api/rundowns/{$talentRundown->id}/status", [
            'status' => 'ready',
        ]);
        $response->assertStatus(403);
    }
}
