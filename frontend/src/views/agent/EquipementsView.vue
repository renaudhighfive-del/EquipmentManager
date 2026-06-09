<script setup>
import { ref, onMounted, reactive } from 'vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import SideModal from '../../components/layout/SideModal.vue'
import { Smartphone, RotateCcw, AlertOctagon, Loader2 } from 'lucide-vue-next'
import { useEquipementStore } from '../../stores/equipement'
import { useSinistreStore } from '../../stores/sinistre'
import { useToast } from 'primevue/usetoast'

const equipementStore = useEquipementStore();
const sinistreStore = useSinistreStore();
const toast = useToast();

const showSinistreModal = ref(false);
const selectedEquipement = ref(null);
const isSubmitting = ref(false);

const sinistreForm = reactive({
  type: 'Perte',
  description: ''
});

onMounted(() => {
  equipementStore.fetchEquipements();
});

const openSinistreModal = (equip) => {
  selectedEquipement.value = equip;
  sinistreForm.type = 'Perte';
  sinistreForm.description = '';
  showSinistreModal.value = true;
};

const handleDeclareSinistre = async () => {
  if (!sinistreForm.description) {
    toast.add({ severity: 'warn', summary: 'Attention', detail: 'Veuillez fournir une description', life: 3000 });
    return;
  }

  isSubmitting.value = true;
  try {
    await sinistreStore.declareSinistre({
      equipement_id: selectedEquipement.value.id,
      type: sinistreForm.type,
      description: sinistreForm.description
    });
    toast.add({ severity: 'success', summary: 'Succès', detail: 'Déclaration envoyée avec succès', life: 3000 });
    showSinistreModal.value = false;
    // Rafraîchir les équipements pour voir le changement d'état si nécessaire
    equipementStore.fetchEquipements();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de l\'envoi de la déclaration', life: 3000 });
  } finally {
    isSubmitting.value = false;
  }
};

const getEtatClass = (etat) => {
  switch (etat) {
    case 'en_service': return 'bg-emerald-50 text-emerald-600';
    case 'en_panne': return 'bg-red-50 text-red-600';
    case 'en_attente_sinistre': return 'bg-orange-50 text-orange-600';
    default: return 'bg-slate-50 text-slate-600';
  }
};

const getEtatLabel = (etat) => {
  const labels = {
    neuf: 'Neuf',
    en_service: 'En service',
    en_panne: 'En panne',
    en_maintenance: 'En maintenance',
    en_attente_sinistre: 'En attente sinistre',
    reforme: 'Réformé',
    perdu: 'Perdu'
  };
  return labels[etat] || etat;
};
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <PageHeader title="Mes équipements" subtitle="Équipements actuellement affectés à votre compte" />

    <div v-if="equipementStore.loading" class="space-y-4">
      <div v-for="i in 2" :key="i" class="h-24 bg-slate-100 animate-pulse rounded-2xl"></div>
    </div>

    <div v-else class="space-y-4">
      <div 
        v-for="equip in equipementStore.equipements" :key="equip.id"
        class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:shadow-md transition-all"
      >
        <div class="flex items-center gap-4 sm:gap-5">
          <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400">
            <Smartphone class="w-6 h-6 sm:w-7 sm:h-7" />
          </div>
          <div>
            <p class="font-bold text-slate-900">{{ equip.marque }} {{ equip.modele }}</p>
            <p class="text-xs text-slate-500 font-medium mt-0.5">{{ equip.reference }}</p>
          </div>
        </div>
        <div class="flex items-center justify-between sm:justify-end gap-4 border-t sm:border-t-0 pt-4 sm:pt-0">
          <span :class="['px-3 py-1 rounded-lg text-[10px] font-black uppercase', getEtatClass(equip.etat)]">
            {{ getEtatLabel(equip.etat) }}
          </span>
          <div class="flex items-center gap-2">
            <button 
              @click="openSinistreModal(equip)"
              class="p-2.5 text-orange-500 hover:bg-orange-50 rounded-xl transition-all"
              title="Déclarer perte/casse"
            >
              <AlertOctagon class="w-5 h-5" />
            </button>
            <button class="p-2 sm:p-2.5 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-all">
              <RotateCcw class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>
      
      <div v-if="equipementStore.equipements.length === 0" class="text-center py-12 bg-slate-50 rounded-3xl border border-dashed border-slate-200">
        <Smartphone class="w-12 h-12 text-slate-300 mx-auto mb-4" />
        <p class="text-slate-500 font-medium">Aucun équipement affecté pour le moment.</p>
      </div>
    </div>

    <!-- Modal Déclaration Sinistre -->
    <SideModal :show="showSinistreModal" title="Déclarer un sinistre" @close="showSinistreModal = false">
      <div v-if="selectedEquipement" class="space-y-6">
        <div class="p-4 bg-orange-50 border border-orange-100 rounded-2xl">
          <p class="text-sm text-orange-800 font-medium">
            Vous allez déclarer un incident pour l'équipement : 
            <span class="font-black">{{ selectedEquipement.marque }} {{ selectedEquipement.modele }} ({{ selectedEquipement.reference }})</span>
          </p>
        </div>

        <div class="space-y-4">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Type de sinistre</label>
            <select v-model="sinistreForm.type" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-primary-500/10 outline-none">
              <option value="Perte">Perte</option>
              <option value="Casse">Casse</option>
              <option value="Vol">Vol</option>
            </select>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Description des faits</label>
            <textarea 
              v-model="sinistreForm.description"
              rows="4" 
              placeholder="Expliquez brièvement les circonstances..."
              class="w-full p-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-primary-500/10 outline-none resize-none"
            ></textarea>
          </div>
        </div>

        <button 
          @click="handleDeclareSinistre"
          :disabled="isSubmitting"
          class="w-full h-12 bg-primary-600 text-white rounded-xl text-sm font-black shadow-lg shadow-primary-200 hover:bg-primary-700 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
        >
          <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
          {{ isSubmitting ? 'Envoi en cours...' : 'Envoyer la déclaration' }}
        </button>
      </div>
    </SideModal>
  </div>
</template>

