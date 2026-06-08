<?php

namespace App\Http\Controllers;

use App\Models\Equipement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EquipementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Equipement::with(['categorie', 'currentAffectation.agent']);

        if ($user->role === 'admin') {
            // Admin sees all
        } elseif ($user->role === 'gestionnaire') {
            // Gestionnaire sees only equipments in their category
            if ($user->categorie_id) {
                $query->where('categorie_id', $user->categorie_id);
            } else {
                // If no category assigned, maybe they see nothing or all? 
                // Let's assume they see nothing if no category is assigned to them.
                return response()->json([]);
            }
        } elseif ($user->role === 'agent') {
            // Agent sees only equipments assigned to them
            $query->whereHas('affectations', function ($q) use ($user) {
                $q->whereHas('agent', function ($sq) use ($user) {
                    $sq->where('user_id', $user->id);
                })->where('statut', 'en_cours');
            });
        }

        $equipements = $query->get();

        return response()->json($equipements);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'categorie_id' => 'required|exists:categories,id',
            'reference' => 'required|string|unique:equipements',
            'numero_serie' => 'required|string|unique:equipements',
            'code_inventaire' => 'required|string|unique:equipements',
            'marque' => 'required|string',
            'modele' => 'required|string',
            'fournisseur' => 'nullable|string',
            'date_acquisition' => 'nullable|date',
            'prix_achat' => 'nullable|numeric',
            'garantie_fin' => 'nullable|date',
            'etat' => 'required|in:neuf,en_service,en_panne,en_maintenance,en_attente_sinistre,reforme,perdu',
            'localisation' => 'nullable|string',
            'notes' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $equipement = Equipement::create($validated);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('equipements', 'public');
                $equipement->images()->create(['path' => $path]);
            }
        }

        return response()->json($equipement->load(['images', 'categorie']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Equipement $equipement)
    {
        $equipement->load(['categorie', 'affectations.agent', 'pannes', 'maintenances', 'mouvements']);
        return response()->json($equipement);
    }

    /**
     * Update
     */
    public function update(Request $request, Equipement $equipement)
    {
        $validated = $request->validate([
            'categorie_id' => 'sometimes|exists:categories,id',
            'reference' => 'sometimes|string|unique:equipements,reference,' . $equipement->id,
            'numero_serie' => 'sometimes|string|unique:equipements,numero_serie,' . $equipement->id,
            'code_inventaire' => 'sometimes|string|unique:equipements,code_inventaire,' . $equipement->id,
            'marque' => 'sometimes|string',
            'modele' => 'sometimes|string',
            'fournisseur' => 'nullable|string',
            'date_acquisition' => 'nullable|date',
            'prix_achat' => 'nullable|numeric',
            'garantie_fin' => 'nullable|date',
            'etat' => 'sometimes|in:neuf,en_service,en_panne,en_maintenance,en_attente_sinistre,reforme,perdu',
            'localisation' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_archived' => 'sometimes|boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $equipement->update($validated);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('equipements', 'public');
                $equipement->images()->create(['path' => $path]);
            }
        }

        return response()->json($equipement->load(['images', 'categorie']));
    }

    public function archive(Equipement $equipement)
    {
        $equipement->update(['is_archived' => true]);
        return response()->json(['message' => 'Équipement archivé avec succès']);
    }

    /**
     * supression d'équiment
     */
    public function destroy(Equipement $equipement)
    {
        $equipement->delete();
        return response()->json(null, 204);
    }
}
