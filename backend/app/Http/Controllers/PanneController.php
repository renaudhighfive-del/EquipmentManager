<?php

namespace App\Http\Controllers;

use App\Models\Panne;
use App\Models\Equipement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PanneController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Panne::with(['equipement', 'declarePar', 'validePar']);

        if ($user->role === 'agent') {
            $query->where('declare_par', $user->id);
        } elseif ($user->role === 'gestionnaire' && $user->categorie_id) {
            $query->whereHas('equipement', function ($q) use ($user) {
                $q->where('categorie_id', $user->categorie_id);
            });
        }

        $pannes = $query->latest()->get();

        return response()->json($pannes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'equipement_id' => 'required|exists:equipements,id',
            'description' => 'required|string',
            'gravite' => 'required|in:faible,moyenne,critique',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $photoPaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filename = 'panne_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('pannes', $filename, 'public');
                $photoPaths[] = $path;
            }
        }

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
            'statut' => 'required|in:declaree,en_cours,en_maintenance,resolue,irrecuperable',
        ]);

        $panne->update([
            'statut' => $validated['statut']
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Statut de la panne mis à jour',
            'data' => $panne->load(['equipement', 'declarePar'])
        ]);
    }

    public function destroy(Panne $panne)
    {
        // Supprimer les photos du stockage
        if ($panne->photos) {
            foreach ($panne->photos as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $panne->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Déclaration de panne supprimée'
        ]);
    }
}
