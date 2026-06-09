<?php

namespace App\Http\Controllers;

use App\Models\PerteCasse;
use App\Models\Equipement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PerteCasseController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = PerteCasse::with(['equipement', 'declarePar', 'validePar']);

        if ($user->role === 'agent') {
            $query->where('declare_par', $user->id);
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'equipement_id' => 'required|exists:equipements,id',
            'type' => 'required|in:perte,casse,vol', // Lowercase to match migration
            'description' => 'required|string',
        ]);

        $sinistre = PerteCasse::create([
            'equipement_id' => $validated['equipement_id'],
            'declare_par' => Auth::id(),
            'type' => $validated['type'],
            'date_declaration' => now(),
            'description' => $validated['description'],
            'statut' => 'en_attente_validation',
        ]);

        // Update equipment status
        $equipement = Equipement::find($validated['equipement_id']);
        $equipement->update(['etat' => 'en_attente_sinistre']);

        return response()->json($sinistre->load(['equipement', 'declarePar']), 201);
    }

    public function valider(PerteCasse $sinistre)
    {
        $sinistre->update([
            'statut' => 'validee',
            'valide_par' => Auth::id(),
            'date_validation' => now(),
        ]);

        // Update equipment status based on type
        $equipement = $sinistre->equipement;
        if ($sinistre->type === 'perte' || $sinistre->type === 'vol') {
            $equipement->update(['etat' => 'perdu']);
        } else {
            $equipement->update(['etat' => 'reforme']);
        }

        return response()->json($sinistre->load(['validePar']));
    }

    public function rejeter(Request $request, PerteCasse $sinistre)
    {
        $validated = $request->validate([
            'motif' => 'required|string',
        ]);

        $sinistre->update([
            'statut' => 'rejetee',
            'valide_par' => Auth::id(),
            'date_validation' => now(),
            'motif_rejet' => $validated['motif'],
        ]);

        // Put equipment back to service
        $equipement = $sinistre->equipement;
        $equipement->update(['etat' => 'en_service']);

        return response()->json($sinistre->load(['validePar']));
    }
}
