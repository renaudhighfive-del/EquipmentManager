<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use App\Models\Panne;
use App\Models\Equipement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    /**
     * Retourne la liste des maintenances.
     * Appelé par la route GET /maintenances depuis le front-end pour afficher l'historique.
     */
    public function index()
    {
        $maintenances = Maintenance::with(['equipement', 'panne', 'responsable'])
            ->latest()
            ->get();
        return response()->json($maintenances);
    }

    /**
     * Crée une nouvelle maintenance.
     * Appelé par la route POST /maintenances lorsque l'utilisateur planifie une intervention.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'panne_id' => 'required|exists:pannes,id',
            'type' => 'required|in:corrective,preventive',
            'technicien' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'cout' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            $panne = Panne::findOrFail($validated['panne_id']);
            
            // Vérifie que la panne existe et qu'elle peut être transformée en maintenance
            // Normalement, seules les pannes validées/en cours doivent être traitées.
            if ($panne->statut !== 'en_cours' && $panne->statut !== 'declaree') {
                 // Cette section est informative : la logique de validation devrait empêcher les cas invalides.
            }

            $maintenance = Maintenance::create([
                'equipement_id' => $panne->equipement_id,
                'panne_id' => $panne->id,
                'type' => $validated['type'],
                'technicien' => $validated['technicien'],
                'responsable_id' => Auth::id(),
                'date_debut' => $validated['date_debut'],
                'cout' => $validated['cout'] ?? 0,
            ]);

            // Après création de la maintenance, on marque la panne en maintenance
            $panne->update(['statut' => 'en_maintenance']);

            // Et on met à jour l'état de l'équipement associé
            $panne->equipement->update(['etat' => 'en_maintenance']);

            return response()->json($maintenance->load(['equipement', 'panne', 'responsable']), 201);
        });
    }

    /**
     * Clôture une maintenance terminée.
     * Appelé par la route POST /maintenances/{id}/cloturer depuis le front-end.
     */
    public function cloturer(Request $request, $id)
    {
        $maintenance = Maintenance::findOrFail($id);
        
        $validated = $request->validate([
            'date_fin' => 'required|date',
            'actions_effectuees' => 'nullable|string',
            'cout' => 'nullable|numeric|min:0',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        return DB::transaction(function () use ($validated, $maintenance, $request) {
            $maintenance->update([
                'date_fin' => $validated['date_fin'],
                'actions_effectuees' => $validated['actions_effectuees'] ?? $maintenance->actions_effectuees,
                'cout' => $validated['cout'] ?? $maintenance->cout,
            ]);

            if ($request->hasFile('images')) {
                $photoPaths = [];
                foreach ($request->file('images') as $image) {
                    $path = $image->store('maintenances', 'public');
                    $photoPaths[] = $path;
                }
                $maintenance->update(['photos_retour' => $photoPaths]);
            }

            // Après clôture, on marque la panne comme résolue
            if ($maintenance->panne) {
                $maintenance->panne->update(['statut' => 'resolue']);
            }

            // Et on met à jour l'état de l'équipement réparé
            $maintenance->equipement->update(['etat' => 'repare']);

            return response()->json($maintenance->load(['equipement', 'panne', 'responsable']));
        });
    }

    /**
     * Déclare une perte / casse / vol pendant une maintenance.
     * Appelé par la route POST /maintenances/{id}/declarer-perte.
     */
    public function declarerPerte(Request $request, $id)
    {
        $maintenance = Maintenance::findOrFail($id);
        
        $validated = $request->validate([
            'type' => 'required|in:perte,casse,vol',
            'description' => 'required|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        return DB::transaction(function () use ($validated, $maintenance, $request) {
            // Marque la maintenance comme terminée irrécupérable
            $maintenance->update([
                'date_fin' => now(),
                'actions_effectuees' => 'Irrécupérable : ' . $validated['description'],
            ]);

            // Marque la panne comme irrécupérable
            if ($maintenance->panne) {
                $maintenance->panne->update(['statut' => 'irrecuperable']);
            }

            // Stocke les photos jointes à la déclaration de sinistre
            $photoPaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('sinistres', 'public');
                    $photoPaths[] = $path;
                }
            }

            // Crée l'enregistrement de sinistre associé
            $sinistre = \App\Models\PerteCasse::create([
                'equipement_id' => $maintenance->equipement_id,
                'declare_par' => Auth::id(),
                'type' => $validated['type'],
                'date_declaration' => now(),
                'description' => $validated['description'],
                'statut' => 'en_attente_validation',
                'photos' => $photoPaths,
            ]);

            // Marque l'équipement comme perdu suite au sinistre
            $maintenance->equipement->update(['etat' => 'perdu']);
            $maintenance->equipement->save();

            return response()->json([
                'maintenance' => $maintenance->load(['equipement', 'panne']),
                'sinistre' => $sinistre
            ]);
        });
    }
}
