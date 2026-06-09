<script setup>
import { ref, onMounted } from 'vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import SideModal from '../../components/layout/SideModal.vue'
import { AlertTriangle, CheckCircle2, Clock, User, Calendar, Loader2, Smartphone, FileText, Image as ImageIcon, XCircle } from 'lucide-vue-next'
import { usePanneStore } from '../../stores/panne'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

const panneStore = usePanneStore()
const toast = useToast()
const confirm = useConfirm()

const showDetailModal = ref(false)
const selectedPanne = ref(null)

onMounted(() => {
  panneStore.fetchPannes()
})

const openDetail = (panne) => {
  selectedPanne.value = panne
  showDetailModal.value = true
}

const getGraviteClass = (gravite) => {
  switch (gravite) {
    case 'faible': return 'bg-blue-50 text-blue-600'
    case 'moyenne': return 'bg-amber-50 text-amber-600'
    case 'critique': return 'bg-red-50 text-red-600'
    default: return 'bg-slate-50 text-slate-600'
  }
}

const getStatutLabel = (statut) => {
  const labels = {
    declaree: 'Déclarée',
    en_cours: 'En cours',
    en_maintenance: 'En maintenance',
    resolue: 'Résolue',
    irrecuperable: 'Irrécupérable'
  }
  return labels[statut] || statut
}

const handleValider = (id) => {
  confirm.require({
    message: 'Voulez-vous valider cette panne et la passer en cours de traitement ?',
    header: 'Validation de panne',
    icon: 'pi pi-check-circle',
    acceptClass: 'p-button-success',
    accept: async () => {
      try {
        await panneStore.validerPanne(id)
        toast.add({ severity: 'success', summary: 'Succès', detail: 'Panne validée', life: 3000 })
        showDetailModal.value = false
      } catch (error) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de la validation', life: 3000 })
      }
    }
  })
}

const handleRejeter = (id) => {
  confirm.require({
    message: 'Voulez-vous rejeter ce signalement de panne ?',
    header: 'Rejet de panne',
    icon: 'pi pi-times-circle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await panneStore.rejeterPanne(id, 'Signalement non fondé ou erreur de saisie')
        toast.add({ severity: 'success', summary: 'Succès', detail: 'Panne rejetée', life: 3000 })
        showDetailModal.value = false
      } catch (error) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec du rejet', life: 3000 })
      }
    }
  })
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <PageHeader title="Gestion des pannes" subtitle="Validez et suivez les pannes signalées par les agents" />

    <div v-if="panneStore.loading && panneStore.pannes.length === 0" class="space-y-4">
      <div v-for="i in 3" :key="i" class="h-32 bg-slate-100 animate-pulse rounded-2xl"></div>
    </div>

    <div v-else-if="panneStore.pannes.length === 0" class="bg-white p-16 rounded-3xl border border-slate-200 shadow-sm text-center">
      <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-slate-400">
        <AlertTriangle class="w-8 h-8" />
      </div>
      <h3 class="text-lg font-bold text-slate-900 mb-2">Aucune panne signalée</h3>
      <p class="text-slate-500 text-sm">Toutes les pannes ont été traitées ou aucune n'a été déclarée.</p>
    </div>

    <div v-else class="grid grid-cols-1 gap-6">
      <div 
        v-for="panne in panneStore.pannes" 
        :key="panne.id" 
        @click="openDetail(panne)"
        class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all border-l-4 cursor-pointer group" 
        :class="panne.statut === 'declaree' ? 'border-l-amber-400' : 'border-l-emerald-500'"
      >
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="flex items-start gap-5">
            <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center flex-shrink-0', getGraviteClass(panne.gravite)]">
              <AlertTriangle class="w-7 h-7" />
            </div>
            <div class="space-y-1">
              <div class="flex items-center gap-3">
                <h4 class="font-bold text-slate-900">{{ panne.equipement?.marque }} {{ panne.equipement?.modele }}</h4>
                <span :class="['px-2 py-0.5 rounded text-[10px] font-black uppercase', getGraviteClass(panne.gravite)]">
                  {{ panne.gravite }}
                </span>
              </div>
              <p class="text-sm text-slate-600 line-clamp-2">{{ panne.description }}</p>
              
              <div class="flex flex-wrap items-center gap-4 mt-3">
                <div class="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                  <User class="w-3.5 h-3.5" />
                  Déclaré par {{ panne.declare_par?.name || 'Agent' }}
                </div>
                <div class="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                  <Calendar class="w-3.5 h-3.5" />
                  {{ formatDate(panne.date_declaration) }}
                </div>
                <div class="flex items-center gap-1.5 text-xs font-bold" :class="panne.statut === 'declaree' ? 'text-amber-600' : 'text-emerald-600'">
                  <Clock class="w-3.5 h-3.5" />
                  {{ getStatutLabel(panne.statut) }}
                </div>
              </div>
            </div>
          </div>

          <div class="flex flex-col items-end gap-3">
            <div v-if="panne.statut === 'declaree'" class="flex items-center gap-2">
              <button 
                @click.stop="handleRejeter(panne.id)"
                class="p-2.5 text-rose-600 hover:bg-rose-50 rounded-xl transition-all"
                title="Rejeter"
              >
                <XCircle class="w-6 h-6" />
              </button>
              <button 
                @click.stop="handleValider(panne.id)"
                class="px-6 py-2.5 bg-primary-600 text-white rounded-xl text-sm font-black hover:bg-primary-700 transition-all shadow-lg shadow-primary-200 flex items-center gap-2"
              >
                <CheckCircle2 class="w-4 h-4" />
                Valider
              </button>
            </div>
            
            <div v-if="panne.valide_par" class="text-right">
              <p class="text-[10px] font-black text-emerald-600 uppercase tracking-wider flex items-center justify-end gap-1">
                <CheckCircle2 class="w-3 h-3" />
                Validé par {{ panne.valide_par_name || panne.valide_par?.name || 'Admin' }}
              </p>
              <p class="text-[9px] text-slate-400 font-bold uppercase mt-0.5">le {{ formatDate(panne.date_validation) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Détail Panne -->
    <SideModal :show="showDetailModal" title="Détail du signalement" @close="showDetailModal = false">
      <div v-if="selectedPanne" class="space-y-8">
        <!-- Équipement -->
        <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 flex items-center gap-5">
          <div class="w-16 h-16 rounded-2xl bg-white shadow-sm flex items-center justify-center text-slate-400">
            <Smartphone class="w-8 h-8" />
          </div>
          <div>
            <h3 class="text-xl font-black text-slate-900">{{ selectedPanne.equipement?.marque }} {{ selectedPanne.equipement?.modele }}</h3>
            <p class="text-sm font-mono font-bold text-slate-400 uppercase tracking-widest">{{ selectedPanne.equipement?.reference }}</p>
          </div>
        </div>

        <!-- Infos Déclaration -->
        <div class="grid grid-cols-2 gap-4">
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Déclaré par</p>
            <div class="flex items-center gap-2">
              <User class="w-4 h-4 text-primary-500" />
              <span class="text-sm font-bold text-slate-700">{{ selectedPanne.declare_par?.name }}</span>
            </div>
          </div>
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Date</p>
            <div class="flex items-center gap-2">
              <Calendar class="w-4 h-4 text-primary-500" />
              <span class="text-sm font-bold text-slate-700">{{ formatDate(selectedPanne.date_declaration) }}</span>
            </div>
          </div>
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Gravité</p>
            <span :class="['px-3 py-1 rounded-lg text-[10px] font-black uppercase inline-block', getGraviteClass(selectedPanne.gravite)]">
              {{ selectedPanne.gravite }}
            </span>
          </div>
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Statut actuel</p>
            <span :class="['text-xs font-bold', selectedPanne.statut === 'declaree' ? 'text-amber-600' : 'text-emerald-600']">
              {{ getStatutLabel(selectedPanne.statut) }}
            </span>
          </div>
        </div>

        <!-- Description -->
        <div class="space-y-3">
          <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
            <FileText class="w-4 h-4 text-primary-500" />
            Description du problème
          </h4>
          <div class="p-5 bg-slate-50 rounded-2xl border border-slate-100">
            <p class="text-sm text-slate-600 font-medium leading-relaxed whitespace-pre-wrap">{{ selectedPanne.description }}</p>
          </div>
        </div>

        <!-- Photos -->
        <div v-if="selectedPanne.photos?.length" class="space-y-3">
          <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
            <ImageIcon class="w-4 h-4 text-primary-500" />
            Photos jointes
          </h4>
          <div class="grid grid-cols-2 gap-3">
            <div v-for="(photo, idx) in selectedPanne.photos" :key="idx" class="aspect-video rounded-2xl overflow-hidden border border-slate-200 shadow-sm">
              <img :src="`http://localhost:8000/storage/${photo}`" class="w-full h-full object-cover hover:scale-110 transition-transform duration-500">
            </div>
          </div>
        </div>

        <!-- Actions de validation (si non validé) -->
        <div v-if="selectedPanne.statut === 'declaree'" class="flex gap-4 pt-4 border-t border-slate-100">
          <button 
            @click="handleRejeter(selectedPanne.id)"
            class="flex-1 h-12 bg-rose-50 text-rose-600 rounded-2xl text-sm font-black hover:bg-rose-100 transition-all flex items-center justify-center gap-2"
          >
            <XCircle class="w-5 h-5" />
            Rejeter
          </button>
          <button 
            @click="handleValider(selectedPanne.id)"
            class="flex-1 h-12 bg-primary-600 text-white rounded-2xl text-sm font-black shadow-lg shadow-primary-200 hover:bg-primary-700 transition-all flex items-center justify-center gap-2"
          >
            <CheckCircle2 class="w-5 h-5" />
            Valider la panne
          </button>
        </div>

        <!-- Traçabilité (si validé) -->
        <div v-else-if="selectedPanne.valide_par" class="p-6 bg-emerald-50 rounded-3xl border border-emerald-100 flex flex-col items-center text-center gap-2">
          <CheckCircle2 class="w-10 h-10 text-emerald-500" />
          <p class="text-sm font-bold text-emerald-900">Ce signalement a été validé</p>
          <p class="text-xs text-emerald-600 font-medium">Par {{ selectedPanne.valide_par?.name }} le {{ formatDate(selectedPanne.date_validation) }}</p>
        </div>
      </div>
    </SideModal>
  </div>
</template>
