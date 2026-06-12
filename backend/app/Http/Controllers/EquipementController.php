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
        //Récupère l'instance de l'utilisateur actuellement connecté et on Prépare une requête pour récupérer les équipements non archivés
        // 'with(...)' permet d'inclure directement les relations (Eager Loading) pour éviter les requêtes à répétition
        $user = Auth::user();
        $query = Equipement::with(['images', 'categorie', 'currentAffectation.agent', 'pendingAffectation.agent'])
            ->where('is_archived', false);
    //Restriction de sécurité : Si l'utilisateur connecté est un simple 'agent', L'agent ne doit pas voir les équipements marqués comme "perdu"
        if ($user->role === 'agent') {
            if (!$user->agent) {
                return response()->json([]);
            }
            $query->where('etat', '!=', 'perdu')
            //// Et on filtre pour qu'il ne voit QUE les équipements qui lui sont actuellement affectés (confirmés ou en attente de retour)
                ->whereHas('currentAffectation', function ($q) use ($user) {
                    $q->where('agent_id', $user->agent->id);
                });
        }

        return response()->json($query->latest()->get());
    }

    /**
     * create de nouvel équipment
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
        // Grâce au Route Model Binding, Laravel trouve automatiquement l'équipement via l'ID de l'URL.
        // On charge ses images, sa catégorie, et tout son historique d'affectations avec les agents.
        return response()->json($equipement->load(['images', 'categorie', 'affectations.agent']));
    }

    /**
     * Modification/Mise à jour d'un équipement existant
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
        // Vérifier s'il y a une affectation en cours ou confirmée (active)
        $hasActiveAffectation = $equipement->affectations()
            ->whereIn('statut', ['en_cours', 'confirmee'])
            ->exists();
        
        if ($hasActiveAffectation) {
            return response()->json([
                'message' => 'Impossible d\'archiver cet équipement : il a une affectation en cours. Faites d\'abord retourner l\'équipement.'
            ], 422);
        }
        
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
