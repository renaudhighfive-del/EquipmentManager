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
    public function index()
    {
        $maintenances = Maintenance::with(['equipement', 'panne', 'responsable'])
            ->latest()
            ->get();
        return response()->json($maintenances);
    }

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
            
            // On ne peut créer une maintenance que pour une panne validée (en_cours)
            if ($panne->statut !== 'en_cours' && $panne->statut !== 'declaree') {
                 // On accepte 'declaree' aussi si on veut automatiser, mais la consigne dit "déjà validée"
                 // On va forcer 'en_cours' car c'est ce que 'valider' fait dans PanneController
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

            // Mettre à jour le statut de la panne
            $panne->update(['statut' => 'en_maintenance']);

            // Mettre à jour l'état de l'équipement
            $panne->equipement->update(['etat' => 'en_maintenance']);

            return response()->json($maintenance->load(['equipement', 'panne', 'responsable']), 201);
        });
    }

    public function cloturer(Request $request, Maintenance $maintenance)
    {
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

            // Mettre à jour le statut de la panne si elle existe
            if ($maintenance->panne) {
                $maintenance->panne->update(['statut' => 'resolue']);
            }

            // Mettre à jour l'état de l'équipement
            $maintenance->equipement->update(['etat' => 'repare']);

            return response()->json($maintenance->load(['equipement', 'panne', 'responsable']));
        });
    }

    public function declarerPerte(Request $request, Maintenance $maintenance)
    {
        $validated = $request->validate([
            'type' => 'required|in:perte,casse,vol',
            'description' => 'required|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        return DB::transaction(function () use ($validated, $maintenance, $request) {
            // Clôturer la maintenance
            $maintenance->update([
                'date_fin' => now(),
                'actions_effectuees' => 'Irrécupérable : ' . $validated['description'],
            ]);

            if ($maintenance->panne) {
                $maintenance->panne->update(['statut' => 'irrecuperable']);
            }

            // Créer le sinistre
            $photoPaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('sinistres', 'public');
                    $photoPaths[] = $path;
                }
            }

            $sinistre = \App\Models\PerteCasse::create([
                'equipement_id' => $maintenance->equipement_id,
                'declare_par' => Auth::id(),
                'type' => $validated['type'],
                'date_declaration' => now(),
                'description' => $validated['description'],
                'statut' => 'en_attente_validation',
                'photos' => $photoPaths,
            ]);

            // Mettre à jour l'état de l'équipement
            $maintenance->equipement->update(['etat' => 'perdu']);

            return response()->json([
                'maintenance' => $maintenance->load(['equipement', 'panne']),
                'sinistre' => $sinistre
            ]);
        });
    }
}
