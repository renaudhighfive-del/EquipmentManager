<script setup>
import { computed } from 'vue';
import { 
  LayoutDashboard, 
  Users, 
  Package, 
  ArrowLeftRight, 
  Activity, 
  AlertTriangle, 
  Wrench, 
  AlertOctagon, 
  Archive,
  BarChart3, 
  Settings,
  Smartphone,
  CircleUser,
  X
} from 'lucide-vue-next';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const route = useRoute();
const authStore = useAuthStore();

defineProps({
  isCollapsed: Boolean,
  isOpenMobile: Boolean
});

defineEmits(['closeMobile']);

// Menus par rôle
const menusByRole = {
  admin: [
    { label: 'Tableau de bord', icon: LayoutDashboard, path: '/admin/dashboard' },
    { label: 'Agents',          icon: Users,           path: '/admin/agents' },
    { label: 'Équipements',     icon: Package,         path: '/admin/equipements' },
    { label: 'Affectations',    icon: ArrowLeftRight,  path: '/admin/affectations' },
    { label: 'Mouvements',      icon: Activity,        path: '/admin/mouvements' },
    { label: 'Pannes',          icon: AlertTriangle,   path: '/admin/pannes' },
    { label: 'Maintenances',    icon: Wrench,          path: '/admin/maintenances' },
    { label: 'Sinistres',       icon: AlertOctagon,    path: '/admin/sinistres' },
    { label: 'Archives',        icon: Archive,         path: '/admin/archives' },
    { label: 'Rapports',        icon: BarChart3,       path: '/admin/rapports' },
    { label: 'Administration',  icon: Settings,        path: '/admin/administration' },
    { label: 'Mon profil',      icon: CircleUser,      path: '/admin/profile' },
  ],
  gestionnaire: [
    { label: 'Tableau de bord', icon: LayoutDashboard, path: '/gestionnaire/dashboard' },
    { label: 'Agents',          icon: Users,           path: '/gestionnaire/agents' },
    { label: 'Équipements',     icon: Package,         path: '/gestionnaire/equipements' },
    { label: 'Affectations',    icon: ArrowLeftRight,  path: '/gestionnaire/affectations' },
    { label: 'Pannes',          icon: AlertTriangle,   path: '/gestionnaire/pannes' },
    { label: 'Maintenances',    icon: Wrench,          path: '/gestionnaire/maintenances' },
    { label: 'Pertes & Casses', icon: AlertOctagon,    path: '/gestionnaire/sinistres' },
    { label: 'Rapports',        icon: BarChart3,       path: '/gestionnaire/rapports' },
    { label: 'Mon profil',      icon: CircleUser,      path: '/gestionnaire/profile' },
  ],
  agent: [
    { label: 'Mon espace',       icon: LayoutDashboard, path: '/agent/dashboard' },
    { label: 'Mes équipements',  icon: Smartphone,      path: '/agent/equipements' },
    { label: 'Mes Incidents',    icon: AlertTriangle,   path: '/agent/incidents' },
    { label: 'Mon profil',       icon: CircleUser,      path: '/agent/profile' },
  ],
};

const menuItems = computed(() => {
  const role = authStore.user?.role;
  return menusByRole[role] ?? [];
});
</script>

<template>
  <!-- Overlay mobile -->
  <div 
    v-if="isOpenMobile" 
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 lg:hidden transition-opacity"
    @click="$emit('closeMobile')"
  ></div>

  <aside 
    class="bg-white border-r border-slate-200 flex flex-col h-screen fixed inset-y-0 left-0 lg:sticky lg:top-0 transition-all duration-300 z-[60]"
    :class="[
      isCollapsed ? 'lg:w-20' : 'lg:w-64',
      isOpenMobile ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0'
    ]"
  >
    <!-- Logo -->
    <div class="p-4 mb-2 flex items-center justify-between overflow-hidden whitespace-nowrap">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-primary-600 flex items-center justify-center shadow-lg shadow-primary-200 flex-shrink-0">
          <Package class="w-6 h-6 text-white" />
        </div>
        <div v-if="!isCollapsed || isOpenMobile" class="animate-in fade-in slide-in-from-left-2 duration-300">
          <h1 class="text-xl font-bold text-slate-900 leading-tight">EquipTrack</h1>
          <p class="text-xs text-slate-500 font-medium">Une traçabilité impécable</p>
        </div>
      </div>
      <button 
        @click="$emit('closeMobile')"
        class="lg:hidden p-2 text-slate-400 hover:text-slate-600 transition-colors"
      >
        <X class="w-5 h-5" />
      </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-2 space-y-1 overflow-y-auto custom-scrollbar overflow-x-hidden">
      <router-link 
        v-for="item in menuItems" 
        :key="item.path"
        :to="item.path"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group relative whitespace-nowrap"
        :class="[
          route.path === item.path 
            ? 'bg-primary-600 text-white font-semibold shadow-lg shadow-primary-200' 
            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'
        ]"
      >
        <component 
          :is="item.icon" 
          class="w-5 h-5 transition-colors flex-shrink-0"
          :class="route.path === item.path ? 'text-white' : 'text-slate-400 group-hover:text-slate-700'"
        />
        <span 
          v-if="!isCollapsed || isOpenMobile" 
          class="text-sm animate-in fade-in slide-in-from-left-2 duration-300"
        >
          {{ item.label }}
        </span>

        <!-- Tooltip mode réduit -->
        <div 
          v-if="isCollapsed && !isOpenMobile"
          class="absolute left-full ml-4 px-3 py-2 bg-slate-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50 whitespace-nowrap shadow-xl"
        >
          {{ item.label }}
        </div>
      </router-link>
    </nav>

    <!-- Bottom card -->
    <div class="p-3 mt-auto">
      <div 
        v-if="!isCollapsed || isOpenMobile"
        class="bg-primary-600 rounded-2xl p-4 text-white cursor-pointer shadow-xl shadow-primary-200 hover:bg-primary-700 transition-colors animate-in fade-in zoom-in duration-300 flex items-center justify-center gap-2"
      >
        <div class="w-6 h-6 rounded-full border-2 border-white flex items-center justify-center text-[11px] font-black flex-shrink-0">
          ?
        </div>
        <span class="text-sm font-bold">Besoin d'aide ?</span>
      </div>
      <div v-else class="flex justify-center py-4">
        <div class="w-10 h-10 rounded-xl bg-primary-600 flex items-center justify-center text-white cursor-pointer hover:bg-primary-700 transition-colors shadow-lg shadow-primary-200">
          <span class="text-sm font-black">?</span>
        </div>
      </div>
    </div>
  </aside>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 0px; }
</style>
