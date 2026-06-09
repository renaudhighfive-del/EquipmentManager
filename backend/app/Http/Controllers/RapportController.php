<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\Agent;
use App\Models\Categorie;
use App\Models\Equipement;
use App\Models\Maintenance;
use App\Models\Mouvement;
use App\Models\Panne;
use App\Models\PerteCasse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RapportController extends Controller
{
    /**
     * Statistiques globales pour la vue Rapports.
     */
    public function stats(Request $request)
    {
        $periode = (int) $request->input('periode', 30); // jours
        $since   = Carbon::now()->subDays($periode)->startOfDay();

        // ── KPIs principaux ────────────────────────────────────────────────
        $totalEquipements    = Equipement::where('is_archived', false)->count();
        $enMaintenance       = Equipement::where('etat', 'en_maintenance')->count();
        $operationnels       = Equipement::whereIn('etat', ['neuf', 'en_service'])->count();
        $mouvements30j       = Mouvement::where('created_at', '>=', $since)->count();

        // ── Répartition par état ───────────────────────────────────────────
        $parEtat = Equipement::where('is_archived', false)
            ->select('etat', DB::raw('count(*) as total'))
            ->groupBy('etat')
            ->pluck('total', 'etat')
            ->toArray();

        $etatsLabels = [
            'neuf'               => 'Neuf',
            'en_service'         => 'En service',
            'en_panne'           => 'En panne',
            'en_maintenance'     => 'En maintenance',
            'en_attente_sinistre'=> 'Sinistre',
            'reforme'            => 'Réformé',
            'perdu'              => 'Perdu',
        ];

        $repartitionEtat = collect($etatsLabels)->map(fn ($label, $key) => [
            'etat'  => $key,
            'label' => $label,
            'total' => (int) ($parEtat[$key] ?? 0),
        ])->values();

        // ── Répartition par catégorie ──────────────────────────────────────
        $parCategorie = Categorie::withCount(['equipements' => fn ($q) => $q->where('is_archived', false)])
            ->get()
            ->map(fn ($c) => [
                'label' => $c->nom,
                'total' => (int) $c->equipements_count,
            ]);

        // ── Évolution 12 mois (affectations + retours) ────────────────────
        $start12m = Carbon::now()->subMonths(11)->startOfMonth();

        $assignsByMonth = Affectation::select(
                DB::raw("DATE_FORMAT(date_affectation, '%Y-%m') as mois"),
                DB::raw('count(*) as total')
            )
            ->where('date_affectation', '>=', $start12m)
            ->groupBy('mois')
            ->pluck('total', 'mois')
            ->toArray();

        $returnsByMonth = Affectation::select(
                DB::raw("DATE_FORMAT(date_retour, '%Y-%m') as mois"),
                DB::raw('count(*) as total')
            )
            ->where('date_retour', '>=', $start12m)
            ->whereNotNull('date_retour')
            ->groupBy('mois')
            ->pluck('total', 'mois')
            ->toArray();

        $labels = $affectData = $retourData = [];
        for ($i = 11; $i >= 0; $i--) {
            $d      = Carbon::now()->subMonths($i);
            $key    = $d->format('Y-m');
            $labels[]      = $d->locale('fr')->isoFormat('MMM');
            $affectData[]  = (int) ($assignsByMonth[$key] ?? 0);
            $retourData[]  = (int) ($returnsByMonth[$key] ?? 0);
        }

        // ── Pannes par gravité ─────────────────────────────────────────────
        $pannesParGravite = Panne::select('gravite', DB::raw('count(*) as total'))
            ->groupBy('gravite')
            ->pluck('total', 'gravite')
            ->toArray();

        // ── Maintenances ───────────────────────────────────────────────────
        $maintenancesEnCours  = Maintenance::whereNull('date_fin')->count();
        $maintenancesCloturees = Maintenance::whereNotNull('date_fin')
            ->where('date_fin', '>=', $since)
            ->count();
        $coutMaintenances = (float) Maintenance::whereNotNull('date_fin')
            ->where('date_fin', '>=', $since)
            ->sum('cout');

        // ── Sinistres ──────────────────────────────────────────────────────
        $sinistresEnAttente = PerteCasse::where('statut', 'en_attente_validation')->count();
        $sinistresValides   = PerteCasse::where('statut', 'validee')->count();

        // ── Top agents (matériels affectés) ───────────────────────────────
        $topAgents = Agent::with('user')
            ->having(DB::raw('count_aff'), '>', 0)
            ->select('agents.*', DB::raw(
                '(SELECT COUNT(*) FROM affectations WHERE affectations.agent_id = agents.id AND affectations.statut = "en_cours") as count_aff'
            ))
            ->orderByDesc('count_aff')
            ->limit(5)
            ->get()
            ->map(fn ($a) => [
                'nom'        => "{$a->prenom} {$a->nom}",
                'matricule'  => $a->matricule,
                'nb_affectes'=> (int) $a->count_aff,
            ]);

        // ── Activité récente ───────────────────────────────────────────────
        $activiteRecente = Mouvement::with(['equipement:id,marque,modele,reference', 'user:id,name'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn ($m) => [
                'type'       => $m->type_mouvement,
                'equipement' => $m->equipement
                    ? "{$m->equipement->marque} {$m->equipement->modele}"
                    : '—',
                'reference'  => $m->equipement?->reference ?? '—',
                'user'       => $m->user?->name ?? '—',
                'date'       => $m->created_at->diffForHumans(),
            ]);

        return response()->json([
            'kpis' => [
                ['label' => 'Équipements totaux',  'value' => $totalEquipements, 'change' => null, 'color' => 'blue'],
                ['label' => 'En maintenance',       'value' => $enMaintenance,   'change' => null, 'color' => 'amber'],
                ['label' => 'Opérationnels',        'value' => $operationnels,   'change' => null, 'color' => 'emerald'],
                ['label' => "Mouvements ({$periode}j)", 'value' => $mouvements30j, 'change' => null, 'color' => 'purple'],
            ],
            'repartition_etat'     => $repartitionEtat,
            'par_categorie'        => $parCategorie,
            'evolution'            => compact('labels', 'affectData', 'retourData'),
            'pannes_par_gravite'   => $pannesParGravite,
            'maintenances'         => compact('maintenancesEnCours', 'maintenancesCloturees', 'coutMaintenances'),
            'sinistres'            => compact('sinistresEnAttente', 'sinistresValides'),
            'top_agents'           => $topAgents,
            'activite_recente'     => $activiteRecente,
        ]);
    }
}
