<script setup>
import { onMounted } from 'vue';
import { useSinistreStore } from '../../stores/sinistre';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import PageHeader from '../../components/layout/PageHeader.vue';
import { AlertTriangle, CheckCircle2, XCircle, Clock, User, Calendar, FileText } from 'lucide-vue-next';

const sinistreStore = useSinistreStore();
const toast = useToast();
const confirm = useConfirm();

onMounted(() => {
  sinistreStore.fetchSinistres();
});

const getStatusStyle = (status) => {
  switch (status) {
    case 'en_attente_validation':
      return 'bg-orange-50 text-orange-600 border-orange-100';
    case 'validee':
      return 'bg-emerald-50 text-emerald-600 border-emerald-100';
    case 'rejetee':
      return 'bg-rose-50 text-rose-600 border-rose-100';
    case 'cloturee':
      return 'bg-slate-50 text-slate-500 border-slate-100';
    default:
      return 'bg-slate-50 text-slate-500 border-slate-100';
  }
};

const getBorderColor = (status) => {
  switch (status) {
    case 'en_attente_validation': return 'border-l-orange-400';
    case 'validee': return 'border-l-emerald-500';
    case 'rejetee': return 'border-l-rose-500';
    case 'cloturee': return 'border-l-slate-400';
    default: return 'border-l-slate-200';
  }
};

const getStatusLabel = (status) => {
  const labels = {
    en_attente_validation: 'En attente',
    validee: 'Validée',
    rejetee: 'Rejetée',
    cloturee: 'Clôturée'
  };
  return labels[status] || status;
};

const handleValider = (id) => {
  confirm.require({
    message: 'Voulez-vous valider cette déclaration de sinistre ?',
    header: 'Confirmation de validation',
    icon: 'pi pi-check-circle',
    acceptClass: 'p-button-success',
    accept: async () => {
      try {
        await sinistreStore.validerSinistre(id);
        toast.add({ severity: 'success', summary: 'Succès', detail: 'Sinistre validé avec succès', life: 3000 });
      } catch (error) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de la validation', life: 3000 });
      }
    }
  });
};

const handleRejeter = (id) => {
  confirm.require({
    message: 'Voulez-vous rejeter cette déclaration de sinistre ?',
    header: 'Confirmation de rejet',
    icon: 'pi pi-times-circle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await sinistreStore.rejeterSinistre(id, 'Motif non spécifié');
        toast.add({ severity: 'success', summary: 'Succès', detail: 'Sinistre rejeté', life: 3000 });
      } catch (error) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec du rejet', life: 3000 });
      }
    }
  });
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });
};
</script>

<template>
  <div class="p-4 sm:p-8 space-y-8 animate-in fade-in duration-500">
    <PageHeader title="Pertes & Casses" subtitle="Gestion des déclarations de sinistres" />

    <!-- Loading State -->
    <div v-if="sinistreStore.loading && sinistreStore.sinistres.length === 0" class="grid grid-cols-1 xl:grid-cols-2 gap-8">
      <div v-for="i in 4" :key="i" class="h-64 bg-slate-100 animate-pulse rounded-[2rem]"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="sinistreStore.sinistres.length === 0" class="bg-white p-16 rounded-[2rem] border border-slate-200 shadow-sm text-center">
      <AlertTriangle class="w-16 h-16 text-slate-200 mx-auto mb-4" />
      <h3 class="text-xl font-black text-slate-900 mb-2">Aucun sinistre déclaré</h3>
      <p class="text-slate-500 font-medium">Les déclarations de pertes ou casses apparaîtront ici.</p>
    </div>

    <!-- Grid -->
    <div v-else class="grid grid-cols-1 xl:grid-cols-2 gap-8">
      <div 
        v-for="item in sinistreStore.sinistres" 
        :key="item.id"
        class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-100 transition-all hover:shadow-md relative flex flex-col gap-6 border-l-[6px]"
        :class="[getBorderColor(item.status)]"
      >
        <!-- Header: Tags & Actions -->
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-orange-50 text-orange-600 border border-orange-100">
              {{ item.type }}
            </span>
            <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider border" :class="getStatusStyle(item.status)">
              {{ getStatusLabel(item.status) }}
            </span>
          </div>
          
          <div v-if="item.status === 'en_attente_validation'" class="flex items-center gap-2">
            <button 
              @click="handleRejeter(item.id)"
              class="p-2 text-rose-600 hover:bg-rose-50 rounded-xl transition-all"
              title="Rejeter"
            >
              <XCircle class="w-6 h-6" />
            </button>
            <button 
              @click="handleValider(item.id)"
              class="px-6 py-2 bg-primary-600 text-white rounded-2xl text-sm font-black hover:bg-primary-700 transition-all shadow-lg shadow-primary-200"
            >
              Valider
            </button>
          </div>
        </div>

        <!-- Body: Ref & Agent -->
        <div class="space-y-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400">
              <AlertTriangle class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-xl font-black text-slate-900">{{ item.equipement?.reference || item.ref }}</h3>
              <p class="text-sm text-slate-500 font-bold tracking-tight">{{ item.equipement?.marque }} {{ item.equipement?.modele }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="flex items-center gap-2 text-slate-600">
              <User class="w-4 h-4 text-slate-400" />
              <span class="text-sm font-bold">{{ item.agent?.nom || item.agent }}</span>
            </div>
            <div class="flex items-center gap-2 text-slate-600">
              <Calendar class="w-4 h-4 text-slate-400" />
              <span class="text-sm font-bold">{{ formatDate(item.created_at || item.date) }}</span>
            </div>
          </div>
        </div>

        <!-- Description -->
        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
          <div class="flex items-start gap-3">
            <FileText class="w-4 h-4 text-slate-400 mt-0.5" />
            <p class="text-sm text-slate-600 font-medium leading-relaxed">{{ item.description }}</p>
          </div>
        </div>

        <!-- Validation Info -->
        <div v-if="item.status === 'validee' && item.validated_by" class="flex items-center gap-2 pt-4 border-t border-slate-50 text-[11px] font-bold text-emerald-600 uppercase tracking-wider">
          <CheckCircle2 class="w-4 h-4" />
          Validé par {{ item.validated_by_name || item.validated_by?.name || 'Admin' }} le {{ formatDate(item.validated_at) }}
        </div>
        <div v-else-if="item.status === 'rejetee' && item.validated_by" class="flex items-center gap-2 pt-4 border-t border-slate-50 text-[11px] font-bold text-rose-600 uppercase tracking-wider">
          <XCircle class="w-4 h-4" />
          Rejeté par {{ item.validated_by_name || item.validated_by?.name || 'Admin' }} le {{ formatDate(item.validated_at) }}
        </div>
        <div v-else-if="item.status === 'en_attente_validation'" class="flex items-center gap-2 pt-4 border-t border-slate-50 text-[11px] font-bold text-orange-500 uppercase tracking-wider">
          <Clock class="w-4 h-4" />
          En attente de traitement
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
