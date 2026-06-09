<script setup>
import { onMounted } from 'vue';
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
  Box
} from 'lucide-vue-next';

const mouvementStore = useMouvementStore();
const { mouvements, loading } = storeToRefs(mouvementStore);

onMounted(() => {
  mouvementStore.fetchMouvements();
});

const getMouvementConfig = (type) => {
  const configs = {
    'affectation': { 
      label: 'Affectation', 
      icon: ArrowLeftRight, 
      color: 'text-blue-500', 
      bg: 'bg-blue-50' 
    },
    'retour': { 
      label: 'Retour', 
      icon: RotateCcw, 
      color: 'text-emerald-500', 
      bg: 'bg-emerald-50' 
    },
    'panne': { 
      label: 'Déclaration panne', 
      icon: AlertTriangle, 
      color: 'text-amber-500', 
      bg: 'bg-amber-50' 
    },
    'maintenance': { 
      label: 'Maintenance', 
      icon: Wrench, 
      color: 'text-blue-600', 
      bg: 'bg-blue-50' 
    },
    'sinistre': { 
      label: 'Sinistre', 
      icon: AlertOctagon, 
      color: 'text-rose-500', 
      bg: 'bg-rose-50' 
    },
    'perte': { 
      label: 'Perte', 
      icon: Trash2, 
      color: 'text-slate-600', 
      bg: 'bg-slate-100' 
    }
  };
  
  return configs[type] || { 
    label: type, 
    icon: History, 
    color: 'text-slate-400', 
    bg: 'bg-slate-50' 
  };
};

const formatTime = (dateStr) => {
  return new Date(dateStr).toLocaleTimeString('fr-FR', { 
    hour: '2-digit', 
    minute: '2-digit' 
  });
};

const formatDate = (dateStr) => {
  return new Date(dateStr).toLocaleDateString('fr-FR', { 
    day: 'numeric', 
    month: 'long', 
    year: 'numeric' 
  });
};
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <PageHeader 
      title="Journal des mouvements" 
      subtitle="Historique immuable de toutes les opérations"
    />

    <div v-if="loading" class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-slate-200">
      <Loader2 class="w-10 h-10 text-primary-500 animate-spin mb-4" />
      <p class="text-slate-500 font-medium">Chargement de l'historique...</p>
    </div>

    <div v-else-if="mouvements.length === 0" class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-slate-200">
      <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mb-4">
        <History class="w-8 h-8 text-slate-300" />
      </div>
      <p class="text-slate-500 font-medium text-lg">Aucun mouvement enregistré</p>
    </div>

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
  </div>
</template>
