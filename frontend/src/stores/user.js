import { defineStore } from 'pinia'
import api from '../services/axios'

export const useUserStore = defineStore('user', {
  state: () => ({
    users: [],
    loading: false,
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
        const index = this.users.findIndex(u => u.id === id)
        if (index !== -1) this.users[index] = response.data.data
        return response.data.data
      } catch (err) {
        console.error(err)
        throw err
      }
    },

    async toggleStatus(user) {
      try {
        const response = await api.patch(`/users/${user.id}/toggle-status`)
        const index = this.users.findIndex(u => u.id === user.id)
        if (index !== -1) this.users[index] = response.data.data
        return response.data.data
      } catch (err) {
        console.error(err)
        throw err
      }
    },
  },
})
