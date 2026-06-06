<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { Mail, Lock, LogIn, Chrome, AlertCircle } from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const rememberMe = ref(false)
const isLoading = ref(false)
const error = ref('')

const handleLogin = async () => {
  isLoading.value = true
  error.value = ''
  try {
    const result = await authStore.login({
      email: email.value,
      password: password.value,
      remember: rememberMe.value
    })

    if (result.status === 'otp_required') {
      router.push('/otp')
    } else {
      router.push(`/${result.role}/dashboard`)
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Identifiants invalides'
  } finally {
    isLoading.value = false
  }
}

const handleGoogleLogin = () => {
  authStore.googleLogin()
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-900 via-primary-800 to-slate-900 p-4">
    <!-- Background decorations -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-24 -left-24 w-96 h-96 bg-primary-500/20 rounded-full blur-3xl"></div>
      <div class="absolute top-1/2 -right-24 w-80 h-80 bg-blue-500/20 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-md relative">
      <!-- Glass Card -->
      <div class="glass rounded-2xl p-8 shadow-2xl border border-white/10 animate-in fade-in zoom-in duration-500">
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-16 h-16 rounded-xl bg-white/10 mb-4 border border-white/20">
            <LogIn class="w-8 h-8 text-white" />
          </div>
          <h1 class="text-3xl font-bold text-white mb-2">EquipTrack</h1>
          <p class="text-primary-100/70">Gestion & Traçabilité des Équipements</p>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-6">
          <div v-if="error" class="bg-red-500/20 border border-red-500/50 text-red-200 p-3 rounded-lg flex items-center gap-3 animate-shake">
            <AlertCircle class="w-5 h-5 flex-shrink-0" />
            <span class="text-sm">{{ error }}</span>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium text-white/80 ml-1">Email</label>
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-white/40 group-focus-within:text-primary-400 transition-colors">
                <Mail class="w-5 h-5" />
              </div>
              <input 
                v-model="email"
                type="email" 
                required
                class="block w-full pl-10 pr-3 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-white/20 focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all"
                placeholder="votre@email.com"
              >
            </div>
          </div>

          <div class="space-y-2">
            <div class="flex items-center justify-between ml-1">
              <label class="text-sm font-medium text-white/80">Mot de passe</label>
              <a href="#" class="text-xs text-primary-400 hover:text-primary-300 transition-colors">Mot de passe oublié ?</a>
            </div>
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-white/40 group-focus-within:text-primary-400 transition-colors">
                <Lock class="w-5 h-5" />
              </div>
              <input 
                v-model="password"
                type="password" 
                required
                class="block w-full pl-10 pr-3 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-white/20 focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all"
                placeholder="••••••••"
              >
            </div>
          </div>

          <div class="flex items-center">
            <input 
              v-model="rememberMe"
              id="remember-me" 
              type="checkbox" 
              class="h-4 w-4 rounded border-white/10 bg-white/5 text-primary-600 focus:ring-primary-500"
            >
            <label for="remember-me" class="ml-2 block text-sm text-white/60">Se souvenir de moi</label>
          </div>

          <button 
            type="submit" 
            :disabled="isLoading"
            class="w-full flex justify-center py-3 px-4 rounded-xl text-white bg-primary-600 hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 font-semibold shadow-lg shadow-primary-900/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="isLoading" class="flex items-center gap-2">
              <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewbox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Connexion en cours...
            </span>
            <span v-else>Se connecter</span>
          </button>
        </form>

        <div class="mt-8">
          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-white/10"></div>
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-4 bg-transparent text-white/40">Ou continuer avec</span>
            </div>
          </div>

          <div class="mt-6">
            <button 
              @click="handleGoogleLogin"
              class="w-full flex items-center justify-center gap-3 py-3 px-4 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-white transition-all"
            >
              <Chrome class="w-5 h-5 text-red-400" />
              <span>Google</span>
            </button>
          </div>
        </div>
      </div>
      
      <p class="mt-8 text-center text-sm text-white/40">
        &copy; 2026 EquipTrack. Tous droits réservés.
      </p>
    </div>
  </div>
</template>

<style scoped>
.glass {
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  border: 1px solid rgba(255, 255, 255, 0.12);
}

.animate-shake {
  animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
}

@keyframes shake {
  10%, 90% { transform: translate3d(-1px, 0, 0); }
  20%, 80% { transform: translate3d(2px, 0, 0); }
  30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
  40%, 60% { transform: translate3d(4px, 0, 0); }
}
</style>
