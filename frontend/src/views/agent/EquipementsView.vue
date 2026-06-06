<script setup>
import PageHeader from '../../components/layout/PageHeader.vue'
import { Smartphone, RotateCcw } from 'lucide-vue-next'

const equipements = [
  { id: 1, name: 'Zebra Zebra Pro 01', ref: 'REF-80001', etat: 'En service', etatClass: 'bg-emerald-50 text-emerald-600' },
  { id: 2, name: 'Zebra Zebra Pro 02', ref: 'REF-80002', etat: 'En service', etatClass: 'bg-emerald-50 text-emerald-600' },
]
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <PageHeader title="Mes équipements" subtitle="Équipements actuellement affectés à votre compte" />

    <div class="space-y-4">
      <div 
        v-for="equip in equipements" :key="equip.id"
        class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between hover:shadow-md transition-all"
      >
        <div class="flex items-center gap-5">
          <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400">
            <Smartphone class="w-7 h-7" />
          </div>
          <div>
            <p class="font-bold text-slate-900">{{ equip.name }}</p>
            <p class="text-xs text-slate-500 font-medium mt-0.5">{{ equip.ref }}</p>
          </div>
        </div>
        <div class="flex items-center gap-4">
          <span :class="['px-3 py-1 rounded-lg text-[10px] font-black uppercase', equip.etatClass]">
            {{ equip.etat }}
          </span>
          <button class="p-2.5 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded-xl transition-all">
            <RotateCcw class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
