<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\Equipement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;


class AffectationController extends Controller
{
    public function index()
    {
        $affectations = Affectation::with(['equipement', 'agent', 'affectePar'])
            ->latest()
            ->paginate(2);
     return response()->json([
        'status' => 'success',
        'data' => $affectations->items(),
        'pagination' => [
        'current_page' => $affectations->currentPage(), //la page actuellement afficher (le numero de la page)
        'per_page' => $affectations->perPage(), //Nombre d'element par page 
        'total' => $affectations->total(),// Nombre total d'element récuperer
        'last_page' => $affectations->lastPage(),//Numero de la derniere page 
        'from' => $affectations->firstItem(),//Numero du premier élement afficher sur la page 
        'to' => $affectations->lastItem(),// Numero du dernier élement afficher su la page 
                        ]
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

            return response()->json([
                'status' => 'success',
                'message' => 'Affectation effectuée avec succès',
                'data' => $affectation->load(['equipement', 'agent', 'affectePar'])
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

    public function update(Request $request, Affectation $affectation)
    {
        try {
            // Seulement modification générale (plus de logique de retour ici)
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
                'data' => $affectation->load(['equipement', 'agent', 'affectePar'])
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


        // Récupérer les affectations à confirmer pour l'agent connecté
    public function getAConfirmer()
    {
        try {
            $user = Auth::user();

            // Vérifier que l'utilisateur est bien un agent et a une fiche agent liée
            if (!$user->agent) {
                return response()->json([
                    'status' => 'success',
                    'data' => []
                ]);
            }

            $affectations = Affectation::with(['equipement', 'agent', 'affectePar'])
                ->where('agent_id', $user->agent->id)
                ->where('statut', 'en_cours') // Seulement les affectations en cours (pas encore confirmées)
                ->latest()
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $affectations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération : ' . $e->getMessage()
            ], 500);
        }
    }

    // Confirmer la réception d'une affectation par l'agent
    public function confirmerReception(Affectation $affectation)
    {
        try {
            $user = Auth::user();

            // Vérifier que l'agent est bien le propriétaire de l'affectation
            if (!$user->agent || $user->agent->id !== $affectation->agent_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Vous ne pouvez pas confirmer cette affectation'
                ], 403);
            }

            // Vérifier que le statut est bien "en_cours"
            if ($affectation->statut !== 'en_cours') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cette affectation ne peut plus être confirmée'
                ], 422);
            }

            $affectation->update(['statut' => 'confirmee']);

            return response()->json([
                'status' => 'success',
                'message' => 'Réception confirmée avec succès',
                'data' => $affectation->load(['equipement', 'agent', 'affectePar'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la confirmation : ' . $e->getMessage()
            ], 500);
        }
    }

    // Demande de retour par l'agent
    public function requestReturn(Request $request, Affectation $affectation)
    {
        try {
            $user = Auth::user();

            // Vérifier que l'agent est bien le propriétaire de l'affectation
            if (!$user->agent || $user->agent->id !== $affectation->agent_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Vous ne pouvez pas demander le retour de cet équipement'
                ], 403);
            }

            // Vérifier que le statut est bien "confirmee"
            if (!in_array($affectation->statut, ['confirmee', 'en_cours'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cette affectation ne peut pas faire l\'objet d\'une demande de retour'
                ], 422);
            }

            $validated = $request->validate([
                'date_retour' => 'required|date',
                'etat_retour' => 'required|string',
                'photo_retour' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
                'observations' => 'nullable|string',
            ]);

            // Gestion de l'upload de la photo
            if ($request->hasFile('photo_retour')) {
                $file = $request->file('photo_retour');
                $filename = 'demande_retour_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('affectations', $filename, 'public');
                $validated['photo_retour'] = $path;
            }

            $validated['statut'] = 'retour_en_attente';
            $affectation->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Demande de retour envoyée avec succès',
                'data' => $affectation->load(['equipement', 'agent', 'affectePar'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la demande de retour : ' . $e->getMessage()
            ], 500);
        }
    }

    // Valider le retour par Admin/Gestionnaire et ajouter la photo en tant que nouvelle image de l'équipement
    public function validateReturn(Affectation $affectation)
    {
        try {
            // Vérifier que le statut est bien "retour_en_attente"
            if ($affectation->statut !== 'retour_en_attente') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cette affectation ne peut pas être validée'
                ], 422);
            }

            // Si une photo de retour existe, l'ajouter comme nouvelle image de l'équipement
            if ($affectation->photo_retour) {
                $affectation->equipement->images()->create([
                    'path' => $affectation->photo_retour
                ]);
            }

            $affectation->update(['statut' => 'retourne']);

            return response()->json([
                'status' => 'success',
                'message' => 'Retour validé avec succès',
                'data' => $affectation->load(['equipement', 'agent', 'affectePar'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la validation du retour : ' . $e->getMessage()
            ], 500);
        }
    }

    //Export pdf
    public function exportPdf(){
        try {
            $user=Auth::user();//recuperation de l'utilisateur connecter 
            $stats=$user->role !== 'agent';
            if($stats) {

                $this->affectationsExport($user);

                //Générer le PDF 
                $pdf = pdf::loadView('exports.affectation' , [
                    'stats' =>$stats,
                    'user' => $user,
                    'date' => Carbon::now()->format('d/m/y H:i')
                ]);

                //Télécharger le PDF 
                return $pdf ->download('rapport_affectation_' . Carbon::now()->format('d_m_Y_H_i') . '.pdf');
            }

        } catch (\Exception $e) {
             return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'export PDF : ' . $e->getMessage()
            ], 500);
        }
    }

    public function affectationsExport($user){
            if($user->role!=='agent'){

            }
    }


        //Export pour export excel
    public function export(Request $request)
{
    // 1. Préparation de la requête avec les relations (charge les données liées pour éviter le problème N+1)

    $query = Affectation::with([ 'agent', 'equipement']);

    // 2. Génération et téléchargement instantané du fichier Excel via une classe anonyme
    return Excel::download(new class($query) implements FromQuery, WithHeadings, WithMapping {
        
        protected $query;

        // Le constructeur reçoit la requête SQL préparée
        public function __construct($query) {
            $this->query = $query;
        }

        // On fournit la requête au moteur de Laravel Excel
        public function query() {
            return $this->query;
        }

        // Définition des titres des colonnes de la première ligne du fichier Excel
        public function headings(): array {
            return [
                'ID Affectation', 
                'Équipement / Matériel', 
                'Agent Bénéficiaire', 
                // 'Date d\'Affectation', 
                'Observations'
            ];
        }

        // Association des données de chaque ligne de ta base de données aux colonnes Excel
        public function map($affectation): array {
            return [
                $affectation->id,
                $affectation->equipement->agent ?? 'N/A', 
                $affectation->agent->nom ?? 'N/A',            // Remplace par le nom exact de ta colonne/relation
                // $affectation->date_affectation,
                $affectation->observations ?? 'Aucune observation'
            ];
        }
    }, 'export_affectations_' . time() . '.xlsx');
}
    // Rejeter le retour par Admin/Gestionnaire
    public function rejectReturn(Request $request, Affectation $affectation)
    {
        try {
            // Vérifier que le statut est bien "retour_en_attente"
            if ($affectation->statut !== 'retour_en_attente') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cette affectation ne peut pas être rejetée'
                ], 422);
            }

            $validated = $request->validate([
                'motif_rejet' => 'required|string'
            ]);

            // Remettre le statut à confirmee et enregistrer le motif
            $affectation->update([
                'statut' => 'confirmee',
                'motif_rejet' => $validated['motif_rejet']
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Retour rejeté avec succès',
                'data' => $affectation->load(['equipement', 'agent', 'affectePar'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du rejet du retour : ' . $e->getMessage()
            ], 500);
        }
    }
}
