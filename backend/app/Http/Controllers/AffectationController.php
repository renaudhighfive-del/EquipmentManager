<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\Equipement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AffectationController extends Controller
{
    public function index()
    {
        $affectations = Affectation::with(['equipement', 'agent', 'affectePar'])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $affectations
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'equipement_id' => 'required|exists:equipements,id',
                'agent_id' => 'required|exists:agents,id',
                'date_affectation' => 'required|date|before_or_equal:today',
                'photo_remise' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
                'observations' => 'nullable|string',
            ], [
                'date_affectation.before_or_equal' => 'La date d\'affectation ne peut pas être une date future.',
            ]);

            // Vérifier si l'équipement est disponible
            $equipement = Equipement::findOrFail($validated['equipement_id']);
            if ($equipement->etat !== 'neuf') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cet équipement n\'est pas disponible pour une affectation (État actuel: ' . $equipement->etat . '). Un équipement doit être à l\'état "neuf" pour être affecté.'
                ], 422);
            }

            // Gestion de l'upload de la photo
            if ($request->hasFile('photo_remise')) {
                $file = $request->file('photo_remise');
                $filename = 'remise_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('affectations', $filename, 'public');
                $validated['photo_remise'] = $path;
            }

            // Ajouter l'auteur et le statut
            $validated['affecte_par'] = Auth::id() ?? 1;
            $validated['statut'] = 'en_cours';

            $affectation = Affectation::create($validated);

            // Mettre à jour l'équipement
            $equipement->update(['etat' => 'en_service']);

            return response()->json([
                'status' => 'success',
                'message' => 'Affectation effectuée avec succès',
                'data' => $affectation->load(['equipement', 'agent'])
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la création : ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Affectation $affectation)
    {
        try {
            $affectation->load(['equipement', 'agent', 'affectePar', 'mouvements.user']);
            return response()->json([
                'status' => 'success',
                'data' => $affectation
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de récupérer les détails de l\'affectation : ' . $e->getMessage()
            ], 500);
        }
    }

    // Demande de retour par l'agent
    public function requestReturn(Request $request, Affectation $affectation)
    {
        try {
            if ($affectation->statut !== 'en_cours') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cette affectation n\'est pas en cours'
                ], 422);
            }

            $validated = $request->validate([
                'date_retour' => 'required|date',
                'etat_retour' => 'required|string',
                'photo_retour' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
                'observations' => 'nullable|string',
            ]);

            if ($request->hasFile('photo_retour')) {
                $file = $request->file('photo_retour');
                $filename = 'retour_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('affectations', $filename, 'public');
                $validated['photo_retour'] = $path;
            }

            $validated['statut'] = 'retour_en_attente';
            $affectation->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Demande de retour envoyée, en attente de validation',
                'data' => $affectation->load(['equipement', 'agent'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue : ' . $e->getMessage()
            ], 500);
        }
    }

    // Validation du retour par l'admin/gestionnaire
    public function validateReturn(Request $request, Affectation $affectation)
    {
        try {
            if ($affectation->statut !== 'retour_en_attente') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cette affectation n\'est pas en attente de validation de retour'
                ], 422);
            }

            $affectation->update(['statut' => 'retourne']);

            // Mettre à jour l'équipement pour le rendre disponible
            $equipement = $affectation->equipement;
            if ($equipement) {
                $equipement->update(['etat' => 'neuf']);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Retour validé avec succès, équipement disponible',
                'data' => $affectation->load(['equipement', 'agent'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue : ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Affectation $affectation)
    {
        try {
            // Modification générale seulement (pas de retour)
            $validated = $request->validate([
                'agent_id' => 'sometimes|required|exists:agents,id',
                'date_affectation' => 'sometimes|required|date|before_or_equal:today',
                'observations' => 'nullable|string',
                'photo_remise' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            ], [
                'date_affectation.before_or_equal' => 'La date d\'affectation ne peut pas être une date future.',
            ]);

            if ($request->hasFile('photo_remise')) {
                $file = $request->file('photo_remise');
                $filename = 'remise_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('affectations', $filename, 'public');
                $validated['photo_remise'] = $path;
            }

            $affectation->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Affectation mise à jour avec succès',
                'data' => $affectation->load(['equipement', 'agent'])
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la mise à jour : ' . $e->getMessage()
            ], 500);
        }
    }
}
