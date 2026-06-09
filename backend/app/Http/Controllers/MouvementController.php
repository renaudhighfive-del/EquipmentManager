<?php

namespace App\Http\Controllers;

use App\Models\Mouvement;
use Illuminate\Http\Request;

class MouvementController extends Controller
{
    public function index()
    {
        try {
            $mouvements = Mouvement::with(['equipement', 'user', 'reference'])
                ->latest('created_at')
                ->get();

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
