<script setup>
import { ref, onMounted, computed } from 'vue';
import PageHeader from '../../components/layout/PageHeader.vue';
import { useEquipementStore } from '../../stores/equipement';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { 
  Archive, 
  RotateCcw, 
  Search, 
  AlertTriangle, 
  Loader2, 
  Smartphone,
  Calendar,
  Tag,
  Hash
} from 'lucide-vue-next';

const equipementStore = useEquipementStore();
const toast = useToast();
const confirm = useConfirm();

const archives = ref([]);
const loading = ref(false);
const searchQuery = ref('');

const fetchArchives = async () => {
  loading.value = true;
  try {
    archives.value = await equipementStore.fetchArchives();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger les archives', life: 3000 });
  } finally {
    loading.value = false;
  }
};

onMounted(fetchArchives);

const filteredArchives = computed(() => {
  if (!searchQuery.value) return archives.value;
  const q = searchQuery.value.toLowerCase();
  return archives.value.filter(e => 
    e.reference?.toLowerCase().includes(q) ||
    e.marque?.toLowerCase().includes(q) ||
    e.modele?.toLowerCase().includes(q) ||
    e.numero_serie?.toLowerCase().includes(q)
  );
});

const handleUnarchive = (equip) => {
  confirm.require({
    message: `Voulez-vous vraiment restaurer l'équipement ${equip.marque} ${equip.modele} ?`,
    header: 'Confirmation de désarchivage',
    icon: 'pi pi-refresh',
    acceptClass: 'p-button-primary',
    accept: async () => {
      try {
        await equipementStore.unarchiveEquipement(equip.id);
        toast.add({ severity: 'success', summary: 'Succès', detail: 'Équipement restauré avec succès', life: 3000 });
        // Retirer de la liste locale
        archives.value = archives.value.filter(a => a.id !== equip.id);
      } catch (error) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de la restauration', life: 3000 });
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
    <PageHeader title="Archives" subtitle="Consultez et restaurez les équipements archivés" />

    <!-- Filters -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
      <div class="relative flex-1 max-w-md">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Rechercher une archive..." 
          class="w-full h-10 pl-10 pr-4 bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary-500/10 outline-none transition-all"
        >
      </div>
      <div v-if="loading" class="flex items-center gap-2 text-slate-400">
        <Loader2 class="w-4 h-4 animate-spin" />
        <span class="text-xs font-medium">Chargement...</span>
      </div>
    </div>

    <!-- Content -->
    <div v-if="loading && archives.length === 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div v-for="i in 3" :key="i" class="h-64 bg-slate-100 animate-pulse rounded-3xl"></div>
    </div>

    <div v-else-if="filteredArchives.length === 0" class="bg-white p-16 rounded-[2rem] border border-slate-200 shadow-sm text-center">
      <Archive class="w-16 h-16 text-slate-200 mx-auto mb-4" />
      <h3 class="text-xl font-black text-slate-900 mb-2">Aucune archive trouvée</h3>
      <p class="text-slate-500 font-medium">Les équipements archivés apparaîtront ici.</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div 
        v-for="equip in filteredArchives" 
        :key="equip.id"
        class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden group hover:shadow-xl transition-all duration-300"
      >
        <!-- Header -->
        <div class="p-6 border-b border-slate-50">
          <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400">
              <Smartphone class="w-6 h-6" />
            </div>
            <button 
              @click="handleUnarchive(equip)"
              class="p-2.5 bg-primary-50 text-primary-600 rounded-xl hover:bg-primary-600 hover:text-white transition-all shadow-sm"
              title="Restaurer l'équipement"
            >
              <RotateCcw class="w-5 h-5" />
            </button>
          </div>
          <h3 class="text-lg font-black text-slate-900">{{ equip.marque }} {{ equip.modele }}</h3>
          <p class="text-xs font-mono font-bold text-slate-400 uppercase tracking-wider">{{ equip.reference }}</p>
        </div>

        <!-- Info Grid -->
        <div class="p-6 bg-slate-50/50 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <div class="flex items-center gap-1.5 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                <Tag class="w-3 h-3" />
                Catégorie
              </div>
              <p class="text-sm font-bold text-slate-700">{{ equip.categorie?.nom || 'N/A' }}</p>
            </div>
            <div class="space-y-1">
              <div class="flex items-center gap-1.5 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                <Hash class="w-3 h-3" />
                Série
              </div>
              <p class="text-sm font-bold text-slate-700 truncate">{{ equip.numero_serie }}</p>
            </div>
          </div>

          <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Calendar class="w-3.5 h-3.5 text-slate-400" />
              <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Archivé le {{ formatDate(equip.updated_at) }}</span>
            </div>
            <span class="px-3 py-1 bg-slate-200 text-slate-600 text-[10px] font-black uppercase rounded-lg">
              Archivé
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
