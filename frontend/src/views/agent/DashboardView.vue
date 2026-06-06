<script setup>
import { useAuthStore } from '../../stores/auth'
import PageHeader from '../../components/layout/PageHeader.vue'
import StatCard from '../../components/dashboard/StatCard.vue'
import { useRouter } from 'vue-router'
import { 
  AlertTriangle, 
  RotateCcw,
  Smartphone
} from 'lucide-vue-next'

const authStore = useAuthStore()
const router = useRouter()

const stats = [
  { label: 'Mes équipements', value: 2, icon: Smartphone,    colorClass: 'bg-blue-50 text-blue-600' },
  { label: 'Pannes signalées', value: 0, icon: AlertTriangle, colorClass: 'bg-red-50 text-red-600' },
]
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

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <StatCard v-for="stat in stats" :key="stat.label" v-bind="stat" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Équipements -->
      <div class="lg:col-span-2 space-y-6">
        <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
          <div class="flex items-center justify-between mb-8">
            <h3 class="text-xl font-black text-slate-900">Mes équipements actuels</h3>
            <span class="px-3 py-1 bg-primary-50 text-primary-600 text-[10px] font-black uppercase rounded-lg">
              {{ stats[0].value }} Appareils
            </span>
          </div>
          <div class="space-y-4">
            <div 
              v-for="i in 2" :key="i"
              class="p-5 bg-white border border-slate-100 rounded-3xl flex items-center justify-between group hover:border-primary-200 hover:shadow-lg hover:shadow-primary-50 transition-all duration-300"
            >
              <div class="flex items-center gap-5">
                <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-primary-50 group-hover:text-primary-600 transition-colors">
                  <Smartphone class="w-7 h-7" />
                </div>
                <div>
                  <p class="font-black text-slate-900">Zebra Zebra Pro 0{{ i }}</p>
                  <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs text-slate-500 font-medium">REF-8000{{ i }}</span>
                    <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                    <span class="text-[10px] font-black text-emerald-600 uppercase">En service</span>
                  </div>
                </div>
              </div>
              <button class="p-3 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded-2xl transition-all">
                <RotateCcw class="w-5 h-5" />
              </button>
            </div>
          </div>
          <div class="mt-6 text-center">
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
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 p-8 rounded-[2.5rem] shadow-2xl text-white">
          <div class="w-16 h-16 bg-white/10 rounded-[1.5rem] flex items-center justify-center mb-6">
            <RotateCcw class="w-8 h-8 text-white" />
          </div>
          <h4 class="text-xl font-black mb-3 leading-tight">Besoin de rendre du matériel ?</h4>
          <p class="text-slate-400 text-sm mb-8 font-medium leading-relaxed">
            Contactez votre gestionnaire de parc pour initier une procédure de retour en toute sécurité.
          </p>
          <button class="w-full py-4 bg-white text-slate-900 font-black rounded-2xl hover:scale-[1.02] active:scale-[0.98] transition-all shadow-xl">
            Consulter la procédure
          </button>
        </div>

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
      </div>
    </div>
  </div>
</template>
