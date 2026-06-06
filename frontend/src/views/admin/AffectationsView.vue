<script setup>
import { ref } from 'vue';
import PageHeader from '../../components/layout/PageHeader.vue';
import SideModal from '../../components/layout/SideModal.vue';
import { 
  Plus, 
  ArrowLeftRight, 
  History,
  CheckCircle2,
  Clock,
  RotateCcw,
  Camera,
  Search,
  Filter
} from 'lucide-vue-next';

const showReturnModal = ref(false);
const selectedAffectation = ref(null);

const affectations = [
  { id: 1, equipRef: 'REF-80000', equipName: 'Zebra Zebra Pro 0', agentName: 'Amélie Dubois', date: '02 mars 2026', statut: 'En cours', agentAvatar: null },
  { id: 2, equipRef: 'REF-80001', equipName: 'Samsung Samsung X 1', agentName: 'Karim Benali', date: '23 mars 2026', statut: 'Retourné', agentAvatar: null },
  { id: 3, equipRef: 'REF-80002', equipName: 'Apple Apple Max 2', agentName: 'Sophie Martin', date: '07 mai 2026', statut: 'Renouvelé', agentAvatar: null },
  { id: 4, equipRef: 'REF-80003', equipName: 'Dell Dell Lite 3', agentName: 'Youssef El Amrani', date: '26 mai 2026', statut: 'En cours', agentAvatar: null },
];

const openReturnModal = (aff) => {
  selectedAffectation.value = aff;
  showReturnModal.value = true;
};
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <PageHeader 
      title="Affectations" 
      subtitle="Gestion des remises et retours de matériels"
    >
      <template #actions>
        <button class="flex items-center gap-2 px-5 py-2.5 bg-primary-600 rounded-xl text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-lg shadow-primary-200">
          <Plus class="w-4 h-4" />
          Nouvelle affectation
        </button>
      </template>
    </PageHeader>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <table class="w-full text-left">
        <thead>
          <tr class="bg-slate-50/50 text-[11px] font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">
            <th class="px-8 py-5">Équipement</th>
            <th class="px-6 py-5">Agent</th>
            <th class="px-6 py-5">Date</th>
            <th class="px-6 py-5">Statut</th>
            <th class="px-8 py-5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <tr v-for="aff in affectations" :key="aff.id" class="hover:bg-slate-50/50 transition-colors group">
            <td class="px-8 py-5">
              <div>
                <p class="text-sm font-bold text-slate-900">{{ aff.equipRef }}</p>
                <p class="text-xs text-slate-500">{{ aff.equipName }}</p>
              </div>
            </td>
            <td class="px-6 py-5">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500">
                  {{ aff.agentName.charAt(0) }}
                </div>
                <span class="text-sm font-medium text-slate-700">{{ aff.agentName }}</span>
              </div>
            </td>
            <td class="px-6 py-5 text-sm text-slate-500 font-medium">{{ aff.date }}</td>
            <td class="px-6 py-5">
              <span 
                class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight"
                :class="{
                  'bg-blue-50 text-blue-600': aff.statut === 'En cours',
                  'bg-slate-100 text-slate-500': aff.statut === 'Retourné',
                  'bg-purple-50 text-purple-600': aff.statut === 'Renouvelé'
                }"
              >
                {{ aff.statut }}
              </span>
            </td>
            <td class="px-8 py-5 text-right">
              <button 
                v-if="aff.statut === 'En cours'"
                @click="openReturnModal(aff)"
                class="text-xs font-bold text-primary-600 hover:text-primary-700 bg-primary-50 px-4 py-2 rounded-lg transition-colors"
              >
                Retour
              </button>
              <span v-else class="text-xs font-bold text-slate-300">Clôturé</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Return Modal (Glassmorphism inspired) -->
    <SideModal 
      :show="showReturnModal" 
      title="Retour d'équipement" 
      mode="center"
      @close="showReturnModal = false"
    >
      <div v-if="selectedAffectation" class="space-y-6">
        <div class="p-5 bg-primary-500 rounded-2xl border border-primary-400 mb-6 text-white shadow-lg shadow-primary-200">
          <p class="text-[10px] font-bold text-primary-100 uppercase tracking-widest mb-1">Équipement à retourner</p>
          <p class="text-base font-bold">{{ selectedAffectation.equipName }} ({{ selectedAffectation.equipRef }})</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-bold text-slate-500 uppercase">Date de retour</label>
            <input type="date" class="w-full h-11 px-4 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20">
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold text-slate-500 uppercase">État constaté</label>
            <select class="w-full h-11 px-4 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20">
              <option>Bon état</option>
              <option>Abîmé</option>
              <option>En panne</option>
            </select>
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold text-slate-500 uppercase">Observations</label>
          <textarea 
            rows="4" 
            placeholder="Détails..."
            class="w-full p-4 bg-white border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20 resize-none"
          ></textarea>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold text-slate-500 uppercase">Photo retour (obligatoire)</label>
          <div class="border-2 border-dashed border-slate-200 rounded-2xl p-8 flex flex-col items-center justify-center gap-3 hover:bg-slate-50 transition-colors cursor-pointer group">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center group-hover:scale-110 transition-transform">
              <Camera class="w-6 h-6 text-slate-400" />
            </div>
            <p class="text-xs font-bold text-slate-500">Ajouter une photo</p>
          </div>
        </div>

        <div class="pt-6 flex gap-3">
          <button @click="showReturnModal = false" class="flex-1 py-3 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-colors">Annuler</button>
          <button class="flex-1 py-3 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 transition-colors shadow-lg shadow-emerald-200">Confirmer retour</button>
        </div>
      </div>
    </SideModal>
  </div>
</template>
