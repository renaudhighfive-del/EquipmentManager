<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useUserStore } from '../../stores/user'
import PageHeader from '../../components/layout/PageHeader.vue'
import {
  User, Lock, Eye, EyeOff, Check, AlertCircle,
  CheckCircle2, LogOut, Calendar, KeyRound, X, Camera
} from 'lucide-vue-next'

const authStore = useAuthStore()
const userStore = useUserStore()

// ── Avatar upload ────────────────────────────────────────────────────────
const avatarFile    = ref(null)
const avatarPreview = ref(
  authStore.user?.avatar ? `/storage/${authStore.user.avatar}` : null
)

const handleAvatarChange = (e) => {
  const file = e.target.files[0]
  if (!file) return
  avatarFile.value    = file
  avatarPreview.value = URL.createObjectURL(file)
}

// ── Infos profil ────────────────────────────────────────────────────────
const profileForm = ref({
  name:  authStore.user?.name  ?? '',
  email: authStore.user?.email ?? '',
})
const profileSubmitting = ref(false)
const profileSuccess    = ref('')
const profileError      = ref('')

// ── Mot de passe ─────────────────────────────────────────────────────────
const passwordForm = ref({
  current_password:      '',
  password:              '',
  password_confirmation: '',
})
const showCurrent  = ref(false)
const showNew      = ref(false)
const showConfirm  = ref(false)
const pwdSubmitting = ref(false)
const pwdSuccess    = ref('')
const pwdError      = ref('')

// Validation mot de passe en temps réel
const pwdRules = computed(() => ({
  length:    passwordForm.value.password.length >= 8,
  uppercase: /[A-Z]/.test(passwordForm.value.password),
  digit:     /\d/.test(passwordForm.value.password),
  match:     passwordForm.value.password === passwordForm.value.password_confirmation
            && passwordForm.value.password.length > 0,
}))
const pwdValid = computed(() => Object.values(pwdRules.value).every(Boolean))

// ── Role label ───────────────────────────────────────────────────────────
const roleLabel = (role) => ({ admin: 'Administrateur', gestionnaire: 'Gestionnaire', agent: 'Agent' }[role] ?? role)
const roleBadgeClass = (role) => ({
  admin:        'bg-rose-100 text-rose-600',
  gestionnaire: 'bg-amber-100 text-amber-600',
  agent:        'bg-blue-100 text-blue-600',
}[role] ?? 'bg-slate-100 text-slate-500')

const formatDate = (d) => {
  if (!d) return '—'
  return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(d))
}

// ── Soumettre profil ─────────────────────────────────────────────────────
const submitProfile = async () => {
  profileError.value      = ''
  profileSuccess.value    = ''
  profileSubmitting.value = true
  try {
    let payload

    if (avatarFile.value) {
      // Utiliser FormData si un avatar est sélectionné
      // Laravel ne supporte pas multipart sur PUT → POST + _method
      payload = new FormData()
      payload.append('_method', 'PUT')
      payload.append('name',  profileForm.value.name)
      payload.append('email', profileForm.value.email)
      payload.append('avatar', avatarFile.value)
    } else {
      payload = {
        name:  profileForm.value.name,
        email: profileForm.value.email,
      }
    }

    const updated = await userStore.updateProfile(payload)
    // Sync auth store
    authStore.user = { ...authStore.user, ...updated }
    localStorage.setItem('user', JSON.stringify(authStore.user))
    // Reset le fichier sélectionné après succès
    avatarFile.value = null
    // Mettre à jour le preview avec l'URL serveur
    if (updated.avatar) {
      avatarPreview.value = `/storage/${updated.avatar}`
    }
    profileSuccess.value = 'Informations mises à jour.'
  } catch (err) {
    const errors = err.response?.data?.errors
    profileError.value = errors
      ? Object.values(errors).flat().join(' ')
      : err.response?.data?.message ?? 'Une erreur est survenue.'
  } finally {
    profileSubmitting.value = false
    setTimeout(() => { profileSuccess.value = '' }, 3000)
  }
}

// ── Soumettre mot de passe ───────────────────────────────────────────────
const submitPassword = async () => {
  if (!pwdValid.value) return
  pwdError.value   = ''
  pwdSuccess.value = ''
  pwdSubmitting.value = true
  try {
    await userStore.changePassword({
      current_password:      passwordForm.value.current_password,
      password:              passwordForm.value.password,
      password_confirmation: passwordForm.value.password_confirmation,
    })
    pwdSuccess.value = 'Mot de passe mis à jour.'
    passwordForm.value = { current_password: '', password: '', password_confirmation: '' }
  } catch (err) {
    const errors = err.response?.data?.errors
    pwdError.value = errors
      ? Object.values(errors).flat().join(' ')
      : err.response?.data?.message ?? 'Une erreur est survenue.'
  } finally {
    pwdSubmitting.value = false
    setTimeout(() => { pwdSuccess.value = '' }, 3000)
  }
}

// ── Se déconnecter de partout ─────────────────────────────────────────────
const logoutAll = async () => {
  await authStore.logout()
  window.location.href = '/login'
}

onMounted(() => {
  profileForm.value = {
    name:  authStore.user?.name  ?? '',
    email: authStore.user?.email ?? '',
  }
})
</script>

<template>
  <div class="space-y-8 animate-in fade-in duration-500 max-w-5xl">

    <PageHeader title="Mon profil" subtitle="Gérez vos informations personnelles et vos préférences de compte" />

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">

      <!-- ── Colonne gauche (formulaires) ──────────────────────────── -->
      <div class="lg:col-span-3 space-y-6">

        <!-- Informations personnelles -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-7 py-5 border-b border-slate-100 flex items-center gap-3">
            <div class="w-8 h-8 bg-primary-50 rounded-xl flex items-center justify-center">
              <User class="w-4 h-4 text-primary-600" />
            </div>
            <h2 class="text-sm font-bold text-slate-900">Informations personnelles</h2>
          </div>

          <form @submit.prevent="submitProfile" class="p-7 space-y-5">
            <!-- Feedback -->
            <div v-if="profileError" class="flex items-start gap-3 p-4 bg-red-50 border border-red-100 rounded-2xl text-sm text-red-600">
              <AlertCircle class="w-4 h-4 mt-0.5 flex-shrink-0" />
              {{ profileError }}
            </div>
            <div v-if="profileSuccess" class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-100 rounded-2xl text-sm text-emerald-600">
              <CheckCircle2 class="w-4 h-4 flex-shrink-0" />
              {{ profileSuccess }}
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nom complet</label>
                <input
                  v-model="profileForm.name"
                  type="text"
                  required
                  class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all"
                >
              </div>
              <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Email</label>
                <input
                  v-model="profileForm.email"
                  type="email"
                  required
                  class="w-full h-11 px-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all"
                >
              </div>
            </div>

            <!-- Champs non éditables -->
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Rôle</label>
                <div class="h-11 px-4 bg-slate-100 border border-slate-200 rounded-2xl text-sm font-medium text-slate-500 flex items-center cursor-not-allowed select-none">
                  {{ roleLabel(authStore.user?.role) }}
                </div>
              </div>
              <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Statut</label>
                <div class="h-11 px-4 bg-slate-100 border border-slate-200 rounded-2xl text-sm flex items-center gap-2 cursor-not-allowed select-none">
                  <div class="w-2 h-2 bg-emerald-500 rounded-full"></div>
                  <span class="font-medium text-emerald-700">Actif</span>
                </div>
              </div>
            </div>

            <button
              type="submit"
              :disabled="profileSubmitting"
              class="flex items-center gap-2 px-6 py-2.5 bg-primary-600 text-white text-sm font-bold rounded-2xl hover:bg-primary-700 transition-all shadow-lg shadow-primary-100 disabled:opacity-60"
            >
              <Check v-if="!profileSubmitting" class="w-4 h-4" />
              <div v-else class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
              Enregistrer
            </button>
          </form>
        </div>

        <!-- Sécurité & mot de passe -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-7 py-5 border-b border-slate-100 flex items-center gap-3">
            <div class="w-8 h-8 bg-amber-50 rounded-xl flex items-center justify-center">
              <Lock class="w-4 h-4 text-amber-600" />
            </div>
            <h2 class="text-sm font-bold text-slate-900">Sécurité & mot de passe</h2>
          </div>

          <form @submit.prevent="submitPassword" class="p-7 space-y-5">
            <!-- Feedback -->
            <div v-if="pwdError" class="flex items-start gap-3 p-4 bg-red-50 border border-red-100 rounded-2xl text-sm text-red-600">
              <AlertCircle class="w-4 h-4 mt-0.5 flex-shrink-0" />
              {{ pwdError }}
            </div>
            <div v-if="pwdSuccess" class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-100 rounded-2xl text-sm text-emerald-600">
              <CheckCircle2 class="w-4 h-4 flex-shrink-0" />
              {{ pwdSuccess }}
            </div>

            <!-- Mot de passe actuel -->
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mot de passe actuel</label>
              <div class="relative">
                <input
                  v-model="passwordForm.current_password"
                  :type="showCurrent ? 'text' : 'password'"
                  required
                  placeholder="••••••••"
                  class="w-full h-11 px-4 pr-12 bg-slate-50 border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all"
                >
                <button type="button" @click="showCurrent = !showCurrent" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                  <component :is="showCurrent ? EyeOff : Eye" class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- Nouveau + confirmation -->
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nouveau mot de passe</label>
                <div class="relative">
                  <input
                    v-model="passwordForm.password"
                    :type="showNew ? 'text' : 'password'"
                    required
                    placeholder="••••••••"
                    class="w-full h-11 px-4 pr-12 bg-slate-50 border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all"
                  >
                  <button type="button" @click="showNew = !showNew" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                    <component :is="showNew ? EyeOff : Eye" class="w-4 h-4" />
                  </button>
                </div>
              </div>
              <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Confirmer</label>
                <div class="relative">
                  <input
                    v-model="passwordForm.password_confirmation"
                    :type="showConfirm ? 'text' : 'password'"
                    required
                    placeholder="••••••••"
                    class="w-full h-11 px-4 pr-12 bg-slate-50 border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all"
                  >
                  <button type="button" @click="showConfirm = !showConfirm" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                    <component :is="showConfirm ? EyeOff : Eye" class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>

            <!-- Règles de validation visuelles -->
            <div v-if="passwordForm.password" class="grid grid-cols-2 gap-2">
              <div v-for="rule in [
                { key: 'length',    label: '8 caractères minimum' },
                { key: 'uppercase', label: 'Une majuscule' },
                { key: 'digit',     label: 'Un chiffre' },
                { key: 'match',     label: 'Mots de passe identiques' },
              ]" :key="rule.key" :class="['flex items-center gap-2 text-xs font-medium', pwdRules[rule.key] ? 'text-emerald-600' : 'text-slate-400']">
                <div :class="['w-4 h-4 rounded-full flex items-center justify-center flex-shrink-0', pwdRules[rule.key] ? 'bg-emerald-100' : 'bg-slate-100']">
                  <Check v-if="pwdRules[rule.key]" class="w-2.5 h-2.5" />
                  <X v-else class="w-2.5 h-2.5" />
                </div>
                {{ rule.label }}
              </div>
            </div>

            <button
              type="submit"
              :disabled="pwdSubmitting || !pwdValid || !passwordForm.current_password"
              class="flex items-center gap-2 px-6 py-2.5 bg-amber-500 text-white text-sm font-bold rounded-2xl hover:bg-amber-600 transition-all shadow-lg shadow-amber-100 disabled:opacity-60 disabled:cursor-not-allowed"
            >
              <div v-if="pwdSubmitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
              <KeyRound v-else class="w-4 h-4" />
              Mettre à jour
            </button>
          </form>
        </div>

        <!-- Zone de danger -->
        <div class="bg-white rounded-3xl border border-rose-100 shadow-sm overflow-hidden">
          <div class="px-7 py-5 border-b border-rose-50 flex items-center gap-3">
            <div class="w-8 h-8 bg-rose-50 rounded-xl flex items-center justify-center">
              <LogOut class="w-4 h-4 text-rose-500" />
            </div>
            <h2 class="text-sm font-bold text-slate-900">Zone de danger</h2>
          </div>
          <div class="p-7">
            <p class="text-sm text-slate-500 mb-5">Cette action déconnectera toutes vos sessions actives sur tous les appareils.</p>
            <button
              @click="logoutAll"
              class="flex items-center gap-2 px-6 py-2.5 border border-rose-200 text-rose-600 text-sm font-bold rounded-2xl hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all"
            >
              <LogOut class="w-4 h-4" />
              Se déconnecter de partout
            </button>
          </div>
        </div>

      </div>

      <!-- ── Colonne droite (carte profil + stats) ──────────────────── -->
      <div class="lg:col-span-2 space-y-6">

        <!-- Carte identité -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-7 text-center">
          <!-- Avatar avec upload -->
          <div class="relative inline-block mb-4 group">
            <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-white shadow-xl mx-auto bg-gradient-to-br from-primary-500 to-primary-600">
              <img
                v-if="avatarPreview"
                :src="avatarPreview"
                class="w-full h-full object-cover"
                :alt="authStore.user?.name"
              >
              <div v-else class="w-full h-full flex items-center justify-center text-white text-3xl font-bold">
                {{ authStore.user?.name?.charAt(0)?.toUpperCase() }}
              </div>
            </div>
            <!-- Bouton overlay -->
            <label class="absolute -bottom-1 -right-1 w-8 h-8 bg-primary-600 rounded-full flex items-center justify-center cursor-pointer shadow-lg hover:bg-primary-700 transition-colors">
              <Camera class="w-4 h-4 text-white" />
              <input
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="hidden"
                @change="handleAvatarChange"
              >
            </label>
          </div>

          <h3 class="text-xl font-bold text-slate-900">{{ authStore.user?.name }}</h3>
          <span :class="['inline-block mt-1.5 px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider', roleBadgeClass(authStore.user?.role)]">
            {{ roleLabel(authStore.user?.role) }}
          </span>
          <p class="text-sm text-slate-500 mt-3 font-medium">{{ authStore.user?.email }}</p>

          <!-- Indication si photo sélectionnée non encore sauvegardée -->
          <p v-if="avatarFile" class="text-[11px] text-amber-600 font-medium mt-3 flex items-center justify-center gap-1.5">
            <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span>
            Photo non sauvegardée — cliquez sur "Enregistrer"
          </p>
          <p v-else class="text-[11px] text-slate-400 mt-2">Cliquez sur l'icône pour changer la photo</p>
        </div>

        <!-- Informations du compte -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
            <Calendar class="w-4 h-4 text-slate-400" />
            <h3 class="text-sm font-bold text-slate-900">Informations du compte</h3>
          </div>
          <div class="divide-y divide-slate-50">
            <div class="px-6 py-3.5 flex items-center justify-between">
              <span class="text-xs font-medium text-slate-500">Membre depuis</span>
              <span class="text-xs font-bold text-slate-900">{{ formatDate(authStore.user?.created_at) }}</span>
            </div>
            <div class="px-6 py-3.5 flex items-center justify-between">
              <span class="text-xs font-medium text-slate-500">Accès</span>
              <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-600 text-[10px] font-bold rounded-lg">
                {{ authStore.user?.role === 'admin' ? 'Complet' : 'Limité' }}
              </span>
            </div>
            <div class="px-6 py-3.5 flex items-center justify-between">
              <span class="text-xs font-medium text-slate-500">Compte</span>
              <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-600 text-[10px] font-bold rounded-lg">Actif</span>
            </div>
            <div class="px-6 py-3.5 flex items-center justify-between">
              <span class="text-xs font-medium text-slate-500">Authentification</span>
              <span class="px-2.5 py-0.5 bg-amber-50 text-amber-600 text-[10px] font-bold rounded-lg">OTP activé</span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>
