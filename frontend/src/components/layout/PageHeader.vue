<script setup>
import { ChevronRight, Home } from '@lucide/vue';

defineProps({
  title: String,
  subtitle: String,
  breadcrumb: {
    type: Array,
    default: () => []
  }
});
</script>

<template>
  <div class="mb-8 space-y-4">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs font-medium text-slate-400">
      <router-link to="/admin/dashboard" class="hover:text-primary-600 transition-colors flex items-center gap-1">
        <Home class="w-3 h-3" />
        Tableau de bord
      </router-link>
      <template v-for="item in breadcrumb" :key="item.label">
        <ChevronRight class="w-3 h-3" />
        <router-link 
          v-if="item.path" 
          :to="item.path" 
          class="hover:text-primary-600 transition-colors"
        >
          {{ item.label }}
        </router-link>
        <span v-else class="text-slate-600">{{ item.label }}</span>
      </template>
    </nav>

    <!-- Title Area -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">{{ title }}</h1>
        <p v-if="subtitle" class="text-sm sm:text-base text-slate-500 font-medium mt-1">{{ subtitle }}</p>
      </div>
      <div class="flex flex-wrap items-center gap-2 sm:gap-3">
        <slot name="actions"></slot>
      </div>
    </div>
  </div>
</template>
