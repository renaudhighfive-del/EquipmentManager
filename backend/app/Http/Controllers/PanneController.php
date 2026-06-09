<?php

namespace App\Http\Controllers;

use App\Models\Panne;
use App\Models\Equipement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PanneController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = Panne::with(['equipement', 'declarePar', 'validePar']);

        if ($user->role === 'agent') {
            $query->where(function($q) use ($user) {
                $q->where('declare_par', $user->id)
                  ->orWhereHas('equipement.currentAffectation', function($sq) use ($user) {
                      $sq->where('agent_id', $user->agent->id);
                  });
            });
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'equipement_id' => 'required|exists:equipements,id',
            'description' => 'required|string',
            'gravite' => 'required|in:faible,moyenne,critique',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $panne = Panne::create([
            'equipement_id' => $validated['equipement_id'],
            'declare_par' => Auth::id(),
            'date_declaration' => now(),
            'description' => $validated['description'],
            'gravite' => $validated['gravite'],
            'statut' => 'declaree', // Match migration enum
        ]);

        // Update equipment status
        $equipement = Equipement::find($validated['equipement_id']);
        $equipement->update(['etat' => 'en_panne']);

        if ($request->hasFile('images')) {
            $photoPaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('pannes', 'public');
                $photoPaths[] = $path;
            }
            $panne->update(['photos' => $photoPaths]);
        }

        return response()->json($panne->load(['equipement', 'declarePar']), 201);
    }

    public function valider(Panne $panne)
    {
        // Une panne validée passe au statut 'en_cours' (prête pour maintenance)
        $panne->update([
            'statut' => 'en_cours',
            'valide_par' => Auth::id(),
            'date_validation' => now(),
        ]);

        // On s'assure que l'équipement reste marqué 'en_panne' tant qu'il n'est pas en maintenance
        $panne->equipement->update(['etat' => 'en_panne']);

        return response()->json($panne->load(['validePar', 'equipement']));
    }

    public function rejeter(Request $request, Panne $panne)
    {
        $validated = $request->validate([
            'motif' => 'required|string',
        ]);

        $panne->update([
            'statut' => 'irrecuperable', // Or a new status like 'rejetee'
            'valide_par' => Auth::id(),
            'date_validation' => now(),
            'description' => $panne->description . "\n\n[REJET] " . $validated['motif'],
        ]);

        // Put equipment back to service
        $equipement = $panne->equipement;
        $equipement->update(['etat' => 'en_service']);

        return response()->json($panne->load(['validePar']));
    }

    public function update(Request $request, Panne $panne)
    {
        $validated = $request->validate([
            'statut' => 'sometimes|in:declaree,en_cours,en_maintenance,resolue,irrecuperable',
            'description' => 'sometimes|string',
        ]);

        $panne->update($validated);

        return response()->json($panne);
    }
}
