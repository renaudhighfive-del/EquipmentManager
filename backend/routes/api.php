<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\EquipementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AffectationController;
use App\Http\Controllers\MouvementController;
use App\Http\Controllers\PanneController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PerteCasseController;
use App\Http\Controllers\MaintenanceController;

use App\Http\Controllers\RapportController;

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login',      [AuthController::class, 'login']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);

    Route::middleware(['auth:sanctum', 'check.active'])->group(function () {
        Route::get('/me',      [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// ── Routes protégées ──────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'check.active'])->group(function () {

    Route::get('/user', fn (Request $request) => $request->user());

    // Profil de l'utilisateur connecté (AVANT apiResource pour éviter les conflits)
    Route::put('profile',          [UserController::class, 'updateProfile']);
    Route::post('profile',         [UserController::class, 'updateProfile']); // FormData (_method=PUT)
    Route::put('profile/password', [UserController::class, 'changePassword']);

    // Gestion utilisateurs (Admin)
    Route::apiResource('users', UserController::class);
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);

    // Catégories
    Route::apiResource('categories', CategorieController::class);
    
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('dashboard/export-pdf', [DashboardController::class, 'exportPdf']);

    // Affectations
    Route::post('affectations/{affectation}/request-return', [AffectationController::class, 'requestReturn']);
    Route::patch('affectations/{affectation}/validate-return', [AffectationController::class, 'validateReturn']);
    Route::patch('affectations/{affectation}/reject-return', [AffectationController::class, 'rejectReturn']);
        // Pour l'agent : affectations à confirmer et confirmation (PLACES AVANT apiResource !)
    Route::get('affectations/a-confirmer', [AffectationController::class, 'getAConfirmer']);
    Route::patch('affectations/{affectation}/confirmer-reception', [AffectationController::class, 'confirmerReception']);
    Route::apiResource('affectations', AffectationController::class);
    

    // Pannes
    Route::apiResource('pannes', PanneController::class);

    // Equipments
    Route::get('equipements/archives', [EquipementController::class, 'fetchArchives']);
    Route::patch('equipements/{equipement}/archive', [EquipementController::class, 'archive']);
    Route::patch('equipements/{equipement}/unarchive', [EquipementController::class, 'unarchive']);
    Route::apiResource('equipements', EquipementController::class);

    // Pannes
    Route::patch('pannes/{panne}/valider', [PanneController::class, 'valider']);
    Route::patch('pannes/{panne}/rejeter', [PanneController::class, 'rejeter']);
    Route::apiResource('pannes', PanneController::class);

    // Maintenances
    Route::patch('maintenances/{maintenance}/cloturer', [MaintenanceController::class, 'cloturer']);
    Route::patch('maintenances/{maintenance}/declarer-perte', [MaintenanceController::class, 'declarerPerte']);
    Route::apiResource('maintenances', MaintenanceController::class);

    // Sinistres (Pertes & Casses)
    Route::get('sinistres', [PerteCasseController::class, 'index']);
    Route::post('sinistres', [PerteCasseController::class, 'store']);
    Route::patch('sinistres/{sinistre}/valider', [PerteCasseController::class, 'valider']);
    Route::patch('sinistres/{sinistre}/rejeter', [PerteCasseController::class, 'rejeter']);

    // Agent Management
    Route::apiResource('agents', AgentController::class);
    Route::patch('agents/{agent}/desactiver', [AgentController::class, 'desactiver']);
    Route::patch('agents/{agent}/reactiver',  [AgentController::class, 'reactiver']);

    // Journal des mouvements
    Route::get('mouvements', [MouvementController::class, 'index']);

    // Rapports & statistiques
    Route::get('rapports/stats', [RapportController::class, 'stats']);
});

// ── Route de test ─────────────────────────────────────────────────────────────
Route::get('/test', fn () => response()->json([
    'status'  => true,
    'message' => 'API is working correctly 🚀',
]));
