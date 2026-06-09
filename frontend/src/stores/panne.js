import { defineStore } from 'pinia'
import api from '../services/axios'

export const usePanneStore = defineStore('panne', {
  state: () => ({
    pannes: [],
    loading: false,
    error: null,
    successMessage: null
  }),

  actions: {
    async fetchPannes() {
      this.loading = true
      this.error = null
      this.successMessage = null
      try {
        const response = await api.get('/pannes')
        this.pannes = response.data
        return true
      } catch (error) {
        this.error = error.response?.data?.message || "Erreur lors de la récupération des pannes"
        console.error(error)
        return false
      } finally {
        this.loading = false
      }
    },

    async createPanne(formData) {
      this.loading = true
      this.error = null
      this.successMessage = null
      try {
        const response = await api.post('/pannes', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        })
        this.pannes.unshift(response.data.data)
        this.successMessage = response.data.message
        return true
      } catch (error) {
        this.error = error.response?.data?.message || "Erreur lors de la déclaration"
        console.error(error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async updatePanneStatus(id, statut) {
      this.loading = true
      this.error = null
      this.successMessage = null
      try {
        const response = await api.patch(`/pannes/${id}`, { statut })
        const index = this.pannes.findIndex(p => p.id === id)
        if (index !== -1) {
          this.pannes[index] = response.data.data
        }
        this.successMessage = response.data.message
        return true
      } catch (error) {
        this.error = error.response?.data?.message || "Erreur lors de la mise à jour"
        console.error(error)
        return false
      } finally {
        this.loading = false
      }
    },

    async deletePanne(id) {
      this.loading = true
      try {
        const response = await api.delete(`/pannes/${id}`)
        this.pannes = this.pannes.filter(p => p.id !== id)
        this.successMessage = response.data.message
        return true
      } catch (error) {
        console.error(error)
        return false
      } finally {
        this.loading = false
      }
    }
  }
})
