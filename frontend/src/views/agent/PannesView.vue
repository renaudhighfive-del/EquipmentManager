<script setup>
import { ref } from 'vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import SideModal from '../../components/layout/SideModal.vue'
import { AlertTriangle, Plus } from 'lucide-vue-next'

const showModal = ref(false)

const pannes = ref([
  // Vide pour l'instant — sera alimenté par l'API
])
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <PageHeader title="Déclarer une panne" subtitle="Signalez un problème sur l'un de vos équipements">
      <template #actions>
        <button 
          @click="showModal = true"
          class="flex items-center gap-2 px-5 py-2.5 bg-rose-600 rounded-xl text-sm font-bold text-white hover:bg-rose-700 transition-all shadow-lg shadow-rose-200"
        >
          <Plus class="w-4 h-4" />
          Nouvelle déclaration
        </button>
      </template>
    </PageHeader>

    <!-- Liste vide -->
    <div v-if="pannes.length === 0" class="bg-white p-16 rounded-3xl border border-slate-200 shadow-sm text-center">
      <div class="w-16 h-16 bg-rose-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <AlertTriangle class="w-8 h-8 text-rose-400" />
      </div>
      <h3 class="text-lg font-bold text-slate-900 mb-2">Aucune panne déclarée</h3>
      <p class="text-slate-500 text-sm mb-6">Tout fonctionne correctement. En cas de problème, signalez-le ici.</p>
      <button 
        @click="showModal = true"
        class="px-6 py-3 bg-rose-600 text-white font-bold rounded-xl hover:bg-rose-700 transition-all text-sm"
      >
        Déclarer une panne
      </button>
    </div>

    <!-- Modal déclaration -->
    <SideModal :show="showModal" title="Déclarer une panne" mode="center" @close="showModal = false">
      <div class="space-y-5">
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Équipement concerné</label>
          <select class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-rose-500/20">
            <option>Zebra Zebra Pro 01 — REF-80001</option>
            <option>Zebra Zebra Pro 02 — REF-80002</option>
          </select>
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Gravité</label>
          <select class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-rose-500/20">
            <option value="faible">Faible</option>
            <option value="moyenne">Moyenne</option>
            <option value="critique">Critique</option>
          </select>
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Description</label>
          <textarea 
            rows="4"
            placeholder="Décrivez le problème rencontré..."
            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-rose-500/20 resize-none"
          ></textarea>
        </div>
        <button class="w-full py-3 bg-rose-600 text-white font-bold rounded-xl hover:bg-rose-700 transition-all text-sm">
          Soumettre la déclaration
        </button>
      </div>
    </SideModal>
  </div>
</template>
