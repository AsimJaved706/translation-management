<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TranslationController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

// Health check (no authentication required)
Route::get('/health', [HealthController::class, 'check']);

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

// Protected translation routes
Route::middleware('auth:sanctum')->group(function () {
    // Define specific routes BEFORE the resource routes
    Route::get('translations/search', [TranslationController::class, 'search']);
    Route::get('translations/export', [TranslationController::class, 'export']);

    // Resource routes
    Route::apiResource('translations', TranslationController::class);
});
