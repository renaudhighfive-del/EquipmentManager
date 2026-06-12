import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '../services/axios';

export const useAgentsStore = defineStore('agents', () => {
  const agents = ref([]);
  const loading = ref(false);
  const selectedAgent = ref(null);
  const total = ref(0);

  const fetchAgents = async (search = '') => {
    loading.value = true;
    try {
      const params = search ? { search } : {};
      const response = await api.get('/agents', { params });
      agents.value = response.data.agents;
      total.value = response.data.total;
    } catch (error) {
      console.error('Erreur lors de la récupération des agents:', error);
      throw error;
    } finally {
      loading.value = false;
    }
  };

  const fetchAgent = async (id) => {
    try {
      const response = await api.get(`/agents/${id}`);
      selectedAgent.value = response.data.agent;
      return response.data.agent;
    } catch (error) {
      console.error('Erreur lors de la récupération de l\'agent:', error);
      throw error;
    }
  };
//creation d'un agent
  const createAgent = async (agentData) => {
    try {
      const response = await api.post('/agents', agentData);
      agents.value.push(response.data.agent);
      total.value += 1;
      return response.data;
    } catch (error) {
      console.error('Erreur lors de la création de l\'agent:', error);
      throw error;
    }
  };
//modification d'un agent
  const updateAgent = async (id, agentData) => {
    try {
      const response = await api.put(`/agents/${id}`, agentData);
      const index = agents.value.findIndex(a => a.id === id);
      if (index !== -1) {
        agents.value[index] = response.data.agent;
      }
      if (selectedAgent.value?.id === id) {
        selectedAgent.value = response.data.agent;
      }
      return response.data;
    } catch (error) {
      console.error('Erreur lors de la mise à jour de l\'agent:', error);
      throw error;
    }
  };
// desactivation d'un agent
  const desactiverAgent = async (id) => {
    try {
      const response = await api.patch(`/agents/${id}/desactiver`);
      const index = agents.value.findIndex(a => a.id === id);
      if (index !== -1) {
        agents.value[index] = response.data.agent;
      }
      if (selectedAgent.value?.id === id) {
        selectedAgent.value = response.data.agent;
      }
      return response.data;
    } catch (error) {
      console.error('Erreur lors de la désactivation de l\'agent:', error);
      throw error;
    }
  };
//reativation d'un agent
  const reactiverAgent = async (id) => {
    try {
      const response = await api.patch(`/agents/${id}/reactiver`);
      const index = agents.value.findIndex(a => a.id === id);
      if (index !== -1) {
        agents.value[index] = response.data.agent;
      }
      if (selectedAgent.value?.id === id) {
        selectedAgent.value = response.data.agent;
      }
      return response.data;
    } catch (error) {
      console.error('Erreur lors de la réactivation de l\'agent:', error);
      throw error;
    }
  };
//exportation des fonctions et variables
  return {
    agents,
    loading,
    selectedAgent,
    total,
    fetchAgents,
    fetchAgent,
    createAgent,
    updateAgent,
    desactiverAgent,
    reactiverAgent
  };
});
