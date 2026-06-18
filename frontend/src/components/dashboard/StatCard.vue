<script setup>
import { MoreHorizontal } from '@lucide/vue';

defineProps({
  label: String,
  value: [String, Number],
  icon: Object,
  colorClass: {
    type: String,
    default: 'bg-primary-50 text-primary-600'
  },
  trend: String,
  trendUp: Boolean
});
</script>

<template>
  <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all duration-300 group relative overflow-hidden">
    <div class="flex items-start justify-between mb-4">
      <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center transition-all group-hover:scale-105 duration-500 shadow-sm', colorClass]">
        <component :is="icon" class="w-7 h-7" />
      </div>
      <div v-if="trend" :class="['text-xs font-bold px-2 py-1 rounded-lg', trendUp ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50']">
        {{ trend }}
      </div>
    </div>

    <div class="relative z-10">
      <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1">{{ label }}</p>
      <h3 class="text-3xl font-black text-slate-900">{{ value }}</h3>
    </div>

    <!-- Background Decoration -->
    <div class="absolute -right-6 -bottom-6 w-32 h-32 opacity-[0.02] group-hover:opacity-[0.05] group-hover:scale-110 transition-all duration-700 pointer-events-none">
      <component :is="icon" class="w-full h-full" />
    </div>
  </div>
</template>
