<script setup>
import { X } from 'lucide-vue-next';

defineProps({
  show: Boolean,
  title: String,
  mode: {
    type: String,
    default: 'side' // 'side' or 'center'
  }
});

defineEmits(['close']);
</script>

<template>
  <Transition name="fade">
    <div v-if="show" class="fixed inset-0 z-[999] flex items-center justify-center p-4 md:p-6">
      <!-- Backdrop with light blur like capture -->
      <div 
        class="absolute inset-0 bg-slate-900/20 backdrop-blur-sm transition-opacity"
        @click="$emit('close')"
      ></div>

      <!-- Center Modal -->
      <div 
        v-if="mode === 'center'"
        class="relative w-full max-w-2xl bg-white border border-slate-200 shadow-2xl rounded-[2rem] flex flex-col animate-in zoom-in-95 fade-in duration-300 overflow-hidden"
      >
        <!-- Header -->
        <div class="p-6 border-b border-slate-200 flex items-center justify-between bg-white">
          <h2 class="text-xl font-bold text-slate-900">{{ title }}</h2>
          <button 
            @click="$emit('close')"
            class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors"
          >
            <X class="w-6 h-6" />
          </button>
        </div>
        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
          <slot></slot>
        </div>
      </div>

      <!-- Side Panel -->
      <div 
        v-else
        class="absolute right-0 top-0 h-full w-full max-w-lg bg-white border-l border-slate-200 shadow-2xl flex flex-col animate-in slide-in-from-right duration-500"
      >
        <!-- Header -->
        <div class="p-6 border-b border-slate-200 flex items-center justify-between bg-white">
          <h2 class="text-xl font-bold text-slate-900">{{ title }}</h2>
          <button 
            @click="$emit('close')"
            class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors"
          >
            <X class="w-6 h-6" />
          </button>
        </div>
        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
          <slot></slot>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
</style>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s ease-out;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  opacity: 0;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}
</style>
