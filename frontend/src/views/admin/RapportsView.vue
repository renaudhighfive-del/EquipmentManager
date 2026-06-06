<script setup>
import { ref } from 'vue';
import PageHeader from '../../components/layout/PageHeader.vue';
import { 
  BarChart3, 
  Download, 
  Calendar, 
  Filter,
  FileText,
  TrendingUp,
  AlertCircle,
  CheckCircle2
} from 'lucide-vue-next';

const stats = ref([
  { label: 'Équipements Totaux', value: '1,284', change: '+12%', icon: BarChart3, color: 'text-blue-600', bg: 'bg-blue-50' },
  { label: 'En Maintenance', value: '42', change: '-5%', icon: AlertCircle, color: 'text-amber-600', bg: 'bg-amber-50' },
  { label: 'Opérationnels', value: '1,210', change: '+18%', icon: CheckCircle2, color: 'text-emerald-600', bg: 'bg-emerald-50' },
  { label: 'Mouvements (30j)', value: '456', change: '+24%', icon: TrendingUp, color: 'text-purple-600', bg: 'bg-purple-50' },
]);

const reports = ref([
  { id: 1, title: 'Inventaire Complet', type: 'PDF', date: '2024-03-01', size: '2.4 MB' },
  { id: 2, title: 'Rapport de Maintenance - Février', type: 'Excel', date: '2024-02-28', size: '1.1 MB' },
  { id: 3, title: 'Journal des Mouvements', type: 'CSV', date: '2024-03-05', size: '850 KB' },
]);
</script>

<template>
  <div class="space-y-8 animate-in fade-in duration-500">
    <PageHeader 
      title="Rapports & Statistiques" 
      subtitle="Analyse détaillée du parc d'équipements"
    >
      <template #actions>
        <button class="flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
          <Calendar class="w-4 h-4" />
          Mars 2024
        </button>
        <button class="flex items-center gap-2 px-5 py-2.5 bg-primary-600 rounded-xl text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-lg shadow-primary-200">
          <Download class="w-4 h-4" />
          Exporter tout
        </button>
      </template>
    </PageHeader>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div v-for="stat in stats" :key="stat.label" class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all duration-300">
        <div class="flex items-start justify-between mb-4">
          <div :class="['p-3 rounded-2xl', stat.bg]">
            <component :is="stat.icon" :class="['w-6 h-6', stat.color]" />
          </div>
          <span :class="['text-xs font-bold px-2 py-1 rounded-lg', stat.change.startsWith('+') ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50']">
            {{ stat.change }}
          </span>
        </div>
        <p class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-1">{{ stat.label }}</p>
        <h3 class="text-3xl font-black text-slate-900">{{ stat.value }}</h3>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Recent Reports -->
      <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="p-8 border-b border-slate-50 flex items-center justify-between">
            <h3 class="text-xl font-black text-slate-900">Rapports Récents</h3>
            <button class="text-sm font-bold text-primary-600 hover:text-primary-700">Voir tout</button>
          </div>
          <div class="divide-y divide-slate-50">
            <div v-for="report in reports" :key="report.id" class="p-6 flex items-center justify-between group hover:bg-slate-50/50 transition-colors">
              <div class="flex items-center gap-5">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 group-hover:bg-primary-50 group-hover:text-primary-600 transition-colors">
                  <FileText class="w-6 h-6" />
                </div>
                <div>
                  <h4 class="font-bold text-slate-900">{{ report.title }}</h4>
                  <p class="text-xs text-slate-500 font-medium">Généré le {{ report.date }} • {{ report.size }}</p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <span class="px-3 py-1 bg-slate-100 text-slate-600 text-[10px] font-black uppercase rounded-lg">
                  {{ report.type }}
                </span>
                <button class="p-2 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-all">
                  <Download class="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions / Filters -->
      <div class="space-y-6">
        <div class="bg-primary-600 p-8 rounded-[2.5rem] shadow-xl shadow-primary-200 text-white">
          <h3 class="text-xl font-black mb-2">Générer un rapport</h3>
          <p class="text-primary-100 text-sm mb-6">Personnalisez votre rapport en quelques clics.</p>
          
          <div class="space-y-4">
            <div class="space-y-1.5">
              <label class="text-[10px] font-black uppercase tracking-widest text-primary-200">Type de données</label>
              <select class="w-full h-12 px-4 bg-white/10 border border-white/20 rounded-2xl text-sm font-medium outline-none focus:bg-white/20 transition-all appearance-none">
                <option>Tous les équipements</option>
                <option>Maintenance & Pannes</option>
                <option>Mouvements de stock</option>
              </select>
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black uppercase tracking-widest text-primary-200">Format</label>
              <div class="grid grid-cols-2 gap-3">
                <button class="h-12 bg-white text-primary-600 font-bold rounded-2xl text-sm">PDF</button>
                <button class="h-12 bg-white/10 border border-white/20 font-bold rounded-2xl text-sm">Excel</button>
              </div>
            </div>
            <button class="w-full py-4 mt-4 bg-white text-primary-600 font-black rounded-2xl hover:scale-[1.02] active:scale-[0.98] transition-all shadow-lg">
              Générer le rapport
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
