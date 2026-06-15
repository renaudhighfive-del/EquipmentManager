import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/axios'

export const useMouvementStore = defineStore('mouvement', () => {
  const mouvements = ref([])
  const loading = ref(false)
  const error = ref(null)

  //pour gerer la pagination
  const pagination=ref({
    current_page:1,
    per_page:10,
    total:0,
    last_page:1,
    from:null,
    to:null
  })

  const fetchMouvements = async (filters = {} , page= 1) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/mouvements?page=${page}`, {params: filters} )
      mouvements.value = response.data.data
      pagination.value=response.data.pagination

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
    fetchMouvements,
    pagination
  }
})
