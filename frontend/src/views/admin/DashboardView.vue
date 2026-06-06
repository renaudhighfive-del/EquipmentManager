<script setup>
import { ref, onMounted } from 'vue';
import { 
  Package, 
  CheckCircle2, 
  ArrowLeftRight, 
  AlertTriangle, 
  Wrench, 
  AlertOctagon, 
  History,
  Download,
  Plus
} from 'lucide-vue-next';
import PageHeader from '../../components/layout/PageHeader.vue';
import StatCard from '../../components/dashboard/StatCard.vue';
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

const stats = [
  { label: 'Total équipements', value: 48, icon: Package, colorClass: 'bg-blue-50 text-blue-600' },
  { label: 'Disponibles', value: 14, icon: CheckCircle2, colorClass: 'bg-emerald-50 text-emerald-600' },
  { label: 'Affectés', value: 16, icon: ArrowLeftRight, colorClass: 'bg-purple-50 text-purple-600' },
  { label: 'En panne', value: 7, icon: AlertTriangle, colorClass: 'bg-red-50 text-red-600' },
  { label: 'En maintenance', value: 7, icon: Wrench, colorClass: 'bg-amber-50 text-amber-600' },
  { label: 'Perdus', value: 7, icon: AlertOctagon, colorClass: 'bg-rose-50 text-rose-600' },
  { label: 'Réformés', value: 6, icon: History, colorClass: 'bg-slate-100 text-slate-600' },
];

const evolutionData = {
  labels: ['Janv.', 'Févr.', 'Mars', 'Avr.', 'Mai', 'Juin', 'Juil.', 'Août', 'Sept.', 'Oct.', 'Nov.', 'Déc.'],
  datasets: [
    {
      label: 'Affectations',
      data: [35, 42, 48, 40, 38, 52, 45, 40, 55, 48, 50, 45],
      borderColor: '#2563eb',
      backgroundColor: 'rgba(37, 99, 235, 0.1)',
      fill: true,
      tension: 0.4,
    },
    {
      label: 'Retours',
      data: [20, 15, 25, 22, 18, 28, 25, 20, 32, 28, 30, 25],
      borderColor: '#10b981',
      backgroundColor: 'rgba(16, 185, 129, 0.1)',
      fill: true,
      tension: 0.4,
    }
  ]
};

const categoryData = {
  labels: ['PDA', 'Smartphone', 'Tablette', 'Ordinateur portable', 'Scanner', 'Accessoire'],
  datasets: [
    {
      data: [8, 8, 8, 8, 8, 8],
      backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#64748b'],
      borderWidth: 0,
    }
  ]
};

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
</script>

<template>
  <div class="space-y-8 animate-in fade-in duration-700">
    <!-- Page Header -->
    <PageHeader 
      title="Tableau de bord" 
      subtitle="Vue d'ensemble du parc et des opérations en temps réel"
    >
      <template #actions>
        <button class="flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
          <Download class="w-4 h-4" />
          Exporter
        </button>
        <button class="flex items-center gap-2 px-5 py-2.5 bg-primary-600 rounded-xl text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-lg shadow-primary-200">
          <Plus class="w-4 h-4" />
          Nouvelle affectation
        </button>
      </template>
    </PageHeader>

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
          <h3 class="text-lg font-bold text-slate-900">Évolution des affectations (12 mois)</h3>
          <div class="flex items-center gap-2 px-3 py-1 bg-slate-100 rounded-lg text-[11px] font-bold text-slate-500 uppercase tracking-wider">
            2025
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
            <span class="text-3xl font-bold text-slate-900">48</span>
            <span class="text-xs font-medium text-slate-500 uppercase tracking-widest">Total</span>
          </div>
        </div>
        
        <!-- Legend List -->
        <div class="grid grid-cols-2 gap-y-3 gap-x-4">
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
          <button class="text-sm font-bold text-primary-600 hover:text-primary-700">Voir tout</button>
        </div>
        <div class="flex-1 overflow-y-auto">
          <div v-for="i in 6" :key="i" class="p-6 flex items-center gap-4 hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-0">
            <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
              <ArrowLeftRight class="w-5 h-5 text-slate-500" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-slate-900">Affectation — PSS-80000</p>
              <p class="text-xs text-slate-500 truncate">Affecté à Alexandre Martin par Admin</p>
            </div>
            <span class="text-xs font-medium text-slate-400">Il y a 2h</span>
          </div>
        </div>
      </div>

      <!-- Quick Actions / Alerts -->
      <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm flex flex-col h-[400px]">
        <h3 class="text-lg font-bold text-slate-900 mb-6">Alertes critiques</h3>
        <div class="space-y-4 overflow-y-auto">
          <div class="p-5 rounded-2xl bg-rose-50 border border-rose-100 flex gap-4">
            <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center flex-shrink-0">
              <AlertOctagon class="w-5 h-5 text-rose-600" />
            </div>
            <div>
              <p class="text-sm font-bold text-rose-900">3 Sinistres en attente</p>
              <p class="text-xs text-rose-700/70 mt-1">Des déclarations de perte/vol nécessitent une validation immédiate.</p>
            </div>
          </div>
          <div class="p-5 rounded-2xl bg-amber-50 border border-amber-100 flex gap-4">
            <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
              <Wrench class="w-5 h-5 text-amber-600" />
            </div>
            <div>
              <p class="text-sm font-bold text-amber-900">7 Maintenances en retard</p>
              <p class="text-xs text-amber-700/70 mt-1">Certains équipements dépassent la date de fin de maintenance prévue.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
