<script setup>
import { ref, onMounted, computed } from 'vue';
import PageHeader from '../../components/layout/PageHeader.vue';
import SideModal from '../../components/layout/SideModal.vue';
import { useAgentsStore } from '../../stores/agents';
import { useAuthStore } from '../../stores/auth';
import {
  Search,
  Filter,
  Plus,
  Smartphone,
  Edit,
  UserX,
  UserCheck,
  Mail,
  Phone,
  MapPin,
  Briefcase,
  User,
  ChevronRight
} from 'lucide-vue-next';
 
const agentsStore = useAgentsStore();
const authStore = useAuthStore();
 
const showFiche = ref(false);
const showForm = ref(false);
const editingAgent = ref(null);
const searchQuery = ref('');
const formLoading = ref(false);

const filteredAgents = computed(() => {
  return agentsStore.agents.filter(agent => {
    // Si c'est un gestionnaire, on filtre les agents qui ont du matériel dans ses catégories
    if (authStore.user?.role === 'gestionnaire') {
      const allowedCategoryIds = authStore.user.categories?.map(c => Number(c.id)) || [];
      // On garde l'agent si au moins une de ses affectations correspond à une catégorie autorisée
      const hasAllowedEquipment = agent.affectations?.some(aff => 
        allowedCategoryIds.includes(Number(aff.equipement?.categorie_id))
      );
      if (!hasAllowedEquipment) return false;
    }
    return true;
  });
});
 
const formData = ref({
  matricule: '',
  nom: '',
  prenom: '',
  telephone: '',
  email: '',
  direction: '',
  service: '',
  poste: '',
});
 
const openFiche = async (agent) => {
  await agentsStore.fetchAgent(agent.id);
  showFiche.value = true;
};
 
const openCreateForm = () => {
  editingAgent.value = null;
  formData.value = {
    matricule: '',
    nom: '',
    prenom: '',
    telephone: '',
    email: '',
    direction: '',
    service: '',
    poste: '',
  };
  showForm.value = true;
};
 
const openEditForm = (agent) => {
  editingAgent.value = agent;
  formData.value = {
    matricule: agent.matricule,
    nom: agent.nom,
    prenom: agent.prenom,
    telephone: agent.telephone || '',
    email: agent.email || '',
    direction: agent.direction || '',
    service: agent.service || '',
    poste: agent.poste || '',
  };
  showForm.value = true;
};
 
const saveAgent = async () => {
  formLoading.value = true;
  try {
    if (editingAgent.value) {
      await agentsStore.updateAgent(editingAgent.value.id, formData.value);
    } else {
      await agentsStore.createAgent(formData.value);
    }
    showForm.value = false;
  } catch (error) {
    console.error('Erreur lors de la sauvegarde de l\'agent:', error);
  } finally {
    formLoading.value = false;
  }
};
 
const handleDesactiverAgent = async (agent) => {
  try {
    await agentsStore.desactiverAgent(agent.id);
  } catch (error) {
    console.error('Erreur lors de la désactivation:', error);
  }
};
 
const handleReactiverAgent = async (agent) => {
  try {
    await agentsStore.reactiverAgent(agent.id);
  } catch (error) {
    console.error('Erreur lors de la réactivation:', error);
  }
};
 
const formatAgentName = (agent) => {
  return `${agent.prenom} ${agent.nom}`;
};
 
const getAgentInitials = (agent) => {
  return `${agent.prenom.charAt(0)}${agent.nom.charAt(0)}`;
};
 
onMounted(() => {
  agentsStore.fetchAgents();
});
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
              @input="() => agentsStore.fetchAgents(searchQuery)"
              class="h-10 pl-10 pr-4 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500/20 outline-none transition-all w-64"
            >
          </div>
          <button class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
            <Filter class="w-4 h-4" />
            Filtres
          </button>
          <button @click="openCreateForm" class="flex items-center gap-2 px-4 py-2 bg-primary-600 rounded-xl text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-lg shadow-primary-200">
            <Plus class="w-4 h-4" />
            Nouvel agent
          </button>
        </div>
      </template>
    </PageHeader>
 
    <!-- Agents Table Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50/50 text-[11px] font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">
            <th class="px-8 py-5">Agent</th>
            <th class="px-6 py-5">Matricule</th>
            <th class="px-6 py-5">Direction / Service</th>
            <th class="px-6 py-5 text-center">Statut</th>
            <th class="px-6 py-5 text-center">Matériels</th>
            <th class="px-8 py-5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <tr v-for="agent in filteredAgents" :key="agent.id" class="group hover:bg-slate-50/50 transition-colors">
            <td class="px-8 py-5">
              <div class="flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-primary-100 to-primary-200 border border-primary-300 flex items-center justify-center text-primary-700 font-bold text-lg overflow-hidden">
                  <span>{{ getAgentInitials(agent) }}</span>
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-slate-900">{{ formatAgentName(agent) }}</span>
                    <span v-if="agent.is_nouveau" class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded-md">Nouveau</span>
                  </div>
                  <div class="flex items-center gap-2 text-xs text-slate-500">
                    <Mail class="w-3 h-3" />
                    {{ agent.email || 'Non renseigné' }}
                  </div>
                </div>
              </div>
            </td>
            <td class="px-6 py-5">
              <span class="text-xs font-mono font-semibold text-slate-600 bg-slate-100 px-2 py-1 rounded-lg border border-slate-200/50">{{ agent.matricule }}</span>
            </td>
            <td class="px-6 py-5">
              <div>
                <p class="text-sm font-bold text-slate-900">{{ agent.direction || 'Non renseigné' }}</p>
                <p class="text-xs text-slate-500 font-medium">{{ agent.service || 'Non renseigné' }}</p>
              </div>
            </td>
            <td class="px-6 py-5 text-center">
              <span
                class="inline-flex px-3 py-1 rounded-full text-[11px] font-bold tracking-tight"
                :class="agent.statut === 'actif' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-slate-100 text-slate-400 border border-slate-200'"
              >
                {{ agent.statut === 'actif' ? 'Actif' : 'Inactif' }}
              </span>
            </td>
            <td class="px-6 py-5 text-center">
              <span
                class="text-xs font-bold"
                :class="agent.nb_affectes > 0 ? 'text-primary-600 bg-primary-50 px-2 py-1 rounded-lg border border-primary-200' : 'text-slate-400'"
              >
                {{ agent.nb_affectes }} équipement(s)
              </span>
            </td>
            <td class="px-8 py-5 text-right">
              <div class="flex items-center justify-end gap-2">
                <button
                  @click="openFiche(agent)"
                  class="text-xs font-bold text-slate-600 hover:text-primary-600 transition-colors py-2 px-3 rounded-lg hover:bg-primary-50"
                >
                  Voir fiche
                </button>
                <button
                  @click="openEditForm(agent)"
                  class="text-xs font-bold text-slate-600 hover:text-primary-600 transition-colors py-2 px-2 rounded-lg hover:bg-primary-50"
                  title="Modifier"
                >
                  <Edit class="w-4 h-4" />
                </button>
                <button
                  v-if="agent.statut === 'actif'"
                  @click="handleDesactiverAgent(agent)"
                  class="text-xs font-bold text-rose-600 hover:text-rose-700 transition-colors py-2 px-2 rounded-lg hover:bg-rose-50"
                  title="Désactiver"
                >
                  <UserX class="w-4 h-4" />
                </button>
                <button
                  v-else
                  @click="handleReactiverAgent(agent)"
                  class="text-xs font-bold text-emerald-600 hover:text-emerald-700 transition-colors py-2 px-2 rounded-lg hover:bg-emerald-50"
                  title="Réactiver"
                >
                  <UserCheck class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="agentsStore.loading">
            <td colspan="6" class="px-8 py-16 text-center text-slate-500">
              <div class="flex flex-col items-center gap-2">
                <div class="w-8 h-8 border-4 border-slate-200 border-t-primary-500 rounded-full animate-spin"></div>
                <span>Chargement des agents...</span>
              </div>
            </td>
          </tr>
          <tr v-if="!agentsStore.loading && agentsStore.agents.length === 0">
            <td colspan="6" class="px-8 py-16 text-center text-slate-500">
              <div class="flex flex-col items-center gap-2">
                <User class="w-12 h-12 text-slate-300" />
                <span class="text-lg font-medium text-slate-700">Aucun agent trouvé</span>
                <span class="text-sm text-slate-500">Commencez par ajouter un nouvel agent</span>
              </div>
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
      <div v-if="agentsStore.selectedAgent" class="space-y-8">
        <!-- Header Info -->
        <div class="flex items-center gap-6">
          <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-primary-50 to-primary-100 border-2 border-white shadow-xl flex items-center justify-center text-primary-700 text-3xl font-bold">
            {{ getAgentInitials(agentsStore.selectedAgent) }}
          </div>
          <div>
            <h3 class="text-2xl font-bold text-slate-900">{{ formatAgentName(agentsStore.selectedAgent) }}</h3>
            <p class="text-slate-500 font-medium">{{ agentsStore.selectedAgent.poste }} • {{ agentsStore.selectedAgent.service }}</p>
            <div class="mt-3 inline-flex items-center gap-2 px-4 py-1.5 bg-primary-100 text-primary-700 text-[11px] font-bold rounded-xl uppercase tracking-wider">
              <Smartphone class="w-4 h-4" />
              {{ agentsStore.selectedAgent.nb_affectes }} équipement(s) affecté(s)
            </div>
          </div>
        </div>
 
        <!-- Grid Info -->
        <div class="grid grid-cols-2 gap-4">
          <div class="p-5 bg-slate-50/50 rounded-2xl border border-slate-100">
            <div class="flex items-center gap-2 mb-2">
              <MapPin class="w-4 h-4 text-slate-400" />
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Direction</p>
            </div>
            <p class="text-sm font-bold text-slate-900">{{ agentsStore.selectedAgent.direction || 'Non renseigné' }}</p>
          </div>
          <div class="p-5 bg-slate-50/50 rounded-2xl border border-slate-100">
            <div class="flex items-center gap-2 mb-2">
              <Briefcase class="w-4 h-4 text-slate-400" />
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Service</p>
            </div>
            <p class="text-sm font-bold text-slate-900">{{ agentsStore.selectedAgent.service || 'Non renseigné' }}</p>
          </div>
          <div class="p-5 bg-slate-50/50 rounded-2xl border border-slate-100">
            <div class="flex items-center gap-2 mb-2">
              <Phone class="w-4 h-4 text-slate-400" />
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Téléphone</p>
            </div>
            <p class="text-sm font-bold text-slate-900">{{ agentsStore.selectedAgent.telephone || 'Non renseigné' }}</p>
          </div>
          <div class="p-5 bg-slate-50/50 rounded-2xl border border-slate-100">
            <div class="flex items-center gap-2 mb-2">
              <Mail class="w-4 h-4 text-slate-400" />
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Email</p>
            </div>
            <p class="text-sm font-bold text-slate-900 truncate">{{ agentsStore.selectedAgent.email || 'Non renseigné' }}</p>
          </div>
          <div class="p-5 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Matricule</p>
            <p class="text-sm font-bold text-slate-900 font-mono">{{ agentsStore.selectedAgent.matricule }}</p>
          </div>
          <div class="p-5 bg-slate-50/50 rounded-2xl border border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Statut</p>
            <span
              class="text-sm font-bold"
              :class="agentsStore.selectedAgent.statut === 'actif' ? 'text-emerald-600' : 'text-slate-400'"
            >
              {{ agentsStore.selectedAgent.statut === 'actif' ? 'Actif' : 'Inactif' }}
            </span>
          </div>
        </div>
 
        <!-- Affectations -->
        <div class="space-y-4 pt-4 border-t border-slate-100">
          <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <Smartphone class="w-4 h-4 text-primary-500" />
            Équipements affectés
          </h4>
         
          <div v-if="agentsStore.selectedAgent.nb_affectes === 0" class="p-8 text-center bg-slate-50/50 rounded-3xl border border-dashed border-slate-200">
            <Smartphone class="w-10 h-10 text-slate-300 mx-auto mb-2" />
            <p class="text-sm font-medium text-slate-400 italic">Aucun équipement affecté pour le moment</p>
          </div>
         
          <div v-else class="space-y-3">
            <div v-for="(equipement, index) in (agentsStore.selectedAgent.affectations || [])" :key="index" class="p-4 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4 hover:border-primary-200 transition-colors cursor-pointer group">
              <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center overflow-hidden">
                <Smartphone class="w-6 h-6 text-slate-400" />
              </div>
              <div class="flex-1">
                <p class="text-sm font-bold text-slate-900">{{ equipement.equipement?.nom || 'Équipement' }}</p>
                <p class="text-[10px] font-mono font-medium text-slate-400 uppercase">{{ equipement.equipement?.numero_serie || 'N/A' }}</p>
              </div>
              <ChevronRight class="w-4 h-4 text-slate-300 group-hover:text-primary-400 transition-colors" />
            </div>
          </div>
        </div>
 
        <!-- Actions -->
        <div class="flex gap-3 pt-4 border-t border-slate-100">
          <button
            @click="openEditForm(agentsStore.selectedAgent)"
            class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-primary-600 rounded-xl text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-lg shadow-primary-200"
          >
            <Edit class="w-4 h-4" />
            Modifier
          </button>
          <button
            v-if="agentsStore.selectedAgent.statut === 'actif'"
            @click="handleDesactiverAgent(agentsStore.selectedAgent)"
            class="flex items-center justify-center gap-2 px-4 py-3 bg-rose-50 border border-rose-200 rounded-xl text-sm font-bold text-rose-600 hover:bg-rose-100 transition-all"
          >
            <UserX class="w-4 h-4" />
            Désactiver
          </button>
          <button
            v-else
            @click="handleReactiverAgent(agentsStore.selectedAgent)"
            class="flex items-center justify-center gap-2 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-sm font-bold text-emerald-600 hover:bg-emerald-100 transition-all"
          >
            <UserCheck class="w-4 h-4" />
            Réactiver
          </button>
        </div>
      </div>
    </SideModal>
 
    <!-- Agent Form Modal - CENTER MODE -->
    <SideModal
      :show="showForm"
      :title="editingAgent ? 'Modifier l\'agent' : 'Nouvel agent'"
      mode="center"
      @close="showForm = false"
    >
      <div class="space-y-6">
        <div class="grid grid-cols-2 gap-6">
          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Matricule</label>
            <input
              v-model="formData.matricule"
              type="text"
              class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 outline-none transition-all"
              placeholder="MAT-001"
            >
          </div>
          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Prénom</label>
            <input
              v-model="formData.prenom"
              type="text"
              class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 outline-none transition-all"
              placeholder="Jean"
            >
          </div>
          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Nom</label>
            <input
              v-model="formData.nom"
              type="text"
              class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 outline-none transition-all"
              placeholder="Dupont"
            >
          </div>
          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Email</label>
            <input
              v-model="formData.email"
              type="email"
              class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 outline-none transition-all"
              placeholder="jean.dupont@entreprise.fr"
            >
          </div>
          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Téléphone</label>
            <input
              v-model="formData.telephone"
              type="text"
              class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 outline-none transition-all"
              placeholder="+33 6 12 34 56 78"
            >
          </div>
          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Poste</label>
            <input
              v-model="formData.poste"
              type="text"
              class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 outline-none transition-all"
              placeholder="Technicien"
            >
          </div>
          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Direction</label>
            <input
              v-model="formData.direction"
              type="text"
              class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 outline-none transition-all"
              placeholder="Direction IT"
            >
          </div>
          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Service</label>
            <input
              v-model="formData.service"
              type="text"
              class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 outline-none transition-all"
              placeholder="Support"
            >
          </div>
        </div>
       
        <div class="pt-2">
          <button
            @click="saveAgent"
            :disabled="formLoading"
            class="w-full px-8 py-4 bg-primary-600 rounded-2xl text-base font-bold text-white hover:bg-primary-700 transition-all disabled:opacity-60 shadow-xl shadow-primary-200"
          >
            {{ formLoading ? 'Sauvegarde en cours...' : (editingAgent ? 'Mettre à jour l\'agent' : 'Créer l\'agent') }}
          </button>
        </div>
      </div>
    </SideModal>
  </div>
</template>