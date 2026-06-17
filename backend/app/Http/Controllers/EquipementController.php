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
    /**
     * Retourne la liste des équipements non archivés.
     * Appelé par la route GET /equipements ou par le store front-end qui charge la liste des équipements.
     */
    public function index()
    {
        // Récupère l'utilisateur connecté pour appliquer des restrictions selon son rôle
        // 'with(...)' permet d'inclure les relations nécessaires en une seule requête
        $user = Auth::user();
        $query = Equipement::with(['images', 'categorie', 'currentAffectation.agent', 'pendingAffectation.agent', 'latestAffectation.agent', 'latestAffectation.affectePar'])
            ->where('is_archived', false);

        // Si l'utilisateur est un agent, limiter l'affichage aux équipements qui lui sont affectés,
        // et lui cacher les équipements marqués comme 'perdu'
        if ($user->role === 'agent') {
            if (!$user->agent) {
                return response()->json([]);
            }
            $query->where('etat', '!=', 'perdu')
                ->whereHas('latestAffectation', function ($q) use ($user) {
                    $q->where('agent_id', $user->agent->id);
                });
        }

        return response()->json($query->latest()->get());
    }

    /**
     * create de nouvel équipment
     */
    /**
     * Créé un nouvel équipement.
     * Appelé par la route POST /equipements depuis le front-end lors de la création d'un équipement.
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
    /**
     * Retourne les détails d'un équipement.
     * Appelé par la route GET /equipements/{equipement} via route model binding.
     */
    public function show(Equipement $equipement)
    {
        // On charge les relations nécessaires pour afficher les détails et l'historique
        return response()->json($equipement->load(['images', 'categorie', 'affectations.agent']));
    }

    /**
     * Met à jour un équipement existant.
     * Appelé par la route PUT/PATCH /equipements/{equipement} depuis le front-end.
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

    /**
     * Archive un équipement.
     * Appelé par la route POST /equipements/{equipement}/archive ou depuis le front-end lorsque l'utilisateur archivate.
     */
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

    /**
     * Désarchive un équipement.
     * Appelé par la route POST /equipements/{equipement}/unarchive ou depuis le front-end.
     */
    public function unarchive(Equipement $equipement)
    {
        $equipement->update(['is_archived' => false]);
        return response()->json($equipement->load('categorie'));
    }

    /**
     * Retourne la liste des équipements archivés.
     * Appelé par la route GET /equipements/archives ou équivalent côté API.
     */
    public function fetchArchives()
    {
        $archives = Equipement::with('categorie')
            ->where('is_archived', true)
            ->latest()
            ->get();
        return response()->json($archives);
    }

    /**
     * Supprime un équipement définitivement.
     * Appelé par la route DELETE /equipements/{equipement}.
     */
    public function destroy(Equipement $equipement)
    {
        $equipement->delete();
        return response()->json(null, 204);
    }
}
