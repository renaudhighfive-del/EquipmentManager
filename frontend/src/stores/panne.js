import { defineStore } from 'pinia'
import api from '../services/axios'

export const usePanneStore = defineStore('panne', {
  state: () => ({
    pannes: [],
    loading: false,
    error: null,
  }),

  actions: {
    async fetchPannes() {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/pannes')
        this.pannes = response.data
      } catch (error) {
        this.error = "Erreur lors de la récupération des pannes"
        console.error(error)
      } finally {
        this.loading = false
      }
    },

    async createPanne(formData) {
      this.loading = true
      try {
        const response = await api.post('/pannes', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        })
        this.pannes.unshift(response.data)
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
