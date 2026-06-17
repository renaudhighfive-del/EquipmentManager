<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import PageHeader from '../../components/layout/PageHeader.vue'
import SideModal from '../../components/layout/SideModal.vue'
import { useUserStore }  from '../../stores/user'
import { useAgentStore } from '../../stores/agent'
import api from '../../services/axios';
import {
  UserPlus, Search, Power, PowerOff, Edit3, Loader2,
  ChevronDown, UserCheck, AlertCircle, CheckCircle2,
  Eye, Mail, Shield, Calendar, Smartphone, Package,
  ArrowLeftRight, Clock, Hash, X, Download
} from 'lucide-vue-next'

import { useAuthStore } from '../../stores/auth'

const userStore  = useUserStore()
const agentStore = useAgentStore()
const authStore  = useAuthStore()

// ── État UI ────────────────────────────────────────────────────────────────
const showModal    = ref(false)
const showDetail   = ref(false)
const isEditMode   = ref(false)
const editUserId   = ref(null)
const submitting   = ref(false)
const searchQuery  = ref('')
const filterRole   = ref('') // '' = tous
const filterInactif = ref(false)
const formError    = ref('')
const formSuccess  = ref('')

// ── Formulaire ─────────────────────────────────────────────────────────────
const emptyForm = () => ({
  role:     'admin',
  agent_id: null,
  name:     '',
  email:    '',
  password: '',
})

const form = ref(emptyForm())

// ── Filtres ────────────────────────────────────────────────────────────────
const filteredUsers = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  return userStore.users.filter(u => {
    const matchRole    = !filterRole.value || u.role === filterRole.value
    const matchSearch  = !q || u.name?.toLowerCase().includes(q) || u.email?.toLowerCase().includes(q)
    const matchInactif = !filterInactif.value || !u.is_active
    return matchRole && matchSearch && matchInactif
  })
})

const inactifCount = computed(() => userStore.users.filter(u => !u.is_active).length)

// Compteurs par rôle pour les badges sur les tabs
const countByRole = computed(() => ({
  '':           userStore.users.length,
  admin:        userStore.users.filter(u => u.role === 'admin').length,
  gestionnaire: userStore.users.filter(u => u.role === 'gestionnaire').length,
  agent:        userStore.users.filter(u => u.role === 'agent').length,
}))

// ── Quand le rôle change ───────────────────────────────────────────────────
watch(() => form.value.role, async (newRole) => {
  form.value.agent_id = null
  clearAgentFields()

  // S'assurer que la liste des agents est chargée pour le filtre local
  if (newRole === 'agent' && !isEditMode.value && agentStore.agents.length === 0) {
    await agentStore.fetchAgents(1, 100)
  }
})

// ── Quand un agent est sélectionné → pré-remplir nom & email ──────────────
watch(() => form.value.agent_id, (agentId) => {
  if (!agentId) { clearAgentFields(); return }
  const agent = agentStore.agentsSansCompte.find(a => a.id === Number(agentId))
  if (!agent) return

  form.value.name  = `${agent.prenom} ${agent.nom}`
  form.value.email = agent.email ?? ''
})

function clearAgentFields() {
  if (!isEditMode.value && form.value.role === 'agent') {
    form.value.name  = ''
    form.value.email = ''
  }
}

// ── Ouvrir détail utilisateur ──────────────────────────────────────────────
const openDetail = async (user) => {
  showDetail.value = true
  await userStore.fetchUser(user.id)
}

// ── Ouvrir modal création ──────────────────────────────────────────────────
const openCreate = async () => {
  isEditMode.value = false
  editUserId.value = null
  form.value       = emptyForm()
  formError.value  = ''
  formSuccess.value = ''
  showModal.value  = true
  // Charger les agents sans compte d'emblée pour le rôle par défaut (admin)
  // sera rechargé si on change vers "agent"
}

// ── Ouvrir modal édition ───────────────────────────────────────────────────
const openEdit = async (user) => {
  isEditMode.value  = true
  editUserId.value  = user.id
  formError.value   = ''
  formSuccess.value = ''
  
  // On récupère le détail pour être sûr d'avoir les catégories
  submitting.value = true
  try {
    const fullUser = await userStore.fetchUser(user.id)
    form.value = {
      role:     fullUser.role,
      agent_id: null,
      name:     fullUser.name,
      email:    fullUser.email,
      password: '',
    }
    showModal.value = true
  } catch (err) {
    console.error('Erreur chargement détail utilisateur:', err)
  } finally {
    submitting.value = false
  }
}

// ── Soumettre ──────────────────────────────────────────────────────────────
const submit = async () => {
  formError.value   = ''
  formSuccess.value = ''
  submitting.value  = true

  try {
    const payload = {
      role:     form.value.role,
      name:     form.value.name,
      email:    form.value.email,
      password: form.value.password || undefined,
    }

    if (form.value.role === 'agent' && form.value.agent_id && !isEditMode.value) {
      payload.agent_id = Number(form.value.agent_id)
    }

    if (isEditMode.value) {
      await userStore.updateUser(editUserId.value, payload)
      formSuccess.value = 'Utilisateur modifié avec succès.'
    } else {
      await userStore.createUser(payload)
      formSuccess.value = 'Compte créé avec succès.'
    }

    // Fermer après un court délai pour que l'utilisateur voit le succès
    setTimeout(() => { showModal.value = false }, 900)
  } catch (err) {
    const errors = err.response?.data?.errors
    if (errors) {
      formError.value = Object.values(errors).flat().join(' ')
    } else {
      formError.value = err.response?.data?.message ?? 'Une erreur est survenue.'
    }
  } finally {
    submitting.value = false
  }
}

// ── Toggle statut ──────────────────────────────────────────────────────────
const toggleStatus = async (user) => {
  try {
    await userStore.toggleStatus(user)
  } catch (err) {
    console.error(err)
  }
}

// ── Export users ──────────────────────────────────────────────────────────
const exportUsers = async () => {
  try {
    const response = await api.get('/users/export/excel', {
      responseType: 'blob',
    })

    const contentDisposition = response.headers['content-disposition']
    let filename = 'utilisateurs.xlsx'
    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename="?([^"]+)"?/)
      if (filenameMatch && filenameMatch[1]) {
        filename = filenameMatch[1]
      }
    }

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Erreur lors de l\'export:', error)
  }
}

// ── Helpers visuels ────────────────────────────────────────────────────────
const roleBadge = (role) => ({
  admin:        'bg-rose-50 text-rose-600 border-rose-100',
  gestionnaire: 'bg-amber-50 text-amber-600 border-amber-100',
  agent:        'bg-blue-50 text-blue-600 border-blue-100',
}[role] ?? 'bg-slate-100 text-slate-500 border-slate-200')

const roleLabel = (role) => ({
  admin:        'Administrateur',
  gestionnaire: 'Gestionnaire',
  agent:        'Agent',
}[role] ?? role)

// ── Init ───────────────────────────────────────────────────────────────────
onMounted(async () => {
  userStore.fetchUsers()
})

// ── Formatage ──────────────────────────────────────────────────────────────
const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  }).format(new Date(dateStr))
}

const etatBadge = (etat) => ({
  neuf:              'bg-blue-50 text-blue-600',
  en_service:        'bg-emerald-50 text-emerald-600',
  en_panne:          'bg-red-50 text-red-600',
  en_maintenance:    'bg-amber-50 text-amber-600',
  en_attente_sinistre: 'bg-orange-50 text-orange-600',
  reforme:           'bg-slate-100 text-slate-500',
  perdu:             'bg-rose-50 text-rose-600',
}[etat] ?? 'bg-slate-100 text-slate-500')
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">

    <!-- Header -->
    <PageHeader title="Administration" subtitle="Gestion des accès et comptes utilisateurs">
      <template #actions>
        <div class="flex items-center gap-3">
          <button
            @click="exportUsers"
            class="flex items-center gap-2 px-4 py-2.5 bg-emerald-600 rounded-xl text-sm font-bold text-white hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-200"
          >
            <Download class="w-4 h-4" />
            Exporter Excel
          </button>
          <button
            @click="openCreate"
            class="flex items-center gap-2 px-5 py-2.5 bg-primary-600 rounded-xl text-sm font-bold text-white hover:bg-primary-700 transition-all shadow-lg shadow-primary-200"
          >
            <UserPlus class="w-4 h-4" />
            Nouvel utilisateur
          </button>
        </div>
      </template>
    </PageHeader>

    <!-- Table card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">

      <!-- Barre de recherche + filtres rôle -->
      <div class="p-6 border-b border-slate-100 space-y-4">

        <!-- Tabs rôle -->
        <div class="flex items-center gap-1.5 flex-wrap">
          <button
            v-for="tab in [
              { value: '',           label: 'Tous',         color: 'slate'  },
              { value: 'admin',      label: 'Admins',       color: 'rose'   },
              { value: 'gestionnaire', label: 'Gestionnaires', color: 'amber' },
              { value: 'agent',      label: 'Agents',       color: 'blue'   },
            ]"
            :key="tab.value"
            @click="filterRole = tab.value"
            :class="[
              'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all',
              filterRole === tab.value
                ? {
                    '': 'bg-slate-900 text-white shadow-md',
                    admin: 'bg-rose-500 text-white shadow-md shadow-rose-100',
                    gestionnaire: 'bg-amber-500 text-white shadow-md shadow-amber-100',
                    agent: 'bg-blue-500 text-white shadow-md shadow-blue-100',
                  }[tab.value]
                : 'bg-slate-100 text-slate-500 hover:bg-slate-200'
            ]"
          >
            {{ tab.label }}
            <span
              :class="[
                'px-1.5 py-0.5 rounded-md text-[10px] font-black tabular-nums',
                filterRole === tab.value ? 'bg-white/25 text-white' : 'bg-white text-slate-500'
              ]"
            >
              {{ countByRole[tab.value] }}
            </span>
          </button>
        </div>

        <!-- Recherche + loader + toggle inactifs -->
        <div class="flex items-center gap-3">
          <div class="relative flex-1 max-w-sm">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher par nom ou email..."
              class="w-full h-10 pl-10 pr-4 bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary-500/10 outline-none"
            >
          </div>

          <!-- Toggle inactifs -->
          <button
            @click="filterInactif = !filterInactif"
            :class="[
              'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all border',
              filterInactif
                ? 'bg-slate-800 text-white border-slate-800 shadow-md'
                : 'bg-white text-slate-500 border-slate-200 hover:border-slate-300'
            ]"
          >
            <PowerOff class="w-3.5 h-3.5" />
            Inactifs
            <span :class="['px-1.5 py-0.5 rounded-md text-[10px] font-black tabular-nums', filterInactif ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500']">
              {{ inactifCount }}
            </span>
          </button>

          <div v-if="userStore.loading" class="flex items-center gap-2 text-slate-400">
            <Loader2 class="w-4 h-4 animate-spin" />
            <span class="text-xs font-medium">Chargement...</span>
          </div>
        </div>

      </div>

      <!-- Tableau -->
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-slate-50/50 text-[11px] font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100">
              <th class="px-8 py-4">Utilisateur</th>
              <th class="px-6 py-4">Rôle</th>
              <th class="px-6 py-4">Agent lié</th>
              <th class="px-6 py-4">Statut</th>
              <th class="px-8 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr
              v-for="user in filteredUsers"
              :key="user.id"
              class="group hover:bg-slate-50/30 transition-colors"
            >
              <!-- Utilisateur -->
              <td class="px-8 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white text-sm font-bold shadow-md shadow-primary-100 flex-shrink-0">
                    {{ user.name?.charAt(0)?.toUpperCase() }}
                  </div>
                  <div>
                    <p class="text-sm font-bold text-slate-900">{{ user.name }}</p>
                    <p class="text-xs text-slate-500 font-medium">{{ user.email }}</p>
                  </div>
                </div>
              </td>

              <!-- Rôle -->
              <td class="px-6 py-4">
                <span :class="['px-2.5 py-1 rounded-lg text-[10px] font-bold border uppercase tracking-wider', roleBadge(user.role)]">
                  {{ roleLabel(user.role) }}
                </span>
              </td>

              <!-- Agent lié -->
              <td class="px-6 py-4">
                <span v-if="user.agent" class="flex items-center gap-1.5 text-xs font-medium text-slate-700">
                  <UserCheck class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" />
                  {{ user.agent.prenom }} {{ user.agent.nom }}
                  <span class="text-slate-400 font-mono">· {{ user.agent.matricule }}</span>
                </span>
                <span v-else class="text-slate-300 text-xs">—</span>
              </td>

              <!-- Statut -->
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <div :class="['w-2 h-2 rounded-full', user.is_active ? 'bg-emerald-500' : 'bg-slate-300']"></div>
                  <span :class="['text-xs font-bold', user.is_active ? 'text-emerald-600' : 'text-slate-400']">
                    {{ user.is_active ? 'Actif' : 'Inactif' }}
                  </span>
                </div>
              </td>

              <!-- Actions -->
              <td class="px-8 py-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="openDetail(user)"
                    title="Voir le détail"
                    class="p-2 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all"
                  >
                    <Eye class="w-4 h-4" />
                  </button>
                  <button
                    @click="toggleStatus(user)"
                    :disabled="user.id === authStore.user?.id"
                    :title="user.id === authStore.user?.id ? 'Vous ne pouvez pas désactiver votre propre compte' : (user.is_active ? 'Désactiver' : 'Activer')"
                    :class="['p-2 rounded-lg transition-all', user.id === authStore.user?.id ? 'opacity-30 cursor-not-allowed text-slate-300' : user.is_active ? 'text-amber-500 hover:bg-amber-50' : 'text-emerald-500 hover:bg-emerald-50']"
                  >
                    <component :is="user.is_active ? PowerOff : Power" class="w-4 h-4" />
                  </button>
                  <button
                    @click="openEdit(user)"
                    class="p-2 text-slate-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all"
                  >
                    <Edit3 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>

            <!-- État vide -->
            <tr v-if="!userStore.loading && filteredUsers.length === 0">
              <td colspan="5" class="px-8 py-16 text-center">
                <p class="text-sm font-medium text-slate-400">Aucun utilisateur trouvé</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── Modal création / édition ─────────────────────────────────────── -->
    <SideModal
      :show="showModal"
      :title="isEditMode ? 'Modifier l\'utilisateur' : 'Créer un compte utilisateur'"
      mode="center"
      @close="showModal = false"
    >
      <form @submit.prevent="submit" class="space-y-5">

        <!-- Feedback erreur / succès -->
        <div
          v-if="formError"
          class="flex items-start gap-3 p-4 bg-red-50 border border-red-100 rounded-2xl text-sm text-red-600"
        >
          <AlertCircle class="w-4 h-4 mt-0.5 flex-shrink-0" />
          {{ formError }}
        </div>
        <div
          v-if="formSuccess"
          class="flex items-start gap-3 p-4 bg-emerald-50 border border-emerald-100 rounded-2xl text-sm text-emerald-600"
        >
          <CheckCircle2 class="w-4 h-4 mt-0.5 flex-shrink-0" />
          {{ formSuccess }}
        </div>

        <!-- 1. Rôle (toujours en haut) -->
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Rôle</label>
          <div class="relative">
            <select
              v-model="form.role"
              :disabled="isEditMode"
              class="w-full h-12 pl-4 pr-10 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-medium outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all appearance-none disabled:opacity-60 disabled:cursor-not-allowed"
            >
              <option value="admin">Administrateur</option>
              <option value="gestionnaire">Gestionnaire</option>
              <option value="agent">Agent</option>
            </select>
            <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
          </div>
        </div>

        <!-- 2. Sélection d'agent (uniquement rôle=agent + création) -->
        <Transition
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 -translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-2"
        >
          <div
            v-if="form.role === 'agent' && !isEditMode"
            class="space-y-1.5 p-4 bg-blue-50/60 border border-blue-100 rounded-2xl"
          >
            <label class="text-xs font-bold text-blue-700 uppercase tracking-wider flex items-center gap-1.5">
              <UserCheck class="w-3.5 h-3.5" />
              Lier à un agent existant
            </label>

            <!-- Loading agents -->
            <div v-if="agentStore.loading" class="flex items-center gap-2 py-2 text-xs text-slate-400">
              <Loader2 class="w-3.5 h-3.5 animate-spin" />
              Chargement des agents...
            </div>

            <!-- Aucun agent sans compte -->
            <div
              v-else-if="agentStore.agentsSansCompte.length === 0"
              class="py-2 text-xs text-slate-500 italic"
            >
              Tous les agents ont déjà un compte.
            </div>

            <!-- Select agents sans compte -->
            <div v-else class="relative">
              <select
                v-model="form.agent_id"
                class="w-full h-11 pl-4 pr-10 bg-white border border-blue-200 rounded-xl text-sm font-medium outline-none focus:ring-2 focus:ring-blue-400/30 focus:border-blue-400 transition-all appearance-none"
              >
                <option :value="null">— Sélectionner un agent —</option>
                <option
                  v-for="agent in agentStore.agentsSansCompte"
                  :key="agent.id"
                  :value="agent.id"
                >
                  {{ agent.prenom }} {{ agent.nom }} · {{ agent.matricule }}
                  <template v-if="agent.service"> · {{ agent.service }}</template>
                </option>
              </select>
              <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-blue-400 pointer-events-none" />
            </div>

            <p class="text-[11px] text-blue-500/80 font-medium">
              Si vous sélectionnez un agent, son nom et email seront pré-remplis automatiquement.
            </p>
          </div>
        </Transition>

        <!-- 3. Nom et Email -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nom complet</label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="ex: Amélie Dubois"
              class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all"
            >
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Email</label>
            <input
              v-model="form.email"
              type="email"
              required
              placeholder="email@entreprise.com"
              class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all"
            >
          </div>
        </div>

        <!-- 4. Mot de passe -->
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">
            {{ isEditMode ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe' }}
          </label>
          <input
            v-model="form.password"
            type="password"
            :required="!isEditMode"
            placeholder="••••••••  (8 caractères minimum)"
            class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all"
          >
        </div>

        <!-- Submit -->
        <div class="pt-2">
          <button
            type="submit"
            :disabled="submitting"
            class="w-full py-3.5 bg-primary-600 text-white font-bold rounded-2xl hover:bg-primary-700 transition-all shadow-lg shadow-primary-200 flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed"
          >
            <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
            {{ isEditMode ? 'Enregistrer les modifications' : 'Créer le compte' }}
          </button>
        </div>

      </form>
    </SideModal>

    <!-- ── Panel détail utilisateur ────────────────────────────────────── -->
    <SideModal
      :show="showDetail"
      title="Détail du compte"
      mode="side"
      @close="showDetail = false"
    >
      <!-- Chargement -->
      <div v-if="userStore.loadingDetail" class="flex flex-col items-center justify-center py-24 gap-4">
        <Loader2 class="w-8 h-8 animate-spin text-primary-400" />
        <p class="text-sm text-slate-400 font-medium">Chargement du profil...</p>
      </div>

      <div v-else-if="userStore.selectedUser" class="space-y-8">

        <!-- ── En-tête profil ──────────────────────────────────────────── -->
        <div class="flex items-center gap-5">
          <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white text-2xl font-bold shadow-xl shadow-primary-100 flex-shrink-0">
            {{ userStore.selectedUser.name?.charAt(0)?.toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <h3 class="text-xl font-bold text-slate-900 truncate">{{ userStore.selectedUser.name }}</h3>
              <span :class="['px-2.5 py-0.5 rounded-lg text-[10px] font-bold border uppercase tracking-wider flex-shrink-0', roleBadge(userStore.selectedUser.role)]">
                {{ roleLabel(userStore.selectedUser.role) }}
              </span>
            </div>
            <p class="text-sm text-slate-500 font-medium truncate mt-0.5">{{ userStore.selectedUser.email }}</p>
            <div class="flex items-center gap-2 mt-2">
              <div :class="['w-2 h-2 rounded-full flex-shrink-0', userStore.selectedUser.is_active ? 'bg-emerald-500' : 'bg-slate-300']"></div>
              <span :class="['text-xs font-bold', userStore.selectedUser.is_active ? 'text-emerald-600' : 'text-slate-400']">
                {{ userStore.selectedUser.is_active ? 'Compte actif' : 'Compte désactivé' }}
              </span>
            </div>
          </div>
        </div>

        <!-- ── Infos compte ────────────────────────────────────────────── -->
        <div class="space-y-2">
          <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Informations du compte</p>
          <div class="grid grid-cols-2 gap-3">
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100">
              <div class="flex items-center gap-2 mb-1">
                <Hash class="w-3.5 h-3.5 text-slate-400" />
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">ID</p>
              </div>
              <p class="text-sm font-bold text-slate-900">#{{ userStore.selectedUser.id }}</p>
            </div>
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100">
              <div class="flex items-center gap-2 mb-1">
                <Shield class="w-3.5 h-3.5 text-slate-400" />
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sessions actives</p>
              </div>
              <p class="text-sm font-bold text-slate-900">{{ userStore.selectedUser.tokens_count ?? 0 }}</p>
            </div>
            <div class="col-span-2 p-3.5 bg-slate-50 rounded-2xl border border-slate-100">
              <div class="flex items-center gap-2 mb-1">
                <Clock class="w-3.5 h-3.5 text-slate-400" />
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Dernière connexion</p>
              </div>
              <p class="text-sm font-bold text-slate-900">{{ formatDate(userStore.selectedUser.last_login_at) }}</p>
            </div>
            <div class="col-span-2 p-3.5 bg-slate-50 rounded-2xl border border-slate-100">
              <div class="flex items-center gap-2 mb-1">
                <Calendar class="w-3.5 h-3.5 text-slate-400" />
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Compte créé le</p>
              </div>
              <p class="text-sm font-bold text-slate-900">{{ formatDate(userStore.selectedUser.created_at) }}</p>
            </div>
          </div>
        </div>



        <!-- ── Agent lié ───────────────────────────────────────────────── -->
        <div v-if="userStore.selectedUser.agent" class="space-y-3">
          <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Profil agent lié</p>
          <div class="p-4 bg-blue-50/60 border border-blue-100 rounded-2xl space-y-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-blue-500 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                {{ userStore.selectedUser.agent.prenom?.charAt(0) }}{{ userStore.selectedUser.agent.nom?.charAt(0) }}
              </div>
              <div>
                <p class="text-sm font-bold text-slate-900">
                  {{ userStore.selectedUser.agent.prenom }} {{ userStore.selectedUser.agent.nom }}
                </p>
                <p class="text-xs font-mono text-slate-500">{{ userStore.selectedUser.agent.matricule }}</p>
              </div>
              <span :class="['ml-auto px-2 py-0.5 rounded-lg text-[10px] font-bold flex-shrink-0', userStore.selectedUser.agent.statut === 'actif' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400']">
                {{ userStore.selectedUser.agent.statut }}
              </span>
            </div>
            <div class="grid grid-cols-2 gap-2 pt-1 border-t border-blue-100">
              <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase">Direction</p>
                <p class="text-xs font-semibold text-slate-700 mt-0.5">{{ userStore.selectedUser.agent.direction || '—' }}</p>
              </div>
              <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase">Service</p>
                <p class="text-xs font-semibold text-slate-700 mt-0.5">{{ userStore.selectedUser.agent.service || '—' }}</p>
              </div>
              <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase">Poste</p>
                <p class="text-xs font-semibold text-slate-700 mt-0.5">{{ userStore.selectedUser.agent.poste || '—' }}</p>
              </div>
              <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase">Téléphone</p>
                <p class="text-xs font-semibold text-slate-700 mt-0.5">{{ userStore.selectedUser.agent.telephone || '—' }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Équipements affectés ────────────────────────────────────── -->
        <div v-if="userStore.selectedUser.agent?.affectations?.length" class="space-y-3">
          <div class="flex items-center justify-between">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Équipements affectés</p>
            <span class="text-[10px] font-bold text-primary-600 bg-primary-50 px-2 py-0.5 rounded-lg">
              {{ userStore.selectedUser.agent.affectations.filter(a => a.statut === 'en_cours').length }} en cours
            </span>
          </div>
          <div class="space-y-2">
            <div
              v-for="aff in userStore.selectedUser.agent.affectations"
              :key="aff.id"
              class="p-3.5 bg-white border border-slate-200 rounded-2xl flex items-center gap-3 hover:border-primary-200 transition-colors"
            >
              <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                <Package class="w-4 h-4 text-slate-400" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-slate-900 truncate">
                  {{ aff.equipement?.marque }} {{ aff.equipement?.modele }}
                </p>
                <p class="text-[10px] font-mono text-slate-400 uppercase">{{ aff.equipement?.reference }}</p>
              </div>
              <div class="flex flex-col items-end gap-1 flex-shrink-0">
                <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-md', aff.statut === 'en_cours' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400']">
                  {{ aff.statut === 'en_cours' ? 'En cours' : 'Retourné' }}
                </span>
                <span v-if="aff.equipement?.etat" :class="['text-[10px] font-bold px-2 py-0.5 rounded-md', etatBadge(aff.equipement.etat)]">
                  {{ aff.equipement.etat.replace('_', ' ') }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Aucune affectation ──────────────────────────────────────── -->
        <div
          v-else-if="userStore.selectedUser.role === 'agent'"
          class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200"
        >
          <Package class="w-8 h-8 text-slate-300 mx-auto mb-2" />
          <p class="text-sm font-medium text-slate-400">Aucun équipement affecté</p>
        </div>

        <!-- ── Actions rapides ─────────────────────────────────────────── -->
        <div class="flex gap-3 pt-2 border-t border-slate-100">
          <button
            @click="showDetail = false; openEdit(userStore.selectedUser)"
            class="flex-1 flex items-center justify-center gap-2 py-3 bg-primary-600 text-white text-sm font-bold rounded-2xl hover:bg-primary-700 transition-all"
          >
            <Edit3 class="w-4 h-4" />
            Modifier
          </button>
          <button
            @click="toggleStatus(userStore.selectedUser)"
            :disabled="userStore.selectedUser.id === authStore.user?.id"
            :class="['flex-1 flex items-center justify-center gap-2 py-3 text-sm font-bold rounded-2xl transition-all border',
              userStore.selectedUser.id === authStore.user?.id
                ? 'opacity-30 cursor-not-allowed border-slate-200 text-slate-400'
                : userStore.selectedUser.is_active
                  ? 'border-amber-200 text-amber-600 hover:bg-amber-50'
                  : 'border-emerald-200 text-emerald-600 hover:bg-emerald-50'
            ]"
          >
            <component :is="userStore.selectedUser.is_active ? PowerOff : Power" class="w-4 h-4" />
            {{ userStore.selectedUser.is_active ? 'Désactiver' : 'Activer' }}
          </button>
        </div>

      </div>
    </SideModal>

  </div>
</template>
