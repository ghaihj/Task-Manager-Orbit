<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/logout', 'logout')->middleware('auth:sanctum');
    Route::post('/me', 'me')->middleware('auth:sanctum');
});

Route::apiResource('projects', ProjectController::class)->middleware('auth:sanctum');


Route::put('/projects/{id}/status', [ProjectController::class, 'changeStatus']);


Route::middleware('auth:sanctum')->group(function () {

    // Task management — Admin & Editor only for CRUD/assign
    Route::middleware('role:admin,editor')->group(function () {
        Route::post('/tasks', [TaskController::class, 'store']);
        Route::put('/tasks/{id}', [TaskController::class, 'update']);
        Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);
    });

    // Viewing tasks — all roles (index/show apply their own scoping internally)
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/{id}', [TaskController::class, 'show']);

    // Status update — Admin, Editor, Developer (developer only if assigned — checked in controller)
    Route::middleware('role:admin,editor,developer')->group(function () {
        Route::put('/tasks/{id}/status', [TaskController::class, 'changeStatus']);
        Route::post('/tasks/{id}/comments', [TaskController::class, 'addComment']);
        Route::get('/tasks/{id}/comments', [TaskController::class, 'comments']);
    });

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/users', [DashboardController::class, 'users']);
});
