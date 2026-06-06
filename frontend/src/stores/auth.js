import { defineStore } from 'pinia'
import axios from 'axios'
import api from '../services/axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    token: localStorage.getItem('token') || null,
    isAuthenticated: !!localStorage.getItem('token'),
    requiresOTP: false,
    tempUser: null, // For OTP phase
  }),

  actions: {
    async login(credentials) {
      try {
        // En Laravel Sanctum, on récupère d'abord le cookie CSRF
        // On utilise l'instance 'api' qui a withCredentials: true et withXSRFToken: true
        await api.get('/../sanctum/csrf-cookie')
        
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
      this.token = data.token
      this.isAuthenticated = true
      localStorage.setItem('user', JSON.stringify(data.user))
      localStorage.setItem('token', data.token)
      // Configurer le header Authorization pour les futures requêtes
      api.defaults.headers.common['Authorization'] = `Bearer ${data.token}`
    },

    async logout() {
      try {
        await api.post('/auth/logout')
      } catch (error) {
        console.error('Logout error', error)
      } finally {
        this.user = null
        this.token = null
        this.isAuthenticated = false
        this.requiresOTP = false
        localStorage.removeItem('user')
        localStorage.removeItem('token')
        delete api.defaults.headers.common['Authorization']
      }
    }
  }
})
