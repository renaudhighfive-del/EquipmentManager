<script setup>
import { ref } from 'vue';
import PageHeader from '../../components/layout/PageHeader.vue';
import SideModal from '../../components/layout/SideModal.vue';
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
  History
} from 'lucide-vue-next';

const showFiche = ref(false);
const selectedEquipement = ref(null);

const equipements = [
  { 
    id: 1, 
    name: 'Zebra Zebra Pro 0', 
    ref: 'REF-80000', 
    sn: 'SN-IAJEGYLB', 
    categorie: 'PDA', 
    etat: 'Neuf', 
    localisation: 'Paris', 
    acquisition: '30 août 2025',
    assigne: 'Oui',
    image: 'https://images.unsplash.com/photo-1556656793-062ff9878258?w=400&h=250&fit=crop',
    colorClass: 'bg-blue-500',
    etatClass: 'bg-blue-50 text-blue-600'
  },
  { 
    id: 2, 
    name: 'Samsung Samsung X 1', 
    ref: 'REF-80001', 
    sn: 'SN-X0DS886B', 
    categorie: 'Smartphone', 
    etat: 'En service', 
    localisation: 'Lyon', 
    acquisition: '07 déc. 2025',
    assigne: 'Non',
    image: 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&h=250&fit=crop',
    colorClass: 'bg-emerald-500',
    etatClass: 'bg-emerald-50 text-emerald-600'
  },
  { 
    id: 3, 
    name: 'Apple Apple Max 2', 
    ref: 'REF-80002', 
    sn: 'SN-ZV5TSUQY', 
    categorie: 'Tablette', 
    etat: 'En panne', 
    localisation: 'Marseille', 
    acquisition: '13 août 2025',
    assigne: 'Non',
    image: 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=400&h=250&fit=crop',
    colorClass: 'bg-red-500',
    etatClass: 'bg-red-50 text-red-600'
  },
  { 
    id: 4, 
    name: 'Dell Dell Lite 3', 
    ref: 'REF-80003', 
    sn: 'SN-YVEP847C', 
    categorie: 'Ordinateur portable', 
    etat: 'En maintenance', 
    localisation: 'Lille', 
    acquisition: '18 juin 2025',
    assigne: 'Oui',
    image: 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400&h=250&fit=crop',
    colorClass: 'bg-amber-500',
    etatClass: 'bg-amber-50 text-amber-600'
  },
];

const openFiche = (equip) => {
  selectedEquipement.value = equip;
  showFiche.value = true;
};
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
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
          <button class="flex items-center gap-2 px-4 py-2 bg-primary-600 rounded-xl text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-lg shadow-primary-200">
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
        <option>Catégorie</option>
        <option>PDA</option>
        <option>Smartphone</option>
      </select>
      <select class="h-10 px-4 bg-slate-50 border-none rounded-xl text-sm font-medium text-slate-600 focus:ring-2 focus:ring-primary-500/10 outline-none">
        <option>État</option>
        <option>Neuf</option>
        <option>En service</option>
      </select>
      <button class="p-2.5 bg-slate-100 text-slate-500 rounded-xl hover:bg-slate-200 transition-colors">
        <Filter class="w-5 h-5" />
      </button>
    </div>

    <!-- Equipements Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div 
        v-for="equip in equipements" 
        :key="equip.id"
        @click="openFiche(equip)"
        class="group bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-xl hover:border-primary-200 transition-all duration-300 cursor-pointer"
      >
        <!-- Image & Badge -->
        <div class="relative h-48 overflow-hidden bg-slate-100">
          <img 
            :src="equip.image" 
            :alt="equip.name"
            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
          >
          <div class="absolute top-4 left-4">
            <span :class="['px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider shadow-sm', equip.etatClass]">
              {{ equip.etat }}
            </span>
          </div>
          <div class="absolute top-4 right-4">
            <button class="w-8 h-8 rounded-lg bg-black/30 text-white flex items-center justify-center hover:bg-black/50 transition-colors">
              <QrCode class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="p-6">
          <div class="flex items-start justify-between mb-4">
            <div>
              <h3 class="font-bold text-slate-900 group-hover:text-primary-600 transition-colors">{{ equip.name }}</h3>
              <p class="text-[10px] font-mono font-medium text-slate-400 uppercase tracking-tight">{{ equip.ref }} • {{ equip.sn }}</p>
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
                <p class="text-xs font-bold text-slate-700">{{ equip.categorie }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400">
                <MapPin class="w-3.5 h-3.5" />
              </div>
              <div>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Localisation</p>
                <p class="text-xs font-bold text-slate-700">{{ equip.localisation }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400">
                <Calendar class="w-3.5 h-3.5" />
              </div>
              <div>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Acquisition</p>
                <p class="text-xs font-bold text-slate-700">{{ equip.acquisition }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400">
                <User class="w-3.5 h-3.5" />
              </div>
              <div>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Assigné</p>
                <p class="text-xs font-bold text-slate-700">{{ equip.assigne }}</p>
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
          <img :src="selectedEquipement.image" class="w-full h-full object-cover">
          <div class="absolute top-4 left-4">
            <span :class="['px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider shadow-sm', selectedEquipement.etatClass]">
              {{ selectedEquipement.etat }}
            </span>
          </div>
        </div>

        <div>
          <h3 class="text-2xl font-bold text-slate-900">{{ selectedEquipement.name }}</h3>
          <p class="text-sm font-mono font-medium text-slate-400 uppercase tracking-tight">{{ selectedEquipement.ref }} • {{ selectedEquipement.sn }}</p>
        </div>

        <!-- Details Grid -->
        <div class="grid grid-cols-2 gap-4">
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Catégorie</p>
            <p class="text-sm font-bold text-slate-900">{{ selectedEquipement.categorie }}</p>
          </div>
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">État actuel</p>
            <p class="text-sm font-bold text-slate-900">{{ selectedEquipement.etat }}</p>
          </div>
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Localisation</p>
            <p class="text-sm font-bold text-slate-900">{{ selectedEquipement.localisation }}</p>
          </div>
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Date acquisition</p>
            <p class="text-sm font-bold text-slate-900">{{ selectedEquipement.acquisition }}</p>
          </div>
        </div>

        <!-- History Section -->
        <div class="space-y-4">
          <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <History class="w-4 h-4 text-primary-500" />
            Historique complet
          </h4>
          <div class="space-y-6 relative before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[2px] before:bg-slate-100">
            <div class="relative pl-10 group">
              <div class="absolute left-0 top-1.5 w-[24px] h-[24px] rounded-full bg-blue-50 border-2 border-blue-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                <ArrowLeftRight class="w-2.5 h-2.5 text-blue-600" />
              </div>
              <p class="text-sm font-bold text-slate-900">Affecté à Amélie Dubois</p>
              <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">06 juin 2026</p>
            </div>
            <div class="relative pl-10 group">
              <div class="absolute left-0 top-1.5 w-[24px] h-[24px] rounded-full bg-amber-50 border-2 border-amber-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                <Wrench class="w-2.5 h-2.5 text-amber-600" />
              </div>
              <p class="text-sm font-bold text-slate-900">Maintenance préventive</p>
              <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">17 mai 2026</p>
            </div>
            <div class="relative pl-10 group">
              <div class="absolute left-0 top-1.5 w-[24px] h-[24px] rounded-full bg-slate-50 border-2 border-slate-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                <Package class="w-2.5 h-2.5 text-slate-600" />
              </div>
              <p class="text-sm font-bold text-slate-900">Retour stock</p>
              <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">27 avr. 2026</p>
            </div>
          </div>
        </div>
      </div>
    </SideModal>
  </div>
</template>
