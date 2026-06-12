import { defineStore } from 'pinia'
import axios from 'axios'
import api from '../services/axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isAuthenticated: false,
    requiresOTP: false,
    tempUser: null,
    hasFetchedUser: false, //  Pour savoir si on a déjà chargé l'utilisateur
  }),

  actions: {
    async login(credentials) {
      try {
        // En Laravel Sanctum, on récupère d'abord le cookie CSRF (sans le préfixe /api)
        const csrfUrl = `${import.meta.env.VITE_API_URL.replace('/api', '')}/sanctum/csrf-cookie`
        await axios.get(csrfUrl, { withCredentials: true })
        
        const response = await api.post('/auth/login', credentials)
        
        // Si le backend demande un OTP
        if (response.data.requires_otp) {
          this.requiresOTP = true
          this.tempUser = response.data.user
          return { status: 'otp_required' }
        }

        this.setAuth(response.data)
        return { status: 'success', role: response.data.user.role }
      } catch (error) {
        throw error
      }
    },

    async verifyOTP(code, rememberMe = false) {
      try {
        const response = await api.post('/auth/verify-otp', {
          code,
          email: this.tempUser?.email,
          remember: rememberMe
        })

        this.setAuth(response.data)
        this.requiresOTP = false
        this.tempUser = null
        return { status: 'success', role: response.data.user.role }
      } catch (error) {
        throw error
      }
    },

    async resendOTP() {
      try {
        const response = await api.post('/auth/resend-otp', {
          email: this.tempUser?.email
        })
        return response.data
      } catch (error) {
        throw error
      }
    },

    async googleLogin() {
      // Redirection vers l'URL de login Google du backend
      window.location.href = `${import.meta.env.VITE_API_URL}/auth/google/redirect`
    },

    setAuth(data) {
      this.user = data.user
      this.isAuthenticated = true
      this.hasFetchedUser = true
    },

    async fetchUser() {
      try {
        const response = await api.get('/auth/me')
        this.user = response.data
        this.isAuthenticated = true
      } catch (error) {
        this.user = null
        this.isAuthenticated = false
        throw error
      } finally {
        this.hasFetchedUser = true
      }
    },

    async logout() {
      // D'abord, on vide TOUT le store et redirige (pour être sûr !)
      this.user = null
      this.isAuthenticated = false
      this.requiresOTP = false
      this.hasFetchedUser = false

      // Ensuite, on essaie d'appeler l'API logout (mais on s'en fiche si ça échoue)
      try {
        await api.post('/auth/logout')
      } catch (error) {
        console.error('Logout error (non bloquant)', error)
      }
    }
  }
})
