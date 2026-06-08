<script setup>
import { ref, watch } from 'vue';
import Sidebar from '../components/layout/Sidebar.vue';
import Header from '../components/layout/Header.vue';
import { useRoute } from 'vue-router';

const isSidebarCollapsed = ref(false);
const isSidebarOpenMobile = ref(false);
const route = useRoute();

const toggleSidebar = () => {
  if (window.innerWidth < 1024) {
    isSidebarOpenMobile.value = !isSidebarOpenMobile.value;
  } else {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
  }
};

// Fermer le sidebar mobile lors du changement de route
watch(() => route.path, () => {
  isSidebarOpenMobile.value = false;
});
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex">
    <!-- Sidebar -->
    <Sidebar 
      :is-collapsed="isSidebarCollapsed" 
      :is-open-mobile="isSidebarOpenMobile"
      @close-mobile="isSidebarOpenMobile = false"
    />

    <!-- Main Wrapper -->
    <div class="flex-1 flex flex-col min-w-0 transition-all duration-300">
      <Header 
        :is-sidebar-collapsed="isSidebarCollapsed" 
        @toggle-sidebar="toggleSidebar" 
      />
      
      <!-- Content Area -->
      <main class="p-4 sm:p-8">
        <router-view v-slot="{ Component }">
          <transition 
            name="fade" 
            mode="out-in"
          >
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
