import { defineStore } from 'pinia'
import api from '../services/axios'

export const useRapportStore = defineStore('rapport', {
  state: () => ({
    kpis:              [],
    repartitionEtat:   [],
    parCategorie:      [],
    evolution:         { labels: [], affectData: [], retourData: [] },
    pannesParGravite:  {},
    maintenances:      {},
    sinistres:         {},
    topAgents:         [],
    activiteRecente:   [],
    periode:           30, // jours
    loading:           false,
    error:             null,
  }),

  actions: {
    async fetchStats() {
      this.loading = true
      this.error   = null
      try {
        const response = await api.get('/rapports/stats', {
          params: { periode: this.periode },
        })
        const d = response.data
        this.kpis             = d.kpis
        this.repartitionEtat  = d.repartition_etat
        this.parCategorie     = d.par_categorie
        this.evolution        = d.evolution
        this.pannesParGravite = d.pannes_par_gravite
        this.maintenances     = d.maintenances
        this.sinistres        = d.sinistres
        this.topAgents        = d.top_agents
        this.activiteRecente  = d.activite_recente
      } catch (err) {
        this.error = 'Erreur lors du chargement des statistiques'
        console.error(err)
      } finally {
        this.loading = false
      }
    },

    setPeriode(jours) {
      this.periode = jours
      this.fetchStats()
    },
  },
})
