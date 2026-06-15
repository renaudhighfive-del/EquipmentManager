import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/axios'

export const useAffectationStore = defineStore('affectation', () => {
  const affectations = ref([])
  const loading = ref(false)
  const error = ref(null)
  const successMessage = ref(null)

  const currentAffectation=ref(null)

    // Pour la confirmation de réception
  const affectationsAConfirmer = ref([])
  const loadingAConfirmer = ref(false)

  

  // Pour l'affichage
  const fetchAffectations = async () => {
    loading.value = true
    error.value = null
    successMessage.value = null
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
    successMessage.value = null
    try {
      const response = await api.post('/affectations', affectationData)
      affectations.value.unshift(response.data.data) // Ajouter au début de la liste
      successMessage.value = response.data.message
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
    successMessage.value = null
    try {
      // Pour les uploads de fichiers en Laravel avec PATCH, on utilise souvent POST avec _method
      const response = await api.post(`/affectations/${id}`, returnData)
      
      // Mettre à jour l'affectation dans la liste locale
      const index = affectations.value.findIndex(a => a.id === id)
      if (index !== -1) {
        affectations.value[index] = response.data.data
      }
      
      successMessage.value = response.data.message
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du retour de l\'équipement'
      return false
    } finally {
      loading.value = false
    }
  }

  // Pour la modification
  const updateAffectation = async (id, updateData) => {
    loading.value = true
    error.value = null
    successMessage.value = null
    try {
      // Utilisation de POST avec _method: PATCH pour gérer l'upload de fichiers
      const response = await api.post(`/affectations/${id}`, updateData)
      
      const index = affectations.value.findIndex(a => a.id === id)
      if (index !== -1) {
        affectations.value[index] = response.data.data
      }
      
      successMessage.value = response.data.message
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la modification de l\'affectation'
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


    // Récupérer la liste des affectations à confirmer par l'agent
  const fetchAffectationsAConfirmer = async () => {
    loadingAConfirmer.value = true
    error.value = null
    try {
      const response = await api.get('/affectations/a-confirmer') // Endpoint backend qu'on créera plus tard
      affectationsAConfirmer.value = response.data.data
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des confirmations'
      return false
    } finally {
      loadingAConfirmer.value = false
    }
  }

        // Confirmer la réception d'une affectation
  const confirmerReceptionAffectation = async (id) => {
    loading.value = true
    error.value = null
    successMessage.value = null
    try {
      const response = await api.patch(`/affectations/${id}/confirmer-reception`)

       // On retire l'affectation de la liste "à confirmer"
      affectationsAConfirmer.value = affectationsAConfirmer.value.filter(a => a.id !== id)
      
      successMessage.value = response.data.message
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la confirmation'
      return false
    } finally {
      loading.value = false
    }
  };

  // Demande de retour par l'agent
  const requestReturnAffectation = async (id, returnData) => {
    loading.value = true;
    error.value = null;
    successMessage.value = null;
    try {
      const response = await api.post(`/affectations/${id}/request-return`, returnData);

      const index = affectations.value.findIndex(a => a.id === id);
      if (index !== -1) {
        affectations.value[index] = response.data.data;
      }

      successMessage.value = response.data.message;
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la demande de retour';
      return false;
    } finally {
      loading.value = false;
    }
  };

  // Valider le retour par Admin/Gestionnaire
  const validateReturn = async (id) => {
    loading.value = true;
    error.value = null;
    successMessage.value = null;
    try {
      const response = await api.patch(`/affectations/${id}/validate-return`);

      const index = affectations.value.findIndex(a => a.id === id);
      if (index !== -1) {
        affectations.value[index] = response.data.data;
      }

      successMessage.value = response.data.message;
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la validation du retour';
      return false;
    } finally {
      loading.value = false;
    }
  };

  // Rejeter le retour par Admin/Gestionnaire
  const rejectReturn = async (id, rejectData) => {
    loading.value = true;
    error.value = null;
    successMessage.value = null;
    try {
      const response = await api.patch(`/affectations/${id}/reject-return`, rejectData);

      const index = affectations.value.findIndex(a => a.id === id);
      if (index !== -1) {
        affectations.value[index] = response.data.data;
      }

      successMessage.value = response.data.message;
      return true;
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du rejet du retour';
      return false;
    } finally {
      loading.value = false;
    }
  };


  return {
    affectations,
    loading,
    error,
    successMessage,
    currentAffectation,

      affectationsAConfirmer,
    loadingAConfirmer,
    fetchAffectationsAConfirmer,
    confirmerReceptionAffectation,

    fetchAffectations,
    fetchAffectationById,
    createAffectation,
    updateAffectation,
    returnAffectation,
    requestReturnAffectation,
    validateReturn,
    rejectReturn,
  }
})
