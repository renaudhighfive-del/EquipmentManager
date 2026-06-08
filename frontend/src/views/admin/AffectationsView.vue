<script setup>
import { ref, onMounted } from 'vue';
import PageHeader from '../../components/layout/PageHeader.vue';
import SideModal from '../../components/layout/SideModal.vue';
import api from '../../services/axios';
import { useAffectationStore } from '../../stores/affectation';
import { storeToRefs } from 'pinia';
import { 
  Plus, 
  ArrowLeftRight, 
  History,
  CheckCircle2,
  Clock,
  RotateCcw,
  Camera,
  Search,
  Filter,
  Loader2,
  X,
  User,
  Briefcase,
  Smartphone,
  ChevronRight
} from 'lucide-vue-next';

const affectationStore = useAffectationStore();
const { affectations, loading, error: storeError ,currentAffectation } = storeToRefs(affectationStore);

const showReturnModal = ref(false);
const showCreateModal = ref(false);
const selectedAffectation = ref(null);
const submitting = ref(false);

// Refs pour la photo de remise (création)
const photoInput = ref(null);
const photoPreview = ref(null);
const photoFile = ref(null);

// Refs pour la photo de retour
const returnPhotoInput = ref(null);
const returnPhotoPreview = ref(null);
const returnPhotoFile = ref(null);

const localError = ref(null);

//Pour le voir detail
const showFiche=ref(false);

const availableEquipements = ref([]);
const agents = ref([]);

const newAffectation = ref({
  equipement_id: '',
  agent_id: '',
  date_affectation: new Date().toISOString().split('T')[0],
  observations: ''
});

const returnForm = ref({
  date_retour: new Date().toISOString().split('T')[0],
  etat_retour: 'Bon état',
  observations: ''
});

const triggerFileInput = () => {
  photoInput.value.click();
};

const triggerReturnFileInput = () => {
  returnPhotoInput.value.click();
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

const fetchInitialData = async () => {
  try {
    const [equipRes, agentRes] = await Promise.all([
      api.get('/equipements'),
      api.get('/agents')
    ]);
    // On accepte uniquement 'neuf' comme état disponible pour une nouvelle affectation
    availableEquipements.value = equipRes.data.data.filter(e => e.etat === 'neuf');
    agents.value = agentRes.data.data;
    
    console.log('Données initiales chargées :', {
      equipements: availableEquipements.value.length,
      agents: agents.value.length
    });
  } catch (error) {
    console.error('Erreur lors du chargement des données initiales', error);
  }
};

onMounted(() => {
  affectationStore.fetchAffectations();
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

const submitReturn = async () => {
  localError.value = null;
  if (!returnPhotoFile.value) {
    localError.value = 'La photo de retour est obligatoire.';
    return;
  }

  try {
    submitting.value = true;
    
    const formData = new FormData();
    formData.append('_method', 'PATCH'); // Pour simuler PATCH via POST
    formData.append('date_retour', returnForm.value.date_retour);
    formData.append('etat_retour', returnForm.value.etat_retour);
    formData.append('observations', returnForm.value.observations || '');
    formData.append('photo_retour', returnPhotoFile.value);

    const success = await affectationStore.returnAffectation(selectedAffectation.value.id, formData);
    if (success) {
      showReturnModal.value = false;
    } else {
      localError.value = storeError.value;
    }
  } catch (error) {
    localError.value = "Une erreur est survenue lors du retour.";
    console.error(error);
  } finally {
    submitting.value = false;
  }
};

const openFiche = async (id) => {
  showFiche.value = true;
  await affectationStore.fetchAffectationById(id);
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
      localError.value = storeError.value;
    }
  } catch (error) {
    localError.value = "Une erreur inattendue est survenue.";
    console.error('Erreur lors de la création de l\'affectation', error);
  } finally {
    submitting.value = false;
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
            <th class="px-6 py-5">Date</th>
            <th class="px-6 py-5">Statut</th>
            <th class="px-8 py-5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <tr v-for="aff in affectations" :key="aff.id" class="hover:bg-slate-50/50 transition-colors group">
            <td class="px-8 py-5">
              <div>
                <p class="text-sm font-bold text-slate-900">{{ aff.equipement?.reference }}</p>
                <p class="text-xs text-slate-500">{{ aff.equipement?.modele }} {{ aff.equipement?.marque }}</p>
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
            <td class="px-6 py-5 text-sm text-slate-500 font-medium">
              {{ new Date(aff.date_affectation).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}
            </td>
            <td class="px-6 py-5">
              <span 
                class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight"
                :class="{
                  'bg-blue-50 text-blue-600': aff.statut === 'en_cours',
                  'bg-slate-100 text-slate-500': aff.statut === 'retourne',
                  'bg-purple-50 text-purple-600': aff.statut === 'renouvele'
                }"
              >
                {{ aff.statut === 'en_cours' ? 'En cours' : (aff.statut === 'retourne' ? 'Retourné' : 'Renouvelé') }}
              </span>
            </td>
            <td class="px-8 py-5 text-right">
              <div class="flex items-center justify-end gap-2">
                <button 
                  @click="openFiche(aff.id)"
                  class="text-xs font-bold text-slate-600 hover:text-primary-600 transition-colors py-2 px-3 rounded-lg hover:bg-primary-50"
                >
                  Détails
                </button>
                
                <button 
                  v-if="aff.statut === 'en_cours'"
                  @click="openReturnModal(aff)"
                  class="text-xs font-bold text-primary-600 hover:text-primary-700 bg-primary-50 px-4 py-2 rounded-lg transition-colors"
                >
                  Retour
                </button>
                <span v-else class="text-xs font-bold text-slate-300 px-2 py-2">Clôturé</span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
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
            <p v-if="currentAffectation.affecte_par" class="mt-2 text-[10px] text-slate-400 font-medium">
              Enregistré par : {{ currentAffectation.affecte_par?.name }}
            </p>
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

        <div class="space-y-4">
          <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <Camera class="w-4 h-4 text-primary-500" />
            Photo justificative de remise
          </h4>
          
          <div v-if="!currentAffectation.photo_remise" class="p-8 text-center bg-slate-50/50 rounded-3xl border border-dashed border-slate-200">
            <p class="text-sm font-medium text-slate-400 italic">Aucune photo enregistrée</p>
          </div>
          
          <div v-else class="w-full rounded-2xl border border-slate-200 overflow-hidden shadow-sm bg-slate-50 group relative">
            <img 
              :src="currentAffectation.photo_remise_url" 
              alt="Photo de remise" 
              class="w-full h-52 object-cover"
            />
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
          
          <div v-else class="w-full rounded-2xl border border-slate-200 overflow-hidden shadow-sm bg-slate-50 group relative">
            <img 
              :src="currentAffectation.photo_retour_url" 
              alt="Photo de retour" 
              class="w-full h-52 object-cover"
            />
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

  </div>
</template>
