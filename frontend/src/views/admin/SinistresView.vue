<script setup>
import { ref } from 'vue';

const sinistres = ref([
  {
    id: 1,
    ref: 'REF-80000',
    agent: 'Amélie Dubois',
    type: 'Perte',
    status: 'En attente validation',
    date: '28 avr. 2026',
    description: 'Déclaration via application mobile',
    typeStyle: 'bg-orange-50 text-orange-600 border-orange-100',
    statusStyle: 'bg-slate-50 text-slate-500 border-slate-100',
    borderColor: 'border-l-orange-400'
  },
  {
    id: 2,
    ref: 'REF-80001',
    agent: 'Karim Benali',
    type: 'Casse',
    status: 'Validée',
    date: '10 mars 2026',
    description: 'Déclaration via application mobile',
    typeStyle: 'bg-orange-50 text-orange-600 border-orange-100',
    statusStyle: 'bg-emerald-50 text-emerald-600 border-emerald-100',
    borderColor: 'border-l-emerald-500'
  },
  {
    id: 3,
    ref: 'REF-80002',
    agent: 'Sophie Martin',
    type: 'Perte',
    status: 'Clôturée',
    date: '29 avr. 2026',
    description: 'Déclaration via application mobile',
    typeStyle: 'bg-orange-50 text-orange-600 border-orange-100',
    statusStyle: 'bg-slate-50 text-slate-500 border-slate-100',
    borderColor: 'border-l-rose-500'
  },
  {
    id: 4,
    ref: 'REF-80003',
    agent: 'Youssef El Amrani',
    type: 'Casse',
    status: 'Rejetée',
    date: '28 mars 2026',
    description: 'Déclaration via application mobile',
    typeStyle: 'bg-orange-50 text-orange-600 border-orange-100',
    statusStyle: 'bg-slate-50 text-slate-500 border-slate-100',
    borderColor: 'border-l-rose-500'
  },
  {
    id: 5,
    ref: 'REF-80004',
    agent: 'Claire Moreau',
    type: 'Perte',
    status: 'En attente validation',
    date: '18 mai 2026',
    description: 'Déclaration via application mobile',
    typeStyle: 'bg-orange-50 text-orange-600 border-orange-100',
    statusStyle: 'bg-slate-50 text-slate-500 border-slate-100',
    borderColor: 'border-l-orange-400'
  },
  {
    id: 6,
    ref: 'REF-80005',
    agent: 'Thomas Lefevre',
    type: 'Casse',
    status: 'Validée',
    date: '08 mars 2026',
    description: 'Déclaration via application mobile',
    typeStyle: 'bg-orange-50 text-orange-600 border-orange-100',
    statusStyle: 'bg-emerald-50 text-emerald-600 border-emerald-100',
    borderColor: 'border-l-emerald-500'
  }
]);
</script>

<template>
  <div class="p-4 sm:p-8 space-y-8 animate-in fade-in duration-500">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-3xl font-black text-slate-900 tracking-tight">Pertes & Casses</h1>
    </div>

    <!-- Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
      <div 
        v-for="item in sinistres" 
        :key="item.id"
        class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-100 transition-all hover:shadow-md relative flex flex-col gap-6 border-l-[6px]"
        :class="[item.borderColor]"
      >
        <!-- Header: Tags & Button -->
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="px-4 py-1.5 rounded-full text-[11px] font-black uppercase tracking-wider" :class="item.typeStyle">
              {{ item.type }}
            </span>
            <span class="px-4 py-1.5 rounded-full text-[11px] font-black uppercase tracking-wider" :class="item.statusStyle">
              {{ item.status }}
            </span>
          </div>
          <button class="px-6 py-2 border border-slate-200 rounded-2xl text-sm font-black text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
            Valider
          </button>
        </div>

        <!-- Body: Ref, Name & Date -->
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <h3 class="text-xl font-black text-slate-900">{{ item.ref }}</h3>
            <span class="text-slate-900 font-black">•</span>
            <h3 class="text-xl font-black text-slate-900">{{ item.agent }}</h3>
          </div>
          <p class="text-sm text-slate-400 font-bold tracking-tight">{{ item.date }}</p>
        </div>

        <!-- Footer: Description -->
        <div class="text-slate-500">
          <p class="text-sm font-bold tracking-tight">{{ item.description }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Ajout d'une légère ombre portée douce pour correspondre à l'image */
.rounded-3xl {
  border-radius: 1.5rem;
}
</style>
