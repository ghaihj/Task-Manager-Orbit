<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/logout', 'logout')->middleware('auth:sanctum');
    Route::post('/me', 'me')->middleware('auth:sanctum');
});

Route::apiResource('projects', ProjectController::class);


Route::put('/projects/{id}/status', [ProjectController::class, 'changeStatus']);
