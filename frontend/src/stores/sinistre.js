import { defineStore } from 'pinia'
import api from '../services/axios'

export const useSinistreStore = defineStore('sinistre', {
  state: () => ({
    sinistres: [],
    loading: false,
    error: null,
  }),

  actions: {
    async fetchSinistres() {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/sinistres')
        this.sinistres = response.data
      } catch (error) {
        this.error = "Erreur lors de la récupération des sinistres"
        console.error(error)
      } finally {
        this.loading = false
      }
    },

    async declareSinistre(formData) {
      this.loading = true
      try {
        const response = await api.post('/sinistres', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        this.sinistres.unshift(response.data)
        return response.data
      } catch (error) {
        console.error(error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async validerSinistre(id) {
      this.loading = true
      try {
        const response = await api.patch(`/sinistres/${id}/valider`)
        const index = this.sinistres.findIndex(s => s.id === id)
        if (index !== -1) {
          this.sinistres[index] = response.data
        }
        return response.data
      } catch (error) {
        console.error(error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async rejeterSinistre(id, motif) {
      this.loading = true
      try {
        const response = await api.patch(`/sinistres/${id}/rejeter`, { motif })
        const index = this.sinistres.findIndex(s => s.id === id)
        if (index !== -1) {
          this.sinistres[index] = response.data
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
