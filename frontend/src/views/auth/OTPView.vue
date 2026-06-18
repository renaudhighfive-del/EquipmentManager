<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { ShieldCheck, ArrowLeft, AlertCircle } from '@lucide/vue'

const router = useRouter()
const authStore = useAuthStore()

const otpCode = ref(['', '', '', '', '', ''])
const rememberMe = ref(false)
const isLoading = ref(false)
const error = ref('')
const successMessage = ref('')
const isResending = ref(false)

const handleInput = (index, event) => {
  const value = event.target.value
  if (value.length > 1) {
    otpCode.value[index] = value.slice(-1)
  }
  
  if (value && index < 5) {
    document.getElementById(`otp-${index + 1}`).focus()
  }
}

const handleKeyDown = (index, event) => {
  if (event.key === 'Backspace' && !otpCode.value[index] && index > 0) {
    document.getElementById(`otp-${index - 1}`).focus()
  }
}

const handleVerify = async () => {
  const code = otpCode.value.join('')
  if (code.length < 6) {
    error.value = 'Veuillez entrer le code complet'
    return
  }

  isLoading.value = true
  error.value = ''
  successMessage.value = ''
  try {
    const result = await authStore.verifyOTP(code, rememberMe.value)
    router.push(`/${result.role}/dashboard`)
  } catch (err) {
    error.value = err.response?.data?.message || 'Code invalide ou expiré'
  } finally {
    isLoading.value = false
  }
}

const handleResend = async () => {
  isResending.value = true
  error.value = ''
  successMessage.value = ''
  try {
    const result = await authStore.resendOTP()
    successMessage.value = result.message
    // Reset OTP inputs
    otpCode.value = ['', '', '', '', '', '']
    document.getElementById('otp-0').focus()
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur lors du renvoi du code'
  } finally {
    isResending.value = false
  }
}

onMounted(() => {
  if (!authStore.tempUser && !authStore.requiresOTP) {
    router.push('/login')
  }
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-primary-900 to-slate-900 p-4">
    <div class="w-full max-w-md relative">
      <div class="glass rounded-2xl p-8 shadow-2xl border border-white/10">
        <button 
          @click="router.push('/login')" 
          class="flex items-center text-sm text-white/60 hover:text-white mb-6 transition-colors"
        >
          <ArrowLeft class="w-4 h-4 mr-2" />
          Retour à la connexion
        </button>

        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-16 h-16 rounded-xl bg-primary-500/20 mb-4 border border-primary-500/30">
            <ShieldCheck class="w-8 h-8 text-primary-400" />
          </div>
          <h1 class="text-2xl font-bold text-white mb-2">Vérification OTP</h1>
          <p class="text-white/60 text-sm">
            Un code de vérification a été envoyé à <br>
            <span class="text-primary-300 font-medium">{{ authStore.tempUser?.email }}</span>
          </p>
        </div>

        <div class="space-y-6">
          <div v-if="error" class="bg-red-500/20 border border-red-500/50 text-red-200 p-3 rounded-lg flex items-center gap-3 animate-shake">
            <AlertCircle class="w-5 h-5 flex-shrink-0" />
            <span class="text-sm">{{ error }}</span>
          </div>

          <div v-if="successMessage" class="bg-emerald-500/20 border border-emerald-500/50 text-emerald-200 p-3 rounded-lg flex items-center gap-3 animate-in fade-in slide-in-from-top-2">
            <ShieldCheck class="w-5 h-5 flex-shrink-0" />
            <span class="text-sm">{{ successMessage }}</span>
          </div>

          <div class="flex justify-between gap-2">
            <input 
              v-for="(digit, index) in otpCode" 
              :key="index"
              :id="`otp-${index}`"
              v-model="otpCode[index]"
              type="text"
              maxlength="1"
              class="w-12 h-14 text-center text-2xl font-bold bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all"
              @input="handleInput(index, $event)"
              @keydown="handleKeyDown(index, $event)"
            >
          </div>

          <div class="flex items-center">
            <input 
              v-model="rememberMe"
              id="trust-device" 
              type="checkbox" 
              class="h-4 w-4 rounded border-white/10 bg-white/5 text-primary-600 focus:ring-primary-500"
            >
            <label for="trust-device" class="ml-2 block text-sm text-white/60">Faire confiance à cet appareil pour 30 jours</label>
          </div>

          <button 
            @click="handleVerify"
            :disabled="isLoading"
            class="w-full flex justify-center py-3 px-4 rounded-xl text-white bg-primary-600 hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 font-semibold shadow-lg shadow-primary-900/20 transition-all disabled:opacity-50"
          >
            <span v-if="isLoading" class="flex items-center gap-2">
              <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewbox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Vérification...
            </span>
            <span v-else>Vérifier le code</span>
          </button>

          <p class="text-center text-sm text-white/40">
            Vous n'avez pas reçu le code ? <br>
            <button 
              @click="handleResend"
              :disabled="isResending"
              class="text-primary-400 hover:text-primary-300 transition-colors mt-2 font-medium disabled:opacity-50"
            >
              {{ isResending ? 'Envoi en cours...' : 'Renvoyer le code' }}
            </button>
          </p>
        </div>
      </div>
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
