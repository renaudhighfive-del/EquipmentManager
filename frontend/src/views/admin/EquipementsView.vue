<script setup>
import { ref, onMounted, computed, reactive, watch } from 'vue';
import PageHeader from '../../components/layout/PageHeader.vue';
import SideModal from '../../components/layout/SideModal.vue';
import { useEquipementStore } from '../../stores/equipement';
import api from '../../services/axios';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
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
  ArrowLeftRight,
  RotateCcw,
  Edit2,
  Archive,
  Maximize2,
  Tags,
  Trash2,
  Save
} from 'lucide-vue-next';

const equipementStore = useEquipementStore();
const toast = useToast();
const confirm = useConfirm();

const showFiche = ref(false);
const showAddModal = ref(false);
const showEditModal = ref(false);
const showZoomModal = ref(false);
const showCategoriesModal = ref(false);

const selectedEquipement = ref(null);
const zoomImage = ref('');
const categories = ref([]);
const isSubmitting = ref(false);

// Filtres
const searchQuery = ref('');
const selectedCategory = ref('');
const selectedStatus = ref('');

const filteredEquipements = computed(() => {
  return equipementStore.equipements.filter(equip => {
    const matchesSearch = !searchQuery.value || 
      equip.reference?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      equip.numero_serie?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      equip.marque?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      equip.modele?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      equip.code_inventaire?.toLowerCase().includes(searchQuery.value.toLowerCase());
    
    const matchesCategory = !selectedCategory.value || equip.categorie_id == selectedCategory.value;
    const matchesStatus = !selectedStatus.value || equip.etat === selectedStatus.value;
    
    return matchesSearch && matchesCategory && matchesStatus;
  });
});

// Formulaire Catégorie
const categoryForm = reactive({
  id: null,
  nom: '',
  description: '',
  isEditing: false
});

const form = reactive({
  id: null,
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
    id: null,
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
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger les catégories', life: 3000 });
  }
};

const saveCategory = async () => {
  if (!categoryForm.nom) return;
  try {
    if (categoryForm.isEditing) {
      await api.put(`/categories/${categoryForm.id}`, {
        nom: categoryForm.nom,
        description: categoryForm.description
      });
      toast.add({ severity: 'success', summary: 'Succès', detail: 'Catégorie mise à jour', life: 3000 });
    } else {
      await api.post('/categories', {
        nom: categoryForm.nom,
        description: categoryForm.description
      });
      toast.add({ severity: 'success', summary: 'Succès', detail: 'Catégorie créée', life: 3000 });
    }
    resetCategoryForm();
    fetchCategories();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de l\'opération', life: 3000 });
  }
};

const editCategory = (cat) => {
  categoryForm.id = cat.id;
  categoryForm.nom = cat.nom;
  categoryForm.description = cat.description;
  categoryForm.isEditing = true;
};

const deleteCategory = async (id) => {
  confirm.require({
    message: 'Êtes-vous sûr de vouloir supprimer cette catégorie ?',
    header: 'Confirmation de suppression',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await api.delete(`/categories/${id}`);
        toast.add({ severity: 'success', summary: 'Succès', detail: 'Catégorie supprimée', life: 3000 });
        fetchCategories();
      } catch (error) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Suppression impossible (utilisée ?)', life: 3000 });
      }
    }
  });
};

const resetCategoryForm = () => {
  categoryForm.id = null;
  categoryForm.nom = '';
  categoryForm.description = '';
  categoryForm.isEditing = false;
};

watch(() => equipementStore.error, (newError) => {
  if (newError) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: newError, life: 5000 });
  }
});

onMounted(() => {
  equipementStore.fetchEquipements();
  fetchCategories();
});

const handleSubmit = async (isEdit = false) => {
  isSubmitting.value = true;
  try {
    const formData = new FormData();
    Object.keys(form).forEach(key => {
      if (form[key] !== null && form[key] !== undefined) {
        formData.append(key, form[key]);
      }
    });
    
    photos.value.forEach((file) => {
      formData.append('images[]', file);
    });

    if (isEdit) {
      await equipementStore.updateEquipement(form.id, formData);
      toast.add({ severity: 'success', summary: 'Succès', detail: 'Équipement mis à jour', life: 3000 });
      showEditModal.value = false;
    } else {
      await equipementStore.createEquipement(formData);
      toast.add({ severity: 'success', summary: 'Succès', detail: 'Équipement créé avec succès', life: 3000 });
      showAddModal.value = false;
    }
    resetForm();
  } catch (error) {
    console.error('Error handling equipement:', error);
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de l\'opération.', life: 3000 });
  } finally {
    isSubmitting.value = false;
  }
};

const startEdit = (equip) => {
  Object.assign(form, {
    id: equip.id,
    categorie_id: equip.categorie_id,
    reference: equip.reference,
    numero_serie: equip.numero_serie,
    code_inventaire: equip.code_inventaire,
    marque: equip.marque,
    modele: equip.modele,
    fournisseur: equip.fournisseur,
    date_acquisition: equip.date_acquisition ? equip.date_acquisition.split('T')[0] : '',
    prix_achat: equip.prix_achat,
    garantie_fin: equip.garantie_fin ? equip.garantie_fin.split('T')[0] : '',
    etat: equip.etat,
    localisation: equip.localisation,
    notes: equip.notes,
  });
  showEditModal.value = true;
  showFiche.value = false;
};

const handleArchive = async (id) => {
  confirm.require({
    message: 'Êtes-vous sûr de vouloir archiver cet équipement ?',
    header: 'Confirmation d\'archivage',
    acceptClass: 'p-button-warning',
    accept: async () => {
      try {
        await equipementStore.archiveEquipement(id);
        toast.add({ severity: 'success', summary: 'Succès', detail: 'Équipement archivé', life: 3000 });
        showFiche.value = false;
      } catch (error) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de l\'archivage', life: 3000 });
      }
    }
  });
};

const openZoom = (url) => {
  zoomImage.value = url;
  showZoomModal.value = true;
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
    <PageHeader title="Équipements" subtitle="Catalogue complet du parc">
      <template #actions>
        <div class="flex items-center gap-3">
          <button @click="showCategoriesModal = true" class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
            <Tags class="w-4 h-4" />
            Catégories
          </button>
          <button class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
            <QrCode class="w-4 h-4" />
            Scan QR
          </button>
          <button @click="showAddModal = true" class="flex items-center gap-2 px-4 py-2 bg-primary-600 rounded-xl text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-lg shadow-primary-200">
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
        <input v-model="searchQuery" type="text" placeholder="Rechercher par référence, série..." class="w-full h-10 pl-10 pr-4 bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary-500/10 outline-none transition-all">
      </div>
      <select v-model="selectedCategory" class="h-10 px-4 bg-slate-50 border-none rounded-xl text-sm font-medium text-slate-600 focus:ring-2 focus:ring-primary-500/10 outline-none">
        <option value="">Catégories (Toutes)</option>
        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nom }}</option>
      </select>
      <select v-model="selectedStatus" class="h-10 px-4 bg-slate-50 border-none rounded-xl text-sm font-medium text-slate-600 focus:ring-2 focus:ring-primary-500/10 outline-none">
        <option value="">États (Tous)</option>
        <option value="neuf">Neuf</option>
        <option value="en_service">En service</option>
        <option value="en_panne">En panne</option>
        <option value="en_maintenance">En maintenance</option>
      </select>
      <button @click="searchQuery = ''; selectedCategory = ''; selectedStatus = ''" class="p-2.5 bg-slate-100 text-slate-500 rounded-xl hover:bg-slate-200 transition-colors" title="Réinitialiser les filtres">
        <RotateCcw class="w-5 h-5" />
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
      <button @click="equipementStore.fetchEquipements()" class="px-6 py-2 bg-rose-600 text-white font-bold rounded-xl hover:bg-rose-700 transition-all"> Réessayer </button>
    </div>

    <div v-else-if="filteredEquipements.length === 0" class="bg-white p-12 rounded-[2rem] border border-slate-200 text-center">
      <Search class="w-12 h-12 text-slate-300 mx-auto mb-4" />
      <h3 class="text-lg font-bold text-slate-900 mb-2">Aucun équipement trouvé</h3>
      <p class="text-slate-500">Essayez de modifier vos filtres ou votre recherche.</p>
      <button @click="searchQuery = ''; selectedCategory = ''; selectedStatus = ''" class="mt-6 px-6 py-2 text-primary-600 font-bold hover:bg-primary-50 rounded-xl transition-all">
        Effacer les filtres
      </button>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div v-for="equip in filteredEquipements" :key="equip.id" @click="openFiche(equip)" class="group bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-xl hover:border-primary-200 transition-all duration-300 cursor-pointer">
        <!-- Image & Badge -->
        <div class="relative h-48 overflow-hidden bg-slate-100">
          <img :src="equip.images?.[0]?.path ? `http://localhost:8000/storage/${equip.images[0].path}` : 'https://images.unsplash.com/photo-1556656793-062ff9878258?w=400&h=250&fit=crop'" :alt="equip.marque" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
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

    <!-- Categories Management Modal -->
    <SideModal :show="showCategoriesModal" title="Gestion des Catégories" @close="showCategoriesModal = false; resetCategoryForm()">
      <div class="space-y-8">
        <!-- Category Form -->
        <div class="bg-slate-50 p-6 rounded-[2rem] border border-slate-100 space-y-4">
          <h4 class="text-sm font-black uppercase tracking-widest text-slate-900">
            {{ categoryForm.isEditing ? 'Modifier la catégorie' : 'Nouvelle catégorie' }}
          </h4>
          <div class="space-y-3">
            <input v-model="categoryForm.nom" type="text" placeholder="Nom de la catégorie (ex: PDA)" class="w-full h-12 px-4 bg-white border border-slate-200 rounded-2xl text-sm font-bold outline-none focus:ring-2 focus:ring-primary-500/10">
            <textarea v-model="categoryForm.description" placeholder="Description..." rows="2" class="w-full p-4 bg-white border border-slate-200 rounded-2xl text-sm font-medium outline-none focus:ring-2 focus:ring-primary-500/10 resize-none"></textarea>
            <div class="flex gap-2">
              <button @click="saveCategory" :disabled="!categoryForm.nom" class="flex-1 h-12 bg-primary-600 text-white rounded-2xl text-sm font-black shadow-lg shadow-primary-200 hover:bg-primary-700 transition-all disabled:opacity-50 flex items-center justify-center gap-2">
                <Save class="w-4 h-4" /> {{ categoryForm.isEditing ? 'Mettre à jour' : 'Ajouter' }}
              </button>
              <button v-if="categoryForm.isEditing" @click="resetCategoryForm" class="px-6 h-12 bg-white border border-slate-200 text-slate-600 rounded-2xl text-sm font-bold hover:bg-slate-50">
                Annuler
              </button>
            </div>
          </div>
        </div>

        <!-- Categories List -->
        <div class="space-y-4">
          <h4 class="text-sm font-black uppercase tracking-widest text-slate-400">Liste des catégories</h4>
          <div class="space-y-3">
            <div v-for="cat in categories" :key="cat.id" class="p-4 bg-white border border-slate-100 rounded-2xl flex items-center justify-between group hover:border-primary-200 transition-all">
              <div>
                <p class="font-bold text-slate-900">{{ cat.nom }}</p>
                <p class="text-xs text-slate-400 font-medium">{{ cat.description || 'Pas de description' }}</p>
              </div>
              <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <button @click="editCategory(cat)" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all">
                  <Edit2 class="w-4 h-4" />
                </button>
                <button @click="deleteCategory(cat.id)" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-all">
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </SideModal>

    <!-- Equipement Detail Modal -->
    <SideModal :show="showFiche" title="Fiche Équipement" @close="showFiche = false">
      <div v-if="selectedEquipement" class="space-y-8">
        <!-- Actions Toolbar -->
        <div class="flex items-center gap-2 pb-4 border-b border-slate-100">
          <button @click="startEdit(selectedEquipement)" class="flex-1 flex items-center justify-center gap-2 h-10 bg-slate-50 text-slate-600 rounded-xl text-xs font-bold hover:bg-slate-100 transition-all">
            <Edit2 class="w-3.5 h-3.5" /> Modifier
          </button>
          <button @click="handleArchive(selectedEquipement.id)" class="flex-1 flex items-center justify-center gap-2 h-10 bg-rose-50 text-rose-600 rounded-xl text-xs font-bold hover:bg-rose-100 transition-all">
            <Archive class="w-3.5 h-3.5" /> Archiver
          </button>
        </div>

        <!-- Photo Gallery -->
        <div class="space-y-3">
          <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Galerie Photos</label>
          <div class="grid grid-cols-2 gap-3">
            <div v-for="(img, idx) in selectedEquipement.images" :key="idx" class="relative group aspect-video rounded-2xl overflow-hidden bg-slate-100 border border-slate-200">
              <img :src="`http://localhost:8000/storage/${img.path}`" class="w-full h-full object-cover">
              <button @click="openZoom(`http://localhost:8000/storage/${img.path}`)" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                <Maximize2 class="w-6 h-6" />
              </button>
            </div>
            <div v-if="!selectedEquipement.images?.length" class="col-span-2 py-8 bg-slate-50 rounded-2xl border border-dashed border-slate-200 flex flex-col items-center justify-center text-slate-400">
              <ImageIcon class="w-8 h-8 mb-2" />
              <p class="text-xs font-medium">Aucune photo disponible</p>
            </div>
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
            <History class="w-4 h-4 text-primary-500" /> Historique complet
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

    <!-- Modal Zoom Image -->
    <div v-if="showZoomModal" class="fixed inset-0 z-[100] bg-black/90 flex items-center justify-center p-4 animate-in fade-in duration-300">
      <button @click="showZoomModal = false" class="absolute top-6 right-6 text-white hover:text-primary-400 transition-colors">
        <X class="w-8 h-8" />
      </button>
      <img :src="zoomImage" class="max-w-full max-h-full object-contain rounded-2xl shadow-2xl">
    </div>

    <!-- Add/Edit Equipement Modal -->
    <SideModal :show="showAddModal || showEditModal" :title="showEditModal ? 'Modifier Équipement' : 'Nouvel Équipement'" @close="showAddModal = false; showEditModal = false; resetForm()">
      <form @submit.prevent="handleSubmit(showEditModal)" class="space-y-6">
        <!-- Photo Upload Section -->
        <div class="space-y-4">
          <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Photos de l'équipement</label>
          <div class="grid grid-cols-3 gap-3">
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
          <button type="button" @click="showAddModal = false; showEditModal = false; resetForm()" class="flex-1 h-12 border border-slate-200 rounded-2xl text-sm font-bold text-slate-600 hover:bg-slate-50 transition-all"> Annuler </button>
          <button type="submit" :disabled="isSubmitting" class="flex-1 h-12 bg-primary-600 text-white rounded-2xl text-sm font-black shadow-lg shadow-primary-200 hover:bg-primary-700 transition-all disabled:opacity-50 flex items-center justify-center gap-2">
            <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
            {{ isSubmitting ? (showEditModal ? 'Mise à jour...' : 'Création...') : (showEditModal ? 'Mettre à jour' : 'Créer l\'équipement') }}
          </button>
        </div>
      </form>
    </SideModal>
  </div>
</template>
