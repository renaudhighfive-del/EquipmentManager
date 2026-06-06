<script setup>
import PageHeader from '../../components/layout/PageHeader.vue';
import { 
  ArrowLeftRight, 
  RotateCcw, 
  AlertTriangle, 
  Wrench, 
  AlertOctagon, 
  History,
  Trash2
} from 'lucide-vue-next';

const mouvements = [
  { id: 1, type: 'Affectation', ref: 'REF-80000', detail: 'Amélie Dubois', date: '31 mai 2026', time: '00:18', icon: ArrowLeftRight, color: 'text-blue-500', bg: 'bg-blue-50' },
  { id: 2, type: 'Retour', ref: 'REF-80001', detail: 'Karim Benali', date: '30 mai 2026', time: '20:49', icon: RotateCcw, color: 'text-slate-500', bg: 'bg-slate-50' },
  { id: 3, type: 'Déclaration panne', ref: 'REF-80002', detail: 'Sophie Martin', date: '27 mai 2026', time: '23:02', icon: AlertTriangle, color: 'text-red-500', bg: 'bg-red-50' },
  { id: 4, type: 'Maintenance', ref: 'REF-80003', detail: 'Youssef El Amrani', date: '25 mai 2026', time: '17:25', icon: Wrench, color: 'text-amber-500', bg: 'bg-amber-50' },
  { id: 5, type: 'Sinistre', ref: 'REF-80004', detail: 'Claire Moreau', date: '25 mai 2026', time: '01:08', icon: AlertOctagon, color: 'text-rose-500', bg: 'bg-rose-50' },
  { id: 6, type: 'Perte', ref: 'REF-80005', detail: 'Thomas Lefevre', date: '20 mai 2026', time: '23:39', icon: Trash2, color: 'text-indigo-500', bg: 'bg-indigo-50' },
  { id: 7, type: 'Réforme', ref: 'REF-80006', detail: 'Nadia Bensalem', date: '21 mai 2026', time: '15:46', icon: History, color: 'text-slate-400', bg: 'bg-slate-100' },
];
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <PageHeader 
      title="Journal des mouvements" 
      subtitle="Historique immuable de toutes les opérations"
    />

    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-8">
      <div class="space-y-8 relative before:absolute before:left-[23px] before:top-4 before:bottom-4 before:w-[2px] before:bg-slate-50">
        <div v-for="mvt in mouvements" :key="mvt.id" class="relative pl-16 group">
          <!-- Icon -->
          <div 
            class="absolute left-0 top-0 w-12 h-12 rounded-2xl flex items-center justify-center transition-all group-hover:scale-110 z-10 border-4 border-white shadow-sm"
            :class="[mvt.bg, mvt.color]"
          >
            <component :is="mvt.icon" class="w-5 h-5" />
          </div>

          <!-- Content -->
          <div class="flex items-center justify-between gap-4 border-b border-slate-50 pb-6 group-last:border-0 group-last:pb-0">
            <div>
              <h4 class="text-base font-bold text-slate-900 flex items-center gap-2">
                {{ mvt.type }} <span class="text-slate-300 font-normal">•</span> <span class="font-mono text-sm text-slate-500">{{ mvt.ref }}</span>
              </h4>
              <p class="text-sm text-slate-500 font-medium mt-1">{{ mvt.detail }} • {{ mvt.date }}</p>
              <div class="mt-2 inline-flex px-2 py-0.5 bg-slate-100 text-[10px] font-bold text-slate-500 rounded-md uppercase tracking-wider">
                En stock — Affecté
              </div>
            </div>
            <div class="text-right">
              <span class="text-sm font-mono font-medium text-slate-400">{{ mvt.time }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
