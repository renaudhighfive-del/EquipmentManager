<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\EquipementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AffectationController;

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login',      [AuthController::class, 'login']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',      [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// ── Routes protégées ──────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'check.active'])->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });


    // Gestion utilisateurs (Admin)
    Route::apiResource('users', UserController::class);
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);

    // Catégories
    Route::apiResource('categories', CategorieController::class);
    
    // Affectations
    Route::apiResource('affectations', AffectationController::class);

    // Equipments
    Route::patch('equipements/{equipement}/archive', [EquipementController::class, 'archive']);
    Route::apiResource('equipements', EquipementController::class);

    // Agent Management
    Route::apiResource('agents', \App\Http\Controllers\AgentController::class);
    Route::patch('agents/{agent}/desactiver', [\App\Http\Controllers\AgentController::class, 'desactiver']);
    Route::patch('agents/{agent}/reactiver', [\App\Http\Controllers\AgentController::class, 'reactiver']);
});

// ── Route de test ─────────────────────────────────────────────────────────────
Route::get('/test', fn () => response()->json([
    'status'  => true,
    'message' => 'API is working correctly 🚀',
]));
