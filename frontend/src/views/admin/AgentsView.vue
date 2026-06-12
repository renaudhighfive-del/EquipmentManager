<script setup>
import { ref, computed, onMounted } from 'vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import SideModal from '../../components/layout/SideModal.vue'
import { useAgentStore } from '../../stores/agent'
import { useAuthStore } from '../../stores/auth'
import {
  Search, Plus, Edit, UserX, UserCheck, Mail, Phone,
  MapPin, Briefcase, Smartphone, ChevronRight, Loader2,
  Camera, AlertCircle, CheckCircle2, Package
} from 'lucide-vue-next'

const agentStore = useAgentStore()
const authStore  = useAuthStore()

// ── État UI ─────────────────────────────────────────────────────────────
const showFiche    = ref(false)
const showForm     = ref(false)
const editingAgent = ref(null)
const formLoading  = ref(false)
const formError    = ref('')
const formSuccess  = ref('')

// ── Filtres (frontend only) ──────────────────────────────────────────────
const searchQuery  = ref('')
const filterStatut = ref('') // '' | 'actif' | 'inactif'

const filteredAgents = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  return agentStore.agents.filter(a => {
    const matchStatut = !filterStatut.value || a.statut === filterStatut.value
    const matchSearch = !q
      || `${a.prenom} ${a.nom}`.toLowerCase().includes(q)
      || a.matricule.toLowerCase().includes(q)
      || a.email?.toLowerCase().includes(q)
    return matchStatut && matchSearch
  })
})

const countByStatut = computed(() => ({
  '':       agentStore.agents.length,
  actif:    agentStore.agentsActifs.length,
  inactif:  agentStore.agentsInactifs.length,
}))

// ── Formulaire ───────────────────────────────────────────────────────────
const photoPreview = ref(null)
const photoFile    = ref(null)

const emptyForm = () => ({
  nom:       '',
  prenom:    '',
  telephone: '',
  email:     '',
  direction: '',
  service:   '',
  poste:     '',
})
const formData = ref(emptyForm())

const handlePhotoChange = (e) => {
  const file = e.target.files[0]
  if (!file) return
  photoFile.value = file
  photoPreview.value = URL.createObjectURL(file)
}

const openCreate = () => {
  editingAgent.value = null
  formData.value     = emptyForm()
  photoPreview.value = null
  photoFile.value    = null
  formError.value    = ''
  formSuccess.value  = ''
  showForm.value     = true
}

const openEdit = (agent) => {
  editingAgent.value = agent
  formData.value = {
    nom:       agent.nom,
    prenom:    agent.prenom,
    telephone: agent.telephone || '',
    email:     agent.email || '',
    direction: agent.direction || '',
    service:   agent.service || '',
    poste:     agent.poste || '',
  }
  photoPreview.value = agent.photo_url ?? null
  photoFile.value    = null
  formError.value    = ''
  formSuccess.value  = ''
  showForm.value     = true
}

const saveAgent = async () => {
  formError.value   = ''
  formLoading.value = true

  const fd = new FormData()
  Object.entries(formData.value).forEach(([k, v]) => { if (v) fd.append(k, v) })
  if (photoFile.value) fd.append('photo', photoFile.value)

  try {
    if (editingAgent.value) {
      await agentStore.updateAgent(editingAgent.value.id, fd)
    } else {
      await agentStore.createAgent(fd)
    }
    showForm.value = false
  } catch (err) {
    const errors = err.response?.data?.errors
    formError.value = errors
      ? Object.values(errors).flat().join(' ')
      : err.response?.data?.message ?? 'Une erreur est survenue.'
  } finally {
    formLoading.value = false
  }
}

// ── Fiche ────────────────────────────────────────────────────────────────
const openFiche = async (agent) => {
  await agentStore.fetchAgent(agent.id)
  showFiche.value = true
}

// ── Helpers ──────────────────────────────────────────────────────────────
const initiales = (a) => `${a.prenom?.charAt(0) ?? ''}${a.nom?.charAt(0) ?? ''}`
const fullName  = (a) => `${a.prenom} ${a.nom}`
const isAdmin   = computed(() => authStore.user?.role === 'admin')
const avatarUrl = (agent) => {
  if (!agent.photo_url) return null
  if (agent.photo_url.startsWith('http')) return agent.photo_url
  return import.meta.env.VITE_API_URL.replace('/api', '') + agent.photo_url
}

onMounted(() => agentStore.fetchAgents())
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">

    <!-- Header -->
    <PageHeader title="Agents" :subtitle="`${filteredAgents.length} agent(s) affiché(s)`">
      <template #actions>
        <button
          v-if="isAdmin"
          @click="openCreate"
          class="flex items-center gap-2 px-4 py-2.5 bg-primary-600 rounded-xl text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-lg shadow-primary-200"
        >
          <Plus class="w-4 h-4" />
          Nouvel agent
        </button>
      </template>
    </PageHeader>

    <!-- Table card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">

      <!-- Filtres -->
      <div class="p-5 border-b border-slate-100 space-y-4">
        <!-- Tabs statut -->
        <div class="flex items-center gap-1.5 flex-wrap">
          <button
            v-for="tab in [
              { value: '',       label: 'Tous' },
              { value: 'actif',  label: 'Actifs' },
              { value: 'inactif', label: 'Inactifs' },
            ]"
            :key="tab.value"
            @click="filterStatut = tab.value"
            :class="[
              'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all',
              filterStatut === tab.value
                ? tab.value === 'inactif'
                  ? 'bg-slate-800 text-white shadow-md'
                  : tab.value === 'actif'
                    ? 'bg-emerald-500 text-white shadow-md shadow-emerald-100'
                    : 'bg-primary-600 text-white shadow-md shadow-primary-100'
                : 'bg-slate-100 text-slate-500 hover:bg-slate-200'
            ]"
          >
            {{ tab.label }}
            <span :class="['px-1.5 py-0.5 rounded-md text-[10px] font-black tabular-nums', filterStatut === tab.value ? 'bg-white/25 text-white' : 'bg-white text-slate-500']">
              {{ countByStatut[tab.value] }}
            </span>
          </button>
        </div>

        <!-- Recherche -->
        <div class="flex items-center gap-3">
          <div class="relative flex-1 max-w-sm">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Nom, matricule, email..."
              class="w-full h-10 pl-10 pr-4 bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary-500/10 outline-none"
            >
          </div>
          <div v-if="agentStore.loading" class="flex items-center gap-2 text-slate-400">
            <Loader2 class="w-4 h-4 animate-spin" />
            <span class="text-xs">Chargement...</span>
          </div>
        </div>
      </div>

      <!-- Tableau -->
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-slate-50/50 text-[11px] font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">
              <th class="px-8 py-4">Agent</th>
              <th class="px-6 py-4">Matricule</th>
              <th class="px-6 py-4">Direction / Service</th>
              <th class="px-6 py-4 text-center">Statut</th>
              <th class="px-6 py-4 text-center">Matériels</th>
              <th class="px-8 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr
              v-for="agent in filteredAgents"
              :key="agent.id"
              class="group hover:bg-slate-50/40 transition-colors"
            >
              <!-- Agent -->
              <td class="px-8 py-4">
                <div class="flex items-center gap-4">
                  <div class="w-12 h-12 rounded-full overflow-hidden flex-shrink-0 border border-slate-200 bg-primary-50">
                    <img v-if="avatarUrl(agent)" :src="avatarUrl(agent)" class="w-full h-full object-cover" :alt="fullName(agent)">
                    <div v-else class="w-full h-full flex items-center justify-center text-primary-700 font-bold text-sm">
                      {{ initiales(agent) }}
                    </div>
                  </div>
                  <div>
                    <div class="flex items-center gap-2">
                      <span class="text-sm font-bold text-slate-900">{{ fullName(agent) }}</span>
                      <span v-if="agent.is_nouveau" class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded-md">Nouveau</span>
                    </div>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">
                      {{ agent.email || '—' }}
                    </p>
                  </div>
                </div>
              </td>

              <!-- Matricule -->
              <td class="px-6 py-4">
                <span class="text-xs font-mono font-semibold text-slate-600 bg-slate-100 px-2 py-1 rounded-lg border border-slate-200/50">
                  {{ agent.matricule }}
                </span>
              </td>

              <!-- Direction / Service -->
              <td class="px-6 py-4">
                <p class="text-sm font-bold text-slate-900">{{ agent.direction || '—' }}</p>
                <p class="text-xs text-slate-500 font-medium">{{ agent.service || '—' }}</p>
              </td>

              <!-- Statut -->
              <td class="px-6 py-4 text-center">
                <span :class="['inline-flex px-3 py-1 rounded-full text-[11px] font-bold', agent.statut === 'actif' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-slate-100 text-slate-400 border border-slate-200']">
                  {{ agent.statut === 'actif' ? 'Actif' : 'Inactif' }}
                </span>
              </td>

              <!-- Matériels -->
              <td class="px-6 py-4 text-center">
                <span :class="['text-xs font-bold', agent.nb_affectes > 0 ? 'text-primary-600 bg-primary-50 px-2 py-1 rounded-lg border border-primary-100' : 'text-slate-400']">
                  {{ agent.nb_affectes }} affecté(s)
                </span>
              </td>

              <!-- Actions -->
              <td class="px-8 py-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button @click="openFiche(agent)" class="text-xs font-bold text-slate-600 hover:text-primary-600 py-2 px-3 rounded-lg hover:bg-primary-50 transition-all">
                    Voir fiche
                  </button>
                  <template v-if="isAdmin">
                    <button @click="openEdit(agent)" class="p-2 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all" title="Modifier">
                      <Edit class="w-4 h-4" />
                    </button>
                    <button
                      @click="agentStore.toggleStatut(agent)"
                      :class="['p-2 rounded-lg transition-all', agent.statut === 'actif' ? 'text-rose-500 hover:bg-rose-50' : 'text-emerald-500 hover:bg-emerald-50']"
                      :title="agent.statut === 'actif' ? 'Désactiver' : 'Réactiver'"
                    >
                      <component :is="agent.statut === 'actif' ? UserX : UserCheck" class="w-4 h-4" />
                    </button>
                  </template>
                </div>
              </td>
            </tr>

            <!-- Vide -->
            <tr v-if="!agentStore.loading && filteredAgents.length === 0">
              <td colspan="6" class="px-8 py-20 text-center">
                <div class="flex flex-col items-center gap-3">
                  <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center">
                    <Smartphone class="w-7 h-7 text-slate-300" />
                  </div>
                  <p class="text-sm font-bold text-slate-500">Aucun agent trouvé</p>
                  <p class="text-xs text-slate-400">Modifiez les filtres ou créez un nouvel agent</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── Fiche agent ────────────────────────────────────────────────── -->
    <SideModal :show="showFiche" title="Fiche Agent" @close="showFiche = false">
      <div v-if="agentStore.selectedAgent" class="space-y-8">
        <!-- Header -->
        <div class="flex items-start gap-5">
          <div class="w-20 h-20 rounded-2xl overflow-hidden border-2 border-white shadow-xl flex-shrink-0 bg-primary-50">
            <img v-if="avatarUrl(agentStore.selectedAgent)" :src="avatarUrl(agentStore.selectedAgent)" class="w-full h-full object-cover">
            <div v-else class="w-full h-full flex items-center justify-center text-primary-700 text-2xl font-bold">
              {{ initiales(agentStore.selectedAgent) }}
            </div>
          </div>
          <div>
            <h3 class="text-xl font-bold text-slate-900">{{ fullName(agentStore.selectedAgent) }}</h3>
            <p class="text-slate-500 text-sm font-medium mt-0.5">{{ agentStore.selectedAgent.poste || '—' }} · {{ agentStore.selectedAgent.service || '—' }}</p>
            <div class="flex items-center gap-2 mt-2">
              <span class="font-mono text-[10px] bg-slate-100 px-2 py-0.5 rounded-lg text-slate-600">{{ agentStore.selectedAgent.matricule }}</span>
              <span :class="['px-2 py-0.5 rounded-lg text-[10px] font-bold', agentStore.selectedAgent.statut === 'actif' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400']">
                {{ agentStore.selectedAgent.statut }}
              </span>
            </div>
          </div>
        </div>

        <!-- Infos -->
        <div class="grid grid-cols-2 gap-3">
          <div v-for="(item, i) in [
            { label: 'Direction', value: agentStore.selectedAgent.direction, icon: MapPin },
            { label: 'Service',   value: agentStore.selectedAgent.service,   icon: Briefcase },
            { label: 'Téléphone', value: agentStore.selectedAgent.telephone, icon: Phone },
            { label: 'Email',     value: agentStore.selectedAgent.email,     icon: Mail },
          ]" :key="i" class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
            <div class="flex items-center gap-1.5 mb-1">
              <component :is="item.icon" class="w-3.5 h-3.5 text-slate-400" />
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ item.label }}</p>
            </div>
            <p class="text-sm font-bold text-slate-900 truncate">{{ item.value || '—' }}</p>
          </div>
        </div>

        <!-- Affectations -->
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Équipements affectés</p>
            <span class="text-[10px] font-bold text-primary-600 bg-primary-50 px-2 py-0.5 rounded-lg">{{ agentStore.selectedAgent.nb_affectes }} en cours</span>
          </div>
          <div v-if="!agentStore.selectedAgent.affectations?.length" class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
            <Package class="w-8 h-8 text-slate-300 mx-auto mb-2" />
            <p class="text-sm text-slate-400">Aucun équipement affecté</p>
          </div>
          <div v-else class="space-y-2">
            <div v-for="aff in agentStore.selectedAgent.affectations" :key="aff.id" class="p-3.5 bg-white border border-slate-200 rounded-2xl flex items-center gap-3 hover:border-primary-200 transition-colors">
              <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                <Smartphone class="w-4 h-4 text-slate-400" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-slate-900 truncate">{{ aff.equipement?.marque }} {{ aff.equipement?.modele }}</p>
                <p class="text-[10px] font-mono text-slate-400 uppercase">{{ aff.equipement?.reference }}</p>
              </div>
              <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-md flex-shrink-0', aff.statut === 'en_cours' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400']">
                {{ aff.statut === 'en_cours' ? 'En cours' : 'Retourné' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Actions (admin seulement) -->
        <div v-if="isAdmin" class="flex gap-3 pt-2 border-t border-slate-100">
          <button @click="showFiche = false; openEdit(agentStore.selectedAgent)" class="flex-1 flex items-center justify-center gap-2 py-3 bg-primary-600 text-white text-sm font-bold rounded-2xl hover:bg-primary-700 transition-all">
            <Edit class="w-4 h-4" />
            Modifier
          </button>
          <button
            @click="agentStore.toggleStatut(agentStore.selectedAgent)"
            :class="['flex items-center justify-center gap-2 px-4 py-3 text-sm font-bold rounded-2xl transition-all border', agentStore.selectedAgent.statut === 'actif' ? 'border-rose-200 text-rose-600 hover:bg-rose-50' : 'border-emerald-200 text-emerald-600 hover:bg-emerald-50']"
          >
            <component :is="agentStore.selectedAgent.statut === 'actif' ? UserX : UserCheck" class="w-4 h-4" />
            {{ agentStore.selectedAgent.statut === 'actif' ? 'Désactiver' : 'Réactiver' }}
          </button>
        </div>
      </div>
    </SideModal>

    <!-- ── Formulaire création / édition ─────────────────────────────── -->
    <SideModal
      :show="showForm"
      :title="editingAgent ? 'Modifier l\'agent' : 'Nouvel agent'"
      mode="center"
      @close="showForm = false"
    >
      <form @submit.prevent="saveAgent" class="space-y-6">

        <!-- Feedback -->
        <div v-if="formError" class="flex items-start gap-3 p-4 bg-red-50 border border-red-100 rounded-2xl text-sm text-red-600">
          <AlertCircle class="w-4 h-4 mt-0.5 flex-shrink-0" />
          {{ formError }}
        </div>

        <!-- Upload photo -->
        <div class="flex flex-col items-center gap-3">
          <div class="w-24 h-24 rounded-2xl overflow-hidden border-2 border-slate-200 bg-slate-50 flex items-center justify-center relative group">
            <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover">
            <div v-else class="flex flex-col items-center gap-1 text-slate-400">
              <Camera class="w-8 h-8" />
              <span class="text-[10px] font-medium">Photo</span>
            </div>
            <label class="absolute inset-0 cursor-pointer opacity-0 group-hover:opacity-100 bg-black/30 flex items-center justify-center transition-opacity rounded-2xl">
              <Camera class="w-6 h-6 text-white" />
              <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="handlePhotoChange">
            </label>
          </div>
          <p class="text-[11px] text-slate-400">JPEG, PNG ou WebP · max 2 Mo</p>
        </div>

        <!-- Note matricule auto -->
        <div v-if="!editingAgent" class="flex items-center gap-2 p-3 bg-blue-50 border border-blue-100 rounded-xl text-xs text-blue-600 font-medium">
          <CheckCircle2 class="w-4 h-4 flex-shrink-0" />
          Le matricule sera généré automatiquement au format MAT-YYYY-XXXXX
        </div>

        <!-- Champs -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Prénom *</label>
            <input v-model="formData.prenom" type="text" required placeholder="Jean"
              class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all">
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nom *</label>
            <input v-model="formData.nom" type="text" required placeholder="Dupont"
              class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all">
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Email professionnel</label>
            <input v-model="formData.email" type="email" placeholder="jean.dupont@entreprise.fr"
              class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all">
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Téléphone</label>
            <input v-model="formData.telephone" type="number" placeholder="+33 6 12 34 56 78"
              class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all">
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Direction</label>
              <select v-model="formData.direction"
              class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all" >
                <option value="">---Selectionner une Direction---</option>
                <option value="Direction des Systèmes d'Information (DSI)">Direction des Systèmes d'Information (DSI)</option>
                <option value="Direction des Moyens Généraux (DMG)">Direction des Moyens Généraux (DMG)</option>
                <option value="Direction Technique (DT) ou Direction des Opérations">Direction Technique (DT) ou Direction des Opérations</option>
                <option value="Direction de la Logistique et des Approvisionnements">Direction de la Logistique et des Approvisionnements </option>
                <option value="Direction Administrative et Financière (DAF)">Direction Administrative et Financière (DAF)</option>
              </select>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Service</label>
            <select v-model="formData.service"
              class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all" >
              <option value="">---Selectionner un Service---</option>
              <option value="Service Infrastructures et Réseaux">Service Infrastructures et Réseaux</option>
              <option value="Service Support et Parc Informatique">Service Support et Parc Informatique</option>
              <option value="Service Sécurité Informatique">Service Sécurité Informatique</option>
            </select>
            </div>
          <div class="col-span-2 space-y-1.5">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Poste</label>
            <input v-model="formData.poste" type="text" placeholder="Technicien terrain"
              class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all">
          </div>
        </div>

        <button type="submit" :disabled="formLoading"
          class="w-full py-3.5 bg-primary-600 text-white font-bold rounded-2xl hover:bg-primary-700 transition-all shadow-lg shadow-primary-200 flex items-center justify-center gap-2 disabled:opacity-60"
        >
          <Loader2 v-if="formLoading" class="w-4 h-4 animate-spin" />
          {{ editingAgent ? 'Mettre à jour' : 'Créer l\'agent' }}
        </button>
      </form>
    </SideModal>

  </div>
</template>
