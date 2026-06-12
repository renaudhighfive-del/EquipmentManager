<?php

namespace App\Http\Controllers;

use App\Models\Mouvement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MouvementController extends Controller
{
public function index(Request $request) 
{
    try {
        $user = Auth::user();
        $query = Mouvement::with(['equipement', 'user', 'reference']);

        // --- 1. SÉCURITÉ & RÔLES (Votre code existant) ---
        if ($user->role === 'agent') {
            $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHasMorph('reference', [\App\Models\Affectation::class], function($subQ) use ($user) {
                      $subQ->where('agent_id', $user->agent?->id);
                  });
            });
        } elseif ($user->role !== 'admin') {  
            $query->where('user_id', $user->id); 
        } 

       // --- 2. FILTRES DYNAMIQUES ---

        // Filtre par type de mouvement (ajusté sur votre colonne 'type_mouvement')
$query->when($request->filled('type_mouvement'), function ($q) use ($request) {
    $q->where('type_mouvement', $request->input('type_mouvement'));
});

// Filtre par l'auteur du mouvement (celui qui a fait le mouvement)
$query->when($request->filled('user_name'), function ($q) use ($request) {
    $q->whereHas('user', function ($subQ) use ($request) {
        $subQ->where('name', 'LIKE', '%' . $request->input('user_name') . '%');
    });
});

// Filtre par date de début
$query->when($request->filled('date_debut'), function ($q) use ($request) {
    $q->whereDate('created_at', '>=', $request->input('date_debut'));
});
        // --- 3. RÉCUPÉRATION DES DONNÉES ---
        $mouvements = $query->latest('created_at')->get();

        return response()->json([
            'status' => 'success',
            'data' => $mouvements,
            'total' => $mouvements->count(),
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Erreur lors de la récupération des mouvements : ' . $e->getMessage()
        ], 500);
    }
 }
}
