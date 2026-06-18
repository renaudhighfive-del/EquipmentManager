<script setup>
import { ref, onMounted, computed } from 'vue';
import { 
  Package, 
  CheckCircle2, 
  ArrowLeftRight, 
  RotateCcw,
  AlertTriangle, 
  Wrench, 
  AlertOctagon, 
  History,
  Download,
  Plus,
  Loader2
} from '@lucide/vue';
import PageHeader from '../../components/layout/PageHeader.vue';
import StatCard from '../../components/dashboard/StatCard.vue';
import { useDashboardStore } from '../../stores/dashboard';
import { storeToRefs } from 'pinia';
import { 
  Chart as ChartJS, 
  CategoryScale, 
  LinearScale, 
  PointElement, 
  LineElement, 
  Title, 
  Tooltip, 
  Legend, 
  ArcElement,
  BarElement,
  Filler
} from 'chart.js';
import { Line, Doughnut } from 'vue-chartjs';

ChartJS.register(
  CategoryScale, 
  LinearScale, 
  PointElement, 
  LineElement, 
  Title, 
  Tooltip, 
  Legend, 
  ArcElement,
  BarElement,
  Filler
);

const dashboardStore = useDashboardStore();
const { stats: dashboardStats, loading, error: dashboardError } = storeToRefs(dashboardStore);

onMounted(() => {
  dashboardStore.fetchStats();
});

const stats = computed(() => {
  if (!dashboardStats.value || !dashboardStats.value.stats_etat) return [];
  
  const s = dashboardStats.value;
  const e = s.stats_etat;
  return [
    { label: 'Total équipements', value: s.total_equipements || 0, icon: Package, colorClass: 'bg-blue-50 text-blue-600' },
    { label: 'Disponibles', value: e.neuf || 0, icon: CheckCircle2, colorClass: 'bg-emerald-50 text-emerald-600' },
    { label: 'En service', value: e.en_service || 0, icon: ArrowLeftRight, colorClass: 'bg-purple-50 text-purple-600' },
    { label: 'En panne', value: e.en_panne || 0, icon: AlertTriangle, colorClass: 'bg-red-50 text-red-600' },
    { label: 'En maintenance', value: e.en_maintenance || 0, icon: Wrench, colorClass: 'bg-amber-50 text-amber-600' },
    { label: 'Perdus', value: e.perdu || 0, icon: AlertOctagon, colorClass: 'bg-rose-50 text-rose-600' },
    { label: 'Réformés', value: e.reforme || 0, icon: History, colorClass: 'bg-slate-100 text-slate-600' },
  ];
});

const evolutionData = computed(() => {
  if (!dashboardStats.value || !dashboardStats.value.evolution) return { labels: [], datasets: [] };
  
  const e = dashboardStats.value.evolution;
  return {
    labels: e.labels || [],
    datasets: [
      {
        label: 'Affectations',
        data: e.assignments || [],
        borderColor: '#2563eb',
        backgroundColor: 'rgba(37, 99, 235, 0.1)',
        fill: true,
        tension: 0.4,
      },
      {
        label: 'Retours',
        data: e.returns || [],
        borderColor: '#10b981',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        fill: true,
        tension: 0.4,
      }
    ]
  };
});

const categoryData = computed(() => {
  if (!dashboardStats.value || !dashboardStats.value.stats_categorie) return { labels: [], datasets: [] };
  
  const c = dashboardStats.value.stats_categorie;
  return {
    labels: c.map(item => item.label),
    datasets: [
      {
        data: c.map(item => item.total),
        backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#64748b', '#ec4899', '#06b6d4'],
        borderWidth: 0,
      }
    ]
  };
});

const evolutionOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      mode: 'index',
      intersect: false,
      backgroundColor: '#fff',
      titleColor: '#1e293b',
      bodyColor: '#64748b',
      borderColor: '#e2e8f0',
      borderWidth: 1,
      padding: 12,
      displayColors: true,
      boxPadding: 6,
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: { color: '#f1f5f9' },
      border: { display: false },
      ticks: { color: '#94a3b8', font: { size: 11 } }
    },
    x: {
      grid: { display: false },
      border: { display: false },
      ticks: { color: '#94a3b8', font: { size: 11 } }
    }
  }
};

const categoryOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '75%',
  plugins: {
    legend: { display: false }
  }
};

const getMouvementIcon = (type) => {
  switch (type) {
    case 'affectation': return ArrowLeftRight;
    case 'retour': return RotateCcw;
    case 'panne': return AlertTriangle;
    case 'maintenance': return Wrench;
    default: return History;
  }
};

const formatTimeAgo = (dateStr) => {
  const date = new Date(dateStr);
  const now = new Date();
  const diffInMinutes = Math.floor((now - date) / (1000 * 60));
  
  if (diffInMinutes < 1) return "À l'instant";
  if (diffInMinutes < 60) return `Il y a ${diffInMinutes} min`;
  
  const diffInHours = Math.floor(diffInMinutes / 60);
  if (diffInHours < 24) return `Il y a ${diffInHours}h`;
  
  const diffInDays = Math.floor(diffInHours / 24);
  return `Il y a ${diffInDays}j`;
};

const handleExportPdf = async () => {
  try {
    const apiUrl = `${import.meta.env.VITE_API_URL}/dashboard/export-pdf`;
    
    // Créer un lien temporaire pour télécharger le PDF
    const link = document.createElement('a');
    link.href = apiUrl;
    link.target = '_blank';
    link.download = `rapport_dashboard_${Date.now()}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (error) {
    console.error('Erreur lors de l\'export PDF:', error);
    alert('Une erreur est survenue lors de l\'export PDF');
  }
};
</script>

<template>
  <div class="space-y-8 animate-in fade-in duration-700">
    <!-- Page Header -->
    <PageHeader 
      title="Tableau de bord" 
      subtitle="Vue d'ensemble du parc et des opérations en temps réel"
    >
      <template #actions>
        <button @click="handleExportPdf" class="flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
          <Download class="w-4 h-4" />
          Exporter
        </button>
        <router-link to="/admin/affectations" class="flex items-center gap-2 px-5 py-2.5 bg-primary-600 rounded-xl text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-lg shadow-primary-200">
          <Plus class="w-4 h-4" />
          Nouvelle affectation
        </router-link>
      </template>
    </PageHeader>

    <div v-if="loading && !dashboardStats" class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-slate-200">
      <Loader2 class="w-10 h-10 text-primary-500 animate-spin mb-4" />
      <p class="text-slate-500 font-medium text-lg italic">Chargement des données en cours...</p>
    </div>

    <div v-else-if="dashboardError" class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-red-100 shadow-sm">
      <div class="w-16 h-16 bg-red-50 rounded-2xl flex items-center justify-center mb-4">
        <AlertOctagon class="w-8 h-8 text-red-500" />
      </div>
      <p class="text-slate-900 font-bold text-lg mb-2">Impossible de charger le tableau de bord</p>
      <p class="text-slate-500 text-sm mb-6 max-w-md text-center">{{ dashboardError }}</p>
      <button 
        @click="dashboardStore.fetchStats()"
        class="px-6 py-2 bg-slate-900 text-white font-bold rounded-xl hover:bg-slate-800 transition-all"
      >
        Réessayer
      </button>
    </div>

    <template v-else-if="dashboardStats">
      <!-- Stats Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-6">
        <StatCard 
          v-for="stat in stats" 
          :key="stat.label"
          v-bind="stat"
        />
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Evolution Chart -->
        <div class="lg:col-span-2 bg-white p-8 rounded-3xl border border-slate-200 shadow-sm flex flex-col h-[450px]">
          <div class="flex items-center justify-between mb-8">
            <h3 class="text-lg font-bold text-slate-900">Évolution des activités (12 mois)</h3>
            <div class="flex items-center gap-2 px-3 py-1 bg-slate-100 rounded-lg text-[11px] font-bold text-slate-500 uppercase tracking-wider">
              {{ new Date().getFullYear() }}
            </div>
          </div>
          <div class="flex-1 min-h-0">
            <Line :data="evolutionData" :options="evolutionOptions" />
          </div>
        </div>

        <!-- Categories Chart -->
        <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm flex flex-col h-[450px]">
          <h3 class="text-lg font-bold text-slate-900 mb-8">Répartition par catégorie</h3>
          <div class="flex-1 min-h-0 relative mb-8">
            <Doughnut :data="categoryData" :options="categoryOptions" />
            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
              <span class="text-3xl font-bold text-slate-900">{{ dashboardStats.total_equipements }}</span>
              <span class="text-xs font-medium text-slate-500 uppercase tracking-widest">Total</span>
            </div>
          </div>
          
          <!-- Legend List -->
          <div class="grid grid-cols-2 gap-y-3 gap-x-4 overflow-y-auto pr-2">
            <div v-for="(label, index) in categoryData.labels" :key="label" class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: categoryData.datasets[0].backgroundColor[index] }"></div>
                <span class="text-xs font-medium text-slate-600 truncate max-w-[80px]">{{ label }}</span>
              </div>
              <span class="text-xs font-bold text-slate-900">{{ categoryData.datasets[0].data[index] }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Activity -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden flex flex-col h-[400px]">
          <div class="p-8 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-900">Activité récente</h3>
            <router-link to="/admin/mouvements" class="text-sm font-bold text-primary-600 hover:text-primary-700">Voir tout</router-link>
          </div>
          <div class="flex-1 overflow-y-auto">
            <div v-if="dashboardStats.recent_activity.length === 0" class="flex flex-col items-center justify-center h-full text-slate-400 italic text-sm">
              Aucune activité récente
            </div>
            <div v-for="mvt in dashboardStats.recent_activity" :key="mvt.id" class="p-6 flex items-center gap-4 hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-0">
              <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                <component :is="getMouvementIcon(mvt.type_mouvement)" class="w-5 h-5 text-slate-500" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-slate-900 uppercase">{{ mvt.type_mouvement }} — {{ mvt.equipement?.reference }}</p>
                <p class="text-xs text-slate-500 truncate">Par {{ mvt.user?.name }} • {{ mvt.equipement?.modele }}</p>
              </div>
              <span class="text-xs font-medium text-slate-400">{{ formatTimeAgo(mvt.created_at) }}</span>
            </div>
          </div>
        </div>

        <!-- Alerts -->
        <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm flex flex-col h-[400px]">
          <h3 class="text-lg font-bold text-slate-900 mb-6">Alertes & Monitoring</h3>
          <div class="space-y-4 overflow-y-auto">
            <div v-if="dashboardStats.alerts.pannes_critiques > 0" class="p-5 rounded-2xl bg-rose-50 border border-rose-100 flex gap-4">
              <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center flex-shrink-0">
                <AlertOctagon class="w-5 h-5 text-rose-600" />
              </div>
              <div>
                <p class="text-sm font-bold text-rose-900">{{ dashboardStats.alerts.pannes_critiques }} Pannes critiques</p>
                <p class="text-xs text-rose-700/70 mt-1">Des pannes de haute priorité nécessitent une intervention rapide.</p>
              </div>
            </div>

            <div v-if="dashboardStats.alerts.maintenances_en_cours > 0" class="p-5 rounded-2xl bg-amber-50 border border-amber-100 flex gap-4">
              <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                <Wrench class="w-5 h-5 text-amber-600" />
              </div>
              <div>
                <p class="text-sm font-bold text-amber-900">{{ dashboardStats.alerts.maintenances_en_cours }} Matériels en maintenance</p>
                <p class="text-xs text-amber-700/70 mt-1">Suivi des réparations en cours chez les techniciens.</p>
              </div>
            </div>

            <div v-if="dashboardStats.alerts.pannes_critiques === 0 && dashboardStats.alerts.maintenances_en_cours === 0" class="flex flex-col items-center justify-center h-full py-10">
              <CheckCircle2 class="w-12 h-12 text-emerald-200 mb-4" />
              <p class="text-slate-400 font-medium italic">Aucune alerte en attente</p>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

 
