<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\BudgetExpenseController;
use App\Http\Controllers\Api\LogisticController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventReportController;
use App\Http\Controllers\Api\RundownController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Backoffice\PlatformAuthController;
use App\Http\Controllers\Backoffice\PlatformDashboardController;
use App\Http\Controllers\Backoffice\OrganizationController;
use App\Http\Controllers\Backoffice\PlatformAdminController;
use App\Http\Controllers\PublicLandingController;
use App\Http\Controllers\Backoffice\BackofficeLandingController;
use App\Http\Controllers\Backoffice\BackofficeContactController;
use App\Http\Controllers\Backoffice\BackofficeFaqController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Broadcasting auth — uses Sanctum token (Bearer) instead of session cookie
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Auth
Route::post('/login', [AuthController::class, 'login']);

// Public Landing Website API Routes
Route::get('/landing/contents', [PublicLandingController::class, 'contents']);
Route::post('/landing/contact', [PublicLandingController::class, 'contact']);
Route::post('/landing/register', [PublicLandingController::class, 'register']);
Route::get('/landing/faqs', [PublicLandingController::class, 'faqs']);

// Protected routes
Route::middleware(['auth:sanctum', 'org.active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Superadmin: full user management
    Route::middleware('role:superadmin')->group(function () {
        Route::apiResource('users', UserController::class);
    });

    // Project Manager + Superadmin: event management
    Route::middleware('role:superadmin,project_manager')->group(function () {
        Route::apiResource('events', EventController::class)->except(['index', 'show']);
    });

    // All authenticated: view events, view users list for assignment
    Route::get('events', [EventController::class, 'index']);
    Route::get('events/{event}', [EventController::class, 'show']);
    Route::get('users-list', [EventController::class, 'allUsers']);
    Route::get('dashboard/financials', [EventController::class, 'dashboardFinancials']);

    // ---------------------------------------------------------------
    // Task & Workflow Management
    // ---------------------------------------------------------------

    // My tasks (any authenticated user)
    Route::get('my-tasks', [TaskController::class, 'myTasks']);

    // Task list & create (access-scoped by event membership)
    Route::get('tasks', [TaskController::class, 'index']);
    Route::post('tasks', [TaskController::class, 'store']);

    // Single task operations
    Route::get('tasks/{task}', [TaskController::class, 'show']);
    Route::put('tasks/{task}', [TaskController::class, 'update']);
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus']);
    Route::delete('tasks/{task}', [TaskController::class, 'destroy']);

    // Task comments
    Route::get('tasks/{task}/comments', [TaskController::class, 'comments']);
    Route::post('tasks/{task}/comments', [TaskController::class, 'addComment']);

    // Event-scoped task routes (used in event detail panel)
    Route::get('events/{event}/tasks', [TaskController::class, 'eventTasks']);
    Route::get('events/{event}/personnel', [TaskController::class, 'eventPersonnel']);

    // ---------------------------------------------------------------
    // Group Chat (per event)
    // ---------------------------------------------------------------
    Route::get('events/{event}/chat', [ChatController::class, 'index']);
    Route::post('events/{event}/chat', [ChatController::class, 'store']);

    // ---------------------------------------------------------------
    // Budget & Expense Tracker (per event)
    // ---------------------------------------------------------------
    Route::get('events/{event}/budget', [BudgetExpenseController::class, 'index']);
    Route::post('events/{event}/budget/allocations', [BudgetExpenseController::class, 'storeAllocation']);
    Route::delete('events/{event}/budget/allocations/{allocation}', [BudgetExpenseController::class, 'destroyAllocation']);
    Route::post('events/{event}/budget/expenses', [BudgetExpenseController::class, 'storeExpense']);
    Route::delete('events/{event}/budget/expenses/{expense}', [BudgetExpenseController::class, 'destroyExpense']);

    // Global Warehouse Inventories
    Route::get('inventories', [LogisticController::class, 'indexInventory']);
    Route::post('inventories', [LogisticController::class, 'storeInventory']);
    Route::delete('inventories/{inventory}', [LogisticController::class, 'destroyInventory']);
    Route::get('logistics/active', [LogisticController::class, 'indexAllActiveLogistics']);

    // Event Deployed Logistics
    Route::get('events/{event}/logistics', [LogisticController::class, 'indexEventLogistics']);
    Route::post('events/{event}/logistics', [LogisticController::class, 'checkoutLogistic']);
    Route::post('events/{event}/logistics/{logistic}/return', [LogisticController::class, 'returnLogistic']);
    Route::delete('events/{event}/logistics/{logistic}', [LogisticController::class, 'destroyEventLogistic']);

    // ---------------------------------------------------------------
    // Event Evaluation Report System
    // ---------------------------------------------------------------
    Route::get('events/{event}/report', [EventReportController::class, 'show']);
    Route::post('events/{event}/report', [EventReportController::class, 'store']);
    Route::delete('events/{event}/report', [EventReportController::class, 'destroy']);

    // ---------------------------------------------------------------
    // Event Timeline & Rundown System
    // ---------------------------------------------------------------

    // Event-scoped rundown routes
    Route::get('events/{event}/rundowns', [RundownController::class, 'index']);
    Route::post('events/{event}/rundowns', [RundownController::class, 'store']);
    Route::get('events/{event}/rundown-dates', [RundownController::class, 'eventDates']);
    Route::get('events/{event}/rundown-stats', [RundownController::class, 'eventStats']);

    // Single rundown item operations
    Route::get('rundowns/{rundown}', [RundownController::class, 'show']);
    Route::put('rundowns/{rundown}', [RundownController::class, 'update']);
    Route::patch('rundowns/{rundown}/status', [RundownController::class, 'updateStatus']);
    Route::delete('rundowns/{rundown}', [RundownController::class, 'destroy']);
    Route::get('rundowns/{rundown}/logs', [RundownController::class, 'logs']);

    // Dependency management
    Route::post('rundowns/{rundown}/dependencies', [RundownController::class, 'addDependency']);
    Route::delete('rundowns/{rundown}/dependencies/{task}', [RundownController::class, 'removeDependency']);

    // ---------------------------------------------------------------
    // Event Guest Management System
    // ---------------------------------------------------------------
    Route::get('events/{event}/guests', [GuestController::class, 'index']);
    Route::post('events/{event}/guests', [GuestController::class, 'store']);
    Route::put('events/{event}/guests/{guest}', [GuestController::class, 'update']);
    Route::delete('events/{event}/guests/{guest}', [GuestController::class, 'destroy']);
    Route::patch('events/{event}/guests/{guest}/checkin', [GuestController::class, 'checkin']);
});

// Platform Backoffice routes
Route::prefix('backoffice')->group(function () {
    Route::post('/login', [PlatformAuthController::class, 'login']);

    Route::middleware('auth:platform')->group(function () {
        Route::post('/logout', [PlatformAuthController::class, 'logout']);
        Route::get('/me', [PlatformAuthController::class, 'me']);
        Route::get('/dashboard', [PlatformDashboardController::class, 'index']);
        Route::apiResource('organizations', OrganizationController::class);
        Route::patch('/organizations/{organization}/status', [OrganizationController::class, 'updateStatus']);
        Route::apiResource('admins', PlatformAdminController::class);

        // Landing Website Content Management
        Route::get('/landing-contents', [BackofficeLandingController::class, 'index']);
        Route::put('/landing-contents/{key_name}', [BackofficeLandingController::class, 'update']);

        // Public Contact Messages Inbox
        Route::apiResource('contact-messages', BackofficeContactController::class)->only(['index', 'show', 'destroy']);

        // FAQ accordion management
        Route::apiResource('faqs', BackofficeFaqController::class);
    });
});
