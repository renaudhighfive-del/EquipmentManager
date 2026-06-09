import { defineStore } from 'pinia'
import api from '../services/axios'

export const useUserStore = defineStore('user', {
  state: () => ({
    users: [],
    selectedUser: null,
    loading: false,
    loadingDetail: false,
    error: null,
  }),

  actions: {
    async fetchUsers() {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/users')
        this.users = response.data.data
      } catch (err) {
        this.error = 'Erreur lors de la récupération des utilisateurs'
        console.error(err)
      } finally {
        this.loading = false
      }
    },

    async fetchUser(id) {
      this.loadingDetail = true
      try {
        const response = await api.get(`/users/${id}`)
        this.selectedUser = response.data.data
        return this.selectedUser
      } catch (err) {
        console.error(err)
        throw err
      } finally {
        this.loadingDetail = false
      }
    },

    async createUser(data) {
      try {
        const response = await api.post('/users', data)
        this.users.unshift(response.data.data)
        return response.data.data
      } catch (err) {
        console.error(err)
        throw err
      }
    },

    async updateUser(id, data) {
      try {
        const response = await api.put(`/users/${id}`, data)
        const updated = response.data.data
        const index = this.users.findIndex(u => u.id === id)
        if (index !== -1) this.users[index] = updated
        if (this.selectedUser?.id === id) this.selectedUser = updated
        return updated
      } catch (err) {
        console.error(err)
        throw err
      }
    },

    async toggleStatus(user) {
      try {
        const response = await api.patch(`/users/${user.id}/toggle-status`)
        const updated = response.data.data
        const index = this.users.findIndex(u => u.id === user.id)
        if (index !== -1) this.users[index] = updated
        if (this.selectedUser?.id === user.id) this.selectedUser = updated
        return updated
      } catch (err) {
        console.error(err)
        throw err
      }
    },

    // ── Profil utilisateur connecté ──────────────────────────────────────

    async updateProfile(data) {
      try {
        // Si FormData (upload avatar) → POST avec _method=PUT
        // Si objet JSON simple → PUT direct
        const isFormData = data instanceof FormData
        const response = isFormData
          ? await api.post('/profile', data, { headers: { 'Content-Type': 'multipart/form-data' } })
          : await api.put('/profile', data)
        return response.data.data
      } catch (err) {
        console.error(err)
        throw err
      }
    },

    async changePassword(data) {
      try {
        const response = await api.put('/profile/password', data)
        return response.data
      } catch (err) {
        console.error(err)
        throw err
      }
    },
  },
})
