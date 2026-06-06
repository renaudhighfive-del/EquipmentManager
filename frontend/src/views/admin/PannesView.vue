<script setup>
import { ref } from 'vue';
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
  Camera
} from 'lucide-vue-next';

const showFiche = ref(false);
const selectedPanne = ref(null);

const pannes = [
  { id: 1, equipRef: 'REF-80000', equipName: 'Zebra Zebra Pro 0', date: '05 mai 2026', description: 'Écran cassé', statut: 'Déclarée', image: 'https://images.unsplash.com/photo-1556656793-062ff9878258?w=400&h=250&fit=crop' },
  { id: 2, equipRef: 'REF-80001', equipName: 'Samsung Samsung X 1', date: '14 avr. 2026', description: 'Batterie HS', statut: 'En cours', image: 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=250&fit=crop' },
  { id: 3, equipRef: 'REF-80002', equipName: 'Apple Apple Max 2', date: '10 mai 2026', description: 'Ne s\'allume plus', statut: 'En maintenance', image: 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=400&h=250&fit=crop' },
  { id: 4, equipRef: 'REF-80003', equipName: 'Dell Dell Lite 3', date: '29 avr. 2026', description: 'Problème réseau', statut: 'Résolue', image: 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400&h=250&fit=crop' },
  { id: 5, equipRef: 'REF-80004', equipName: 'Honeywell Honeywell Ultra 4', date: '09 mars 2026', description: 'Écran cassé', statut: 'Irrécupérable', image: 'https://images.unsplash.com/photo-1556656793-062ff9878258?w=400&h=250&fit=crop' },
];

const openFiche = (panne) => {
  selectedPanne.value = panne;
  showFiche.value = true;
};

const getStatutClass = (statut) => {
  switch (statut) {
    case 'Déclarée': return 'bg-amber-50 text-amber-600 border-amber-100';
    case 'En cours': return 'bg-blue-50 text-blue-600 border-blue-100';
    case 'En maintenance': return 'bg-purple-50 text-purple-600 border-purple-100';
    case 'Résolue': return 'bg-emerald-50 text-emerald-600 border-emerald-100';
    case 'Irrécupérable': return 'bg-rose-50 text-rose-600 border-rose-100';
    default: return 'bg-slate-50 text-slate-600 border-slate-100';
  }
};
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <PageHeader 
      title="Pannes" 
      subtitle="Suivi des incidents et réparations"
    >
      <template #actions>
        <button class="flex items-center gap-2 px-5 py-2.5 bg-primary-600 rounded-xl text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-lg shadow-primary-200">
          <Plus class="w-4 h-4" />
          Déclarer une panne
        </button>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 gap-6">
      <div 
        v-for="panne in pannes" 
        :key="panne.id"
        class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 hover:shadow-md transition-all group"
      >
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 flex items-center justify-center text-rose-500">
              <AlertTriangle class="w-6 h-6" />
            </div>
            <div>
              <h3 class="font-bold text-slate-900">{{ panne.equipRef }} • {{ panne.equipName }}</h3>
              <p class="text-xs text-slate-400 font-medium">{{ panne.date }}</p>
            </div>
          </div>
          <span :class="['px-3 py-1 rounded-lg text-[10px] font-bold border uppercase tracking-wider', getStatutClass(panne.statut)]">
            {{ panne.statut }}
          </span>
        </div>

        <p class="text-sm text-slate-600 mb-4 font-medium">{{ panne.description }}</p>

        <div class="flex items-center justify-between">
          <div class="flex -space-x-2">
            <div class="w-12 h-12 rounded-xl border-2 border-white overflow-hidden bg-slate-100">
              <img :src="panne.image" class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all">
            </div>
          </div>
          <button 
            @click="openFiche(panne)"
            class="flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-primary-600 transition-colors"
          >
            Voir détails
            <Eye class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <SideModal 
      :show="showFiche" 
      title="Fiche Panne" 
      @close="showFiche = false"
    >
      <div v-if="selectedPanne" class="space-y-8">
        <div class="flex items-center justify-between">
          <span :class="['px-3 py-1 rounded-lg text-[10px] font-bold border uppercase tracking-wider', getStatutClass(selectedPanne.statut)]">
            {{ selectedPanne.statut }}
          </span>
          <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Signalé le {{ selectedPanne.date }}</p>
        </div>

        <div class="space-y-2">
          <h3 class="text-2xl font-bold text-slate-900">{{ selectedPanne.description }}</h3>
          <p class="text-sm text-slate-500 font-medium">{{ selectedPanne.equipName }} ({{ selectedPanne.equipRef }})</p>
        </div>

        <div class="rounded-3xl overflow-hidden border border-slate-200 shadow-xl">
          <img :src="selectedPanne.image" class="w-full object-cover aspect-video">
        </div>

        <div class="pt-6">
          <button class="w-full py-4 bg-emerald-500 text-white font-bold rounded-2xl hover:bg-emerald-600 transition-all shadow-lg shadow-emerald-200 flex items-center justify-center gap-3">
            <CheckCircle2 class="w-5 h-5" />
            Marquer résolue
          </button>
        </div>
      </div>
    </SideModal>
  </div>
</template>
