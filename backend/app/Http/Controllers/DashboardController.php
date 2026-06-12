<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Equipement;
use App\Models\Affectation;
use App\Models\Panne;
use App\Models\Categorie;
use App\Models\Mouvement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();

            if ($user->role === 'agent') {
                return $this->agentStats($user);
            }

            return $this->adminStats($user);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du calcul des statistiques : ' . $e->getMessage()
            ], 500);
        }
    }

    private function adminStats($user)
    {
        // 1. Statistiques par État
        $query = Equipement::query();
        if ($user->role === 'gestionnaire' && $user->categorie_id) {
            $query->where('categorie_id', $user->categorie_id);
        }

        $totalEquipements = (clone $query)->count();
        
        $statsByEtatRaw = (clone $query)->select('etat', DB::raw('count(*) as total'))
            ->groupBy('etat')
            ->pluck('total', 'etat')
            ->toArray();

        $etats = ['neuf', 'en_service', 'en_panne', 'en_maintenance', 'reforme', 'perdu'];
        $formattedStats = [];
        foreach ($etats as $etat) {
            $formattedStats[$etat] = (int)($statsByEtatRaw[$etat] ?? 0);
        }

        // 2. Statistiques par Catégorie
        $statsByCategorie = Categorie::select('id', 'nom')
            ->withCount(['equipements' => function($q) use ($user) {
                if ($user->role === 'gestionnaire' && $user->categorie_id) {
                    $q->where('categorie_id', $user->categorie_id);
                }
            }])
            ->get()
            ->map(function($cat) {
                return [
                    'label' => $cat->nom,
                    'total' => (int)$cat->equipements_count
                ];
            });

        // 3. Évolution des 12 derniers mois (Optimisée en 2 requêtes au lieu de 24)
        $startDate = Carbon::now()->subMonths(11)->startOfMonth();
        
        // Affectations
        $assignments = Affectation::select(
                DB::raw('DATE_FORMAT(date_affectation, "%Y-%m") as month_key'),
                DB::raw('count(*) as total')
            )
            ->where('date_affectation', '>=', $startDate)
            ->when($user->role === 'gestionnaire' && $user->categorie_id, function($q) use ($user) {
                $q->whereHas('equipement', function($sq) use ($user) {
                    $sq->where('categorie_id', $user->categorie_id);
                });
            })
            ->groupBy('month_key')
            ->pluck('total', 'month_key')
            ->toArray();

        // Retours
        $returns = Affectation::select(
                DB::raw('DATE_FORMAT(date_retour, "%Y-%m") as month_key'),
                DB::raw('count(*) as total')
            )
            ->where('date_retour', '>=', $startDate)
            ->where('statut', 'retourne')
            ->when($user->role === 'gestionnaire' && $user->categorie_id, function($q) use ($user) {
                $q->whereHas('equipement', function($sq) use ($user) {
                    $sq->where('categorie_id', $user->categorie_id);
                });
            })
            ->groupBy('month_key')
            ->pluck('total', 'month_key')
            ->toArray();

        $monthsLabels = [];
        $assignmentsData = [];
        $returnsData = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $key = $date->format('Y-m');
            $monthsLabels[] = $date->format('M');
            $assignmentsData[] = (int)($assignments[$key] ?? 0);
            $returnsData[] = (int)($returns[$key] ?? 0);
        }

        // 4. Activité récente
        $recentActivity = Mouvement::with(['equipement', 'user'])
            ->when($user->role === 'gestionnaire' && $user->categorie_id, function($q) use ($user) {
                $q->whereHas('equipement', function($sq) use ($user) {
                    $sq->where('categorie_id', $user->categorie_id);
                });
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // 5. Alertes
        $alerts = [
            'pannes_critiques' => (int)Panne::where('gravite', 'critique')
                ->whereNotIn('statut', ['resolue', 'irrecuperable'])
                ->when($user->role === 'gestionnaire' && $user->categorie_id, function($q) use ($user) {
                    $q->whereHas('equipement', function($sq) use ($user) {
                        $sq->where('categorie_id', $user->categorie_id);
                    });
                })
                ->count(),
            'maintenances_en_cours' => (int)Panne::where('statut', 'en_maintenance')
                ->when($user->role === 'gestionnaire' && $user->categorie_id, function($q) use ($user) {
                    $q->whereHas('equipement', function($sq) use ($user) {
                        $sq->where('categorie_id', $user->categorie_id);
                    });
                })
                ->count(),
        ];

        return response()->json([
            'total_equipements' => $totalEquipements,
            'stats_etat' => $formattedStats,
            'stats_categorie' => $statsByCategorie,
            'evolution' => [
                'labels' => $monthsLabels,
                'assignments' => $assignmentsData,
                'returns' => $returnsData
            ],
            'recent_activity' => $recentActivity,
            'alerts' => $alerts
        ]);
    }

    private function agentStats($user)
    {
        if (!$user->agent) {
            return response()->json([
                'mes_equipements' => 0,
                'mes_pannes' => 0
            ]);
        }
        
        $mesEquipements = Equipement::whereHas('currentAffectation', function($q) use ($user) {
            $q->where('agent_id', $user->agent->id);
        })->count();

        $mesPannes = Panne::where('declare_par', $user->id)
            ->whereNotIn('statut', ['resolue', 'irrecuperable'])
            ->count();

        return response()->json([
            'mes_equipements' => $mesEquipements,
            'mes_pannes' => $mesPannes
        ]);
    }
}
