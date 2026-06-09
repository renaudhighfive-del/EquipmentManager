<?php

namespace App\Http\Controllers;

use App\Models\Equipement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EquipementController extends Controller
{
    /**
     * list d'équipment
     */
    public function index()
    {
        $user = Auth::user();
        $query = Equipement::with(['images', 'categorie', 'currentAffectation.agent'])
            ->where('is_archived', false);

        if ($user->role === 'agent') {
            $query->whereHas('currentAffectation', function ($q) use ($user) {
                $q->where('agent_id', $user->agent->id);
            });
        }

        return response()->json($query->latest()->get());
    }

    /**
     * create d'équipment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reference' => 'required|string|unique:equipements',
            'marque' => 'required|string',
            'modele' => 'required|string',
            'categorie_id' => 'required|exists:categories,id',
            'numero_serie' => 'nullable|string',
            'code_inventaire' => 'nullable|string',
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
     * detail d'équipment
     */
    public function show(Equipement $equipement)
    {
        return response()->json($equipement->load(['images', 'categorie', 'affectations.agent']));
    }

    /**
     * update d'équipment
     */
    public function update(Request $request, Equipement $equipement)
    {
        $validated = $request->validate([
            'reference' => 'sometimes|string|unique:equipements,reference,' . $equipement->id,
            'marque' => 'sometimes|string',
            'modele' => 'sometimes|string',
            'categorie_id' => 'sometimes|exists:categories,id',
            'numero_serie' => 'nullable|string',
            'code_inventaire' => 'nullable|string',
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
        return response()->json(['message' => 'Equipement archivé']);
    }

    public function unarchive(Equipement $equipement)
    {
        $equipement->update(['is_archived' => false]);
        return response()->json($equipement->load('categorie'));
    }

    public function fetchArchives()
    {
        $archives = Equipement::with('categorie')
            ->where('is_archived', true)
            ->latest()
            ->get();
        return response()->json($archives);
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
