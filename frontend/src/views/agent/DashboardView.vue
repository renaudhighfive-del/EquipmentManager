<script setup>
import { onMounted, computed } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useEquipementStore } from '../../stores/equipement'
import { usePanneStore } from '../../stores/panne'
import PageHeader from '../../components/layout/PageHeader.vue'
import StatCard from '../../components/dashboard/StatCard.vue'
import { useRouter } from 'vue-router'
import { 
  AlertTriangle, 
  RotateCcw,
  Smartphone
} from 'lucide-vue-next'

const authStore = useAuthStore()
const equipementStore = useEquipementStore()
const panneStore = usePanneStore()
const router = useRouter()

onMounted(() => {
  equipementStore.fetchEquipements()
  panneStore.fetchPannes()
})

const stats = computed(() => [
  { 
    label: 'Mes équipements', 
    value: equipementStore.equipements.length, 
    icon: Smartphone,    
    colorClass: 'bg-blue-50 text-blue-600' 
  },
  { 
    label: 'Pannes signalées', 
    value: panneStore.pannes.length, 
    icon: AlertTriangle, 
    colorClass: 'bg-red-50 text-red-600' 
  },
])

const recentEquipments = computed(() => {
  return equipementStore.equipements.slice(0, 3)
})

const getEtatLabel = (etat) => {
  const labels = {
    neuf: 'Neuf',
    en_service: 'En service',
    en_panne: 'En panne',
    en_maintenance: 'En maintenance',
    reforme: 'Réformé',
    perdu: 'Perdu'
  }
  return labels[etat] || etat
}
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <PageHeader 
      title="Mon Espace" 
      :subtitle="`Bienvenue, ${authStore.user?.name}`"
    >
      <template #actions>
        <button 
          @click="router.push('/agent/pannes')"
          class="flex items-center gap-2 px-5 py-2.5 bg-rose-600 rounded-xl text-sm font-bold text-white hover:bg-rose-700 transition-all shadow-lg shadow-rose-200"
        >
          <AlertTriangle class="w-4 h-4" />
          Signaler une panne
        </button>
      </template>
    </PageHeader>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
      <StatCard v-for="stat in stats" :key="stat.label" v-bind="stat" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
      <!-- Équipements -->
      <div class="lg:col-span-2 space-y-6">
        <div class="bg-white p-5 sm:p-8 rounded-3xl border border-slate-200 shadow-sm">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 sm:mb-8">
            <h3 class="text-lg sm:text-xl font-black text-slate-900">Mes équipements actuels</h3>
            <span class="inline-flex w-fit px-3 py-1 bg-primary-50 text-primary-600 text-[10px] font-black uppercase rounded-lg">
              {{ equipementStore.equipements.length }} Appareils
            </span>
          </div>

          <div v-if="equipementStore.loading" class="space-y-4">
            <div v-for="i in 2" :key="i" class="h-24 bg-slate-50 animate-pulse rounded-3xl"></div>
          </div>

          <div v-else-if="equipementStore.equipements.length === 0" class="text-center py-10 sm:py-12 bg-slate-50 rounded-3xl border border-dashed border-slate-200">
            <Smartphone class="w-10 h-10 sm:w-12 h-12 text-slate-300 mx-auto mb-4" />
            <p class="text-slate-500 text-sm font-medium">Aucun équipement affecté pour le moment.</p>
          </div>

          <div v-else class="space-y-4">
            <div 
              v-for="equip in recentEquipments" :key="equip.id"
              @click="router.push('/agent/equipements')"
              class="p-4 sm:p-5 bg-white border border-slate-100 rounded-3xl flex items-center justify-between group hover:border-primary-200 hover:shadow-lg hover:shadow-primary-50 transition-all duration-300 cursor-pointer"
            >
              <div class="flex items-center gap-3 sm:gap-5">
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-primary-50 group-hover:text-primary-600 transition-colors">
                  <Smartphone class="w-6 h-6 sm:w-7 sm:h-7" />
                </div>
                <div>
                  <p class="text-sm sm:text-base font-black text-slate-900">{{ equip.marque }} {{ equip.modele }}</p>
                  <div class="flex items-center gap-2 mt-0.5 sm:mt-1">
                    <span class="text-[10px] sm:text-xs text-slate-500 font-medium">{{ equip.reference }}</span>
                    <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                    <span :class="['text-[9px] sm:text-[10px] font-black uppercase', equip.etat === 'en_service' ? 'text-emerald-600' : 'text-amber-600']">
                      {{ getEtatLabel(equip.etat) }}
                    </span>
                  </div>
                </div>
              </div>
              <button class="p-2 sm:p-3 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded-2xl transition-all">
                <RotateCcw class="w-4 h-4 sm:w-5 sm:h-5" />
              </button>
            </div>
          </div>

          <div v-if="equipementStore.equipements.length > 0" class="mt-6 text-center">
            <button 
              @click="router.push('/agent/equipements')"
              class="text-sm font-bold text-primary-600 hover:text-primary-700 transition-colors"
            >
              Voir tous mes équipements →
            </button>
          </div>
        </div>
      </div>

      <!-- Support -->
      <div class="space-y-6">
        

        <div class="bg-rose-50 p-8 rounded-[2.5rem] border border-rose-100">
          <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-rose-500 rounded-2xl flex items-center justify-center shadow-lg shadow-rose-200">
              <AlertTriangle class="w-6 h-6 text-white" />
            </div>
            <h4 class="font-black text-rose-900">Assistance</h4>
          </div>
          <p class="text-rose-700/70 text-sm font-medium mb-6">
            Un problème avec votre matériel ? Nos techniciens sont là pour vous aider.
          </p>
          <button 
            @click="router.push('/agent/pannes')"
            class="w-full py-3 bg-white border border-rose-200 text-rose-600 font-black rounded-2xl hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all"
          >
            Signaler un incident
          </button>
        </div>

        <div class="bg-red-400 p-8 rounded-[2.5rem] border border-rose-100">
          <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-rose-500 rounded-2xl flex items-center justify-center shadow-lg shadow-rose-200">
              <AlertTriangle class="w-6 h-6 text-white" />
            </div>
            <h4 class="font-black text-rose-900">Assistance</h4>
          </div>
          <p class="text-rose-700/70 text-sm font-medium mb-6">
            Un problème avec votre matériel ? Nos techniciens sont là pour vous aider.
          </p>
          <button 
            @click="router.push('/agent/pannes')"
            class="w-full py-3 bg-white border border-rose-200 text-rose-600 font-black rounded-2xl hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all"
          >
            Signaler une perte
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
