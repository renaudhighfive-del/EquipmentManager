import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/axios'

export const useMouvementStore = defineStore('mouvement', () => {
  const mouvements = ref([])
  const loading = ref(false)
  const error = ref(null)

  const fetchMouvements = async (filters = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/mouvements' , {params: filters} )
      mouvements.value = response.data.data
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des mouvements'
      return false
    } finally {
      loading.value = false
    }
  }

  return {
    mouvements,
    loading,
    error,
    fetchMouvements
  }
})
