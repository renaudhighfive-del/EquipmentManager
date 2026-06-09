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
    },

    async cloturerMaintenance(id, formData) {
      this.loading = true
      try {
        // Laravel workaround for multipart/form-data with PATCH
        formData.append('_method', 'PATCH')
        const response = await api.post(`/maintenances/${id}/cloturer`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        const index = this.maintenances.findIndex(m => m.id === id)
        if (index !== -1) {
          this.maintenances[index] = response.data
        }
        return response.data
      } catch (error) {
        console.error(error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async declarerPerteMaintenance(id, formData) {
      this.loading = true
      try {
        // Laravel workaround for multipart/form-data with PATCH
        formData.append('_method', 'PATCH')
        const response = await api.post(`/maintenances/${id}/declarer-perte`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        const index = this.maintenances.findIndex(m => m.id === id)
        if (index !== -1) {
          this.maintenances[index] = response.data.maintenance
        }
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
