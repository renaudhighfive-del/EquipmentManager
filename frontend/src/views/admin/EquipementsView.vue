<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import PageHeader from '../../components/layout/PageHeader.vue';
import SideModal from '../../components/layout/SideModal.vue';
import { useEquipementStore } from '../../stores/equipement';
import api from '../../services/axios';
import { 
  Search, 
  Filter, 
  Plus, 
  QrCode, 
  Package, 
  MapPin, 
  Calendar, 
  User, 
  Activity,
  ChevronRight,
  ShieldCheck,
  AlertTriangle,
  Wrench,
  History,
  Image as ImageIcon,
  X,
  Upload,
  Loader2,
  ArrowLeftRight
} from 'lucide-vue-next';

const equipementStore = useEquipementStore();
const showFiche = ref(false);
const showAddModal = ref(false);
const selectedEquipement = ref(null);
const categories = ref([]);
const isSubmitting = ref(false);

const form = reactive({
  categorie_id: '',
  reference: '',
  numero_serie: '',
  code_inventaire: '',
  marque: '',
  modele: '',
  fournisseur: '',
  date_acquisition: '',
  prix_achat: '',
  garantie_fin: '',
  etat: 'neuf',
  localisation: '',
  notes: '',
});

const photos = ref([]);
const photoPreviews = ref([]);

const onFileChange = (e) => {
  const files = Array.from(e.target.files);
  files.forEach(file => {
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

const resetForm = () => {
  Object.assign(form, {
    categorie_id: '',
    reference: '',
    numero_serie: '',
    code_inventaire: '',
    marque: '',
    modele: '',
    fournisseur: '',
    date_acquisition: '',
    prix_achat: '',
    garantie_fin: '',
    etat: 'neuf',
    localisation: '',
    notes: '',
  });
  photos.value = [];
  photoPreviews.value = [];
};

const fetchCategories = async () => {
  try {
    const response = await api.get('/categories');
    categories.value = response.data;
  } catch (error) {
    console.error('Error fetching categories:', error);
  }
};

onMounted(() => {
  equipementStore.fetchEquipements();
  fetchCategories();
});

const handleSubmit = async () => {
  isSubmitting.value = true;
  try {
    const formData = new FormData();
    Object.keys(form).forEach(key => {
      if (form[key]) formData.append(key, form[key]);
    });
    
    photos.value.forEach((file) => {
      formData.append('images[]', file);
    });

    await equipementStore.createEquipement(formData);
    showAddModal.value = false;
    resetForm();
  } catch (error) {
    console.error('Error creating equipement:', error);
    alert('Une erreur est survenue lors de la création de l\'équipement.');
  } finally {
    isSubmitting.value = false;
  }
};

const getEtatClass = (etat) => {
  switch (etat) {
    case 'neuf': return 'bg-blue-50 text-blue-600';
    case 'en_service': return 'bg-emerald-50 text-emerald-600';
    case 'en_panne': return 'bg-red-50 text-red-600';
    case 'en_maintenance': return 'bg-amber-50 text-amber-600';
    case 'perdu': return 'bg-slate-50 text-slate-600';
    default: return 'bg-slate-50 text-slate-600';
  }
};

const getEtatLabel = (etat) => {
  const labels = {
    neuf: 'Neuf',
    en_service: 'En service',
    en_panne: 'En panne',
    en_maintenance: 'En maintenance',
    en_attente_sinistre: 'En attente sinistre',
    reforme: 'Réformé',
    perdu: 'Perdu'
  };
  return labels[etat] || etat;
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });
};

const openFiche = (equip) => {
  selectedEquipement.value = equip;
  showFiche.value = true;
};
</script>

<template>
  <div class="p-4 sm:p-8 space-y-8 animate-in fade-in duration-500">
    <!-- Page Header -->
    <PageHeader 
      title="Équipements" 
      subtitle="Catalogue complet du parc"
    >
      <template #actions>
        <div class="flex items-center gap-3">
          <button class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
            <QrCode class="w-4 h-4" />
            Scan QR
          </button>
          <button 
            @click="showAddModal = true"
            class="flex items-center gap-2 px-4 py-2 bg-primary-600 rounded-xl text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-lg shadow-primary-200"
          >
            <Plus class="w-4 h-4" />
            Nouvel équipement
          </button>
        </div>
      </template>
    </PageHeader>

    <!-- Filters Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap gap-4 items-center">
      <div class="flex-1 min-w-[200px] relative">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
        <input 
          type="text" 
          placeholder="Rechercher par référence, série..."
          class="w-full h-10 pl-10 pr-4 bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary-500/10 outline-none transition-all"
        >
      </div>
      <select class="h-10 px-4 bg-slate-50 border-none rounded-xl text-sm font-medium text-slate-600 focus:ring-2 focus:ring-primary-500/10 outline-none">
        <option value="">Catégorie</option>
        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nom }}</option>
      </select>
      <select class="h-10 px-4 bg-slate-50 border-none rounded-xl text-sm font-medium text-slate-600 focus:ring-2 focus:ring-primary-500/10 outline-none">
        <option value="">État</option>
        <option value="neuf">Neuf</option>
        <option value="en_service">En service</option>
        <option value="en_panne">En panne</option>
        <option value="en_maintenance">En maintenance</option>
      </select>
      <button class="p-2.5 bg-slate-100 text-slate-500 rounded-xl hover:bg-slate-200 transition-colors">
        <Filter class="w-5 h-5" />
      </button>
    </div>

    <!-- Equipements Grid -->
    <div v-if="equipementStore.loading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div v-for="i in 6" :key="i" class="h-80 bg-slate-100 animate-pulse rounded-3xl"></div>
    </div>
    
    <div v-else-if="equipementStore.error" class="bg-rose-50 border border-rose-100 p-8 rounded-[2rem] text-center">
      <AlertTriangle class="w-12 h-12 text-rose-500 mx-auto mb-4" />
      <h3 class="text-lg font-bold text-rose-900 mb-2">Erreur de chargement</h3>
      <p class="text-rose-600 mb-6">{{ equipementStore.error }}</p>
      <button 
        @click="equipementStore.fetchEquipements()"
        class="px-6 py-2 bg-rose-600 text-white font-bold rounded-xl hover:bg-rose-700 transition-all"
      >
        Réessayer
      </button>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div 
        v-for="equip in equipementStore.equipements" 
        :key="equip.id"
        @click="openFiche(equip)"
        class="group bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-xl hover:border-primary-200 transition-all duration-300 cursor-pointer"
      >
        <!-- Image & Badge -->
        <div class="relative h-48 overflow-hidden bg-slate-100">
          <img 
            :src="equip.images?.[0]?.path ? `http://localhost:8000/storage/${equip.images[0].path}` : 'https://images.unsplash.com/photo-1556656793-062ff9878258?w=400&h=250&fit=crop'" 
            :alt="equip.marque"
            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
          >
          <div class="absolute top-4 left-4">
            <span :class="['px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider shadow-sm', getEtatClass(equip.etat)]">
              {{ getEtatLabel(equip.etat) }}
            </span>
          </div>
        </div>

        <!-- Content -->
        <div class="p-6">
          <div class="flex items-start justify-between mb-4">
            <div>
              <h3 class="font-bold text-slate-900 group-hover:text-primary-600 transition-colors">{{ equip.marque }} {{ equip.modele }}</h3>
              <p class="text-[10px] font-mono font-medium text-slate-400 uppercase tracking-tight">{{ equip.reference }} • {{ equip.numero_serie }}</p>
            </div>
            <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center">
              <Package class="w-4 h-4 text-slate-400" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-y-4">
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400">
                <Filter class="w-3.5 h-3.5" />
              </div>
              <div>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Catégorie</p>
                <p class="text-xs font-bold text-slate-700">{{ equip.categorie?.nom }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400">
                <MapPin class="w-3.5 h-3.5" />
              </div>
              <div>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Localisation</p>
                <p class="text-xs font-bold text-slate-700">{{ equip.localisation || 'Non défini' }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400">
                <Calendar class="w-3.5 h-3.5" />
              </div>
              <div>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Acquisition</p>
                <p class="text-xs font-bold text-slate-700">{{ formatDate(equip.date_acquisition) }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400">
                <User class="w-3.5 h-3.5" />
              </div>
              <div>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Assigné</p>
                <p class="text-xs font-bold text-slate-700">{{ equip.current_affectation ? 'Oui' : 'Non' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Equipement Detail Modal -->
    <SideModal 
      :show="showFiche" 
      title="Fiche Équipement" 
      @close="showFiche = false"
    >
      <div v-if="selectedEquipement" class="space-y-8">
        <!-- Main Info -->
        <div class="relative rounded-3xl overflow-hidden aspect-video bg-slate-100 group shadow-lg">
          <img 
            :src="selectedEquipement.images?.[0]?.path ? `http://localhost:8000/storage/${selectedEquipement.images[0].path}` : 'https://images.unsplash.com/photo-1556656793-062ff9878258?w=400&h=250&fit=crop'" 
            class="w-full h-full object-cover"
          >
          <div class="absolute top-4 left-4">
            <span :class="['px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider shadow-sm', getEtatClass(selectedEquipement.etat)]">
              {{ getEtatLabel(selectedEquipement.etat) }}
            </span>
          </div>
        </div>

        <div>
          <h3 class="text-2xl font-bold text-slate-900">{{ selectedEquipement.marque }} {{ selectedEquipement.modele }}</h3>
          <p class="text-sm font-mono font-medium text-slate-400 uppercase tracking-tight">{{ selectedEquipement.reference }} • {{ selectedEquipement.numero_serie }}</p>
        </div>

        <!-- Details Grid -->
        <div class="grid grid-cols-2 gap-4">
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Catégorie</p>
            <p class="text-sm font-bold text-slate-900">{{ selectedEquipement.categorie?.nom }}</p>
          </div>
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">État actuel</p>
            <p class="text-sm font-bold text-slate-900">{{ getEtatLabel(selectedEquipement.etat) }}</p>
          </div>
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Localisation</p>
            <p class="text-sm font-bold text-slate-900">{{ selectedEquipement.localisation || 'Non défini' }}</p>
          </div>
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Date acquisition</p>
            <p class="text-sm font-bold text-slate-900">{{ formatDate(selectedEquipement.date_acquisition) }}</p>
          </div>
        </div>

        <!-- History Section -->
        <div class="space-y-4">
          <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <History class="w-4 h-4 text-primary-500" />
            Historique complet
          </h4>
          <div class="space-y-6 relative before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[2px] before:bg-slate-100">
            <div v-if="selectedEquipement.current_affectation" class="relative pl-10 group">
              <div class="absolute left-0 top-1.5 w-[24px] h-[24px] rounded-full bg-blue-50 border-2 border-blue-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                <ArrowLeftRight class="w-2.5 h-2.5 text-blue-600" />
              </div>
              <p class="text-sm font-bold text-slate-900">Affecté à {{ selectedEquipement.current_affectation.agent?.nom }}</p>
              <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">{{ formatDate(selectedEquipement.current_affectation.date_affectation) }}</p>
            </div>
            <div v-else class="text-xs text-slate-400 italic">Aucun historique récent disponible</div>
          </div>
        </div>
      </div>
    </SideModal>

    <!-- Add Equipement Modal -->
    <SideModal 
      :show="showAddModal" 
      title="Nouvel Équipement" 
      @close="showAddModal = false"
    >
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Photo Upload Section -->
        <div class="space-y-4">
          <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Photos de l'équipement</label>
          
          <div class="grid grid-cols-3 gap-3">
            <div 
              v-for="(preview, index) in photoPreviews" 
              :key="index"
              class="relative aspect-square rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 group"
            >
              <img :src="preview" class="w-full h-full object-cover">
              <button 
                type="button"
                @click="removePhoto(index)"
                class="absolute top-1 right-1 p-1 bg-black/50 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
              >
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

        <div class="grid grid-cols-1 gap-4">
          <div class="space-y-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Catégorie</label>
            <select v-model="form.categorie_id" required class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-medium focus:ring-2 focus:ring-primary-500/10 outline-none">
              <option value="">Sélectionner une catégorie</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nom }}</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Marque</label>
              <input v-model="form.marque" type="text" required placeholder="ex: Apple" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-medium focus:ring-2 focus:ring-primary-500/10 outline-none">
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Modèle</label>
              <input v-model="form.modele" type="text" required placeholder="ex: iPhone 15" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-medium focus:ring-2 focus:ring-primary-500/10 outline-none">
            </div>
          </div>

          <div class="space-y-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Référence interne</label>
            <input v-model="form.reference" type="text" required placeholder="ex: REF-2024-001" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-medium focus:ring-2 focus:ring-primary-500/10 outline-none">
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">N° de Série</label>
              <input v-model="form.numero_serie" type="text" required placeholder="ex: SN123456" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-medium focus:ring-2 focus:ring-primary-500/10 outline-none">
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Code Inventaire</label>
              <input v-model="form.code_inventaire" type="text" required placeholder="ex: INV-001" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-medium focus:ring-2 focus:ring-primary-500/10 outline-none">
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Date Acquisition</label>
              <input v-model="form.date_acquisition" type="date" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-medium focus:ring-2 focus:ring-primary-500/10 outline-none">
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">État</label>
              <select v-model="form.etat" required class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-medium focus:ring-2 focus:ring-primary-500/10 outline-none">
                <option value="neuf">Neuf</option>
                <option value="en_service">En service</option>
                <option value="en_panne">En panne</option>
                <option value="en_maintenance">En maintenance</option>
              </select>
            </div>
          </div>
          
          <div class="space-y-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Localisation</label>
            <input v-model="form.localisation" type="text" placeholder="ex: Entrepôt A, Bureau 202..." class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-medium focus:ring-2 focus:ring-primary-500/10 outline-none">
          </div>

          <div class="space-y-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Notes</label>
            <textarea v-model="form.notes" rows="3" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-medium focus:ring-2 focus:ring-primary-500/10 outline-none resize-none"></textarea>
          </div>
        </div>

        <div class="flex gap-3 pt-4">
          <button 
            type="button" 
            @click="showAddModal = false"
            class="flex-1 h-12 border border-slate-200 rounded-2xl text-sm font-bold text-slate-600 hover:bg-slate-50 transition-all"
          >
            Annuler
          </button>
          <button 
            type="submit" 
            :disabled="isSubmitting"
            class="flex-1 h-12 bg-primary-600 text-white rounded-2xl text-sm font-black shadow-lg shadow-primary-200 hover:bg-primary-700 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
          >
            <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
            {{ isSubmitting ? 'Création...' : 'Créer l\'équipement' }}
          </button>
        </div>
      </form>
    </SideModal>
  </div>
</template>
