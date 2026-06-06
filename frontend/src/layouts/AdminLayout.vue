<script setup>
import { ref } from 'vue';
import Sidebar from '../components/layout/Sidebar.vue';
import Header from '../components/layout/Header.vue';

const isSidebarCollapsed = ref(false);

const toggleSidebar = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value;
};
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex">
    <!-- Sidebar -->
    <Sidebar :is-collapsed="isSidebarCollapsed" />

    <!-- Main Wrapper -->
    <div class="flex-1 flex flex-col min-w-0 transition-all duration-300">
      <Header 
        :is-sidebar-collapsed="isSidebarCollapsed" 
        @toggle-sidebar="toggleSidebar" 
      />
      
      <!-- Content Area -->
      <main class="p-8">
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
