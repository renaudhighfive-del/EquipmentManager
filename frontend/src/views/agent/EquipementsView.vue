<script setup>
import { onMounted, ref } from 'vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import SideModal from '../../components/layout/SideModal.vue'
import { Smartphone, RotateCcw, Calendar, Camera, Info } from 'lucide-vue-next'
import { useEquipementStore } from '../../stores/equipement'

const equipementStore = useEquipementStore();
const selectedEquip = ref(null);
const showDetailModal = ref(false);

onMounted(() => {
  equipementStore.fetchEquipements();
});

const openDetails = (equip) => {
  selectedEquip.value = equip;
  showDetailModal.value = true;
};

const formatDate = (dateStr) => {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleDateString('fr-FR', { 
    day: 'numeric', 
    month: 'long', 
    year: 'numeric' 
  });
};

const getEtatClass = (etat) => {
  switch (etat) {
    case 'en_service': return 'bg-emerald-50 text-emerald-600';
    case 'en_panne': return 'bg-red-50 text-red-600';
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
        class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:shadow-md transition-all cursor-pointer group"
        @click="openDetails(equip)"
      >
        <div class="flex items-center gap-4 sm:gap-5">
          <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-primary-50 group-hover:text-primary-600 transition-colors">
            <Smartphone class="w-6 h-6 sm:w-7 sm:h-7" />
          </div>
          <div>
            <p class="font-bold text-slate-900">{{ equip.marque }} {{ equip.modele }}</p>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1">
              <span class="text-xs text-slate-500 font-medium">{{ equip.reference }}</span>
              <span v-if="equip.current_affectation" class="flex items-center gap-1 text-[10px] text-primary-600 font-bold bg-primary-50 px-2 py-0.5 rounded-md">
                <Calendar class="w-3 h-3" />
                Depuis le {{ formatDate(equip.current_affectation.date_affectation) }}
              </span>
            </div>
          </div>
        </div>
        <div class="flex items-center justify-between sm:justify-end gap-4 border-t sm:border-t-0 pt-4 sm:pt-0">
          <span :class="['px-3 py-1 rounded-lg text-[10px] font-black uppercase', getEtatClass(equip.etat)]">
            {{ getEtatLabel(equip.etat) }}
          </span>
          <div class="flex items-center gap-2">
            <button 
              @click.stop="openDetails(equip)"
              class="p-2 sm:p-2.5 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-all"
              title="Détails"
            >
              <Info class="w-5 h-5" />
            </button>
            <button 
              @click.stop="$router.push('/agent/pannes')"
              class="p-2 sm:p-2.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all"
              title="Signaler une panne"
            >
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

    <!-- Modal Détails -->
    <SideModal 
      :show="showDetailModal" 
      title="Détails de l'équipement" 
      @close="showDetailModal = false"
    >
      <div v-if="selectedEquip" class="space-y-8 animate-in fade-in duration-300">
        <div class="flex items-center gap-5">
          <div class="w-20 h-20 rounded-2xl bg-primary-50 flex items-center justify-center text-primary-600">
            <Smartphone class="w-10 h-10" />
          </div>
          <div>
            <h3 class="text-xl font-bold text-slate-900">{{ selectedEquip.marque }} {{ selectedEquip.modele }}</h3>
            <p class="text-slate-500 font-medium">{{ selectedEquip.reference }}</p>
            <div class="mt-2 inline-flex px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider" :class="getEtatClass(selectedEquip.etat)">
              {{ getEtatLabel(selectedEquip.etat) }}
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Numéro de série</p>
            <p class="text-sm font-bold text-slate-900">{{ selectedEquip.numero_serie || '—' }}</p>
          </div>
          <div v-if="selectedEquip.imei" class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">IMEI</p>
            <p class="text-sm font-bold text-slate-900">{{ selectedEquip.imei }}</p>
          </div>
          <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Date d'affectation</p>
            <p class="text-sm font-bold text-slate-900">{{ formatDate(selectedEquip.current_affectation?.date_affectation) }}</p>
          </div>
          <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Garantie jusqu'au</p>
            <p class="text-sm font-bold text-slate-900">{{ formatDate(selectedEquip.garantie_fin) }}</p>
          </div>
        </div>

        <div v-if="selectedEquip.current_affectation?.photo_remise_url" class="space-y-3">
          <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <Camera class="w-4 h-4 text-primary-500" />
            Photo lors de la remise
          </h4>
          <div class="rounded-2xl border border-slate-200 overflow-hidden shadow-sm bg-slate-100 aspect-video relative group">
            <img 
              :src="selectedEquip.current_affectation.photo_remise_url" 
              alt="Photo de remise"
              class="w-full h-full object-cover"
            />
          </div>
        </div>

        <div v-if="selectedEquip.current_affectation?.observations" class="p-4 bg-primary-50/50 rounded-2xl border border-primary-100">
          <h4 class="text-[10px] font-bold text-primary-600 uppercase tracking-widest mb-1">Observations de remise</h4>
          <p class="text-sm text-slate-600 italic leading-relaxed">
            "{{ selectedEquip.current_affectation.observations }}"
          </p>
        </div>

        <button 
          @click="$router.push('/agent/pannes')"
          class="w-full py-4 bg-red-50 text-red-600 font-bold rounded-2xl hover:bg-red-100 transition-colors flex items-center justify-center gap-2"
        >
          <AlertTriangle class="w-5 h-5" />
          Signaler un problème sur cet appareil
        </button>
      </div>
    </SideModal>
  </div>
</template>
