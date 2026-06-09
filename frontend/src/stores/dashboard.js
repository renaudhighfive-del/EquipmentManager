import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/axios'

export const useDashboardStore = defineStore('dashboard', () => {
  const stats = ref(null)
  const loading = ref(false)
  const error = ref(null)

  const fetchStats = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/dashboard')
      stats.value = response.data
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des statistiques'
      return false
    } finally {
      loading.value = false
    }
  }

  return {
    stats,
    loading,
    error,
    fetchStats
  }
})
