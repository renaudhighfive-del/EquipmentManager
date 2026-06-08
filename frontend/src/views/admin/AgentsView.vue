<script setup>
import { ref, onMounted, computed } from 'vue';
import PageHeader from '../../components/layout/PageHeader.vue';
import SideModal from '../../components/layout/SideModal.vue';
import { useAgentStore } from '../../stores/agent';
import { 
  Search, 
  Filter, 
  Plus, 
  User, 
  Phone, 
  Mail, 
  MapPin, 
  Briefcase, 
  Calendar,
  Smartphone,
  ChevronRight,
  History,
  Loader2
} from 'lucide-vue-next';

const agentStore = useAgentStore();
const showFiche = ref(false);
const selectedAgent = ref(null);
const searchQuery = ref('');

onMounted(() => {
  agentStore.fetchAgents();
});

const filteredAgents = computed(() => {
  return agentStore.agents.filter(agent => {
    const search = searchQuery.value.toLowerCase();
    return agent.name?.toLowerCase().includes(search) ||
           agent.email?.toLowerCase().includes(search) ||
           agent.matricule?.toLowerCase().includes(search);
  });
});

const openFiche = (agent) => {
  selectedAgent.value = agent;
  showFiche.value = true;
};
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <!-- Page Header -->
    <PageHeader 
      title="Agents" 
      :subtitle="`${filteredAgents.length} agents affichés`"
    >
      <template #actions>
        <div class="flex items-center gap-3">
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input 
              v-model="searchQuery"
              type="text" 
              placeholder="Rechercher un agent..."
              class="h-10 pl-10 pr-4 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500/20 outline-none transition-all w-64"
            >
          </div>
          <button class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
            <Filter class="w-4 h-4" />
            Filtres
          </button>
          <button class="flex items-center gap-2 px-4 py-2 bg-primary-600 rounded-xl text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-lg shadow-primary-200">
            <Plus class="w-4 h-4" />
            Nouvel agent
          </button>
        </div>
      </template>
    </PageHeader>

    <!-- Agents Table Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <div v-if="agentStore.loading" class="p-12 flex justify-center">
        <Loader2 class="w-8 h-8 animate-spin text-primary-600" />
      </div>

      <div v-else-if="filteredAgents.length === 0" class="p-12 text-center">
        <User class="w-12 h-12 text-slate-200 mx-auto mb-4" />
        <p class="text-slate-500 font-medium">Aucun agent trouvé</p>
      </div>

      <table v-else class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50/50 text-[11px] font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">
            <th class="px-8 py-5">Agent</th>
            <th class="px-6 py-5">Matricule</th>
            <th class="px-6 py-5">Direction / Service</th>
            <th class="px-6 py-5 text-center">Statut</th>
            <th class="px-6 py-5 text-center">Matériels</th>
            <th class="px-6 py-5 text-center">Pertes</th>
            <th class="px-8 py-5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <tr v-for="agent in filteredAgents" :key="agent.id" class="group hover:bg-slate-50/50 transition-colors">
            <td class="px-8 py-5">
              <div class="flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 font-bold overflow-hidden">
                  <span v-if="!agent.avatar">{{ agent.name?.charAt(0) }}</span>
                  <img v-else :src="agent.avatar" class="w-full h-full object-cover">
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-slate-900">{{ agent.name }}</span>
                    <span v-if="agent.is_nouveau" class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded-md">Nouveau</span>
                  </div>
                  <p class="text-xs text-slate-500 font-medium">{{ agent.email }}</p>
                </div>
              </div>
            </td>
            <td class="px-6 py-5">
              <span class="text-xs font-mono font-semibold text-slate-600 bg-slate-100 px-2 py-1 rounded-lg border border-slate-200/50">{{ agent.matricule }}</span>
            </td>
            <td class="px-6 py-5">
              <div>
                <p class="text-sm font-bold text-slate-900">{{ agent.direction }}</p>
                <p class="text-xs text-slate-500 font-medium">{{ agent.service }}</p>
              </div>
            </td>
            <td class="px-6 py-5 text-center">
              <span 
                class="inline-flex px-3 py-1 rounded-full text-[11px] font-bold tracking-tight"
                :class="agent.statut === 'actif' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400'"
              >
                {{ agent.statut === 'actif' ? 'Actif' : 'Inactif' }}
              </span>
            </td>
            <td class="px-6 py-5 text-center">
              <span 
                class="text-xs font-bold"
                :class="agent.nb_affectes > 0 ? 'text-primary-600' : 'text-slate-400'"
              >
                {{ agent.nb_affectes || 0 }} affecté(s)
              </span>
            </td>
            <td class="px-6 py-5 text-center">
              <span 
                v-if="agent.nb_perdus > 0"
                class="px-2 py-1 bg-rose-50 text-rose-600 text-[10px] font-bold rounded-lg border border-rose-100"
              >
                {{ agent.nb_perdus }} perdu(s)
              </span>
              <span v-else class="text-slate-300">—</span>
            </td>
            <td class="px-8 py-5 text-right">
              <button 
                @click="openFiche(agent)"
                class="text-xs font-bold text-slate-600 hover:text-primary-600 transition-colors py-2 px-3 rounded-lg hover:bg-primary-50"
              >
                Voir fiche
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Agent Detail Modal -->
    <SideModal 
      :show="showFiche" 
      title="Fiche Agent" 
      @close="showFiche = false"
    >
      <div v-if="selectedAgent" class="space-y-10">
        <!-- Header Info -->
        <div class="flex items-center gap-5">
          <div class="w-20 h-20 rounded-2xl bg-primary-50 border-2 border-white shadow-xl flex items-center justify-center text-primary-600 text-2xl font-bold">
            {{ selectedAgent.name.charAt(0) }}
          </div>
          <div>
            <h3 class="text-2xl font-bold text-slate-900">{{ selectedAgent.name }}</h3>
            <p class="text-slate-500 font-medium">{{ selectedAgent.poste || 'Agent' }} • {{ selectedAgent.service }}</p>
            <div class="mt-2 inline-flex px-3 py-1 bg-primary-100 text-primary-700 text-[10px] font-bold rounded-lg uppercase tracking-wider">
              {{ selectedAgent.nb_affectes || 0 }} équipements
            </div>
          </div>
        </div>

        <!-- Grid Info -->
        <div class="grid grid-cols-2 gap-4">
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Direction</p>
            <p class="text-sm font-bold text-slate-900">{{ selectedAgent.direction }}</p>
          </div>
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Service</p>
            <p class="text-sm font-bold text-slate-900">{{ selectedAgent.service }}</p>
          </div>
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Téléphone</p>
            <p class="text-sm font-bold text-slate-900">{{ selectedAgent.telephone || 'Non renseigné' }}</p>
          </div>
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Email</p>
            <p class="text-sm font-bold text-slate-900 truncate">{{ selectedAgent.email }}</p>
          </div>
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Matricule</p>
            <p class="text-sm font-bold text-slate-900">{{ selectedAgent.matricule }}</p>
          </div>
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Statut</p>
            <span 
              class="text-xs font-bold"
              :class="selectedAgent.statut === 'actif' ? 'text-emerald-600' : 'text-slate-400'"
            >
              {{ selectedAgent.statut === 'actif' ? 'Actif' : 'Inactif' }}
            </span>
          </div>
        </div>

        <!-- Affectations -->
        <div class="space-y-4">
          <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <Smartphone class="w-4 h-4 text-primary-500" />
            Équipements affectés
          </h4>
          
          <div v-if="!selectedAgent.affectations || selectedAgent.affectations.length === 0" class="p-8 text-center bg-slate-50/50 rounded-3xl border border-dashed border-slate-200">
            <p class="text-sm font-medium text-slate-400 italic">Aucun équipement affecté pour le moment</p>
          </div>
          
          <div v-else class="space-y-3">
            <div v-for="affectation in selectedAgent.affectations" :key="affectation.id" class="p-4 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4 hover:border-primary-200 transition-colors cursor-pointer group">
              <div class="w-14 h-14 rounded-xl bg-slate-100 flex items-center justify-center overflow-hidden grayscale group-hover:grayscale-0 transition-all">
                <img :src="affectation.equipement?.images?.[0]?.path ? `http://localhost:8000/storage/${affectation.equipement.images[0].path}` : 'https://images.unsplash.com/photo-1556656793-062ff9878258?w=200&h=200&fit=crop'" class="w-full h-full object-cover">
              </div>
              <div class="flex-1">
                <p class="text-sm font-bold text-slate-900">{{ affectation.equipement?.marque }} {{ affectation.equipement?.modele }}</p>
                <p class="text-[10px] font-mono font-medium text-slate-400 uppercase">{{ affectation.equipement?.reference }}</p>
              </div>
              <span class="px-2 py-0.5 bg-blue-50 text-blue-600 text-[10px] font-bold rounded-md capitalize">{{ affectation.equipement?.etat }}</span>
              <ChevronRight class="w-4 h-4 text-slate-300 group-hover:text-primary-400 transition-colors" />
            </div>
          </div>
        </div>

        <!-- Timeline -->
        <div class="space-y-4">
          <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <History class="w-4 h-4 text-primary-500" />
            Timeline
          </h4>
          <div class="space-y-6 relative before:absolute before:left-[7px] before:top-2 before:bottom-2 before:w-[2px] before:bg-slate-100">
            <div class="relative pl-8">
              <div class="absolute left-0 top-1.5 w-[16px] h-[16px] rounded-full bg-white border-2 border-primary-500"></div>
              <p class="text-xs font-bold text-slate-900">Affectation</p>
              <p class="text-[10px] text-slate-400 font-medium">31 mai 2026 • REF-80000</p>
            </div>
            <div class="relative pl-8">
              <div class="absolute left-0 top-1.5 w-[16px] h-[16px] rounded-full bg-white border-2 border-slate-300"></div>
              <p class="text-xs font-bold text-slate-600">Retour</p>
              <p class="text-[10px] text-slate-400 font-medium">03 juin 2026 • REF-80008</p>
            </div>
          </div>
        </div>
      </div>
    </SideModal>
  </div>
</template>
