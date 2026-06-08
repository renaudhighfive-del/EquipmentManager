import { defineStore } from 'pinia'
import api from '../services/axios'

export const useAgentStore = defineStore('agent', {
  state: () => ({
    agents: [],
    loading: false,
    error: null,
  }),

  actions: {
    async fetchAgents() {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/agents')
        this.agents = response.data
      } catch (error) {
        this.error = "Erreur lors de la récupération des agents"
        console.error(error)
      } finally {
        this.loading = false
      }
    },

    async fetchAgent(id) {
      try {
        const response = await api.get(`/agents/${id}`)
        return response.data
      } catch (error) {
        console.error(error)
        throw error
      }
    },

    async createAgent(agentData) {
      this.loading = true
      try {
        const response = await api.post('/agents', agentData)
        this.agents.unshift(response.data)
        return response.data
      } catch (error) {
        console.error(error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateAgent(id, agentData) {
      this.loading = true
      try {
        const response = await api.put(`/agents/${id}`, agentData)
        const index = this.agents.findIndex(a => a.id === id)
        if (index !== -1) {
          this.agents[index] = response.data
        }
        return response.data
      } catch (error) {
        console.error(error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async toggleAgentStatus(id) {
      try {
        const response = await api.patch(`/agents/${id}/toggle-status`)
        const index = this.agents.findIndex(a => a.id === id)
        if (index !== -1) {
          this.agents[index] = response.data
        }
        return response.data
      } catch (error) {
        console.error(error)
        throw error
      }
    }
  }
})
