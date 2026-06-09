<script setup>
import { ref, onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { usePanneStore } from '../../stores/panne';
import { useToast } from 'primevue/usetoast';
import PageHeader from '../../components/layout/PageHeader.vue';
import SideModal from '../../components/layout/SideModal.vue';
import { 
  AlertTriangle, 
  Plus, 
  Eye, 
  CheckCircle2, 
  Clock, 
  XCircle,
  MessageSquare,
  Camera,
  Loader2,
  Wrench,
  Ban
} from 'lucide-vue-next';

const panneStore = usePanneStore();
const { pannes, loading, successMessage, error: storeError } = storeToRefs(panneStore);
const toast = useToast();

const showFiche = ref(false);
const selectedPanne = ref(null);
const isUpdating = ref(false);

onMounted(() => {
  panneStore.fetchPannes();
});

const openFiche = (panne) => {
  selectedPanne.value = panne;
  showFiche.value = true;
};

const handleStatusUpdate = async (statut) => {
  if (!selectedPanne.value) return;
  
  isUpdating.value = true;
  const success = await panneStore.updatePanneStatus(selectedPanne.value.id, statut);
  
  if (success) {
    toast.add({ severity: 'success', summary: 'Succès', detail: successMessage.value, life: 3000 });
    // Mettre à jour l'objet local pour le modal
    selectedPanne.value = pannes.value.find(p => p.id === selectedPanne.value.id);
  } else {
    toast.add({ severity: 'error', summary: 'Erreur', detail: storeError.value, life: 3000 });
  }
  isUpdating.value = false;
};

const getStatutLabel = (statut) => {
  const labels = {
    'declaree': 'Déclarée',
    'en_cours': 'En cours',
    'en_maintenance': 'En maintenance',
    'resolue': 'Résolue',
    'irrecuperable': 'Irrécupérable'
  }
  return labels[statut] || statut
}

const getStatutClass = (statut) => {
  switch (statut) {
    case 'declaree': return 'bg-amber-50 text-amber-600 border-amber-100';
    case 'en_cours': return 'bg-blue-50 text-blue-600 border-blue-100';
    case 'en_maintenance': return 'bg-purple-50 text-purple-600 border-purple-100';
    case 'resolue': return 'bg-emerald-50 text-emerald-600 border-emerald-100';
    case 'irrecuperable': return 'bg-rose-50 text-rose-600 border-rose-100';
    default: return 'bg-slate-50 text-slate-600 border-slate-100';
  }
};

const getGraviteClass = (gravite) => {
  switch (gravite) {
    case 'faible': return 'text-blue-500';
    case 'moyenne': return 'text-amber-500';
    case 'critique': return 'text-red-500';
    default: return 'text-slate-500';
  }
};

const formatDate = (dateStr) => {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleDateString('fr-FR', { 
    day: 'numeric', 
    month: 'long', 
    year: 'numeric' 
  });
};

// Fonction pour obtenir l'URL de l'image
const getImageUrl = (path) => {
  if (!path) return null;
  if (path.startsWith('http')) return path;
  return `${import.meta.env.VITE_API_URL.replace('/api', '')}/storage/${path}`;
};
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <PageHeader 
      title="Pannes" 
      subtitle="Suivi des incidents et réparations"
    >
      <template #actions>
        <!-- Les pannes sont généralement déclarées par les agents, mais on garde le bouton si besoin -->
      </template>
    </PageHeader>

    <div v-if="loading && pannes.length === 0" class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-slate-200">
      <Loader2 class="w-10 h-10 text-primary-500 animate-spin mb-4" />
      <p class="text-slate-500 font-medium">Chargement des pannes...</p>
    </div>

    <div v-else-if="pannes.length === 0" class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-slate-200 text-center">
      <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mb-4">
        <CheckCircle2 class="w-8 h-8 text-slate-300" />
      </div>
      <p class="text-slate-500 font-medium text-lg">Aucun incident signalé</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 gap-6">
      <div 
        v-for="panne in pannes" 
        :key="panne.id"
        class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 hover:shadow-md transition-all group"
      >
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-4">
            <div :class="['w-12 h-12 rounded-2xl bg-rose-50 flex items-center justify-center', getGraviteClass(panne.gravite)]">
              <AlertTriangle class="w-6 h-6" />
            </div>
            <div>
              <h3 class="font-bold text-slate-900 truncate max-w-[200px]">{{ panne.equipement?.reference }} • {{ panne.equipement?.modele }}</h3>
              <p class="text-xs text-slate-400 font-medium">Signalé par {{ panne.declare_par?.name || 'Inconnu' }} le {{ formatDate(panne.date_declaration) }}</p>
            </div>
          </div>
          <span :class="['px-3 py-1 rounded-lg text-[10px] font-bold border uppercase tracking-wider', getStatutClass(panne.statut)]">
            {{ getStatutLabel(panne.statut) }}
          </span>
        </div>

        <p class="text-sm text-slate-600 mb-4 font-medium line-clamp-2 h-10">{{ panne.description }}</p>

        <div class="flex items-center justify-between">
          <div class="flex -space-x-2">
            <template v-if="panne.photos && panne.photos.length > 0">
              <div v-for="(photo, idx) in panne.photos.slice(0, 3)" :key="idx" class="w-12 h-12 rounded-xl border-2 border-white overflow-hidden bg-slate-100 shadow-sm">
                <img :src="getImageUrl(photo)" class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all">
              </div>
              <div v-if="panne.photos.length > 3" class="w-12 h-12 rounded-xl border-2 border-white bg-slate-200 flex items-center justify-center text-[10px] font-bold text-slate-500 shadow-sm">
                +{{ panne.photos.length - 3 }}
              </div>
            </template>
            <div v-else class="w-12 h-12 rounded-xl border-2 border-white bg-slate-50 flex items-center justify-center text-slate-300">
              <Camera class="w-5 h-5" />
            </div>
          </div>
          <button 
            @click="openFiche(panne)"
            class="flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-primary-600 transition-colors"
          >
            Gérer l'incident
            <Eye class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <SideModal 
      :show="showFiche" 
      title="Gestion de l'incident" 
      @close="showFiche = false"
    >
      <div v-if="selectedPanne" class="space-y-8 animate-in fade-in duration-300">
        <div class="flex items-center justify-between">
          <span :class="['px-3 py-1 rounded-lg text-[10px] font-bold border uppercase tracking-wider', getStatutClass(selectedPanne.statut)]">
            {{ getStatutLabel(selectedPanne.statut) }}
          </span>
          <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Signalé le {{ formatDate(selectedPanne.date_declaration) }}</p>
        </div>

        <div class="space-y-2">
          <h3 class="text-2xl font-bold text-slate-900">{{ selectedPanne.description }}</h3>
          <p class="text-sm text-slate-500 font-medium">
            {{ selectedPanne.equipement?.marque }} {{ selectedPanne.equipement?.modele }} ({{ selectedPanne.equipement?.reference }})
          </p>
          <div class="flex items-center gap-2 mt-2">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Gravité :</span>
            <span :class="['text-[10px] font-black uppercase', getGraviteClass(selectedPanne.gravite)]">{{ selectedPanne.gravite }}</span>
          </div>
        </div>

        <div v-if="selectedPanne.photos && selectedPanne.photos.length > 0" class="space-y-4">
          <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Photos justificatives</h4>
          <div class="grid grid-cols-1 gap-4">
            <div v-for="(photo, idx) in selectedPanne.photos" :key="idx" class="rounded-2xl overflow-hidden border border-slate-200 shadow-sm">
              <img :src="getImageUrl(photo)" class="w-full object-cover">
            </div>
          </div>
        </div>

        <div class="pt-6 space-y-3">
          <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Actions de maintenance</h4>
          
          <div v-if="selectedPanne.statut !== 'resolue' && selectedPanne.statut !== 'irrecuperable'" class="grid grid-cols-1 gap-3">
            <button 
              v-if="selectedPanne.statut === 'declaree'"
              @click="handleStatusUpdate('en_cours')"
              :disabled="isUpdating"
              class="w-full py-3.5 bg-blue-50 text-blue-600 font-bold rounded-2xl hover:bg-blue-100 transition-all flex items-center justify-center gap-3"
            >
              <Clock v-if="!isUpdating" class="w-5 h-5" />
              <Loader2 v-else class="w-5 h-5 animate-spin" />
              Prendre en charge
            </button>

            <button 
              v-if="selectedPanne.statut === 'en_cours'"
              @click="handleStatusUpdate('en_maintenance')"
              :disabled="isUpdating"
              class="w-full py-3.5 bg-purple-50 text-purple-600 font-bold rounded-2xl hover:bg-purple-100 transition-all flex items-center justify-center gap-3"
            >
              <Wrench v-if="!isUpdating" class="w-5 h-5" />
              <Loader2 v-else class="w-5 h-5 animate-spin" />
              Envoyer en réparation
            </button>

            <button 
              @click="handleStatusUpdate('resolue')"
              :disabled="isUpdating"
              class="w-full py-3.5 bg-emerald-500 text-white font-bold rounded-2xl hover:bg-emerald-600 transition-all shadow-lg shadow-emerald-200 flex items-center justify-center gap-3"
            >
              <CheckCircle2 v-if="!isUpdating" class="w-5 h-5" />
              <Loader2 v-else class="w-5 h-5 animate-spin" />
              Marquer comme résolue
            </button>

            <button 
              @click="handleStatusUpdate('irrecuperable')"
              :disabled="isUpdating"
              class="w-full py-3.5 bg-rose-50 text-rose-600 font-bold rounded-2xl hover:bg-rose-100 transition-all flex items-center justify-center gap-3"
            >
              <Ban v-if="!isUpdating" class="w-5 h-5" />
              <Loader2 v-else class="w-5 h-5 animate-spin" />
              Déclarer irrécupérable
            </button>
          </div>
          
          <div v-else class="p-6 bg-slate-50 rounded-2xl border border-slate-100 text-center">
            <p class="text-sm font-bold text-slate-500">Cet incident est clôturé.</p>
          </div>
        </div>
      </div>
    </SideModal>
  </div>
</template>
