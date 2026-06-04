<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventBudgetAllocation;
use App\Models\EventExpense;
use App\Models\EventLogistic;
use App\Models\EventReport;
use App\Models\EventRundown;
use App\Models\Guest;
use App\Models\Inventory;
use App\Models\Organization;
use App\Models\PlatformAdmin;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EndToEndFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Clear headers and force the Auth manager to forget resolved guards.
     * This prevents user credentials/session caching across sequential requests in a single test.
     */
    private function resetAuth(string $defaultGuard = 'web'): void
    {
        $this->flushHeaders();
        Auth::guard('web')->logout();
        Auth::forgetGuards();
        Auth::shouldUse($defaultGuard);
    }

    public function test_complete_saas_lifecycle_flow(): void
    {
        // =========================================================================
        // STAGE 1: Public Organization Registration (Plan: Business -> Pending approval)
        // =========================================================================
        $registerPayload = [
            'name'           => 'Awesome Event Organizer',
            'email'          => 'awesome@example.com',
            'phone'          => '08123456789',
            'address'        => 'Sudirman Suite Lt. 15, Jakarta',
            'admin_name'     => 'Awesome EO Admin',
            'admin_email'    => 'awesomeadmin@example.com',
            'admin_password' => 'awesome_password',
            'plan'           => 'business',
        ];

        $response = $this->postJson('/api/landing/register', $registerPayload);
        $response->assertStatus(201)
                 ->assertJsonStructure(['message', 'organization']);

        $orgId = $response->json('organization.id');
        $this->assertNotNull($orgId);

        // Verify organization is pending in database
        $this->assertDatabaseHas('organizations', [
            'id'     => $orgId,
            'name'   => 'Awesome Event Organizer',
            'plan'   => 'business',
            'status' => 'pending',
        ]);

        // Attempting to login as the new EO Admin should fail because organization is pending approval
        $this->resetAuth('web');
        $loginResponse = $this->postJson('/api/login', [
            'email'    => 'awesomeadmin@example.com',
            'password' => 'awesome_password',
        ]);
        $loginResponse->assertStatus(403)
                     ->assertJsonFragment(['message' => 'Pendaftaran organisasi Anda (Paket Business) sedang menunggu verifikasi pembayaran dan persetujuan Admin Backoffice.']);

        // =========================================================================
        // STAGE 2: Platform Backoffice Admin Login & Approval
        // =========================================================================
        // Setup a backoffice admin
        $platformAdmin = PlatformAdmin::create([
            'name'     => 'Platform Owner',
            'email'    => 'admin@eventconnect.com',
            'password' => Hash::make('password123'),
            'role'     => 'owner',
            'status'   => 'active',
        ]);

        // Platform Admin logs in
        $this->resetAuth('web');
        $boLoginResponse = $this->postJson('/api/backoffice/login', [
            'email'    => 'admin@eventconnect.com',
            'password' => 'password123',
        ]);
        $boLoginResponse->assertStatus(200)
                        ->assertJsonStructure(['admin', 'token']);
        $boToken = $boLoginResponse->json('token');

        // Approve the organization
        $this->resetAuth('platform');
        $approveResponse = $this->withHeader('Authorization', 'Bearer ' . $boToken)
                                ->patchJson("/api/backoffice/organizations/{$orgId}/status", [
                                    'status' => 'active',
                                ]);
        $approveResponse->assertStatus(200)
                        ->assertJsonFragment(['status' => 'active']);

        // Verify organization is active in database
        $this->assertEquals('active', Organization::find($orgId)->status);

        // =========================================================================
        // STAGE 3: EO Admin Login
        // =========================================================================
        $this->resetAuth('web');
        $eoLoginResponse = $this->postJson('/api/login', [
            'email'    => 'awesomeadmin@example.com',
            'password' => 'awesome_password',
        ]);
        $eoLoginResponse->assertStatus(200)
                        ->assertJsonStructure(['user', 'token']);
        $eoAdminToken = $eoLoginResponse->json('token');
        $eoAdminUser = User::where('email', 'awesomeadmin@example.com')->first();

        // =========================================================================
        // STAGE 4: Team Registration (User Management)
        // =========================================================================
        // Create Project Manager
        $this->resetAuth('sanctum');
        $pmResponse = $this->withHeader('Authorization', 'Bearer ' . $eoAdminToken)
                           ->postJson('/api/users', [
                               'name'     => 'Awesome PM',
                               'email'    => 'awesomepm@example.com',
                               'phone'    => '08222222222',
                               'role'     => 'project_manager',
                               'status'   => 'active',
                               'password' => 'pm_password',
                           ]);
        $pmResponse->assertStatus(201);
        $pmId = $pmResponse->json('id');

        // Create Staff 1
        $this->resetAuth('sanctum');
        $staff1Response = $this->withHeader('Authorization', 'Bearer ' . $eoAdminToken)
                               ->postJson('/api/users', [
                                   'name'     => 'Awesome Staff 1',
                                   'email'    => 'awesomestaff1@example.com',
                                   'phone'    => '08333333333',
                                   'role'     => 'staff',
                                   'status'   => 'active',
                                   'password' => 'staff_password',
                               ]);
        $staff1Response->assertStatus(201);
        $staff1Id = $staff1Response->json('id');

        // Create Staff 2
        $this->resetAuth('sanctum');
        $staff2Response = $this->withHeader('Authorization', 'Bearer ' . $eoAdminToken)
                               ->postJson('/api/users', [
                                   'name'     => 'Awesome Staff 2',
                                   'email'    => 'awesomestaff2@example.com',
                                   'phone'    => '08444444444',
                                   'role'     => 'staff',
                                   'status'   => 'active',
                                   'password' => 'staff_password',
                               ]);
        $staff2Response->assertStatus(201);
        $staff2Id = $staff2Response->json('id');

        // Create a 3rd Staff (which will NOT be personnel in our event to test validation bounds)
        $this->resetAuth('sanctum');
        $staff3Response = $this->withHeader('Authorization', 'Bearer ' . $eoAdminToken)
                               ->postJson('/api/users', [
                                   'name'     => 'Awesome Staff 3',
                                   'email'    => 'awesomestaff3@example.com',
                                   'phone'    => '08555555555',
                                   'role'     => 'staff',
                                   'status'   => 'active',
                                   'password' => 'staff_password',
                               ]);
        $staff3Response->assertStatus(201);
        $staff3Id = $staff3Response->json('id');

        // =========================================================================
        // STAGE 5: Login as PM
        // =========================================================================
        $this->resetAuth('web');
        $pmLoginResponse = $this->postJson('/api/login', [
            'email'    => 'awesomepm@example.com',
            'password' => 'pm_password',
        ]);
        $pmLoginResponse->assertStatus(200)
                        ->assertJsonStructure(['user', 'token']);
        $pmToken = $pmLoginResponse->json('token');

        // =========================================================================
        // STAGE 6: Event Planning & Personnel Assignment
        // =========================================================================
        $eventPayload = [
            'name'                 => 'Annual Grand Tech Expo 2026',
            'description'          => 'Major showcase event.',
            'location'             => 'JIExpo Kemayoran',
            'start_date'           => '2026-08-01',
            'end_date'             => '2026-08-03',
            'start_time'           => '09:00',
            'end_time'             => '18:00',
            'status'               => 'draft',
            'budget'               => 150000000,
            'category'             => 'Expo',
            'expected_participants' => 1500,
            'personnel'            => [
                [
                    'user_id'       => $staff1Id,
                    'role_in_event' => 'Event Coordinator',
                    'notes'         => 'Rundown coordination head.',
                ],
                [
                    'user_id'       => $staff2Id,
                    'role_in_event' => 'technical_team',
                    'notes'         => 'Sound and stage setup.',
                ],
            ],
        ];

        $this->resetAuth('sanctum');
        $eventResponse = $this->withHeader('Authorization', 'Bearer ' . $pmToken)
                              ->postJson('/api/events', $eventPayload);
        $eventResponse->assertStatus(201)
                      ->assertJsonFragment(['name' => 'Annual Grand Tech Expo 2026']);
        $eventId = $eventResponse->json('id');
        $this->resetAuth('web');
        $s1LoginResponse = $this->postJson('/api/login', [
            'email'    => 'awesomestaff1@example.com',
            'password' => 'staff_password',
        ]);
        $s1Token = $s1LoginResponse->json('token');

        $this->resetAuth('web');
        $s2LoginResponse = $this->postJson('/api/login', [
            'email'    => 'awesomestaff2@example.com',
            'password' => 'staff_password',
        ]);
        $s2Token = $s2LoginResponse->json('token');

        // =========================================================================
        // STAGE 7: Event Rundown Setup & RBAC Verification
        // =========================================================================
        // Staff 1 (Event Coordinator) creates a VIP opening ceremony rundown item
        $this->resetAuth('sanctum');
        $rundown1Response = $this->withHeader('Authorization', 'Bearer ' . $s1Token)
                                 ->postJson("/api/events/{$eventId}/rundowns", [
                                     'event_date'    => '2026-08-01',
                                     'title'         => 'VIP Opening Ceremony',
                                     'description'   => 'Grand opening speech.',
                                     'category'      => 'ceremony',
                                     'start_time'    => '09:00',
                                     'end_time'      => '10:00',
                                     'pic_id'        => $staff1Id,
                                     'location_note' => 'Main Stage',
                                     'notes'         => 'Ribbon cutting required.',
                                     'order_number'  => 1,
                                 ]);
        $rundown1Response->assertStatus(201);
        $ceremonyRundownId = $rundown1Response->json('data.id');

        // Staff 1 (Event Coordinator) creates a technical rundown item (with Staff 2 as PIC)
        $this->resetAuth('sanctum');
        $rundown2Response = $this->withHeader('Authorization', 'Bearer ' . $s1Token)
                                 ->postJson("/api/events/{$eventId}/rundowns", [
                                     'event_date'    => '2026-08-01',
                                     'title'         => 'Audio Sound Check',
                                     'description'   => 'Speaker and mic validation.',
                                     'category'      => 'technical',
                                     'start_time'    => '08:00',
                                     'end_time'      => '09:00',
                                     'pic_id'        => $staff2Id,
                                     'location_note' => 'Main Stage Console',
                                     'notes'         => 'Check sound pressure levels.',
                                     'order_number'  => 2,
                                 ]);
        $rundown2Response->assertStatus(201);
        $techRundownId = $rundown2Response->json('data.id');

        // RBAC CHECK 1: Staff 2 (technical_team) attempts to change ceremony rundown status (SHOULD FAIL WITH 403)
        $this->resetAuth('sanctum');
        $badUpdateResponse = $this->withHeader('Authorization', 'Bearer ' . $s2Token)
                                  ->patchJson("/api/rundowns/{$ceremonyRundownId}/status", [
                                      'status' => 'ready',
                                  ]);
        $badUpdateResponse->assertStatus(403)
                          ->assertJsonFragment(['message' => 'Anda tidak memiliki izin untuk mengubah status item ini.']);

        // RBAC CHECK 2: Staff 2 (technical_team) updates technical rundown status to 'ready' (SHOULD SUCCEED WITH 200)
        $this->resetAuth('sanctum');
        $goodUpdateResponse = $this->withHeader('Authorization', 'Bearer ' . $s2Token)
                                   ->patchJson("/api/rundowns/{$techRundownId}/status", [
                                       'status' => 'ready',
                                   ]);
        $goodUpdateResponse->assertStatus(200)
                           ->assertJsonFragment(['status' => 'ready']);

        // =========================================================================
        // STAGE 8: Task Creation, Status Updates & Comments Flow
        // =========================================================================
        // PM creates a task assigned to Staff 1
        $this->resetAuth('sanctum');
        $taskResponse = $this->withHeader('Authorization', 'Bearer ' . $pmToken)
                             ->postJson('/api/tasks', [
                                 'title'       => 'Setup Main Stage Speakers',
                                 'description' => 'Deploy speakers and wire connections.',
                                 'event_id'    => $eventId,
                                 'assigned_to' => $staff1Id,
                                 'priority'    => 'high',
                                 'status'      => 'pending',
                                 'due_date'    => '2026-07-31',
                                 'category'    => 'technical',
                             ]);
        $taskResponse->assertStatus(201);
        $taskId = $taskResponse->json('id');

        // Validation test: PM attempts to assign a task to Staff 3 (who is not event personnel) -> should fail with 422
        $this->resetAuth('sanctum');
        $invalidTaskResponse = $this->withHeader('Authorization', 'Bearer ' . $pmToken)
                                    ->postJson('/api/tasks', [
                                        'title'       => 'Event Logistic Oversight',
                                        'description' => 'Verify arrivals.',
                                        'event_id'    => $eventId,
                                        'assigned_to' => $staff3Id,
                                        'priority'    => 'medium',
                                        'status'      => 'pending',
                                    ]);
        $invalidTaskResponse->assertStatus(422)
                            ->assertJsonFragment(['message' => 'User yang ditugaskan bukan personel event ini.']);

        // Staff 1 checks their tasks
        $this->resetAuth('sanctum');
        $myTasksResponse = $this->withHeader('Authorization', 'Bearer ' . $s1Token)
                                ->getJson('/api/my-tasks');
        $myTasksResponse->assertStatus(200);
        $this->assertTrue(collect($myTasksResponse->json())->contains('id', $taskId));

        // Staff 1 updates task to in_progress
        $this->resetAuth('sanctum');
        $this->withHeader('Authorization', 'Bearer ' . $s1Token)
             ->patchJson("/api/tasks/{$taskId}/status", ['status' => 'in_progress'])
             ->assertStatus(200)
             ->assertJsonFragment(['status' => 'in_progress']);

        // Staff 1 updates task to completed
        $this->resetAuth('sanctum');
        $this->withHeader('Authorization', 'Bearer ' . $s1Token)
             ->patchJson("/api/tasks/{$taskId}/status", ['status' => 'completed'])
             ->assertStatus(200)
             ->assertJsonFragment(['status' => 'completed']);

        // Staff 1 comments on the task
        $this->resetAuth('sanctum');
        $commentResponse = $this->withHeader('Authorization', 'Bearer ' . $s1Token)
                                ->postJson("/api/tasks/{$taskId}/comments", [
                                    'comment' => 'Mounted speakers and successfully tested sound check routing.',
                                ]);
        $commentResponse->assertStatus(201);

        // =========================================================================
        // STAGE 9: Budget Allocation & Expense Submission
        // =========================================================================
        // PM creates a budget allocation
        $this->resetAuth('sanctum');
        $allocResponse = $this->withHeader('Authorization', 'Bearer ' . $pmToken)
                              ->postJson("/api/events/{$eventId}/budget/allocations", [
                                  'category'         => 'Logistik & Ops',
                                  'allocated_amount' => 15000000,
                                  'notes'            => 'Stage operations and audio gear hire.',
                              ]);
        $allocResponse->assertStatus(201)
                      ->assertJsonFragment(['category' => 'Logistik & Ops']);

        // Staff 1 logs an actual expense against the allocation
        $this->resetAuth('sanctum');
        $expenseResponse = $this->withHeader('Authorization', 'Bearer ' . $s1Token)
                                ->postJson("/api/events/{$eventId}/budget/expenses", [
                                    'title'          => 'Stage wiring and backup cables',
                                    'category'       => 'Logistik & Ops',
                                    'amount'         => 3500000,
                                    'vendor_name'    => 'Glodok Sound Mart',
                                    'payment_method' => 'cash',
                                    'payment_status' => 'paid',
                                    'spent_at'       => '2026-07-30',
                                    'notes'          => 'Emergency cable replacements.',
                                ]);
        $expenseResponse->assertStatus(201)
                        ->assertJsonFragment(['amount' => '3500000.00']);

        // =========================================================================
        // STAGE 10: Logistics Inventory Setup, Checkout & Return Flow
        // =========================================================================
        // PM adds item to global warehouse catalog
        $this->resetAuth('sanctum');
        $invResponse = $this->withHeader('Authorization', 'Bearer ' . $pmToken)
                            ->postJson('/api/inventories', [
                                'item_name'      => 'Sennheiser Wireless Mic EW100',
                                'serial_number'  => 'SEN-EW100-88',
                                'total_quantity' => 10,
                                'ownership'      => 'owned',
                                'status'         => 'ready',
                                'notes'          => 'Vocal wireless system.',
                            ]);
        $invResponse->assertStatus(201);
        $inventoryId = $invResponse->json('id');

        // Staff 1 deploys (checks out) 4 units to the Event
        $this->resetAuth('sanctum');
        $checkoutResponse = $this->withHeader('Authorization', 'Bearer ' . $s1Token)
                                 ->postJson("/api/events/{$eventId}/logistics", [
                                     'inventory_id' => $inventoryId,
                                     'user_id'      => $staff1Id,
                                     'quantity'     => 4,
                                     'borrowed_at'  => '2026-07-31',
                                     'notes'        => 'For stage microphones setup.',
                                 ]);
        $checkoutResponse->assertStatus(201);
        $logisticId = $checkoutResponse->json('id');

        // Verify stock in warehouse decreased
        $this->assertEquals(6, Inventory::find($inventoryId)->available_quantity);

        // Staff 1 returns the items back to the warehouse
        $this->resetAuth('sanctum');
        $returnResponse = $this->withHeader('Authorization', 'Bearer ' . $s1Token)
                               ->postJson("/api/events/{$eventId}/logistics/{$logisticId}/return", [
                                   'returned_at'   => '2026-08-04',
                                   'return_status' => 'complete',
                                   'notes'         => 'Returned safe and sound.',
                               ]);
        $returnResponse->assertStatus(200);

        // Verify stock in warehouse returned to normal
        $this->assertEquals(10, Inventory::find($inventoryId)->available_quantity);

        // =========================================================================
        // STAGE 11: Guest Registration & Check-in
        // =========================================================================
        // PM registers guest list
        $this->resetAuth('sanctum');
        $guestResponse = $this->withHeader('Authorization', 'Bearer ' . $pmToken)
                              ->postJson("/api/events/{$eventId}/guests", [
                                  'name'        => 'Prof. John Doe',
                                  'email'       => 'johndoe@example.com',
                                  'phone'       => '0899998888',
                                  'category'    => 'vvip',
                                  'rsvp_status' => 'attending',
                              ]);
        $guestResponse->assertStatus(201);
        $guestId = $guestResponse->json('id');

        // Staff 1 marks the guest as checked in
        $this->resetAuth('sanctum');
        $checkinResponse = $this->withHeader('Authorization', 'Bearer ' . $s1Token)
                                ->patchJson("/api/events/{$eventId}/guests/{$guestId}/checkin", [
                                    'checkin_status' => true,
                                ]);
        $checkinResponse->assertStatus(200);
        
        // Assert checked in
        $this->assertTrue((bool)Guest::find($guestId)->checkin_status);

        // =========================================================================
        // STAGE 12: Event Evaluation Report Creation & Verification
        // =========================================================================
        // PM publishes the final report
        $this->resetAuth('sanctum');
        $reportResponse = $this->withHeader('Authorization', 'Bearer ' . $pmToken)
                               ->postJson("/api/events/{$eventId}/report", [
                                   'evaluation_notes' => 'Everything worked flawlessly. Stage and audio met expectations.',
                                   'recommendations'  => 'Double budget stage allocations for multi-day events next year.',
                               ]);
        $reportResponse->assertStatus(201);

        // PM reads the report and asserts metrics are snapshotted correctly
        $this->resetAuth('sanctum');
        $viewReportResponse = $this->withHeader('Authorization', 'Bearer ' . $pmToken)
                                   ->getJson("/api/events/{$eventId}/report");
        $viewReportResponse->assertStatus(200)
                           ->assertJsonFragment(['is_finalized' => true]);
        
        $report = $viewReportResponse->json('report');
        $this->assertEquals('Everything worked flawlessly. Stage and audio met expectations.', $report['evaluation_notes']);
        $this->assertEquals(150000000, $report['budget_snapshot']['event_budget']);
        $this->assertEquals(3500000, $report['budget_snapshot']['total_spent']);
        $this->assertEquals(1, $report['task_snapshot']['completed']);
        $this->assertEquals(4, $report['logistic_snapshot']['total_items_borrowed']);
    }
}
