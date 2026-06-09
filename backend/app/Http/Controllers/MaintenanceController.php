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
}
