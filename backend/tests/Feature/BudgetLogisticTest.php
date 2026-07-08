<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventBudgetAllocation;
use App\Models\EventExpense;
use App\Models\EventLogistic;
use App\Models\Inventory;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BudgetLogisticTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected User $pm;
    protected User $staff1;
    protected Event $event;
    protected string $pmToken;
    protected string $staff1Token;
    protected $allocation;
    protected $expense;
    protected Inventory $inventory;
    protected $logistic;

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
                              'name' => 'Budget Test Event', 'description' => 'Desc',
                              'location' => 'Jakarta', 'start_date' => '2026-09-01',
                              'end_date' => '2026-09-02', 'start_time' => '09:00',
                              'end_time' => '18:00', 'status' => 'draft',
                              'budget' => 50000000, 'category' => 'Conference',
                              'expected_participants' => 100,
                              'personnel' => [
                                  ['user_id' => $this->staff1->id, 'role_in_event' => 'coordinator', 'notes' => 'Test'],
                              ],
                          ]);
        $this->event = Event::find($eventResp->json('id'));

        // Create budget allocation
        $this->resetAuth('sanctum');
        $allocResp = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                          ->postJson("/api/events/{$this->event->id}/budget/allocations", [
                              'category' => 'Konsumsi',
                              'allocated_amount' => 10000000,
                              'notes' => 'Food and beverages',
                          ]);
        $this->allocation = $allocResp->json();

        // Login Staff
        $this->resetAuth('web');
        $r = $this->postJson('/api/login', ['email' => 'staff@test.com', 'password' => 'password123']);
        $this->staff1Token = $r->json('token');

        // Create expense
        $this->resetAuth('sanctum');
        $expResp = $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
                        ->postJson("/api/events/{$this->event->id}/budget/expenses", [
                            'title' => 'Catering order',
                            'category' => 'Konsumsi',
                            'amount' => 2000000,
                            'vendor_name' => 'Catering ABC',
                            'payment_method' => 'transfer',
                            'payment_status' => 'paid',
                            'spent_at' => '2026-08-30',
                            'notes' => 'Lunch catering',
                        ]);
        $this->expense = $expResp->json();

        // Create inventory
        $this->resetAuth('sanctum');
        $invResp = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                        ->postJson('/api/inventories', [
                            'item_name' => 'Projector Epson',
                            'serial_number' => 'PROJ-001',
                            'total_quantity' => 5,
                            'ownership' => 'owned',
                            'status' => 'ready',
                            'notes' => 'HD projector',
                        ]);
        $this->inventory = Inventory::find($invResp->json('id'));

        // Checkout logistic to event
        $this->resetAuth('sanctum');
        $logResp = $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
                        ->postJson("/api/events/{$this->event->id}/logistics", [
                            'inventory_id' => $this->inventory->id,
                            'user_id' => $this->staff1->id,
                            'quantity' => 2,
                            'borrowed_at' => '2026-08-31',
                            'notes' => 'For presentation',
                        ]);
        $this->logistic = $logResp->json();
    }

    // TC-066
    public function test_tc066_lihat_ringkasan_budget_event(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->getJson("/api/events/{$this->event->id}/budget");
        $response->assertStatus(200);
    }

    // TC-067
    public function test_tc067_hapus_alokasi_anggaran(): void
    {
        $allocId = $this->allocation['id'];
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->deleteJson("/api/events/{$this->event->id}/budget/allocations/{$allocId}");
        $response->assertStatus(200);
    }

    // TC-068
    public function test_tc068_hapus_pengeluaran(): void
    {
        $expId = $this->expense['id'];
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->deleteJson("/api/events/{$this->event->id}/budget/expenses/{$expId}");
        $response->assertStatus(200);
    }

    // TC-069
    public function test_tc069_lihat_daftar_inventaris(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->getJson('/api/inventories');
        $response->assertStatus(200);
    }

    // TC-070
    public function test_tc070_hapus_item_inventaris(): void
    {
        // Create a second inventory item to delete (don't delete the one in use)
        $this->resetAuth('sanctum');
        $newInv = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                       ->postJson('/api/inventories', [
                           'item_name' => 'Spare Cable', 'serial_number' => 'CBL-001',
                           'total_quantity' => 20, 'ownership' => 'owned',
                           'status' => 'ready', 'notes' => 'HDMI cables',
                       ]);
        $newInvId = $newInv->json('id');

        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->deleteJson('/api/inventories/' . $newInvId);
        $response->assertStatus(200);
    }

    // TC-071
    public function test_tc071_lihat_semua_logistik_aktif(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->getJson('/api/logistics/active');
        $response->assertStatus(200);
    }

    // TC-072
    public function test_tc072_lihat_logistik_per_event(): void
    {
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->getJson("/api/events/{$this->event->id}/logistics");
        $response->assertStatus(200);
    }

    // TC-073
    public function test_tc073_hapus_logistik_event(): void
    {
        $logId = $this->logistic['id'];
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->deleteJson("/api/events/{$this->event->id}/logistics/{$logId}");
        $response->assertStatus(200);
    }

    public function test_update_budget_allocation(): void
    {
        $allocId = $this->allocation['id'];
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
                         ->putJson("/api/events/{$this->event->id}/budget/allocations/{$allocId}", [
                             'category' => 'Konsumsi',
                             'allocated_amount' => 15000000,
                             'notes' => 'Updated food budget',
                         ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('event_budget_allocations', [
            'id' => $allocId,
            'allocated_amount' => 15000000,
            'notes' => 'Updated food budget',
        ]);
    }

    public function test_update_expense(): void
    {
        $expId = $this->expense['id'];
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->staff1Token)
                         ->putJson("/api/events/{$this->event->id}/budget/expenses/{$expId}", [
                             'title' => 'Catering order updated',
                             'category' => 'Konsumsi',
                             'amount' => 2500000,
                             'vendor_name' => 'Catering ABC',
                             'payment_method' => 'transfer',
                             'payment_status' => 'paid',
                             'spent_at' => '2026-08-30',
                             'notes' => 'Updated lunch catering notes',
                         ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('event_expenses', [
            'id' => $expId,
            'title' => 'Catering order updated',
            'amount' => 2500000,
            'notes' => 'Updated lunch catering notes',
        ]);
    }

    public function test_print_report_view(): void
    {
        // First publish/finalize report
        $this->resetAuth('sanctum');
        $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
             ->postJson("/api/events/{$this->event->id}/report", [
                 'evaluation_notes' => 'Overall good event execution.',
                 'recommendations' => 'Keep up the good work.',
             ])->assertStatus(201);

        // Now print the report using the token as a query parameter
        $this->resetAuth('web');
        $response = $this->get("/api/events/{$this->event->id}/report/print?token=" . $this->pmToken);
        
        $response->assertStatus(200);
        $response->assertSee('Laporan Evaluasi');
        $response->assertSee('Overall good event execution.');
    }
}
