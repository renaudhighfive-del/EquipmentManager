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
    },

    async updateEquipement(id, formData) {
      this.loading = true
      try {
        // Laravel workaround for multipart/form-data with PUT/PATCH
        formData.append('_method', 'PUT')
        const response = await api.post(`/equipements/${id}`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        })
        const index = this.equipements.findIndex(e => e.id === id)
        if (index !== -1) {
          this.equipements[index] = response.data
        }
        return response.data
      } catch (error) {
        console.error(error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async archiveEquipement(id) {
      try {
        await api.patch(`/equipements/${id}/archive`)
        this.equipements = this.equipements.filter(e => e.id !== id)
      } catch (error) {
        console.error(error)
        throw error
      }
    }
  }
})
