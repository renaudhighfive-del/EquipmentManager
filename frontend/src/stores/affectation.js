import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/axios'

export const useAffectationStore = defineStore('affectation', () => {
  const affectations = ref([])
  const loading = ref(false)
  const error = ref(null)

  const currentAffectation=ref(null)

  // Pour l'affichage
  const fetchAffectations = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/affectations')
      affectations.value = response.data.data
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des affectations'
      return false
    } finally {
      loading.value = false
    }
  }

  // Pour la création
  const createAffectation = async (affectationData) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/affectations', affectationData)
      affectations.value.unshift(response.data.data) // Ajouter au début de la liste
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la création de l\'affectation'
      return false
    } finally {
      loading.value = false
    }
  }

  // Pour le retour
  const returnAffectation = async (id, returnData) => {
    loading.value = true
    error.value = null
    try {
      // Pour les uploads de fichiers en Laravel avec PATCH, on utilise souvent POST avec _method
      const response = await api.post(`/affectations/${id}`, returnData)
      
      // Mettre à jour l'affectation dans la liste locale
      const index = affectations.value.findIndex(a => a.id === id)
      if (index !== -1) {
        affectations.value[index] = response.data.data
      }
      
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du retour de l\'équipement'
      return false
    } finally {
      loading.value = false
    }
  }


  //Detail d'une affectation 
  const fetchAffectationById = async (id)=> {
    loading.value=true
    error.value=null
    currentAffectation.value=null 
    try {
      const response = await api.get(`/affectations/${id}`)
      currentAffectation.value=response.data.data
      return true
    } catch (err) {
      error.value=err.response?.data?.message || 'Erreur lors du chargement des affectations'
      return false
    } finally {
      loading.value=false
    }
  }

  return {
    affectations,
    loading,
    error,
    currentAffectation,
    fetchAffectations,
    fetchAffectationById,
    createAffectation,
  }
})
