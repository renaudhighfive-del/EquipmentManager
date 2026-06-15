import { defineStore } from 'pinia'
import api from '../services/axios'

export const useAgentStore = defineStore('agent', {
  state: () => ({
    agents: [],
    selectedAgent: null,
    loading: false,
    error: null,
    pagination: {
      currentPage: 1,
      lastPage: 1,
      perPage: 10,
      total: 0,
      hasMorePages: false,
    },
  }),

  getters: {
    agentsSansCompte: (state) => state.agents.filter(a => !a.user_id),
    agentsActifs:     (state) => state.agents.filter(a => a.statut === 'actif'),
    agentsInactifs:   (state) => state.agents.filter(a => a.statut === 'inactif'),
    totalAgents:      (state) => state.pagination.total || state.agents.length,
  },

  actions: {
    async fetchAgents(page = 1, perPage = 10, search = '') {
      this.loading = true
      this.error = null
      try {
        const params = { page, per_page: perPage }
        const query = search?.trim()
        if (query) params.search = query

        const response = await api.get('/agents', { params })
        this.agents = response.data.agents ?? []
        this.pagination = {
          currentPage: response.data.current_page ?? 1,
          lastPage: response.data.last_page ?? 1,
          perPage: response.data.per_page ?? perPage,
          total: response.data.total ?? this.agents.length,
          hasMorePages: response.data.has_more_pages ?? false,
        }
      } catch (err) {
        this.error = 'Erreur lors de la récupération des agents'
        console.error(err)
      } finally {
        this.loading = false
      }
    },

    async fetchAgent(id) {
      try {
        const response = await api.get(`/agents/${id}`)
        this.selectedAgent = response.data.agent
        return this.selectedAgent
      } catch (err) {
        console.error(err)
        throw err
      }
    },

    // data doit être un FormData (pour supporter l'upload photo)
    async createAgent(formData) {
      try {
        const response = await api.post('/agents', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        this.agents.unshift(response.data.agent)
        return response.data.agent
      } catch (err) {
        console.error(err)
        throw err
      }
    },

    // Laravel ne supporte pas multipart sur PUT → POST + _method=PUT
    async updateAgent(id, formData) {
      try {
        formData.append('_method', 'PUT')
        const response = await api.post(`/agents/${id}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        const updated = response.data.agent
        const index = this.agents.findIndex(a => a.id === id)
        if (index !== -1) this.agents[index] = updated
        if (this.selectedAgent?.id === id) this.selectedAgent = updated
        return updated
      } catch (err) {
        console.error(err)
        throw err
      }
    },

    async toggleStatut(agent) {
      const route = agent.statut === 'actif'
        ? `/agents/${agent.id}/desactiver`
        : `/agents/${agent.id}/reactiver`
      try {
        const response = await api.patch(route)
        const updated = response.data.agent
        const index = this.agents.findIndex(a => a.id === agent.id)
        if (index !== -1) this.agents[index] = updated
        if (this.selectedAgent?.id === agent.id) this.selectedAgent = updated
        return updated
      } catch (err) {
        console.error(err)
        throw err
      }
    },
  },
})
