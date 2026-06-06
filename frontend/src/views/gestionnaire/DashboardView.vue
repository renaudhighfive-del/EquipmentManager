<script setup>
import { useAuthStore } from '../../stores/auth'
import PageHeader from '../../components/layout/PageHeader.vue'
import StatCard from '../../components/dashboard/StatCard.vue'
import { useRouter } from 'vue-router'
import { 
  Package, 
  ArrowLeftRight, 
  AlertTriangle, 
  Wrench,
  Download,
  Plus
} from 'lucide-vue-next'

const authStore = useAuthStore()
const router = useRouter()

const stats = [
  { label: 'Total équipements', value: 48, icon: Package,       colorClass: 'bg-blue-50 text-blue-600' },
  { label: 'Affectés',          value: 16, icon: ArrowLeftRight, colorClass: 'bg-purple-50 text-purple-600' },
  { label: 'En panne',          value: 7,  icon: AlertTriangle,  colorClass: 'bg-red-50 text-red-600' },
  { label: 'En maintenance',    value: 7,  icon: Wrench,         colorClass: 'bg-amber-50 text-amber-600' },
]
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <PageHeader 
      title="Gestion du Parc" 
      :subtitle="`Bienvenue, ${authStore.user?.name}`"
    >
      <template #actions>
        <button class="flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
          <Download class="w-4 h-4" />
          Exporter
        </button>
        <button 
          @click="router.push('/gestionnaire/equipements')"
          class="flex items-center gap-2 px-5 py-2.5 bg-emerald-600 rounded-xl text-sm font-bold text-white hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-200"
        >
          <Plus class="w-4 h-4" />
          Nouvel équipement
        </button>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <StatCard v-for="stat in stats" :key="stat.label" v-bind="stat" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Main -->
      <div class="lg:col-span-2 space-y-6">
        <div class="bg-white p-12 rounded-3xl border border-slate-200 shadow-sm text-center">
          <div class="w-20 h-20 bg-primary-50 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-sm">
            <Package class="w-10 h-10 text-primary-600" />
          </div>
          <h3 class="text-2xl font-black text-slate-900 mb-3">Gestion Active du Parc</h3>
          <p class="text-slate-500 max-w-md mx-auto font-medium leading-relaxed">
            Vous avez un contrôle total sur l'inventaire. Suivez les affectations, gérez les maintenances et assurez la disponibilité des équipements en temps réel.
          </p>
          <div class="mt-10 flex items-center justify-center gap-4">
            <button 
              @click="router.push('/gestionnaire/equipements')"
              class="px-8 py-4 bg-primary-600 text-white font-black rounded-2xl hover:scale-105 transition-all shadow-xl shadow-primary-200"
            >
              Voir l'inventaire
            </button>
            <button 
              @click="router.push('/gestionnaire/pannes')"
              class="px-8 py-4 bg-white border border-slate-200 text-slate-700 font-black rounded-2xl hover:bg-slate-50 transition-all"
            >
              Consulter les pannes
            </button>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="space-y-6">
        <div class="bg-slate-900 p-8 rounded-[2.5rem] shadow-2xl text-white relative overflow-hidden">
          <div class="relative z-10">
            <h3 class="text-xl font-black mb-2">Actions Rapides</h3>
            <p class="text-slate-400 text-sm mb-6 font-medium">Gérez vos tâches quotidiennes efficacement.</p>
            <div class="space-y-3">
              <button 
                @click="router.push('/gestionnaire/equipements')"
                class="w-full p-4 bg-white/10 hover:bg-white/20 border border-white/10 rounded-2xl flex items-center gap-4 transition-all text-left"
              >
                <div class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                  <Plus class="w-5 h-5 text-white" />
                </div>
                <span class="font-bold">Nouvel Équipement</span>
              </button>
              <button 
                @click="router.push('/gestionnaire/affectations')"
                class="w-full p-4 bg-white/10 hover:bg-white/20 border border-white/10 rounded-2xl flex items-center gap-4 transition-all text-left"
              >
                <div class="w-10 h-10 rounded-xl bg-purple-500 flex items-center justify-center shadow-lg shadow-purple-500/20">
                  <ArrowLeftRight class="w-5 h-5 text-white" />
                </div>
                <span class="font-bold">Nouvelle Affectation</span>
              </button>
            </div>
          </div>
          <div class="absolute -right-10 -top-10 w-40 h-40 bg-primary-600/20 rounded-full blur-3xl"></div>
          <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-purple-600/20 rounded-full blur-3xl"></div>
        </div>
      </div>
    </div>
  </div>
</template>
