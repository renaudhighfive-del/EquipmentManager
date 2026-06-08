<script setup>
import { onMounted } from 'vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import { Smartphone, RotateCcw } from 'lucide-vue-next'
import { useEquipementStore } from '../../stores/equipement'

const equipementStore = useEquipementStore();

onMounted(() => {
  equipementStore.fetchEquipements();
});

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
          <button class="p-2 sm:p-2.5 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-all">
            <RotateCcw class="w-5 h-5" />
          </button>
        </div>
      </div>
      
      <div v-if="equipementStore.equipements.length === 0" class="text-center py-12 bg-slate-50 rounded-3xl border border-dashed border-slate-200">
        <Smartphone class="w-12 h-12 text-slate-300 mx-auto mb-4" />
        <p class="text-slate-500 font-medium">Aucun équipement affecté pour le moment.</p>
      </div>
    </div>
  </div>
</template>
