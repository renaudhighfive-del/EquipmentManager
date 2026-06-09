<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import PageHeader from '../../components/layout/PageHeader.vue';
import SideModal from '../../components/layout/SideModal.vue';
import { useMaintenanceStore } from '../../stores/maintenance';
import { usePanneStore } from '../../stores/panne';
import { useToast } from 'primevue/usetoast';
import { 
  Wrench, 
  Plus, 
  Calendar, 
  User, 
  DollarSign, 
  Loader2,
  Smartphone,
  FileText
} from 'lucide-vue-next';

const maintenanceStore = useMaintenanceStore();
const panneStore = usePanneStore();
const toast = useToast();

const showModal = ref(false);
const isSubmitting = ref(false);

const form = reactive({
  panne_id: '',
  type: 'corrective',
  technicien: '',
  date_debut: new Date().toISOString().substr(0, 10),
  cout: 0
});

onMounted(() => {
  maintenanceStore.fetchMaintenances();
  panneStore.fetchPannes();
});

// Récupérer uniquement les pannes validées (statut 'en_cours') qui ne sont pas déjà en maintenance
const pannesValidées = computed(() => {
  return panneStore.pannes.filter(p => p.statut === 'en_cours' || p.statut === 'declaree');
});

const resetForm = () => {
  Object.assign(form, {
    panne_id: '',
    type: 'corrective',
    technicien: '',
    date_debut: new Date().toISOString().substr(0, 10),
    cout: 0
  });
};

const handleSubmit = async () => {
  if (!form.panne_id || !form.technicien || !form.date_debut) {
    toast.add({ severity: 'warn', summary: 'Attention', detail: 'Veuillez remplir les champs obligatoires', life: 3000 });
    return;
  }

  isSubmitting.value = true;
  try {
    await maintenanceStore.createMaintenance({ ...form });
    toast.add({ severity: 'success', summary: 'Succès', detail: 'Maintenance planifiée avec succès', life: 3000 });
    showModal.value = false;
    resetForm();
    // Rafraîchir les pannes pour mettre à jour la liste des pannes validées dispo
    panneStore.fetchPannes();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de la création', life: 3000 });
  } finally {
    isSubmitting.value = false;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });
};

const getStatutClass = (statut) => {
  switch (statut) {
    case 'en_maintenance': return 'bg-amber-50 text-amber-600';
    case 'terminee': return 'bg-emerald-50 text-emerald-600';
    default: return 'bg-blue-50 text-blue-600';
  }
};
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <PageHeader 
      title="Maintenances" 
      subtitle="Historique et planification des entretiens"
    >
      <template #actions>
        <button 
          @click="showModal = true"
          class="flex items-center gap-2 px-5 py-2.5 bg-primary-600 rounded-xl text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-lg shadow-primary-200"
        >
          <Plus class="w-4 h-4" />
          Nouvelle maintenance
        </button>
      </template>
    </PageHeader>

    <div v-if="maintenanceStore.loading && maintenanceStore.maintenances.length === 0" class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <div v-for="i in 2" :key="i" class="h-64 bg-slate-100 animate-pulse rounded-3xl"></div>
    </div>

    <div v-else-if="maintenanceStore.maintenances.length === 0" class="bg-white p-16 rounded-[2rem] border border-slate-200 shadow-sm text-center">
      <Wrench class="w-16 h-16 text-slate-200 mx-auto mb-4" />
      <h3 class="text-xl font-black text-slate-900 mb-2">Aucune maintenance</h3>
      <p class="text-slate-500 font-medium">Les interventions de maintenance apparaîtront ici.</p>
    </div>

    <div v-else class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <div 
        v-for="maint in maintenanceStore.maintenances" 
        :key="maint.id"
        class="bg-white rounded-3xl border border-slate-200 shadow-sm p-8 hover:shadow-md transition-all relative overflow-hidden"
      >
        <!-- Type Badge -->
        <div class="absolute top-0 right-0">
          <div :class="['px-6 py-2 rounded-bl-3xl text-[10px] font-bold uppercase tracking-widest', maint.type === 'preventive' ? 'bg-indigo-50 text-indigo-600' : 'bg-orange-50 text-orange-600']">
            {{ maint.type === 'preventive' ? 'Préventive' : 'Curative' }}
          </div>
        </div>

        <div class="flex items-start justify-between mb-8">
          <div>
            <h3 class="text-lg font-bold text-slate-900">{{ maint.equipement?.reference }}</h3>
            <p class="text-sm text-slate-500 font-medium">{{ maint.equipement?.marque }} {{ maint.equipement?.modele }}</p>
          </div>
          <span :class="['px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider mt-1', getStatutClass(maint.panne?.statut)]">
            {{ maint.panne?.statut === 'en_maintenance' ? 'En cours' : 'Terminée' }}
          </span>
        </div>

        <div class="grid grid-cols-2 gap-8 mb-8">
          <div class="space-y-1">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
              <User class="w-3.5 h-3.5" /> Technicien
            </p>
            <p class="text-sm font-bold text-slate-700">{{ maint.technicien }}</p>
          </div>
          <div class="space-y-1">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
              <Calendar class="w-3.5 h-3.5" /> Débutée le
            </p>
            <p class="text-sm font-bold text-slate-700">{{ formatDate(maint.date_debut) }}</p>
          </div>
        </div>

        <div class="space-y-4">
          <div v-if="maint.panne?.description" class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-2">
              <FileText class="w-3 h-3" /> Description de la panne
            </p>
            <p class="text-sm text-slate-600 font-medium line-clamp-2">{{ maint.panne.description }}</p>
          </div>
          <div class="flex items-center justify-between pt-4 border-t border-slate-50">
            <div class="flex items-center gap-2">
              <div class="w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center text-[10px] font-bold text-primary-700">
                {{ maint.responsable?.name?.charAt(0) }}
              </div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Géré par {{ maint.responsable?.name }}</span>
            </div>
            <div class="flex items-center gap-1 text-primary-600">
              <DollarSign class="w-3.5 h-3.5" />
              <span class="text-sm font-black">{{ maint.cout }} €</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Nouvelle Maintenance -->
    <SideModal :show="showModal" title="Nouvelle maintenance" @close="showModal = false; resetForm()">
      <div class="space-y-6">
        <div class="p-4 bg-primary-50 border border-primary-100 rounded-2xl">
          <p class="text-sm text-primary-800 font-medium flex items-center gap-2">
            <Wrench class="w-4 h-4" />
            Planification d'une intervention corrective
          </p>
        </div>

        <div class="space-y-4">
          <!-- Sélection de la panne validée -->
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Panne à traiter (uniquement validées)</label>
            <select v-model="form.panne_id" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-primary-500/10 outline-none">
              <option value="">— Sélectionner une panne validée —</option>
              <option v-for="panne in pannesValidées" :key="panne.id" :value="panne.id">
                {{ panne.equipement?.reference }} : {{ panne.equipement?.marque }} ({{ panne.gravite }})
              </option>
            </select>
            <p v-if="pannesValidées.length === 0" class="text-[10px] text-rose-500 font-bold italic">
              Aucune panne validée n'est actuellement disponible pour maintenance.
            </p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Type</label>
              <select v-model="form.type" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-primary-500/10 outline-none">
                <option value="corrective">Corrective</option>
                <option value="preventive">Préventive</option>
              </select>
            </div>
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Coût estimé (€)</label>
              <input v-model="form.cout" type="number" step="0.01" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-primary-500/10 outline-none">
            </div>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Technicien / Prestataire</label>
            <div class="relative">
              <User class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <input v-model="form.technicien" type="text" placeholder="Nom du technicien..." class="w-full h-12 pl-12 pr-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-primary-500/10 outline-none">
            </div>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Date de début d'intervention</label>
            <div class="relative">
              <Calendar class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <input v-model="form.date_debut" type="date" class="w-full h-12 pl-12 pr-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-primary-500/10 outline-none">
            </div>
          </div>
        </div>

        <button 
          @click="handleSubmit"
          :disabled="isSubmitting || !form.panne_id"
          class="w-full h-12 bg-primary-600 text-white rounded-xl text-sm font-black shadow-lg shadow-primary-200 hover:bg-primary-700 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
        >
          <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
          {{ isSubmitting ? 'Planification...' : 'Lancer la maintenance' }}
        </button>
      </div>
    </SideModal>
  </div>
</template>
