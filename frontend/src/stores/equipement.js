import { defineStore } from 'pinia'
import api from '../services/axios'

export const useEquipementStore = defineStore('equipement', {
  state: () => ({
    equipements: [],
    loading: false,
    error: null,
  }),

  actions: {
    async fetchEquipements() {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/equipements')
        this.equipements = response.data
      } catch (error) {
        this.error = "Erreur lors de la récupération des équipements"
        console.error(error)
      } finally {
        this.loading = false
      }
    },

    async fetchEquipement(id) {
      try {
        const response = await api.get(`/equipements/${id}`)
        return response.data
      } catch (error) {
        console.error(error)
        throw error
      }
    },

    async createEquipement(formData) {
      this.loading = true
      try {
        const response = await api.post('/equipements', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        })
        this.equipements.unshift(response.data)
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
