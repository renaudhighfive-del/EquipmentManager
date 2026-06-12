<script setup>
import { onMounted, ref, reactive } from 'vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import SideModal from '../../components/layout/SideModal.vue'
import { Smartphone, RotateCcw, AlertOctagon, Loader2, AlertTriangle, Upload, X, CheckCircle, Calendar } from 'lucide-vue-next'
import { useAffectationStore } from '../../stores/affectation'
import { useEquipementStore } from '../../stores/equipement'
import { usePanneStore } from '../../stores/panne'
import { useSinistreStore } from '../../stores/sinistre'
import { useToast } from 'primevue/usetoast'
// import { formatDate } from '../../utils/date'

const equipementStore = useEquipementStore();
const panneStore = usePanneStore();
const sinistreStore = useSinistreStore();
const affectationStore = useAffectationStore();
const toast = useToast();

const showIncidentModal = ref(false);
const selectedEquipement = ref(null);
const isSubmitting = ref(false);
const incidentType = ref("panne");

const showReturnModal = ref(false);
const selectedAffectationForReturn = ref(null);
const returnForm = reactive({
  date_retour: new Date().toISOString().split('T')[0],
  etat_retour: 'Bon état',
  observations: ''
});
const returnPhotos = ref([]);
const returnPhotoPreviews = ref([]);

const form = reactive({
  type: "panne",
  gravite: "moyenne",
  description: "",
});

const formatDate = (dateString) => {
  if (!dateString) return "";
  const date = new Date(dateString);
  return date.toLocaleDateString("fr-FR", {
    day: "2-digit",
    month: "long",
    year: "numeric",
  });
};

const photos = ref([]);
const photoPreviews = ref([]);

onMounted(() => {
  equipementStore.fetchEquipements();
});

const openIncidentModal = (equip) => {
  selectedEquipement.value = equip;
  form.type = "panne";
  incidentType.value = "panne";
  form.gravite = "moyenne";
  form.description = "";
  photos.value = [];
  photoPreviews.value = [];
  showIncidentModal.value = true;
};

const onFileChange = (e) => {
  const files = Array.from(e.target.files);
  files.forEach((file) => {
    photos.value.push(file);
    const reader = new FileReader();
    reader.onload = (e) => {
      photoPreviews.value.push(e.target.result);
    };
    reader.readAsDataURL(file);
  });
};

const removePhoto = (index) => {
  photos.value.splice(index, 1);
  photoPreviews.value.splice(index, 1);
};

const handleDeclareIncident = async () => {
  if (!form.description) {
    toast.add({
      severity: "warn",
      summary: "Attention",
      detail: "Veuillez fournir une description",
      life: 3000,
    });
    return;
  }

  isSubmitting.value = true;
  try {
    if (incidentType.value === "panne") {
      const formData = new FormData();
      formData.append("equipement_id", selectedEquipement.value.id);
      formData.append("gravite", form.gravite);
      formData.append("description", form.description);
      photos.value.forEach((file) => {
        formData.append("images[]", file);
      });
      await panneStore.createPanne(formData);
    } else {
      await sinistreStore.declareSinistre({
        equipement_id: selectedEquipement.value.id,
        type: incidentType.value,
        description: form.description,
      });
    }
    toast.add({
      severity: "success",
      summary: "Succès",
      detail: "Déclaration envoyée avec succès",
      life: 3000,
    });
    showIncidentModal.value = false;
    equipementStore.fetchEquipements();
  } catch (error) {
    toast.add({
      severity: "error",
      summary: "Erreur",
      detail: "Échec de l'envoi de la déclaration",
      life: 3000,
    });
  } finally {
    isSubmitting.value = false;
  }
};

const openReturnModal = (equip) => {
  selectedAffectationForReturn.value = equip.current_affectation;
  returnForm.date_retour = new Date().toISOString().split('T')[0];
  returnForm.etat_retour = 'Bon état';
  returnForm.observations = '';
  returnPhotos.value = [];
  returnPhotoPreviews.value = [];
  showReturnModal.value = true;
};

const onReturnFileChange = (e) => {
  const files = Array.from(e.target.files)
  files.forEach(file => {
    returnPhotos.value.push(file)
    const reader = new FileReader()
    reader.onload = (e) => {
      returnPhotoPreviews.value.push(e.target.result)
    }
    reader.readAsDataURL(file)
  })
}

const removeReturnPhoto = (index) => {
  returnPhotos.value.splice(index, 1)
  returnPhotoPreviews.value.splice(index, 1)
}

const handleRequestReturn = async () => {
  if (returnPhotos.value.length === 0) {
    toast.add({ severity: 'warn', summary: 'Attention', detail: 'Veuillez fournir une photo de retour', life: 3000 });
    return;
  }

  isSubmitting.value = true;
  try {
    const formData = new FormData();
    formData.append('date_retour', returnForm.date_retour);
    formData.append('etat_retour', returnForm.etat_retour);
    formData.append('observations', returnForm.observations);
    returnPhotos.value.forEach(file => {
      formData.append('photo_retour', file);
    });
    
    await affectationStore.requestReturnAffectation(selectedAffectationForReturn.value.id, formData);
    
    toast.add({ severity: 'success', summary: 'Succès', detail: 'Demande de retour envoyée', life: 3000 });
    showReturnModal.value = false;
    equipementStore.fetchEquipements();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de l\'envoi de la demande', life: 3000 });
  } finally {
    isSubmitting.value = false;
  }
};

const getEtatClass = (etat) => {
  switch (etat) {
    case "en_service":
      return "bg-emerald-50 text-emerald-600";
    case "repare":
      return "bg-emerald-50 text-emerald-600 border border-emerald-200";
    case "en_panne":
      return "bg-red-50 text-red-600";
    case "en_attente_sinistre":
      return "bg-orange-50 text-orange-600";
    default:
      return "bg-slate-50 text-slate-600";
  }
};

const getEtatLabel = (etat) => {
  const labels = {
    neuf: "Neuf",
    en_service: "En service",
    repare: "Réparé",
    en_panne: "En panne",
    en_maintenance: "En maintenance",
    en_attente_sinistre: "En attente sinistre",
    reforme: "Réformé",
  };
  return labels[etat] || etat;
};
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <PageHeader
      title="Mes équipements"
      subtitle="Équipements actuellement affectés à votre compte"
    />

    <div v-if="equipementStore.loading" class="space-y-4">
      <div
        v-for="i in 2"
        :key="i"
        class="h-24 bg-slate-100 animate-pulse rounded-2xl"
      ></div>
    </div>

    <div v-else class="space-y-4">
      <div v-for="equip in equipementStore.equipements" :key="equip.id"
        class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:shadow-md transition-all">
        <div class="flex items-center gap-4 sm:gap-5">
          <div
            class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400">
            <Smartphone class="w-6 h-6 sm:w-7 sm:h-7" />
          </div>
          <div>
            <p class="font-bold text-slate-900">
              {{ equip.marque }} {{ equip.modele }}
            </p>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1">
              <span class="text-xs text-slate-500 font-medium">{{ equip.reference }}</span>
              <span v-if="equip.current_affectation" class="flex items-center gap-1 text-[10px] text-primary-600 font-bold bg-primary-50 px-2 py-0.5 rounded-md">
                <Calendar class="w-3 h-3" />
                Depuis le {{ formatDate(equip.current_affectation.date_affectation) }}
              </span>
            </div>
          </div>
        </div>
        <div class="flex items-center justify-between sm:justify-end gap-4 border-t sm:border-t-0 pt-4 sm:pt-0">
          <span :class="['px-3 py-1 rounded-lg text-[10px] font-black uppercase', getEtatClass(equip.etat)]">
            {{ getEtatLabel(equip.etat) }}
          </span>
          <div class="flex items-center gap-2">
            <button v-if="equip.current_affectation && equip.current_affectation.statut === 'confirmee'"
              @click.stop="openReturnModal(equip)"
              class="p-2.5 text-primary-500 hover:bg-primary-50 rounded-xl transition-all" title="Demander le retour">
              <CheckCircle class="w-5 h-5" />
            </button>
            <button @click="openIncidentModal(equip)"
              class="p-2.5 text-rose-500 hover:bg-rose-50 rounded-xl transition-all" title="Signaler un incident">
              <AlertTriangle class="w-5 h-5" />
            </button>

          </div>
        </div>
      </div>
    </div>

    <div v-if="equipementStore.equipements.length === 0"
      class="text-center py-12 bg-slate-50 rounded-3xl border border-dashed border-slate-200">
      <Smartphone class="w-12 h-12 text-slate-300 mx-auto mb-4" />
      <p class="text-slate-500 font-medium">Aucun équipement affecté pour le moment.</p>
    </div>
  </div>

  <!-- Modal Déclaration Incident (Fusionné) -->
  <SideModal :show="showIncidentModal" title="Signaler un incident" @close="showIncidentModal = false">
    <div v-if="selectedEquipement" class="space-y-6">
      <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl">
        <p class="text-sm text-slate-600 font-medium">
          Équipement concerné :
          <span class="font-black text-slate-900">{{ selectedEquipement.marque }} {{ selectedEquipement.modele }} ({{
            selectedEquipement.reference }})</span>
        </p>
      </div>

      <!-- Type d'incident -->
      <div class="space-y-3">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Type d'incident</label>
        <div class="grid grid-cols-2 gap-3">
          <button type="button" @click="incidentType = 'panne'"
            :class="['p-4 rounded-2xl border-2 transition-all text-left flex flex-col gap-2', incidentType === 'panne' ? 'border-primary-600 bg-primary-50/50' : 'border-slate-100 hover:border-slate-200']">
            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
              <AlertTriangle class="w-6 h-6" />
            </div>
            <div>
              <p class="text-sm font-black text-slate-900">Une panne</p>
              <p class="text-[10px] text-slate-500 font-medium">Problème technique</p>
            </div>
          </button>

          <button type="button" @click="incidentType = 'perte'"
            :class="['p-4 rounded-2xl border-2 transition-all text-left flex flex-col gap-2', ['perte', 'vol', 'casse'].includes(incidentType) ? 'border-rose-600 bg-rose-50/50' : 'border-slate-100 hover:border-slate-200']">
            <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center">
              <AlertOctagon class="w-6 h-6" />
            </div>
            <div>
              <p class="text-sm font-black text-slate-900">Un sinistre</p>
              <p class="text-[10px] text-slate-500 font-medium">Perte, vol ou casse</p>
            </div>
          </button>
        </div>
      </div>

      <!-- Sous-type pour Sinistre -->
      <div v-if="['perte', 'vol', 'casse'].includes(incidentType)"
        class="space-y-3 animate-in slide-in-from-top-2 duration-300">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Type de sinistre</label>
        <div class="flex gap-2">
          <button v-for="type in ['perte', 'vol', 'casse']" :key="type" type="button" @click="incidentType = type"
            :class="['flex-1 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider border transition-all', incidentType === type ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-500 border-slate-200']">
            {{ type }}
          </button>
        </div>
      </div>

      <!-- Photo Upload (Uniquement pour Pannes) -->
      <div v-if="incidentType === 'panne'" class="space-y-4 animate-in slide-in-from-top-2 duration-300">
        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Photos du problème
          (facultatif)</label>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
          <div v-for="(preview, index) in photoPreviews" :key="index"
            class="relative aspect-square rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 group">
            <img :src="preview" class="w-full h-full object-cover" />
            <button type="button" @click="removePhoto(index)"
              class="absolute top-1 right-1 p-1 bg-black/50 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
              <X class="w-3 h-3" />
            </button>
          </div>
          <label
            class="relative aspect-square rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center gap-2 cursor-pointer hover:border-primary-400 hover:bg-primary-50 transition-all text-slate-400 hover:text-primary-600">
            <input type="file" multiple accept="image/*" class="hidden" @change="onFileChange" />
            <Upload class="w-6 h-6" />
            <span class="text-[10px] font-bold">Ajouter</span>
          </label>
        </div>
      </div>

      <div class="space-y-5">
        <div v-if="incidentType === 'panne'" class="space-y-1.5 animate-in slide-in-from-top-2 duration-300">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Gravité de la panne</label>
          <select v-model="form.gravite"
            class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-primary-500/20">
            <option value="faible">Faible (fonctionne encore)</option>
            <option value="moyenne">Moyenne (gênant)</option>
            <option value="critique">Critique (inutilisable)</option>
          </select>
        </div>

        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Description & Circonstances</label>
          <textarea v-model="form.description" required rows="4"
            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-primary-500/20 resize-none"
            placeholder="Décrivez précisément ce qu'il s'est passé..."></textarea>
        </div>
      </div>

      <button @click="handleDeclareIncident" :disabled="isSubmitting"
        class="w-full h-12 bg-primary-600 text-white rounded-xl text-sm font-black shadow-lg shadow-primary-200 hover:bg-primary-700 transition-all disabled:opacity-50 flex items-center justify-center gap-2">
        <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
        {{ isSubmitting ? 'Envoi en cours...' : 'Confirmer la déclaration' }}
      </button>
    </div>
  </SideModal>

  <!-- Modal Demande de Retour -->
  <SideModal :show="showReturnModal" title="Demander le retour d'équipement" @close="showReturnModal = false">
    <div v-if="selectedAffectationForReturn" class="space-y-6">
      <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl">
        <p class="text-sm text-slate-600 font-medium">
          Équipement concerné :
          <span class="font-black text-slate-900">{{ selectedAffectationForReturn.equipement?.marque }} {{ selectedAffectationForReturn.equipement?.modele }}</span>
        </p>
      </div>

      <div class="space-y-2">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Date de retour</label>
        <input
          type="date"
          v-model="returnForm.date_retour"
          :max="new Date().toISOString().split('T')[0]"
          required
          class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-primary-500/20"
        >
      </div>

      <div class="space-y-2">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">État constaté</label>
        <select v-model="returnForm.etat_retour" class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-primary-500/20">
          <option value="Bon état">Bon état</option>
          <option value="Rayé">Rayé</option>
          <option value="Abîmé">Abîmé</option>
        </select>
      </div>

      <div class="space-y-2">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Photo de retour (obligatoire)</label>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
          <div v-for="(preview, index) in returnPhotoPreviews" :key="index" class="relative aspect-square rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 group">
            <img :src="preview" class="w-full h-full object-cover" />
            <button type="button" @click="removeReturnPhoto(index)" class="absolute top-1 right-1 p-1 bg-black/50 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
              <X class="w-3 h-3" />
            </button>
          </div>
          <label class="relative aspect-square rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center gap-2 cursor-pointer hover:border-primary-400 hover:bg-primary-50 transition-all text-slate-400 hover:text-primary-600">
            <input type="file" accept="image/*" class="hidden" @change="onReturnFileChange" />
            <Upload class="w-6 h-6" />
            <span class="text-[10px] font-bold">Ajouter</span>
          </label>
        </div>
      </div>

      <div class="space-y-2">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Observations</label>
        <textarea
          v-model="returnForm.observations"
          rows="3"
          class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-primary-500/20 resize-none"
          placeholder="Notes éventuelles..."
        ></textarea>
      </div>

      <button @click="handleRequestReturn" :disabled="isSubmitting" class="w-full h-12 bg-primary-600 text-white rounded-xl text-sm font-black shadow-lg shadow-primary-200 hover:bg-primary-700 transition-all disabled:opacity-50 flex items-center justify-center gap-2">
        <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
        {{ isSubmitting ? 'Envoi en cours...' : 'Confirmer la demande de retour' }}
      </button>
    </div>
  </SideModal>
</template>
