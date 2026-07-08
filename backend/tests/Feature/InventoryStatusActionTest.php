<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryStatusAction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InventoryStatusActionTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;
    protected User $pm;
    protected User $staff;
    protected string $pmToken;
    protected Inventory $inventory;

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
            'name' => 'Tech Test Org', 'slug' => 'tech-test-org', 'email' => 'tech@test.com',
            'status' => 'active', 'plan' => 'business', 'max_users' => 10, 'max_events' => 10,
        ]);

        $this->pm = User::create([
            'organization_id' => $this->org->id, 'name' => 'PM User',
            'email' => 'pm@techtest.com', 'password' => Hash::make('password123'),
            'role' => 'project_manager', 'status' => 'active',
        ]);

        $this->staff = User::create([
            'organization_id' => $this->org->id, 'name' => 'Staff User',
            'email' => 'staff@techtest.com', 'password' => Hash::make('password123'),
            'role' => 'staff', 'status' => 'active',
        ]);

        // Login PM
        $this->resetAuth('web');
        $r = $this->postJson('/api/login', ['email' => 'pm@techtest.com', 'password' => 'password123']);
        $this->pmToken = $r->json('token');

        // Create initial Inventory
        $this->inventory = Inventory::create([
            'organization_id' => $this->org->id,
            'item_name' => 'HT Motorola T62',
            'serial_number' => 'HT-MOT-T62',
            'total_quantity' => 10,
            'available_quantity' => 10,
            'ownership' => 'owned',
            'status' => 'ready',
        ]);
    }

    public function test_can_move_items_to_maintenance(): void
    {
        $this->resetAuth('sanctum');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
            ->postJson('/api/logistics/status-actions', [
                'inventory_id' => $this->inventory->id,
                'user_id' => $this->staff->id,
                'type' => 'maintenance',
                'quantity' => 3,
                'notes' => 'Routine inspection and screen clean',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('type', 'maintenance');
        $response->assertJsonPath('quantity', 3);

        // Check if available quantity decreased
        $this->inventory->refresh();
        $this->assertEquals(7, $this->inventory->available_quantity);
        $this->assertEquals(10, $this->inventory->total_quantity);
    }

    public function test_insufficient_available_quantity_fails(): void
    {
        $this->resetAuth('sanctum');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
            ->postJson('/api/logistics/status-actions', [
                'inventory_id' => $this->inventory->id,
                'user_id' => $this->staff->id,
                'type' => 'damaged',
                'quantity' => 15, // greater than 10
                'notes' => 'Bulk damage',
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message']);
    }

    public function test_can_resolve_maintenance_as_repaired(): void
    {
        $this->resetAuth('sanctum');

        // Create action first
        $action = InventoryStatusAction::create([
            'organization_id' => $this->org->id,
            'inventory_id' => $this->inventory->id,
            'user_id' => $this->staff->id,
            'type' => 'maintenance',
            'quantity' => 4,
            'status' => 'active',
        ]);
        $this->inventory->decrement('available_quantity', 4);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
            ->postJson("/api/logistics/status-actions/{$action->id}/resolve", [
                'resolution' => 'repaired',
                'notes' => 'All 4 devices repaired successfully',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'resolved');

        // Check if available quantity restored
        $this->inventory->refresh();
        $this->assertEquals(10, $this->inventory->available_quantity);
        $this->assertEquals(10, $this->inventory->total_quantity);
    }

    public function test_can_resolve_damaged_as_discarded(): void
    {
        $this->resetAuth('sanctum');

        // Create action first
        $action = InventoryStatusAction::create([
            'organization_id' => $this->org->id,
            'inventory_id' => $this->inventory->id,
            'user_id' => $this->staff->id,
            'type' => 'damaged',
            'quantity' => 2,
            'status' => 'active',
        ]);
        $this->inventory->decrement('available_quantity', 2);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->pmToken)
            ->postJson("/api/logistics/status-actions/{$action->id}/resolve", [
                'resolution' => 'discarded',
                'notes' => 'Unrepairable motherboard burn',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'resolved');

        // Check if available quantity remains decreased, but total quantity decreases permanently
        $this->inventory->refresh();
        $this->assertEquals(8, $this->inventory->available_quantity);
        $this->assertEquals(8, $this->inventory->total_quantity);
    }

    public function test_organization_isolation_prevents_viewing_other_org_actions(): void
    {
        // Create second organization and user
        $org2 = Organization::create([
            'name' => 'Org Two', 'slug' => 'org-two', 'email' => 'two@test.com',
            'status' => 'active', 'plan' => 'business', 'max_users' => 10, 'max_events' => 10,
        ]);
        $pm2 = User::create([
            'organization_id' => $org2->id, 'name' => 'PM Two',
            'email' => 'pm2@two.com', 'password' => Hash::make('password123'),
            'role' => 'project_manager', 'status' => 'active',
        ]);

        $this->resetAuth('web');
        $r = $this->postJson('/api/login', ['email' => 'pm2@two.com', 'password' => 'password123']);
        $pm2Token = $r->json('token');

        // Create action for Org 1
        $action = InventoryStatusAction::create([
            'organization_id' => $this->org->id,
            'inventory_id' => $this->inventory->id,
            'user_id' => $this->staff->id,
            'type' => 'maintenance',
            'quantity' => 2,
            'status' => 'active',
        ]);

        // Org 2 PM tries to resolve Org 1 action -> should get 404 since it is out of scope
        $this->resetAuth('sanctum');
        $response = $this->withHeader('Authorization', 'Bearer ' . $pm2Token)
            ->postJson("/api/logistics/status-actions/{$action->id}/resolve", [
                'resolution' => 'repaired',
            ]);

        $response->assertStatus(404);
    }
}
