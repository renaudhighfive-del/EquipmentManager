<script setup>
import { ref } from 'vue';
import { Search, Bell, Menu, ChevronDown, LogOut, User } from '@lucide/vue';
import { useAuthStore } from '../../stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const dropdownOpen = ref(false);

const toggleDropdown = () => {
  dropdownOpen.value = !dropdownOpen.value;
};

const closeDropdown = () => {
  dropdownOpen.value = false;
};

const handleLogout = async () => {
  dropdownOpen.value = false;
  await authStore.logout();
  router.push('/login');
};

defineProps({
  isSidebarCollapsed: Boolean
});

defineEmits(['toggleSidebar']);
</script>

<template>
  <header class="h-20 bg-white border-b border-slate-200 px-4 sm:px-8 flex items-center justify-between sticky top-0 z-30">
    <!-- Left: Hamburger & Search -->
    <div class="flex items-center gap-2 sm:gap-6 flex-1 max-w-2xl">
      <button 
        @click="$emit('toggleSidebar')"
        class="p-2 sm:p-2.5 rounded-xl hover:bg-slate-100 text-slate-500 transition-all active:scale-95"
      >
        <Menu class="w-6 h-6" />
      </button>

      <!-- <div class="relative flex-1 hidden sm:block">
        <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
        <input 
          type="text" 
          placeholder="Recherche globale..."
          class="w-full h-12 bg-slate-100 border-none rounded-2xl pl-12 pr-4 text-sm focus:ring-2 focus:ring-primary-500/20 transition-all placeholder:text-slate-500"
        >
      </div> -->
    </div>

    <!-- Right: Actions & Profile -->
    <div class="flex items-center gap-2 sm:gap-4">
      <!-- Bell -->
      <button class="relative p-2 sm:p-3 rounded-xl hover:bg-slate-100 text-slate-500 transition-colors">
        <Bell class="w-5 h-5 sm:w-6 h-6" />
        <span class="absolute top-2.5 right-2.5 sm:top-3 sm:right-3 w-2.5 h-2.5 bg-red-500 border-2 border-white rounded-full"></span>
      </button>

      <div class="h-8 sm:h-10 w-[1px] bg-slate-200 mx-1"></div>

      <!-- Profile Dropdown -->
      <div class="relative" v-click-outside="closeDropdown">
        <button
          @click="toggleDropdown"
          class="flex items-center gap-2 sm:gap-3 pl-1 pr-1 sm:pr-2 py-1.5 rounded-2xl hover:bg-slate-50 transition-all"
        >
          <!-- Avatar rond -->
          <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-full bg-primary-100 border-2 border-primary-200 flex items-center justify-center text-primary-700 font-bold shadow-sm overflow-hidden flex-shrink-0">
            <img 
              v-if="authStore.user?.avatar_url" 
              :src="authStore.user.avatar_url" 
              class="w-full h-full object-cover"
            >
            <span v-else class="text-xs sm:text-sm">{{ authStore.user?.name?.charAt(0) || 'A' }}</span>
          </div>
          <!-- Nom & Rôle -->
          <div class="text-left hidden md:block">
            <p class="text-sm font-bold text-slate-900 leading-tight">{{ authStore.user?.name || 'Utilisateur' }}</p>
            <p class="text-[11px] font-medium text-slate-500 capitalize">{{ authStore.user?.role || 'Rôle' }}</p>
          </div>
          <!-- Chevron -->
          <ChevronDown 
            class="w-4 h-4 text-slate-400 transition-transform duration-200 flex-shrink-0"
            :class="{ 'rotate-180': dropdownOpen }"
          />
        </button>

        <!-- Dropdown Menu -->
        <Transition
          enter-active-class="transition ease-out duration-150"
          enter-from-class="opacity-0 translate-y-1 scale-95"
          enter-to-class="opacity-100 translate-y-0 scale-100"
          leave-active-class="transition ease-in duration-100"
          leave-from-class="opacity-100 translate-y-0 scale-100"
          leave-to-class="opacity-0 translate-y-1 scale-95"
        >
          <div
            v-if="dropdownOpen"
            class="absolute right-0 top-full mt-2 w-52 bg-white border border-slate-200 rounded-2xl shadow-xl shadow-slate-200/80 overflow-hidden z-50"
          >
            <!-- User info recap -->
            <div class="px-4 py-3 border-b border-slate-100">
              <p class="text-sm font-bold text-slate-900">{{ authStore.user?.name || 'Utilisateur' }}</p>
              <p class="text-xs text-slate-500 truncate">{{ authStore.user?.email || '' }}</p>
            </div>
            <!-- Actions -->
            <div class="py-1.5">
              <button
                @click="closeDropdown(); router.push(`/${authStore.user?.role}/profile`)"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors text-left"
              >
                <User class="w-4 h-4 text-slate-400" />
                Mon profil
              </button>
              <button
                @click="handleLogout"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors text-left"
              >
                <LogOut class="w-4 h-4" />
                Se déconnecter
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </header>
</template>
