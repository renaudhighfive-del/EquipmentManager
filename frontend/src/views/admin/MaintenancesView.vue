<script setup>
import PageHeader from '../../components/layout/PageHeader.vue';
import { 
  Wrench, 
  Plus, 
  Calendar, 
  User, 
  DollarSign, 
  CheckCircle2, 
  Clock,
  History
} from 'lucide-vue-next';

const maintenances = [
  { id: 1, type: 'Préventive', ref: 'REF-80000', equip: 'Zebra Zebra Pro 0', tech: 'Marc Dupont', cout: '64 €', diagnostic: 'Diagnostic complet effectué', actions: 'Remplacement batterie + mise à jour firmware', statut: 'Planifiée', statutClass: 'bg-blue-50 text-blue-600' },
  { id: 2, type: 'Corrective', ref: 'REF-80001', equip: 'Samsung Samsung X 1', tech: 'Sarah Ben', cout: '215 €', diagnostic: 'Diagnostic complet effectué', actions: 'Remplacement batterie + mise à jour firmware', statut: 'En cours', statutClass: 'bg-amber-50 text-amber-600' },
  { id: 3, type: 'Préventive', ref: 'REF-80002', equip: 'Apple Apple Max 2', tech: 'Ali K.', cout: '457 €', diagnostic: 'Diagnostic complet effectué', actions: 'Remplacement batterie + mise à jour firmware', statut: 'Terminée', statutClass: 'bg-emerald-50 text-emerald-600' },
  { id: 4, type: 'Corrective', ref: 'REF-80003', equip: 'Dell Dell Lite 3', tech: 'Marc Dupont', cout: '278 €', diagnostic: 'Diagnostic complet effectué', actions: 'Remplacement batterie + mise à jour firmware', statut: 'Planifiée', statutClass: 'bg-blue-50 text-blue-600' },
];
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <PageHeader 
      title="Maintenances" 
      subtitle="Historique et planification des entretiens"
    >
      <template #actions>
        <button class="flex items-center gap-2 px-5 py-2.5 bg-primary-600 rounded-xl text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-lg shadow-primary-200">
          <Plus class="w-4 h-4" />
          Nouvelle maintenance
        </button>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <div 
        v-for="maint in maintenances" 
        :key="maint.id"
        class="bg-white rounded-3xl border border-slate-200 shadow-sm p-8 hover:shadow-md transition-all relative overflow-hidden"
      >
        <!-- Type Badge -->
        <div class="absolute top-0 right-0">
          <div :class="['px-6 py-2 rounded-bl-3xl text-[10px] font-bold uppercase tracking-widest', maint.type === 'Préventive' ? 'bg-indigo-50 text-indigo-600' : 'bg-orange-50 text-orange-600']">
            {{ maint.type }}
          </div>
        </div>

        <div class="flex items-start justify-between mb-8">
          <div>
            <h3 class="text-lg font-bold text-slate-900">{{ maint.ref }}</h3>
            <p class="text-sm text-slate-500 font-medium">{{ maint.equip }}</p>
          </div>
          <span :class="['px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider mt-1', maint.statutClass]">
            {{ maint.statut }}
          </span>
        </div>

        <div class="grid grid-cols-2 gap-8 mb-8">
          <div class="space-y-1">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
              <User class="w-3 h-3" /> Technicien
            </p>
            <p class="text-sm font-bold text-slate-700">{{ maint.tech }}</p>
          </div>
          <div class="space-y-1">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
              <DollarSign class="w-3 h-3" /> Coût
            </p>
            <p class="text-sm font-bold text-slate-700">{{ maint.cout }}</p>
          </div>
        </div>

        <div class="space-y-4">
          <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Diagnostic</p>
            <p class="text-sm text-slate-600 font-medium">{{ maint.diagnostic }}</p>
          </div>
          <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Actions</p>
            <p class="text-sm text-slate-600 font-medium">{{ maint.actions }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
