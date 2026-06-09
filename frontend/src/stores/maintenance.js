import { defineStore } from 'pinia'
import api from '../services/axios'

export const useMaintenanceStore = defineStore('maintenance', {
  state: () => ({
    maintenances: [],
    loading: false,
    error: null,
  }),

  actions: {
    async fetchMaintenances() {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/maintenances')
        this.maintenances = response.data
      } catch (error) {
        this.error = "Erreur lors de la récupération des maintenances"
        console.error(error)
      } finally {
        this.loading = false
      }
    },

    async createMaintenance(formData) {
      this.loading = true
      try {
        const response = await api.post('/maintenances', formData)
        this.maintenances.unshift(response.data)
        return response.data
      } catch (error) {
        console.error(error)
        throw error
      } finally {
        this.loading = false
      }
    }
  }
})
