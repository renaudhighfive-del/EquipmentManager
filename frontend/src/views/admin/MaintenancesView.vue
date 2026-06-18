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
  FileText,
  CheckCircle2,
  AlertOctagon,
  Upload,
  X,
  History
} from '@lucide/vue';

// Stores et helpers
const maintenanceStore = useMaintenanceStore(); // store des maintenances et actions backend
const panneStore = usePanneStore(); // store des pannes à traiter
const toast = useToast(); // notifications utilisateur PrimeVue

// États d'affichage
const showModal = ref(false); // ouverture du modal de création de maintenance
const showClotureModal = ref(false); // ouverture du modal de clôture de maintenance
const isSubmitting = ref(false); // état de soumission des formulaires
const selectedMaintenance = ref(null); // maintenance sélectionnée pour clôture
const clotureAction = ref('retour'); // choix entre remise en service ou perte irrécupérable

const form = reactive({
  panne_id: '',
  type: 'corrective',
  technicien: '',
  date_debut: new Date().toISOString().substr(0, 10),
  cout: 0
});

const clotureForm = reactive({
  date_fin: new Date().toISOString().substr(0, 10),
  actions_effectuees: '',
  cout: 0,
  type_sinistre: 'perte'
});

const photos = ref([])
const photoPreviews = ref([])

// Chargement initial : récupère les maintenances et les pannes disponibles
// Appelé automatiquement lorsque le composant est monté
onMounted(() => {
  maintenanceStore.fetchMaintenances();
  panneStore.fetchPannes();
});

// Liste calculée des pannes valides pour planifier une maintenance
// Utilisée dans le select du modal de création de maintenance
const pannesValidées = computed(() => {
  return panneStore.pannes.filter(p => p.statut === 'en_cours' || p.statut === 'declaree');
});

// Ouvre le modal de clôture pour une maintenance donnée
// Appelé par le bouton "Clôturer l'intervention" dans la liste des maintenances
const openClotureModal = (maint) => {
  selectedMaintenance.value = maint;
  clotureAction.value = 'retour';
  clotureForm.date_fin = new Date().toISOString().substr(0, 10);
  clotureForm.actions_effectuees = '';
  clotureForm.cout = maint.cout || 0;
  clotureForm.type_sinistre = 'perte';
  photos.value = [];
  photoPreviews.value = [];
  showClotureModal.value = true;
};

// Ajoute des photos justificatives dans le formulaire de clôture
// Appelé par l'input file du modal de clôture
const onFileChange = (e) => {
  const files = Array.from(e.target.files)
  files.forEach(file => {
    photos.value.push(file)
    const reader = new FileReader()
    reader.onload = (e) => {
      photoPreviews.value.push(e.target.result)
    }
    reader.readAsDataURL(file)
  })
}

// Supprime une photo ajoutée du formulaire de clôture
// Appelé par le petit bouton X sur chaque aperçu
const removePhoto = (index) => {
  photos.value.splice(index, 1)
  photoPreviews.value.splice(index, 1)
}

// Réinitialise le formulaire de création de maintenance
// Appelé après une création réussie et lors de la fermeture du modal
const resetForm = () => {
  Object.assign(form, {
    panne_id: '',
    type: 'corrective',
    technicien: '',
    date_debut: new Date().toISOString().substr(0, 10),
    cout: 0
  });
};

// Envoie le formulaire de planification de maintenance au backend
// Appelé par le bouton "Lancer la maintenance" dans le modal de création
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
    maintenanceStore.fetchMaintenances();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de la création', life: 3000 });
  } finally {
    isSubmitting.value = false;
  }
};

// Envoie les données de clôture de maintenance ou de sinistre au backend
// Appelé par le bouton "Confirmer la clôture" dans le modal de clôture
const handleClotureAction = async () => {
  if (!clotureForm.actions_effectuees) {
    toast.add({ severity: 'warn', summary: 'Attention', detail: 'Veuillez renseigner le rapport d\'intervention', life: 3000 });
    return;
  }

  isSubmitting.value = true;
  try {
    const formData = new FormData();
    // Toujours ajouter les photos s'il y en a
    if (photos.value.length > 0) {
      photos.value.forEach((file) => {
        formData.append('images[]', file);
      });
    }

    if (clotureAction.value === 'retour') {
      formData.append('date_fin', clotureForm.date_fin);
      formData.append('actions_effectuees', clotureForm.actions_effectuees);
      formData.append('cout', clotureForm.cout || 0);
      
      console.log('Envoi clôture réparation...');
      await maintenanceStore.cloturerMaintenance(selectedMaintenance.value.id, formData);
      toast.add({ severity: 'success', summary: 'Succès', detail: 'Maintenance clôturée avec succès', life: 3000 });
    } else {
      formData.append('type', clotureForm.type_sinistre);
      formData.append('description', clotureForm.actions_effectuees);
      
      console.log('Envoi clôture irrécupérable...');
      await maintenanceStore.declarerPerteMaintenance(selectedMaintenance.value.id, formData);
      toast.add({ severity: 'success', summary: 'Succès', detail: 'Équipement déclaré irrécupérable', life: 3000 });
    }
    showClotureModal.value = false;
    await maintenanceStore.fetchMaintenances();
  } catch (error) {
    console.error('Erreur lors de la clôture:', error);
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de l\'opération. Vérifiez la console.', life: 3000 });
  } finally {
    isSubmitting.value = false;
  }
};

// Formate une date en français pour l'affichage dans le template
// Utilisé pour les dates de début et de fin de maintenance
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });
};

// Retourne les classes CSS en fonction du statut de panne
// Utilisé dans le badge de statut de chaque carte de maintenance
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
        <!-- Ouvre le modal de création de nouvelle maintenance -->
        <button 
          @click="showModal = true"
          class="flex items-center gap-2 px-5 py-2.5 bg-primary-600 rounded-xl text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-lg shadow-primary-200"
        >
          <Plus class="w-4 h-4" />
          Nouvelle maintenance
        </button>
      </template>
    </PageHeader>

    <!-- Affichage du loader pendant le chargement initial des maintenances -->
    <div v-if="maintenanceStore.loading && maintenanceStore.maintenances.length === 0" class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <div v-for="i in 2" :key="i" class="h-64 bg-slate-100 animate-pulse rounded-3xl"></div>
    </div>

    <!-- Message vide si aucune maintenance n'est trouvée après chargement -->
    <div v-else-if="maintenanceStore.maintenances.length === 0" class="bg-white p-16 rounded-[2rem] border border-slate-200 shadow-sm text-center">
      <Wrench class="w-16 h-16 text-slate-200 mx-auto mb-4" />
      <h3 class="text-xl font-black text-slate-900 mb-2">Aucune maintenance</h3>
      <p class="text-slate-500 font-medium">Les interventions de maintenance apparaîtront ici.</p>
    </div>

    <!-- Liste des maintenances existantes, affichées à partir de maintenanceStore.maintenances -->
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

            <!-- Bouton d'ouverture du modal de clôture s'il n'y a pas encore de date de fin -->
          </div>

          <div v-if="maint.date_fin" class="p-4 bg-emerald-50 rounded-2xl border border-emerald-100 space-y-2">
            <div class="flex items-center justify-between">
              <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest flex items-center gap-2">
                <CheckCircle2 class="w-3 h-3" /> Clôturée le {{ formatDate(maint.date_fin) }}
              </p>
            </div>
            <p class="text-xs text-emerald-700 font-medium">{{ maint.actions_effectuees }}</p>
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

    <!-- Modal de création d'une nouvelle maintenance -->
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

    <!-- Modal de clôture de maintenance : permet soit de marquer comme réparé, soit de déclarer perte -->
    <SideModal :show="showClotureModal" title="Clôturer la maintenance" @close="showClotureModal = false">
      <div v-if="selectedMaintenance" class="space-y-6">
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl">
          <p class="text-sm text-slate-600 font-medium">
            Équipement : 
            <span class="font-black text-slate-900">{{ selectedMaintenance.equipement?.marque }} {{ selectedMaintenance.equipement?.modele }}</span>
          </p>
          <p class="text-xs text-slate-500 font-bold mt-1">Référence : {{ selectedMaintenance.equipement?.reference }}</p>
        </div>

        <!-- Action Choice -->
        <div class="space-y-3">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Résultat de l'intervention</label>
          <div class="grid grid-cols-2 gap-3">
            <button 
              type="button"
              @click="clotureAction = 'retour'"
              :class="['p-4 rounded-2xl border-2 transition-all text-left flex flex-col gap-2', clotureAction === 'retour' ? 'border-emerald-600 bg-emerald-50/50' : 'border-slate-100 hover:border-slate-200']"
            >
              <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                <CheckCircle2 class="w-6 h-6" />
              </div>
              <div>
                <p class="text-sm font-black text-slate-900">Réparé</p>
                <p class="text-[10px] text-slate-500 font-medium">Remise en service</p>
              </div>
            </button>
            
            <button 
              type="button"
              @click="clotureAction = 'perte'"
              :class="['p-4 rounded-2xl border-2 transition-all text-left flex flex-col gap-2', clotureAction === 'perte' ? 'border-rose-600 bg-rose-50/50' : 'border-slate-100 hover:border-slate-200']"
            >
              <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center">
                <AlertOctagon class="w-6 h-6" />
              </div>
              <div>
                <p class="text-sm font-black text-slate-900">Irrécupérable</p>
                <p class="text-[10px] text-slate-500 font-medium">Déclarer un sinistre</p>
              </div>
            </button>
          </div>
        </div>

        <!-- Photos -->
        <div class="space-y-4">
          <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Photos justificatives (appui au rapport)</label>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div v-for="(preview, index) in photoPreviews" :key="index" class="relative aspect-square rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 group">
              <img :src="preview" class="w-full h-full object-cover">
              <button type="button" @click="removePhoto(index)" class="absolute top-1 right-1 p-1 bg-black/50 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                <X class="w-3 h-3" />
              </button>
            </div>
            <label class="relative aspect-square rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center gap-2 cursor-pointer hover:border-primary-400 hover:bg-primary-50 transition-all text-slate-400 hover:text-primary-600">
              <input type="file" multiple accept="image/*" class="hidden" @change="onFileChange">
              <Upload class="w-6 h-6" />
              <span class="text-[10px] font-bold">Ajouter</span>
            </label>
          </div>
        </div>

        <!-- Form fields -->
        <div class="space-y-5">
          <div v-if="clotureAction === 'retour'" class="grid grid-cols-2 gap-4 animate-in slide-in-from-top-2 duration-300">
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Date de fin</label>
              <input v-model="clotureForm.date_fin" type="date" class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-primary-500/20">
            </div>
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Coût final (€)</label>
              <input v-model="clotureForm.cout" type="number" step="0.01" class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-primary-500/20">
            </div>
          </div>

          <div v-else class="space-y-1.5 animate-in slide-in-from-top-2 duration-300">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Type de sinistre</label>
            <select v-model="clotureForm.type_sinistre" class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-rose-500/20">
              <option value="perte">Perte</option>
              <option value="casse">Casse</option>
              <option value="vol">Vol</option>
            </select>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">
              {{ clotureAction === 'retour' ? 'Rapport d\'intervention' : 'Justification du sinistre' }}
            </label>
            <textarea 
              v-model="clotureForm.actions_effectuees" 
              required
              rows="4" 
              class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-primary-500/20 resize-none"
              placeholder="Détaillez ici..."
            ></textarea>
          </div>
        </div>

        <button 
          @click="handleClotureAction"
          :disabled="isSubmitting"
          class="w-full h-12 bg-primary-600 text-white rounded-xl text-sm font-black shadow-lg shadow-primary-200 hover:bg-primary-700 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
        >
          <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
          {{ isSubmitting ? 'Envoi en cours...' : 'Confirmer la clôture' }}
        </button>
      </div>
    </SideModal>
  
</template>
