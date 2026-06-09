import { defineStore } from 'pinia'
import api from '../services/axios'

export const useAgentStore = defineStore('agent', {
  state: () => ({
    agents: [],
    selectedAgent: null,
    loading: false,
    error: null,
  }),

  getters: {
    // Agents sans compte utilisateur — filtre local, pas de requête supplémentaire
    agentsSansCompte: (state) => state.agents.filter(a => !a.user_id),
    agentsActifs:     (state) => state.agents.filter(a => a.statut === 'actif'),
    totalAgents:      (state) => state.agents.length,
  },

  actions: {
    async fetchAgents() {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/agents')
        // L'API retourne { agents: [...], total: n }
        this.agents = response.data.agents
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

    async createAgent(data) {
      try {
        const response = await api.post('/agents', data)
        this.agents.unshift(response.data.agent)
        return response.data.agent
      } catch (err) {
        console.error(err)
        throw err
      }
    },

    async updateAgent(id, data) {
      try {
        const response = await api.put(`/agents/${id}`, data)
        const updated = response.data.agent
        const index = this.agents.findIndex(a => a.id === id)
        if (index !== -1) this.agents[index] = updated
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
        return updated
      } catch (err) {
        console.error(err)
        throw err
      }
    },
  },
})
