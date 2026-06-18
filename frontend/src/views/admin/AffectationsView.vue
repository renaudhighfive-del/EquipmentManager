<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import PageHeader from '../../components/layout/PageHeader.vue';
import SideModal from '../../components/layout/SideModal.vue';
import api from '../../services/axios';
import { useAffectationStore } from '../../stores/affectation';
import { storeToRefs } from 'pinia';
import { 
  Plus, 
  ArrowLeftRight, 
  History,
  Camera,
  Search,
  Filter,
  Loader2,
  X,
  Maximize2,
  Pencil,
  Download
} from '@lucide/vue';

import Button from 'primevue/button';

// import { Paginator } from 'primevue/paginator'
import Paginator from 'primevue/paginator';

const affectationStore = useAffectationStore();
const { affectations, loading, error: storeError ,currentAffectation, successMessage,pagination , exportAffectationsToExcel} = storeToRefs(affectationStore);
const toast = useToast();

//gestion de la pagination
const currentPage=ref(1);
const onPageChange = (event) => {
  currentPage.value = event.page + 1 
  affectationStore.fetchAffectations(currentPage.value)
}

//Pour le zoom de l'image 
const showZoomModal= ref(false);
const zoomImage=ref('');

const openZoom = (url) => {
  zoomImage.value = url;
  showZoomModal.value = true;
  // console.log('👉 Modal ouvert ?', showZoomModal.value);
};

const showReturnModal = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showReviewReturnModal = ref(false);
const selectedAffectation = ref(null);
const submitting = ref(false);
const submittingValidate = ref(false);
const submittingReject = ref(false);
const rejectForm = ref({
  motif_rejet: ''
});

// Refs pour la photo de remise (création)
const photoInput = ref(null);
const photoPreview = ref(null);
const photoFile = ref(null);

// Refs pour la photo de retour
const returnPhotoInput = ref(null);
const returnPhotoPreview = ref(null);
const returnPhotoFile = ref(null);

// Refs pour la photo d'édition
const editPhotoInput = ref(null);
const editPhotoPreview = ref(null);
const editPhotoFile = ref(null);

const localError = ref(null);

//Pour le voir detail
const showFiche=ref(false);

const availableEquipements = ref([]);
const agents = ref([]);

const today = new Date().toISOString().split('T')[0];

const newAffectation = ref({
  equipement_id: '',
  agent_id: '',
  date_affectation: today,
  observations: ''
});

const editForm = ref({
  agent_id: '',
  date_affectation: '',
  observations: ''
});

const returnForm = ref({
  date_retour: today,
  etat_retour: 'Bon état',
  observations: ''
});

const triggerFileInput = () => {
  photoInput.value.click();
};

const triggerReturnFileInput = () => {
  returnPhotoInput.value.click();
};

const triggerEditFileInput = () => {
  editPhotoInput.value.click();
};

const onFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    photoFile.value = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      photoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const onReturnFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    returnPhotoFile.value = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      returnPhotoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const onEditFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    editPhotoFile.value = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      editPhotoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const showToast = (severity, summary, detail) => {
  toast.add({ severity, summary, detail, life: 3000 });
};


//pour recuperer les equip et les agents 
const fetchInitialData = async () => {
  try {
    const [equipRes, agentRes] = await Promise.all([
      api.get('/equipements'),
      api.get('/agents?statut=actif&per_page=100')
    ]);
    // On accepte uniquement 'neuf' comme état disponible pour une nouvelle affectation
    availableEquipements.value = equipRes.data.filter(e => e.etat === 'neuf');
    agents.value = agentRes.data.agents;
    
    console.log('Données initiales chargées :', {
      equipements: availableEquipements.value.length,
      agentsTotal: agents.value.length,
    });
  } catch (error) {
    showToast('error', 'Erreur', 'Impossible de charger les données initiales');
    console.error('Erreur lors du chargement des données initiales', error);
  }
};

onMounted(async () => {
  const success = await affectationStore.fetchAffectations(1);
  if (!success) {
    showToast('error', 'Erreur', storeError.value);
  }
  fetchInitialData();
});

const openReturnModal = (aff) => {
  selectedAffectation.value = aff;
  returnForm.value = {
    date_retour: new Date().toISOString().split('T')[0],
    etat_retour: 'Bon état',
    observations: ''
  };
  returnPhotoFile.value = null;
  returnPhotoPreview.value = null;
  localError.value = null;
  showReturnModal.value = true;
};

const openEditModal = async (aff) => {
  await fetchInitialData(); // Charger les agents si pas déjà fait
  selectedAffectation.value = aff;
  editForm.value = {
    agent_id: aff.agent_id,
    date_affectation: aff.date_affectation.split('T')[0],
    observations: aff.observations || ''
  };
  editPhotoFile.value = null;
  editPhotoPreview.value = aff.photo_remise_url;
  localError.value = null;
  showEditModal.value = true;
};

const submitReturn = async () => {
  localError.value = null;
  if (!returnPhotoFile.value) {
    localError.value = 'La photo de retour est obligatoire.';
    return;
  }

  try {
    submitting.value = true;
    
    const formData = new FormData();
    formData.append('date_retour', returnForm.value.date_retour);
    formData.append('etat_retour', returnForm.value.etat_retour);
    formData.append('observations', returnForm.value.observations || '');
    formData.append('photo_retour', returnPhotoFile.value);

    const success = await affectationStore.requestReturnAffectation(selectedAffectation.value.id, formData);
    if (success) {
      showToast('success', 'Succès', successMessage.value);
      showReturnModal.value = false;
    } else {
      showToast('error', 'Erreur', storeError.value);
      localError.value = storeError.value;
    }
  } catch (error) {
    showToast('error', 'Erreur', "Une erreur est survenue lors du retour.");
    localError.value = "Une erreur est survenue lors du retour.";
    console.error(error);
  } finally {
    submitting.value = false;
  }
};

const submitEdit = async () => {
  localError.value = null;
  try {
    submitting.value = true;
    
    const formData = new FormData();
    formData.append('_method', 'PATCH');
    formData.append('agent_id', editForm.value.agent_id);
    formData.append('date_affectation', editForm.value.date_affectation);
    formData.append('observations', editForm.value.observations || '');
    if (editPhotoFile.value) {
      formData.append('photo_remise', editPhotoFile.value);
    }

    const success = await affectationStore.updateAffectation(selectedAffectation.value.id, formData);
    if (success) {
      showToast('success', 'Succès', successMessage.value);
      showEditModal.value = false;
    } else {
      showToast('error', 'Erreur', storeError.value);
      localError.value = storeError.value;
    }
  } catch (error) {
    showToast('error', 'Erreur', "Une erreur est survenue lors de la modification.");
    localError.value = "Une erreur est survenue lors de la modification.";
    console.error(error);
  } finally {
    submitting.value = false;
  }
};

const openFiche = async (id) => {
  showFiche.value = true;
  const success = await affectationStore.fetchAffectationById(id);
  if (!success) {
    showToast('error', 'Erreur', storeError.value);
  }
};

const openCreateModal = () => {
  fetchInitialData();
  showCreateModal.value = true;
};

const submitAffectation = async () => {
  localError.value = null;
  if (!photoFile.value) {
    localError.value = 'La photo de remise est obligatoire.';
    return;
  }

  try {
    submitting.value = true;
    
    // Utilisation de FormData pour envoyer le fichier
    const formData = new FormData();
    formData.append('equipement_id', newAffectation.value.equipement_id);
    formData.append('agent_id', newAffectation.value.agent_id);
    formData.append('date_affectation', newAffectation.value.date_affectation);
    formData.append('observations', newAffectation.value.observations || '');
    formData.append('photo_remise', photoFile.value);

    const success = await affectationStore.createAffectation(formData);
    if (success) {
      showToast('success', 'Succès', successMessage.value);
      showCreateModal.value = false;
      // Reset form
      newAffectation.value = {
        equipement_id: '',
        agent_id: '',
        date_affectation: new Date().toISOString().split('T')[0],
        observations: ''
      };
      photoFile.value = null;
      photoPreview.value = null;
    } else {
      showToast('error', 'Erreur', storeError.value);
      localError.value = storeError.value;
    }
  } catch (error) {
    showToast('error', 'Erreur', "Une erreur inattendue est survenue.");
    localError.value = "Une erreur inattendue est survenue.";
    console.error('Erreur lors de la création de l\'affectation', error);
  } finally {
    submitting.value = false;
  }
};

const openReviewReturnModal = (aff) => {
  selectedAffectation.value = aff;
  rejectForm.value = { motif_rejet: '' };
  localError.value = null;
  showReviewReturnModal.value = true;
};

const handleValidateReturn = async () => {
  try {
    submittingValidate.value = true;
    const success = await affectationStore.validateReturn(selectedAffectation.value.id);
    if (success) {
      showToast('success', 'Succès', successMessage.value);
      showReviewReturnModal.value = false;
    } else {
      showToast('error', 'Erreur', storeError.value);
    }
  } catch (error) {
    showToast('error', 'Erreur', "Une erreur est survenue lors de la validation du retour.");
    console.error(error);
  } finally {
    submittingValidate.value = false;
  }
};


//Gestion de la logique de l'export Excel via le Store
const exportToExcel = async () => {
  try {
    showToast('info', 'Export', 'Génération du fichier Excel en cours...');

    // 1. On appelle l'action du store
    const data = await affectationStore.exportAffectationsToExcel();

    // 2. On crée le lien de téléchargement avec le résultat
    const url = window.URL.createObjectURL(new Blob([data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `export_affectations_${Date.now()}.xlsx`); 
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);

    showToast('success', 'Succès', 'Le fichier Excel a été téléchargé.');
  } catch (error) {
    showToast('error', 'Erreur', 'Impossible de générer le fichier Excel.');
    }
    };
    
const handleRejectReturn = async () => {
  if (!rejectForm.value.motif_rejet) {
    localError.value = 'Le motif de rejet est obligatoire.';
    return;
  }

  try {
    submittingReject.value = true;
    const success = await affectationStore.rejectReturn(selectedAffectation.value.id, rejectForm.value);
    if (success) {
      showToast('success', 'Succès', successMessage.value);
      showReviewReturnModal.value = false;
    } else {
      showToast('error', 'Erreur', storeError.value);
    }
  } catch (error) {
    showToast('error', 'Erreur', "Une erreur est survenue lors du rejet du retour.");
    console.error(error);
  } finally {
    submittingReject.value = false;
  }
};
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <PageHeader 
      title="Affectations" 
      subtitle="Gestion des remises et retours de matériels"
    >
      <template #actions>
        <Button 
  label="Exporter en Excel" 
  icon="pi pi-file-excel" 
  class="p-button-success" 
  @click="exportToExcel" 
/>
        <div v-if="affectations.filter(a => a.statut === 'retour_en_attente').length > 0"
          class="flex items-center gap-2 mr-4 text-sm font-bold text-amber-700 bg-amber-50 px-4 py-2 rounded-xl border border-amber-200">
          <div class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></div>
          {{ affectations.filter(a => a.statut === 'retour_en_attente').length }} retour(s) en attente
        </div>
        <button 
          @click="openCreateModal"
          class="flex items-center gap-2 px-5 py-2.5 bg-primary-600 rounded-xl text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-lg shadow-primary-200"
        >
          <Plus class="w-4 h-4" />
          Nouvelle affectation
        </button>
      </template>
    </PageHeader>

    <div v-if="loading" class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-slate-200">
      <Loader2 class="w-10 h-10 text-primary-500 animate-spin mb-4" />
      <p class="text-slate-500 font-medium">Chargement des affectations...</p>
    </div>

    <div v-else-if="affectations.length === 0" class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-slate-200">
      <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mb-4">
        <ArrowLeftRight class="w-8 h-8 text-slate-300" />
      </div>
      <p class="text-slate-500 font-medium text-lg">Aucune affectation enregistrée</p>
      <button @click="openCreateModal" class="mt-4 text-primary-600 font-bold hover:underline">Créer la première affectation</button>
    </div>

    <div v-else class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <table class="w-full text-left">
        <thead>
          <tr class="bg-slate-50/50 text-[11px] font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">
            <th class="px-8 py-5">Équipement</th>
            <th class="px-6 py-5">Agent</th>
            <th class="px-6 py-5">Affecté par</th>
            <th class="px-6 py-5">Date</th>
            <th class="px-6 py-5">Statut</th>
            <th class="px-8 py-5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <tr v-for="aff in affectations" :key="aff.id" 
            :class="['hover:bg-slate-50/50 transition-colors group', aff.statut === 'retour_en_attente' ? 'bg-amber-50 hover:bg-amber-100' : '']">
            <td class="px-8 py-5">
              <div class="flex items-center gap-2">
                <div v-if="aff.statut === 'retour_en_attente'" class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></div>
                <div>
                  <p class="text-sm font-bold text-slate-900">{{ aff.equipement?.reference }}</p>
                  <p class="text-xs text-slate-500">{{ aff.equipement?.modele }} {{ aff.equipement?.marque }}</p>
                </div>
              </div>
            </td>
            <td class="px-6 py-5">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500">
                  {{ aff.agent?.nom.charAt(0) }}
                </div>
                <span class="text-sm font-medium text-slate-700">{{ aff.agent?.prenom }} {{ aff.agent?.nom }}</span>
              </div>
            </td>
            <td class="px-6 py-5">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-primary-50 flex items-center justify-center text-[10px] font-bold text-primary-600">
                  {{ aff.affecte_par?.name.charAt(0) }}
                </div>
                <span class="text-xs font-medium text-slate-600">{{ aff.affecte_par?.name }}</span>
              </div>
            </td>
            <td class="px-6 py-5 text-sm text-slate-500 font-medium">
              {{ new Date(aff.date_affectation).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}
            </td>
            <td class="px-6 py-5">
              <div class="flex flex-col gap-1">
                <span 
                  class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight w-fit"
                  :class="{
                      'bg-blue-50 text-blue-600': aff.statut === 'en_cours',
                      'bg-emerald-50 text-emerald-600': aff.statut === 'confirmee',
                      'bg-amber-50 text-amber-600': aff.statut === 'retour_en_attente',
                      'bg-slate-100 text-slate-500': aff.statut === 'retourne',
                      'bg-purple-50 text-purple-600': aff.statut === 'renouvele'
                  }"
                >
                  {{ aff.statut === 'en_cours' ? 'À confirmer' : (aff.statut === 'confirmee' ? 'Confirmée' : (aff.statut === 'retour_en_attente' ? 'Retour en attente' : (aff.statut === 'retourne' ? 'Retourné' : 'Renouvelé'))) }}
                </span>
                <span 
                  v-if="aff.motif_rejet"
                  class="px-2 py-1 bg-red-50 text-red-600 rounded-full text-[9px] font-medium w-fit"
                  title="Motif de rejet : "
                >
                  Retour rejeté
                </span>
              </div>
            </td>
            <td class="px-8 py-5 text-right">
              <div class="flex items-center justify-end gap-2">
                <button 
                  @click="openFiche(aff.id)"
                  class="text-xs font-bold text-slate-600 hover:text-primary-600 transition-colors py-2 px-3 rounded-lg hover:bg-primary-50"
                  title="Voir détails"
                >
                  Détails
                </button>
                
                <button 
                  v-if="aff.statut === 'en_cours'"
                  @click="openEditModal(aff)"
                  class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                  title="Modifier l'affectation"
                >
                  <Pencil class="w-4 h-4" />
                </button>
                
                <button 
                  v-if="aff.statut === 'en_cours' || aff.statut === 'confirmee'"
                  @click="openReturnModal(aff)"
                  class="text-xs font-bold text-primary-600 hover:text-primary-700 bg-primary-50 px-4 py-2 rounded-lg transition-colors"
                >
                  Retour
                </button>

                <button
                  v-if="aff.statut === 'retour_en_attente'"
                  @click="openReviewReturnModal(aff)"
                  class="text-xs font-bold text-green-600 hover:text-green-700 bg-green-50 px-4 py-2 rounded-lg transition-colors"
                >
                  Valider/Rejeter
                </button>
                
                <span v-if="aff.statut === 'retourne'" class="text-xs font-bold text-slate-300 px-2 py-2">Clôturé</span>
              </div>
            </td>
          </tr>
        </tbody>


        
      </table>
      
        <!-- La pagination -->
      <Paginator
  :first="(affectationStore.pagination.current_page - 1) * affectationStore.pagination.per_page"
  :rows="affectationStore.pagination.per_page"
  :total-records="affectationStore.pagination.total"
  @page="onPageChange"
  class="mt-4"
/>
      
    </div>

    <SideModal 
      :show="showCreateModal" 
      title="Nouvelle affectation" 
      mode="center"
      @close="showCreateModal = false"
    >
      <form @submit.prevent="submitAffectation" class="space-y-6">
        <div v-if="localError" class="p-4 bg-red-50 border border-red-100 rounded-2xl flex items-start gap-3 animate-in fade-in slide-in-from-top-2 duration-300">
          <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
            <X class="w-3 h-3 text-red-600" />
          </div>
          <p class="text-sm font-medium text-red-600">{{ localError }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Équipement</label>
            <select 
              v-model="newAffectation.equipement_id"
              required
              class="w-full h-11 px-4 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20"
            >
              <option value="" disabled>Sélectionner un équipement</option>
              <option v-for="equip in availableEquipements" :key="equip.id" :value="equip.id">
                {{ equip.reference }} - {{ equip.modele }}
              </option>
              <option v-if="availableEquipements.length === 0" value="1">REF-TEST - Équipement Test</option>
            </select>
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Agent</label>
            <select 
              v-model="newAffectation.agent_id"
              required
              class="w-full h-11 px-4 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20"
            >
              <option value="" disabled>Sélectionner un agent</option>
              <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                {{ agent.nom }} {{ agent.prenom }}
              </option>
              <option v-if="agents.length === 0" value="1">Agent Test</option>
            </select>
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Date d'affectation</label>
          <input 
            type="date" 
            v-model="newAffectation.date_affectation"
            required
            :max="today"
            class="w-full h-11 px-4 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20"
          >
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Observations</label>
          <textarea 
            v-model="newAffectation.observations"
            rows="3" 
            placeholder="Notes éventuelles..."
            class="w-full p-4 bg-white border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20 resize-none"
          ></textarea>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Photo remise (obligatoire)</label>
          <input 
            type="file" 
            ref="photoInput" 
            class="hidden" 
            accept="image/*" 
            capture="environment"
            @change="onFileChange"
          >
          <div 
            @click="triggerFileInput"
            class="border-2 border-dashed border-slate-200 rounded-2xl p-6 flex flex-col items-center justify-center gap-2 hover:bg-slate-50 transition-colors cursor-pointer group overflow-hidden relative"
          >
            <template v-if="photoPreview">
              <img :src="photoPreview" class="absolute inset-0 w-full h-full object-cover" />
              <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <Camera class="w-8 h-8 text-white" />
              </div>
            </template>
            <template v-else>
              <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                <Camera class="w-5 h-5 text-slate-400" />
              </div>
              <p class="text-xs font-bold text-slate-500">Prendre / Ajouter une photo</p>
            </template>
          </div>
        </div>

        <div class="pt-4 flex gap-3">
          <button 
            type="button"
            @click="showCreateModal = false" 
            class="flex-1 py-3 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-colors"
          >
            Annuler
          </button>
          <button 
            type="submit"
            :disabled="submitting"
            class="flex-1 py-3 bg-primary-600 text-white font-bold rounded-xl hover:bg-primary-700 transition-colors shadow-lg shadow-primary-200 flex items-center justify-center gap-2"
          >
            <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
            {{ submitting ? 'Enregistrement...' : 'Confirmer l\'affectation' }}
          </button>
        </div>
      </form>
    </SideModal>

    <SideModal 
      :show="showEditModal" 
      title="Modifier l'affectation" 
      mode="center"
      @close="showEditModal = false"
    >
      <form @submit.prevent="submitEdit" v-if="selectedAffectation" class="space-y-6">
        <div v-if="localError" class="p-4 bg-red-50 border border-red-100 rounded-2xl flex items-start gap-3">
          <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
            <X class="w-3 h-3 text-red-600" />
          </div>
          <p class="text-sm font-medium text-red-600">{{ localError }}</p>
        </div>

        <div class="p-5 bg-blue-600 rounded-2xl border border-blue-500 mb-6 text-white shadow-lg shadow-blue-200">
          <p class="text-[10px] font-bold text-blue-100 uppercase tracking-widest mb-1">Équipement</p>
          <p class="text-base font-bold">{{ selectedAffectation.equipement?.modele }} ({{ selectedAffectation.equipement?.reference }})</p>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Agent</label>
          <select 
            v-model="editForm.agent_id"
            required
            class="w-full h-11 px-4 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20"
          >
            <option v-for="agent in agents" :key="agent.id" :value="agent.id">
              {{ agent.nom }} {{ agent.prenom }}
            </option>
          </select>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Date d'affectation</label>
          <input 
            type="date" 
            v-model="editForm.date_affectation"
            required
            :max="today"
            class="w-full h-11 px-4 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20"
          >
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Observations</label>
          <textarea 
            v-model="editForm.observations"
            rows="3" 
            placeholder="Notes éventuelles..."
            class="w-full p-4 bg-white border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20 resize-none"
          ></textarea>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Photo remise (laisser vide pour ne pas modifier)</label>
          <input 
            type="file" 
            ref="editPhotoInput" 
            class="hidden" 
            accept="image/*" 
            capture="environment"
            @change="onEditFileChange"
          >
          <div 
            @click="triggerEditFileInput"
            class="border-2 border-dashed border-slate-200 rounded-2xl p-6 flex flex-col items-center justify-center gap-2 hover:bg-slate-50 transition-colors cursor-pointer group overflow-hidden relative"
          >
            <template v-if="editPhotoPreview">
              <img :src="editPhotoPreview" class="absolute inset-0 w-full h-full object-cover" />
              <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <Camera class="w-8 h-8 text-white" />
              </div>
            </template>
            <template v-else>
              <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                <Camera class="w-5 h-5 text-slate-400" />
              </div>
              <p class="text-xs font-bold text-slate-500">Modifier la photo</p>
            </template>
          </div>
        </div>

        <div class="pt-4 flex gap-3">
          <button 
            type="button"
            @click="showEditModal = false" 
            class="flex-1 py-3 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-colors"
          >
            Annuler
          </button>
          <button 
            type="submit"
            :disabled="submitting"
            class="flex-1 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors shadow-lg shadow-blue-200 flex items-center justify-center gap-2"
          >
            <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
            {{ submitting ? 'Modification...' : 'Enregistrer les modifications' }}
          </button>
        </div>
      </form>
    </SideModal>

    <SideModal 
      :show="showReturnModal" 
      title="Retour d'équipement" 
      mode="center"
      @close="showReturnModal = false"
    >
      <form @submit.prevent="submitReturn" v-if="selectedAffectation" class="space-y-6">
        <div v-if="localError" class="p-4 bg-red-50 border border-red-100 rounded-2xl flex items-start gap-3">
          <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
            <X class="w-3 h-3 text-red-600" />
          </div>
          <p class="text-sm font-medium text-red-600">{{ localError }}</p>
        </div>

        <div class="p-5 bg-primary-500 rounded-2xl border border-primary-400 mb-6 text-white shadow-lg shadow-primary-200">
          <p class="text-[10px] font-bold text-primary-100 uppercase tracking-widest mb-1">Équipement à retourner</p>
          <p class="text-base font-bold">{{ selectedAffectation.equipement?.modele }} ({{ selectedAffectation.equipement?.reference }})</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-bold text-slate-500 uppercase">Date de retour</label>
            <input 
              type="date" 
              v-model="returnForm.date_retour"
              required
              :max="today"
              class="w-full h-11 px-4 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20"
            >
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold text-slate-500 uppercase">État constaté</label>
            <select 
              v-model="returnForm.etat_retour"
              required
              class="w-full h-11 px-4 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20"
            >
              <option>Bon état</option>
              <option>Abîmé</option>
              <option>En panne</option>
            </select>
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold text-slate-500 uppercase">Observations</label>
          <textarea 
            v-model="returnForm.observations"
            rows="4" 
            placeholder="Détails..."
            class="w-full p-4 bg-white border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20 resize-none"
          ></textarea>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold text-slate-500 uppercase">Photo retour (obligatoire)</label>
          <input 
            type="file" 
            ref="returnPhotoInput" 
            class="hidden" 
            accept="image/*" 
            capture="environment"
            @change="onReturnFileChange"
          >
          <div 
            @click="triggerReturnFileInput"
            class="border-2 border-dashed border-slate-200 rounded-2xl p-8 flex flex-col items-center justify-center gap-3 hover:bg-slate-50 transition-colors cursor-pointer group relative overflow-hidden"
          >
            <template v-if="returnPhotoPreview">
              <img :src="returnPhotoPreview" class="absolute inset-0 w-full h-full object-cover" />
              <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <Camera class="w-8 h-8 text-white" />
              </div>
            </template>
            <template v-else>
              <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                <Camera class="w-6 h-6 text-slate-400" />
              </div>
              <p class="text-xs font-bold text-slate-500">Ajouter une photo</p>
            </template>
          </div>
        </div>

        <div class="pt-6 flex gap-3">
          <button 
            type="button"
            @click="showReturnModal = false" 
            class="flex-1 py-3 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-colors"
          >
            Annuler
          </button>
          <button 
            type="submit"
            :disabled="submitting"
            class="flex-1 py-3 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 transition-colors shadow-lg shadow-emerald-200 flex items-center justify-center gap-2"
          >
            <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
            {{ submitting ? 'Traitement...' : 'Confirmer retour' }}
          </button>
        </div>
      </form>
    </SideModal>

    <SideModal 
      :show="showFiche" 
      title="Détails Affectation" 
      @close="showFiche = false"
    >
      <div v-if="loading" class="flex flex-col items-center justify-center py-20">
        <Loader2 class="w-8 h-8 text-primary-500 animate-spin mb-2" />
        <p class="text-sm font-medium text-slate-400">Chargement de la fiche...</p>
      </div>

      <div v-else-if="storeError" class="p-8 text-center">
        <div class="w-12 h-12 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <X class="w-6 h-6 text-red-500" />
        </div>
        <p class="text-sm font-bold text-slate-900 mb-1">Erreur de chargement</p>
        <p class="text-xs text-slate-500">{{ storeError }}</p>
        <button 
          @click="showFiche = false"
          class="mt-4 text-xs font-bold text-primary-600 hover:underline"
        >
          Fermer la fenêtre
        </button>
      </div>

      <div v-else-if="currentAffectation" class="space-y-10 animate-in fade-in duration-300">
        
        <div class="flex items-center gap-5">
          <div class="w-20 h-20 rounded-2xl bg-primary-50 border-2 border-white shadow-xl flex items-center justify-center text-primary-600 text-2xl font-bold">
            {{ currentAffectation.agent?.nom.charAt(0) }}
          </div>
          <div>
            <h3 class="text-xl font-bold text-slate-900">
              {{ currentAffectation.agent?.prenom }} {{ currentAffectation.agent?.nom }}
            </h3>
            <p class="text-slate-500 text-sm font-medium">
              Matricule : {{ currentAffectation.agent?.matricule || 'N/A' }}
            </p>
            <div 
              class="mt-2 inline-flex px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider"
              :class="currentAffectation.statut === 'en_cours' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600'"
            >
              {{ currentAffectation.statut === 'en_cours' ? 'Affectation active' : 'Clôturée' }}
            </div>
            <div v-if="currentAffectation.affecte_par" class="mt-2 inline-flex items-center gap-2 px-3 py-1 bg-slate-100 rounded-lg">
              <div class="w-6 h-6 rounded-full bg-primary-50 flex items-center justify-center text-[10px] font-bold text-primary-600">
                {{ currentAffectation.affecte_par.name.charAt(0) }}
              </div>
              <span class="text-[10px] font-medium text-slate-700">
                Enregistré par : {{ currentAffectation.affecte_par.name }}
              </span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Marque & Modèle</p>
            <p class="text-sm font-bold text-slate-900">
              {{ currentAffectation.equipement?.marque }} {{ currentAffectation.equipement?.modele }}
            </p>
          </div>
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Référence Matériel</p>
            <p class="text-sm font-mono font-bold text-slate-900">
              {{ currentAffectation.equipement?.reference }}
            </p>
          </div>
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Date de remise</p>
            <p class="text-sm font-bold text-slate-900">
              {{ new Date(currentAffectation.date_affectation).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}
            </p>
          </div>
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Date de Retour Prévue</p>
            <p class="text-sm font-bold text-slate-900">
              {{ currentAffectation.date_retour ? new Date(currentAffectation.date_retour).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) : '—' }}
            </p>
          </div>
        </div>

        <div class="space-y-2 p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
          <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Observations initiales</h4>
          <p class="text-sm font-medium text-slate-600 italic">
            {{ currentAffectation.observations || 'Aucune observation enregistrée.' }}
          </p>
        </div>

        <div v-if="currentAffectation.motif_rejet" class="space-y-2 p-4 bg-red-50/50 rounded-2xl border border-red-100">
          <h4 class="text-[10px] font-bold text-red-500 uppercase tracking-widest">Motif de rejet du retour</h4>
          <p class="text-sm font-medium text-red-700 italic">
            {{ currentAffectation.motif_rejet }}
          </p>
        </div>

        <div class="space-y-4">
          <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <Camera class="w-4 h-4 text-primary-500" />
            Photo justificative de remise
          </h4>
          
          <div v-if="!currentAffectation.photo_remise" class="p-8 text-center bg-slate-50/50 rounded-3xl border border-dashed border-slate-200">
            <p class="text-sm font-medium text-slate-400 italic">Aucune photo enregistrée</p>
          </div>
          <div v-else class="w-full rounded-2xl border border-slate-200 overflow-hidden shadow-sm bg-slate-50 group relative cursor-pointer" @click="openZoom(currentAffectation.photo_remise_url)">
  <img :src="currentAffectation.photo_remise_url" alt="Photo de remise" class="w-full h-52 object-cover">
  <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300 flex items-center justify-center">
    <Maximize2 class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-all duration-300" />
  </div>
</div>
          
        </div>

        <div v-if="currentAffectation.statut === 'retourne'" class="space-y-4">
          <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <Camera class="w-4 h-4 text-emerald-500" />
            Photo justificative de retour
          </h4>
          
          <div v-if="!currentAffectation.photo_retour" class="p-8 text-center bg-slate-50/50 rounded-3xl border border-dashed border-slate-200">
            <p class="text-sm font-medium text-slate-400 italic">Aucune photo enregistrée</p>
          </div>
          
          <div v-else class="w-full rounded-2xl border border-slate-200 overflow-hidden shadow-sm bg-slate-50 group relative cursor-pointer" @click="openZoom(currentAffectation.photo_retour_url)">
  <img :src="currentAffectation.photo_retour_url" alt="Photo de retour" class="w-full h-52 object-cover">
  <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300 flex items-center justify-center">
    <Maximize2 class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-all duration-300" />
  </div>
</div>
        </div>

        <div class="space-y-4">
          <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <History class="w-4 h-4 text-primary-500" />
            Suivi du cycle
          </h4>
          <div v-if="currentAffectation.mouvements?.length > 0" class="space-y-6 relative before:absolute before:left-[7px] before:top-2 before:bottom-2 before:w-[2px] before:bg-slate-100">
            <div v-for="mvt in currentAffectation.mouvements" :key="mvt.id" class="relative pl-8">
              <div 
                class="absolute left-0 top-1.5 w-[16px] h-[16px] rounded-full bg-white border-2"
                :class="mvt.type_mouvement === 'affectation' ? 'border-primary-500' : 'border-emerald-500'"
              ></div>
              <p class="text-xs font-bold text-slate-900">{{ mvt.type_mouvement === 'affectation' ? 'Attribution' : 'Retour' }}</p>
              <p class="text-[10px] text-slate-400 font-medium">
                {{ new Date(mvt.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }} 
                par {{ mvt.user?.name }}
              </p>
            </div>
          </div>
          <div v-else class="space-y-6 relative before:absolute before:left-[7px] before:top-2 before:bottom-2 before:w-[2px] before:bg-slate-100">
            <div class="relative pl-8">
              <div class="absolute left-0 top-1.5 w-[16px] h-[16px] rounded-full bg-white border-2 border-primary-500"></div>
              <p class="text-xs font-bold text-slate-900">Création de l'affectation</p>
              <p class="text-[10px] text-slate-400 font-medium">Matériel livré à l'agent en bon état</p>
            </div>
          </div>
        </div>

      </div>
    </SideModal>


    <!-- Modal Zoom Image -->
<div v-if="showZoomModal" class="fixed inset-0 z-[100] bg-black/90 flex items-center justify-center p-4 animate-in fade-in duration-300">
  <button @click="showZoomModal = false" class="absolute top-6 right-6 text-white hover:text-primary-400 transition-colors">
    <X class="w-8 h-8" />
  </button>
  <img :src="zoomImage" class="max-w-full max-h-full object-contain rounded-2xl shadow-2xl">
</div>
    <SideModal 
      :show="showReviewReturnModal" 
      title="Examiner le retour d'équipement" 
      mode="center"
      @close="showReviewReturnModal = false"
    >
      <div v-if="selectedAffectation" class="space-y-6">
        <div v-if="localError" class="p-4 bg-red-50 border border-red-100 rounded-2xl flex items-start gap-3">
          <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
            <X class="w-3 h-3 text-red-600" />
          </div>
          <p class="text-sm font-medium text-red-600">{{ localError }}</p>
        </div>

        <div class="p-5 bg-amber-50 rounded-2xl border border-amber-100 mb-6">
          <p class="text-[10px] font-bold text-amber-700 uppercase tracking-widest mb-1">Équipement à retourner</p>
          <p class="text-base font-bold text-slate-900">{{ selectedAffectation.equipement?.modele }} ({{ selectedAffectation.equipement?.reference }})</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Date de retour</p>
            <p class="text-sm font-bold text-slate-900">
              {{ selectedAffectation.date_retour ? new Date(selectedAffectation.date_retour).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) : '—' }}
            </p>
          </div>
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">État constaté</p>
            <p class="text-sm font-bold text-slate-900">
              {{ selectedAffectation.etat_retour || '—' }}
            </p>
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Observations de l'agent</label>
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-sm font-medium text-slate-600 italic">
              {{ selectedAffectation.observations || 'Aucune observation enregistrée.' }}
            </p>
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Photo de retour</label>
          <div v-if="selectedAffectation.photo_retour" class="w-full rounded-2xl border border-slate-200 overflow-hidden shadow-sm bg-slate-50 group relative">
            <img 
              :src="selectedAffectation.photo_retour_url" 
              alt="Photo de retour" 
              class="w-full h-52 object-cover"
            />
          </div>
          <div v-else class="p-8 text-center bg-slate-50/50 rounded-3xl border border-dashed border-slate-200">
            <p class="text-sm font-medium text-slate-400 italic">Aucune photo enregistrée</p>
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Motif de rejet (si applicable)</label>
          <textarea 
            v-model="rejectForm.motif_rejet"
            rows="3" 
            placeholder="Expliquer pourquoi le retour est rejeté..."
            class="w-full p-4 bg-white border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-red-500/20 resize-none"
          ></textarea>
        </div>

        <div class="pt-4 flex gap-3">
          <button 
            type="button"
            @click="handleValidateReturn"
            :disabled="submittingValidate || submittingReject"
            class="flex-1 py-3 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 transition-colors shadow-lg shadow-emerald-200 flex items-center justify-center gap-2"
          >
            <Loader2 v-if="submittingValidate" class="w-4 h-4 animate-spin" />
            {{ submittingValidate ? 'Validation...' : 'Valider le retour' }}
          </button>
          <button 
            type="button"
            @click="handleRejectReturn"
            :disabled="submittingValidate || submittingReject"
            class="flex-1 py-3 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700 transition-colors shadow-lg shadow-red-200 flex items-center justify-center gap-2"
          >
            <Loader2 v-if="submittingReject" class="w-4 h-4 animate-spin" />
            {{ submittingReject ? 'Rejet...' : 'Rejeter le retour' }}
          </button>
        </div>
      </div>
    </SideModal>

  </div>
</template>
