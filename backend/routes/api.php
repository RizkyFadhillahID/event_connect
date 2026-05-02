<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

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
});
