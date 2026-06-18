<script setup>
import { onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useMouvementStore } from '../../stores/mouvement';
import PageHeader from '../../components/layout/PageHeader.vue';
import { 
  ArrowLeftRight, 
  RotateCcw, 
  AlertTriangle, 
  Wrench, 
  AlertOctagon, 
  History,
  Trash2,
  Loader2,
  Filter,
  X
} from '@lucide/vue';

import Paginator from 'primevue/paginator'

const mouvementStore = useMouvementStore();
const { mouvements, loading, pagination } = storeToRefs(mouvementStore);

// --- 1. VARIABLES DES FILTRES DEMANDÉS ---
const filters = ref({
  type_mouvement: '',
  user_name: '',
  date_debut: ''
});

const currentPage=ref(1)

const onPageChange = (event) => {
  currentPage.value = event.page + 1 
  mouvementStore.fetchMouvements(filters.value ,currentPage.value)
}

onMounted(() => {
  mouvementStore.fetchMouvements({},1);
});

// --- 2. LOGIQUE DE FILTRAGE ---
const handleFilter = () => {
  const activeFilters = Object.fromEntries(
    Object.entries(filters.value).filter(([_, v]) => v !== '')
  );
  currentPage.value=1
  mouvementStore.fetchMouvements(activeFilters , 1);
};

const resetFilters = () => {
  filters.value = { type_mouvement: '', user_name: '', date_debut: '' };
  currentPage.value=1;
  mouvementStore.fetchMouvements({},1);
};

const getMouvementConfig = (type) => {
  const configs = {
    'affectation': { label: 'Affectation', icon: ArrowLeftRight, color: 'text-blue-500', bg: 'bg-blue-50' },
    'reception_confirmee': { label: 'Réception confirmée', icon: ArrowLeftRight, color: 'text-indigo-500', bg: 'bg-indigo-50' },
    'retour_demande': { label: 'Demande de retour', icon: RotateCcw, color: 'text-orange-500', bg: 'bg-orange-50' },
    'retour_valide': { label: 'Retour validé', icon: RotateCcw, color: 'text-emerald-500', bg: 'bg-emerald-50' },
    'retour': { label: 'Retour', icon: RotateCcw, color: 'text-emerald-500', bg: 'bg-emerald-50' },
    'panne': { label: 'Déclaration panne', icon: AlertTriangle, color: 'text-amber-500', bg: 'bg-amber-50' },
    'maintenance': { label: 'Maintenance', icon: Wrench, color: 'text-blue-600', bg: 'bg-blue-50' },
    'sinistre': { label: 'Sinistre', icon: AlertOctagon, color: 'text-rose-500', bg: 'bg-rose-50' },
    'perte': { label: 'Perte', icon: Trash2, color: 'text-slate-600', bg: 'bg-slate-100' }
  };
  
  return configs[type] || { label: type, icon: History, color: 'text-slate-400', bg: 'bg-slate-50' };
};

const formatTime = (dateStr) => {
  return new Date(dateStr).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};

const formatDate = (dateStr) => {
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
};
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <PageHeader 
      title="Journal des mouvements" 
      subtitle="Historique immuable de toutes les opérations"
    />

    <!-- --- 3. BLOC DE FILTRES MIS À JOUR --- -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex flex-wrap items-center gap-4">
      
      <!-- Filtre 1 : Type d'opération -->
      <div class="flex-1 min-w-[200px]">
        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Type d'opération</label>
        <select 
          v-model="filters.type_mouvement" 
          class="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-500"
        >
          <option value="">Tous les types</option>
          <option value="affectation">Affectation</option>
          <option value="reception_confirmee">Réception confirmée</option>
          <option value="retour_demande">Demande de retour</option>
          <option value="retour_valide">Retour validé</option>
          <option value="retour">Retour</option>
          <option value="panne">Déclaration panne</option>
          <option value="maintenance">Maintenance</option>
          <option value="sinistre">Sinistre</option>
          <option value="perte">Perte</option>
        </select>
      </div>

      <!-- Filtre 2 : Qui a fait le mouvement (Auteur) -->
      <div class="flex-1 min-w-[200px]">
        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nom de l'Auteur</label>
        <input 
          v-model="filters.user_name" 
          type="text" 
          placeholder="Ex: Nom Utilisateur"  
          class="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-500"
        />
      </div>

      <!-- Filtre 3 : Date de début -->
      <div class="flex-1 min-w-[200px]">
        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Depuis le</label>
        <input 
          v-model="filters.date_debut" 
          type="date" 
          class="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-500"
        />
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-2 self-end pt-5">
        <button 
          @click="handleFilter"
          class="flex items-center gap-2 bg-slate-900 text-white font-medium text-sm px-4 py-2.5 rounded-xl hover:bg-slate-800 transition"
        >
          <Filter class="w-4 h-4" />
          Filtrer
        </button>
        
        <button 
          @click="resetFilters"
          class="flex items-center gap-2 bg-slate-100 text-slate-600 font-medium text-sm px-4 py-2.5 rounded-xl hover:bg-slate-200 transition"
        >
          <X class="w-4 h-4" />
          Vider
        </button>
      </div>
    </div>

    <!-- ÉTAT CHARGEMENT -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-slate-200">
      <Loader2 class="w-10 h-10 text-primary-500 animate-spin mb-4" />
      <p class="text-slate-500 font-medium">Chargement de l'historique...</p>
    </div>

    <!-- ÉTAT AUCUNE DONNÉE -->
    <div v-else-if="mouvements.length === 0" class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-slate-200">
      <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mb-4">
        <History class="w-8 h-8 text-slate-300" />
      </div>
      <p class="text-slate-500 font-medium text-lg">Aucun mouvement trouvé avec ces filtres</p>
    </div>

    <!-- AFFICHAGE LISTE -->
    <div v-else class="bg-white rounded-3xl border border-slate-200 shadow-sm p-8">
      <div class="space-y-8 relative before:absolute before:left-[23px] before:top-4 before:bottom-4 before:w-[2px] before:bg-slate-50">
        <div v-for="mvt in mouvements" :key="mvt.id" class="relative pl-16 group">
          <!-- Icon -->
          <div 
            class="absolute left-0 top-0 w-12 h-12 rounded-2xl flex items-center justify-center transition-all group-hover:scale-110 z-10 border-4 border-white shadow-sm"
            :class="[getMouvementConfig(mvt.type_mouvement).bg, getMouvementConfig(mvt.type_mouvement).color]"
          >
            <component :is="getMouvementConfig(mvt.type_mouvement).icon" class="w-5 h-5" />
          </div>

          <!-- Content -->
          <div class="flex items-center justify-between gap-4 border-b border-slate-50 pb-6 group-last:border-0 group-last:pb-0">
            <div>
              <h4 class="text-base font-bold text-slate-900 flex items-center gap-2">
                {{ getMouvementConfig(mvt.type_mouvement).label }} 
                <span class="text-slate-300 font-normal">•</span> 
                <span class="font-mono text-sm text-slate-500">{{ mvt.equipement?.reference }}</span>
              </h4>
              <p class="text-sm text-slate-500 font-medium mt-1">
                {{ mvt.equipement?.marque }} {{ mvt.equipement?.modele }} 
                <span class="text-slate-300 px-1">•</span>
                {{ formatDate(mvt.created_at) }}
              </p>
              <div class="mt-2 flex items-center gap-2">
                <div class="inline-flex px-2 py-0.5 bg-slate-100 text-[10px] font-bold text-slate-500 rounded-md uppercase tracking-wider">
                  {{ mvt.user?.name || 'Système' }}
                </div>
                <div v-if="mvt.nouvelle_valeur?.agent_id" class="inline-flex px-2 py-0.5 bg-primary-50 text-[10px] font-bold text-primary-600 rounded-md uppercase tracking-wider">
                  Agent ID: {{ mvt.nouvelle_valeur.agent_id }}
                </div>
              </div>
            </div>
            <div class="text-right">
              <span class="text-sm font-mono font-medium text-slate-400">{{ formatTime(mvt.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

     <Paginator
  :first="(mouvementStore.pagination.current_page - 1) * mouvementStore.pagination.per_page"
  :rows="mouvementStore.pagination.per_page"
  :total-records="mouvementStore.pagination.total"
  @page="onPageChange"
  class="mt-4"
/>
  </div>
</template>
