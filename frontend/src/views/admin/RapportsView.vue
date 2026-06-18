<script setup>
import { computed, onMounted } from 'vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import { useRapportStore } from '../../stores/rapport'
import {
  BarChart3, TrendingUp, AlertCircle, CheckCircle2,
  Wrench, AlertOctagon, Activity, Users,
  Package, Loader2, RefreshCw, ArrowLeftRight
} from '@lucide/vue'

const store = useRapportStore()

// ── Helpers ────────────────────────────────────────────────────────────
const kpiIcon = (color) => ({
  blue:    BarChart3,
  amber:   Wrench,
  emerald: CheckCircle2,
  purple:  TrendingUp,
}[color] ?? BarChart3)

const kpiBg = (color) => ({
  blue:    'bg-blue-50 text-blue-600',
  amber:   'bg-amber-50 text-amber-600',
  emerald: 'bg-emerald-50 text-emerald-600',
  purple:  'bg-purple-50 text-purple-600',
}[color] ?? 'bg-slate-50 text-slate-600')

const etatColor = (etat) => ({
  neuf:                'bg-blue-500',
  en_service:          'bg-emerald-500',
  en_panne:            'bg-red-500',
  en_maintenance:      'bg-amber-500',
  en_attente_sinistre: 'bg-orange-500',
  reforme:             'bg-slate-400',
  perdu:               'bg-rose-600',
}[etat] ?? 'bg-slate-300')

const graviteColor = (g) => ({
  faible:   'bg-blue-100 text-blue-700',
  moyenne:  'bg-amber-100 text-amber-700',
  critique: 'bg-red-100 text-red-700',
}[g] ?? 'bg-slate-100 text-slate-600')

const mouvementIcon = (type) => ({
  affectation:       ArrowLeftRight,
  retour:            ArrowLeftRight,
  panne_declaree:    AlertCircle,
  maintenance_debut: Wrench,
  maintenance_fin:   CheckCircle2,
  perte_declaree:    AlertOctagon,
  changement_etat:   Activity,
}[type] ?? Activity)

const mouvementColor = (type) => ({
  affectation:       'bg-blue-50 text-blue-600',
  retour:            'bg-slate-100 text-slate-600',
  panne_declaree:    'bg-red-50 text-red-600',
  maintenance_debut: 'bg-amber-50 text-amber-600',
  maintenance_fin:   'bg-emerald-50 text-emerald-600',
  perte_declaree:    'bg-rose-50 text-rose-600',
  changement_etat:   'bg-purple-50 text-purple-600',
}[type] ?? 'bg-slate-50 text-slate-600')

// Max pour la barre de progression
const maxCategorie = computed(() =>
  Math.max(...store.parCategorie.map(c => c.total), 1)
)
const maxEtat = computed(() =>
  Math.max(...store.repartitionEtat.map(e => e.total), 1)
)
const maxEvol = computed(() =>
  Math.max(...store.evolution.affectData, ...store.evolution.retourData, 1)
)

onMounted(() => store.fetchStats())
</script>

<template>
  <div class="space-y-8 animate-in fade-in duration-500">

    <!-- Header -->
    <PageHeader title="Rapports & Statistiques" subtitle="Analyse en temps réel du parc d'équipements">
      <template #actions>
        <!-- Sélecteur période -->
        <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl">
          <button
            v-for="p in [7, 30, 90]"
            :key="p"
            @click="store.setPeriode(p)"
            :class="['px-4 py-2 rounded-lg text-xs font-bold transition-all', store.periode === p ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700']"
          >
            {{ p }}j
          </button>
        </div>
        <button
          @click="store.fetchStats()"
          :disabled="store.loading"
          class="flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all shadow-sm disabled:opacity-50"
        >
          <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': store.loading }" />
          Actualiser
        </button>
      </template>
    </PageHeader>

    <!-- Loader global -->
    <div v-if="store.loading && !store.kpis.length" class="flex items-center justify-center py-24">
      <div class="flex flex-col items-center gap-3 text-slate-400">
        <Loader2 class="w-10 h-10 animate-spin" />
        <p class="text-sm font-medium">Chargement des statistiques...</p>
      </div>
    </div>

    <template v-else>

      <!-- ── KPIs ─────────────────────────────────────────────────────── -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div
          v-for="kpi in store.kpis"
          :key="kpi.label"
          class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all"
        >
          <div class="flex items-start justify-between mb-4">
            <div :class="['p-3 rounded-2xl', kpiBg(kpi.color)]">
              <component :is="kpiIcon(kpi.color)" class="w-6 h-6" />
            </div>
          </div>
          <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">{{ kpi.label }}</p>
          <h3 class="text-3xl font-black text-slate-900">{{ kpi.value.toLocaleString('fr-FR') }}</h3>
        </div>
      </div>

      <!-- ── Ligne 2 : Répartition état + Catégories ────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Répartition par état -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-7 py-5 border-b border-slate-100 flex items-center gap-3">
            <div class="w-8 h-8 bg-slate-100 rounded-xl flex items-center justify-center">
              <Package class="w-4 h-4 text-slate-600" />
            </div>
            <h3 class="text-sm font-bold text-slate-900">Répartition par état</h3>
          </div>
          <div class="p-7 space-y-4">
            <div v-for="item in store.repartitionEtat.filter(e => e.total > 0)" :key="item.etat">
              <div class="flex items-center justify-between mb-1.5">
                <div class="flex items-center gap-2">
                  <div :class="['w-2.5 h-2.5 rounded-full flex-shrink-0', etatColor(item.etat)]"></div>
                  <span class="text-xs font-semibold text-slate-700">{{ item.label }}</span>
                </div>
                <span class="text-xs font-black text-slate-900 tabular-nums">{{ item.total }}</span>
              </div>
              <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                <div
                  :class="['h-full rounded-full transition-all duration-700', etatColor(item.etat)]"
                  :style="{ width: `${Math.round((item.total / maxEtat) * 100)}%` }"
                ></div>
              </div>
            </div>
            <p v-if="!store.repartitionEtat.some(e => e.total > 0)" class="text-sm text-slate-400 text-center py-4">
              Aucune donnée
            </p>
          </div>
        </div>

        <!-- Par catégorie -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-7 py-5 border-b border-slate-100 flex items-center gap-3">
            <div class="w-8 h-8 bg-slate-100 rounded-xl flex items-center justify-center">
              <BarChart3 class="w-4 h-4 text-slate-600" />
            </div>
            <h3 class="text-sm font-bold text-slate-900">Par catégorie</h3>
          </div>
          <div class="p-7 space-y-4">
            <div v-for="cat in store.parCategorie.filter(c => c.total > 0)" :key="cat.label">
              <div class="flex items-center justify-between mb-1.5">
                <span class="text-xs font-semibold text-slate-700">{{ cat.label }}</span>
                <span class="text-xs font-black text-slate-900 tabular-nums">{{ cat.total }}</span>
              </div>
              <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                <div
                  class="h-full bg-primary-500 rounded-full transition-all duration-700"
                  :style="{ width: `${Math.round((cat.total / maxCategorie) * 100)}%` }"
                ></div>
              </div>
            </div>
            <p v-if="!store.parCategorie.some(c => c.total > 0)" class="text-sm text-slate-400 text-center py-4">
              Aucune catégorie
            </p>
          </div>
        </div>
      </div>

      <!-- ── Évolution 12 mois ───────────────────────────────────────── -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-7 py-5 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-primary-50 rounded-xl flex items-center justify-center">
              <TrendingUp class="w-4 h-4 text-primary-600" />
            </div>
            <h3 class="text-sm font-bold text-slate-900">Évolution sur 12 mois</h3>
          </div>
          <div class="flex items-center gap-4 text-xs font-semibold">
            <span class="flex items-center gap-1.5"><span class="w-3 h-1.5 bg-primary-500 rounded-full inline-block"></span>Affectations</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-1.5 bg-slate-300 rounded-full inline-block"></span>Retours</span>
          </div>
        </div>
        <div class="p-7">
          <div class="flex items-end gap-2 h-40">
            <div
              v-for="(label, i) in store.evolution.labels"
              :key="i"
              class="flex-1 flex flex-col items-center gap-1"
            >
              <div class="w-full flex items-end gap-0.5 justify-center" style="height: 120px">
                <!-- Barre affectations -->
                <div
                  class="flex-1 bg-primary-500 rounded-t-lg transition-all duration-700 min-h-[2px]"
                  :style="{ height: `${Math.max(2, Math.round((store.evolution.affectData[i] / maxEvol) * 120))}px` }"
                  :title="`Affectations : ${store.evolution.affectData[i]}`"
                ></div>
                <!-- Barre retours -->
                <div
                  class="flex-1 bg-slate-200 rounded-t-lg transition-all duration-700 min-h-[2px]"
                  :style="{ height: `${Math.max(2, Math.round((store.evolution.retourData[i] / maxEvol) * 120))}px` }"
                  :title="`Retours : ${store.evolution.retourData[i]}`"
                ></div>
              </div>
              <span class="text-[10px] text-slate-400 font-medium">{{ label }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Ligne 3 : Pannes + Maintenances + Sinistres ─────────────── -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Pannes par gravité -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-4">
          <div class="flex items-center gap-2 mb-2">
            <AlertCircle class="w-4 h-4 text-rose-500" />
            <h3 class="text-sm font-bold text-slate-900">Pannes par gravité</h3>
          </div>
          <div class="space-y-3">
            <div
              v-for="(label, key) in { faible: 'Faible', moyenne: 'Moyenne', critique: 'Critique' }"
              :key="key"
              class="flex items-center justify-between"
            >
              <span :class="['px-2.5 py-1 rounded-lg text-[10px] font-bold', graviteColor(key)]">{{ label }}</span>
              <span class="text-xl font-black text-slate-900 tabular-nums">
                {{ store.pannesParGravite[key] ?? 0 }}
              </span>
            </div>
          </div>
        </div>

        <!-- Maintenances -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-4">
          <div class="flex items-center gap-2 mb-2">
            <Wrench class="w-4 h-4 text-amber-500" />
            <h3 class="text-sm font-bold text-slate-900">Maintenances</h3>
          </div>
          <div class="space-y-3">
            <div class="flex items-center justify-between p-3 bg-amber-50 rounded-2xl">
              <span class="text-xs font-semibold text-amber-700">En cours</span>
              <span class="text-xl font-black text-amber-700 tabular-nums">{{ store.maintenances.maintenancesEnCours ?? 0 }}</span>
            </div>
            <div class="flex items-center justify-between p-3 bg-emerald-50 rounded-2xl">
              <span class="text-xs font-semibold text-emerald-700">Clôturées (période)</span>
              <span class="text-xl font-black text-emerald-700 tabular-nums">{{ store.maintenances.maintenancesCloturees ?? 0 }}</span>
            </div>
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl">
              <span class="text-xs font-semibold text-slate-600">Coût total (période)</span>
              <span class="text-sm font-black text-slate-900">{{ Number(store.maintenances.coutMaintenances ?? 0).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' }) }}</span>
            </div>
          </div>
        </div>

        <!-- Sinistres -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-4">
          <div class="flex items-center gap-2 mb-2">
            <AlertOctagon class="w-4 h-4 text-orange-500" />
            <h3 class="text-sm font-bold text-slate-900">Pertes & Casses</h3>
          </div>
          <div class="space-y-3">
            <div class="flex items-center justify-between p-3 bg-orange-50 rounded-2xl">
              <span class="text-xs font-semibold text-orange-700">En attente</span>
              <span class="text-xl font-black text-orange-700 tabular-nums">{{ store.sinistres.sinistresEnAttente ?? 0 }}</span>
            </div>
            <div class="flex items-center justify-between p-3 bg-rose-50 rounded-2xl">
              <span class="text-xs font-semibold text-rose-700">Validés</span>
              <span class="text-xl font-black text-rose-700 tabular-nums">{{ store.sinistres.sinistresValides ?? 0 }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Ligne 4 : Top agents + Activité récente ─────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Top agents -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-7 py-5 border-b border-slate-100 flex items-center gap-3">
            <div class="w-8 h-8 bg-blue-50 rounded-xl flex items-center justify-center">
              <Users class="w-4 h-4 text-blue-600" />
            </div>
            <h3 class="text-sm font-bold text-slate-900">Top agents — matériels affectés</h3>
          </div>
          <div class="divide-y divide-slate-50">
            <div
              v-for="(agent, i) in store.topAgents"
              :key="i"
              class="px-7 py-4 flex items-center justify-between hover:bg-slate-50/50 transition-colors"
            >
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-primary-100 flex items-center justify-center text-primary-700 text-xs font-black flex-shrink-0">
                  {{ i + 1 }}
                </div>
                <div>
                  <p class="text-sm font-bold text-slate-900">{{ agent.nom }}</p>
                  <p class="text-[10px] font-mono text-slate-400">{{ agent.matricule }}</p>
                </div>
              </div>
              <span class="text-sm font-black text-primary-600 bg-primary-50 px-3 py-1 rounded-lg tabular-nums">
                {{ agent.nb_affectes }} appareil(s)
              </span>
            </div>
            <div v-if="!store.topAgents.length" class="px-7 py-10 text-center text-sm text-slate-400">
              Aucune affectation en cours
            </div>
          </div>
        </div>

        <!-- Activité récente -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-7 py-5 border-b border-slate-100 flex items-center gap-3">
            <div class="w-8 h-8 bg-purple-50 rounded-xl flex items-center justify-center">
              <Activity class="w-4 h-4 text-purple-600" />
            </div>
            <h3 class="text-sm font-bold text-slate-900">Activité récente</h3>
          </div>
          <div class="divide-y divide-slate-50">
            <div
              v-for="(evt, i) in store.activiteRecente"
              :key="i"
              class="px-7 py-3.5 flex items-center gap-4 hover:bg-slate-50/50 transition-colors"
            >
              <div :class="['w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0', mouvementColor(evt.type)]">
                <component :is="mouvementIcon(evt.type)" class="w-3.5 h-3.5" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">
                  {{ evt.type.replace(/_/g, ' ') }} — {{ evt.equipement }}
                </p>
                <p class="text-[10px] text-slate-400 font-medium">{{ evt.user }} · {{ evt.date }}</p>
              </div>
              <span class="text-[10px] font-mono text-slate-400 flex-shrink-0">{{ evt.reference }}</span>
            </div>
            <div v-if="!store.activiteRecente.length" class="px-7 py-10 text-center text-sm text-slate-400">
              Aucune activité récente
            </div>
          </div>
        </div>
      </div>

    </template>
  </div>
</template>
