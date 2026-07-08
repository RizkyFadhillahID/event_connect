<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Inventory;
use App\Models\EventLogistic;
use App\Models\EventExpense;
use App\Models\InventoryStatusAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LogisticController extends Controller
{
    // =========================================================================
    // 1. GLOBAL WAREHOUSE INVENTORY MANAGEMENT (CRUD)
    // =========================================================================

    /**
     * List all global warehouse items.
     */
    public function indexInventory(Request $request)
    {
        $user = $request->user();
        $query = Inventory::query();

        // Search filter
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        // Status & ownership filters
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->ownership) {
            $query->where('ownership', $request->ownership);
        }

        if ($request->query('paginate') === 'false') {
            $inventories = $query->orderBy('item_name')->get();
        } else {
            $inventories = $query->orderBy('item_name')->paginate(10);
        }

        return response()->json($inventories);
    }

    /**
     * Add or update an inventory item (Manager only).
     */
    public function storeInventory(Request $request)
    {
        $this->authorizeManagerAccess($request->user());

        $data = $request->validate([
            'id' => 'nullable|exists:inventories,id',
            'item_name' => 'required|string|max:255',
            'serial_number' => 'nullable|string|max:100',
            'total_quantity' => 'required|integer|min:1',
            'ownership' => 'required|in:owned,rented',
            'default_rent_price' => 'nullable|numeric|min:0',
            'status' => 'required|in:ready,maintenance,damaged',
            'notes' => 'nullable|string',
        ]);

        // Unique serial number check
        if (!empty($data['serial_number'])) {
            $exists = Inventory::where('serial_number', $data['serial_number'])
                ->when(!empty($data['id']), fn($q) => $q->where('id', '!=', $data['id']))
                ->exists();
            if ($exists) {
                return response()->json(['message' => 'Nomor seri barang sudah terdaftar.'], 422);
            }
        }

        $inventory = DB::transaction(function () use ($data) {
            if (!empty($data['id'])) {
                // Update
                $inventory = Inventory::findOrFail($data['id']);
                
                // Adjust available stock based on total quantity delta
                $delta = $data['total_quantity'] - $inventory->total_quantity;
                $inventory->available_quantity = max(0, $inventory->available_quantity + $delta);
                
                $inventory->update($data);
            } else {
                // Create
                $data['available_quantity'] = $data['total_quantity'];
                $inventory = Inventory::create($data);
            }
            return $inventory;
        });

        return response()->json($inventory, 201);
    }

    /**
     * Remove an inventory item from warehouse (Manager only).
     */
    public function destroyInventory(Request $request, Inventory $inventory)
    {
        $this->authorizeManagerAccess($request->user());

        // Check if there are active loans
        $hasActiveLoans = $inventory->logistics()->whereNull('returned_at')->exists();
        if ($hasActiveLoans) {
            return response()->json([
                'message' => 'Barang tidak dapat dihapus karena masih dideploy / dipinjam di event aktif.'
            ], 409);
        }

        $inventory->delete();

        return response()->json(['message' => 'Barang berhasil dihapus dari katalog gudang.']);
    }

    /**
     * Get all active (unreturned) logistics across all events.
     */
    public function indexAllActiveLogistics(Request $request)
    {
        $logistics = EventLogistic::whereNull('returned_at')
            ->whereHas('event')
            ->with(['event:id,name', 'inventory', 'user:id,name,role'])
            ->orderBy('borrowed_at', 'desc')
            ->paginate(10);

        return response()->json($logistics);
    }


    // =========================================================================
    // 2. EVENT LOGISTICS ASSIGNMENT (CHECKOUT & CHECK-IN)
    // =========================================================================

    /**
     * Get logistics deployed in a specific event.
     */
    public function indexEventLogistics(Request $request, Event $event)
    {
        $this->authorizeEventAccess($request->user(), $event);

        $logistics = $event->logistics()
            ->with(['inventory', 'user:id,name,role'])
            ->orderBy('borrowed_at', 'desc')
            ->get();

        return response()->json($logistics);
    }

    /**
     * Allocate / Checkout equipment to an event.
     */
    public function checkoutLogistic(Request $request, Event $event)
    {
        $this->authorizeEventAccess($request->user(), $event);

        $data = $request->validate([
            'inventory_id' => 'required|exists:inventories,id',
            'user_id' => 'required|exists:users,id', // PIC penanggung jawab
            'quantity' => 'required|integer|min:1',
            'borrowed_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $inventory = Inventory::findOrFail($data['inventory_id']);

        // Check stock availability
        if ($inventory->available_quantity < $data['quantity']) {
            return response()->json([
                'message' => "Stok barang tidak mencukupi. Tersedia: {$inventory->available_quantity} unit."
            ], 422);
        }

        $logistic = DB::transaction(function () use ($event, $data, $inventory, $request) {
            // Decrement available stock in gudang
            $inventory->decrement('available_quantity', $data['quantity']);

            // Calculate rent cost if the item is rented
            $rentCost = 0;
            if ($inventory->ownership === 'rented' && $inventory->default_rent_price > 0) {
                $borrowDate = Carbon::parse($data['borrowed_at']);
                $eventEndDate = Carbon::parse($event->end_date);
                
                // Calculate duration in days (minimum 1 day)
                $days = max(1, $borrowDate->diffInDays($eventEndDate) + 1);
                $rentCost = $inventory->default_rent_price * $data['quantity'] * $days;
            }

            // Create event logistic record
            $data['event_id'] = $event->id;
            $data['rent_cost'] = $rentCost;
            $logistic = EventLogistic::create($data);

            // AUTOMATIC BUDGET INTEGRATION: Log rent expense if rented
            if ($rentCost > 0) {
                EventExpense::create([
                    'event_id' => $event->id,
                    'user_id' => $request->user()->id,
                    'category' => 'Logistik & Ops',
                    'title' => 'Sewa Alat: ' . $inventory->item_name . ' (' . $data['quantity'] . ' unit)',
                    'amount' => $rentCost,
                    'vendor_name' => 'Sewa Gudang / Pihak Ketiga',
                    'payment_method' => 'transfer',
                    'payment_status' => 'pending',
                    'spent_at' => $data['borrowed_at'],
                    'notes' => 'Terbuat otomatis dari peminjaman logistik #' . $logistic->id,
                ]);
            }

            return $logistic;
        });

        return response()->json($logistic->load(['inventory', 'user:id,name,role']), 201);
    }

    /**
     * Check-in / Return equipment from event back to warehouse.
     */
    public function returnLogistic(Request $request, Event $event, EventLogistic $logistic)
    {
        $this->authorizeEventAccess($request->user(), $event);
        abort_unless($logistic->event_id === $event->id, 400, 'Logistic record does not belong to this event.');

        // Verify if it is already returned
        if ($logistic->returned_at !== null) {
            return response()->json(['message' => 'Barang sudah dikembalikan sebelumnya.'], 422);
        }

        $data = $request->validate([
            'returned_at' => 'required|date',
            'return_status' => 'required|in:complete,incomplete,damaged',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($logistic, $data) {
            $inventory = $logistic->inventory;

            // Increment back available stock in gudang
            $inventory->increment('available_quantity', $logistic->quantity);

            // Operational touch: if return_status is damaged, mark global inventory status as damaged/maintenance
            if ($data['return_status'] === 'damaged') {
                $inventory->update(['status' => 'maintenance']);
            }

            // Update logistic record
            $logistic->update([
                'returned_at' => $data['returned_at'],
                'return_status' => $data['return_status'],
                'notes' => $data['notes'] ?? $logistic->notes,
            ]);
        });

        return response()->json($logistic->load(['inventory', 'user:id,name,role']));
    }

    /**
     * Cancel and remove an event logistic allocation.
     */
    public function destroyEventLogistic(Request $request, Event $event, EventLogistic $logistic)
    {
        $this->authorizeEventAccess($request->user(), $event);
        abort_unless($logistic->event_id === $event->id, 400, 'Logistic record does not belong to this event.');

        DB::transaction(function () use ($logistic, $event) {
            $inventory = $logistic->inventory;

            // If the item wasn't returned yet, give its quantity back to available warehouse stock
            if ($logistic->returned_at === null) {
                $inventory->increment('available_quantity', $logistic->quantity);
            }

            // AUTOMATIC BUDGET INTEGRATION: Find and delete the corresponding auto-created expense
            $expenseText = 'Terbuat otomatis dari peminjaman logistik #' . $logistic->id;
            EventExpense::where('event_id', $event->id)
                ->where('category', 'Logistik & Ops')
                ->where('notes', $expenseText)
                ->delete();

            $logistic->delete();
        });

        return response()->json(['message' => 'Alokasi logistik berhasil dibatalkan.']);
    }

    /**
     * Get all active (unresolved) inventory status actions (maintenance / damaged).
     */
    public function indexStatusActions(Request $request)
    {
        $actions = InventoryStatusAction::where('status', 'active')
            ->with(['inventory', 'user:id,name,role'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($actions);
    }

    /**
     * Move inventory items to maintenance or damaged state.
     */
    public function storeStatusAction(Request $request)
    {
        $this->authorizeManagerAccess($request->user());

        $data = $request->validate([
            'inventory_id' => 'required|exists:inventories,id',
            'user_id' => 'required|exists:users,id', // PIC
            'type' => 'required|in:maintenance,damaged',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $inventory = Inventory::findOrFail($data['inventory_id']);

        // Check if available quantity is enough
        if ($inventory->available_quantity < $data['quantity']) {
            return response()->json([
                'message' => "Stok barang tidak mencukupi. Tersedia: {$inventory->available_quantity} unit."
            ], 422);
        }

        $action = DB::transaction(function () use ($data, $inventory) {
            // Decrement available stock
            $inventory->decrement('available_quantity', $data['quantity']);

            // Create status action record
            $data['status'] = 'active';
            $data['organization_id'] = $inventory->organization_id;
            return InventoryStatusAction::create($data);
        });

        return response()->json($action->load(['inventory', 'user:id,name,role']), 201);
    }

    /**
     * Resolve a maintenance or damaged inventory status action.
     */
    public function resolveStatusAction(Request $request, $actionId)
    {
        $this->authorizeManagerAccess($request->user());

        // Resolve manually to ensure organizational scope applies correctly via global scope
        $action = InventoryStatusAction::where('status', 'active')->findOrFail($actionId);

        $data = $request->validate([
            'resolution' => 'required|in:repaired,discarded', // repaired = back to ready, discarded = permanently reduce total stock
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($action, $data) {
            $inventory = $action->inventory;

            if ($data['resolution'] === 'repaired') {
                // Return items back to available stock
                $inventory->increment('available_quantity', $action->quantity);
            } else {
                // Permanently discard items: reduce total quantity
                // available_quantity is already decremented, so we just decrease total_quantity
                $inventory->decrement('total_quantity', $action->quantity);
            }

            // Append resolution to notes if provided
            $resolvedNotes = $action->notes;
            if (!empty($data['notes'])) {
                $resolvedNotes = ($resolvedNotes ? $resolvedNotes . "\n" : "") . "Resolusi: " . $data['notes'];
            }

            $action->update([
                'status' => 'resolved',
                'resolved_at' => Carbon::now(),
                'notes' => $resolvedNotes,
            ]);
        });

        return response()->json($action->load(['inventory', 'user:id,name,role']));
    }




    // =========================================================================
    // 3. PRIVATE HELPER AUTHORIZATION METHODS
    // =========================================================================

    private function authorizeEventAccess($user, Event $event): void
    {
        if (in_array($user->role, ['superadmin', 'project_manager'])) {
            return;
        }

        $isMember = DB::table('event_personnel')
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($isMember, 403, 'Anda tidak memiliki akses ke manajemen logistik event ini.');
    }

    private function authorizeManagerAccess($user): void
    {
        abort_unless(
            in_array($user->role, ['superadmin', 'project_manager']),
            403,
            'Hanya Project Manager atau Super Admin yang memiliki hak mengelola inventaris gudang global.'
        );
    }
}
