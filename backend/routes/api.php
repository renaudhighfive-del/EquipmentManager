<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// Protected Routes
Route::middleware(['auth:sanctum', 'check.active'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // User Management (Admin only)
    Route::apiResource('users', \App\Http\Controllers\UserController::class);
    Route::patch('users/{user}/toggle-status', [\App\Http\Controllers\UserController::class, 'toggleStatus']);
    
    // Add other protected routes here as we develop them
});

// Public Test Route
Route::get('/test', function () {
    return response()->json([
        'status' => true,
        'message' => 'API is working correctly 🚀'
    ]);
});
