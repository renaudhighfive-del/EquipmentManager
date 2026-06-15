import { defineStore } from 'pinia'
// Importation de l'instance Axios partagée (provenance : dossier local services)
// Elle applique automatiquement la configuration de base (URL, headers, authentification)
import api from '../services/axios'

// Définition globale du store Pinia nommé 'maintenance'
export const useMaintenanceStore = defineStore('maintenance', {
  
  // 📦 ÉTAT (STATE) : Stockage local et réactif des données de maintenance
  state: () => ({
    maintenances: [], // Tableau principal contenant la liste des fiches de maintenance en cours ou terminées
    loading: false,   // Indicateur global pour afficher un spinner ou un squelette de chargement à l'écran
    error: null,      // Stockage des messages d'erreur à afficher en cas de défaillance des requêtes
  }),

  // ⚡ ACTIONS : Méthodes de traitement asynchrones (requêtes API + mise à jour du state)
  actions: {
    
    /**
     * Récupère toutes les interventions de maintenance enregistrées.
     * Provenance API : GET /api/maintenances
     * Utilité : Alimenter le tableau de bord ou la liste de suivi général des maintenances.
     */
    async fetchMaintenances() {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/maintenances')
        // Injection directe du tableau d'interventions reçu du backend dans le state
        this.maintenances = response.data
      } catch (error) {
        this.error = "Erreur lors de la récupération des maintenances"
        console.error(error)
      } finally {
        this.loading = false
      }
    },

    /**
     * Crée une nouvelle fiche d'intervention ou de maintenance.
     * Provenance API : POST /api/maintenances
     * Utilité : Enregistrer une mise en réparation ou un diagnostic (envoie un JSON ou objet classique).
     */
    async createMaintenance(formData) {
      this.loading = true
      try {
        const response = await api.post('/maintenances', formData)
        // unshift() insère le nouvel enregistrement en première position (index 0) du tableau local.
        // L'utilisateur voit instantanément sa saisie s'ajouter en haut de l'écran sans rafraîchir.
        this.maintenances.unshift(response.data)
        return response.data
      } catch (error) {
        console.error(error)
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Finalise et clôture avec succès une maintenance en cours.
     * Provenance API : POST /api/maintenances/{id}/cloturer (Simulé en PATCH)
     * Particularité technique Laravel : Étant donné que tu passes un 'multipart/form-data' 
     * (probablement pour joindre un rapport, une photo de l'équipement réparé ou une facture), 
     * PHP/Laravel ne sait pas décoder ce format via une vraie méthode PATCH. 
     * On contourne le problème en envoyant un POST et en demandant à Laravel de l'interpréter en PATCH via '_method'.
     */
    async cloturerMaintenance(id, formData) {
      this.loading = true
      try {
        // Injection de la directive pour forcer Laravel à traiter la requête comme un PATCH
        formData.append('_method', 'PATCH')
        const response = await api.post(`/maintenances/${id}/cloturer`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        
        // Recherche l'ancienne ligne de cette maintenance dans le state local
        const index = this.maintenances.findIndex(m => m.id === id)
        if (index !== -1) {
          // Remplace l'ancienne fiche par la nouvelle mise à jour (statut clos, date de fin, etc.)
          this.maintenances[index] = response.data
        }
        return response.data
      } catch (error) {
        console.error(error)
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Interrompt une maintenance en déclarant l'équipement comme définitivement perdu ou irréparable.
     * Provenance API : POST /api/maintenances/{id}/declarer-perte (Simulé en PATCH)
     * Utilité : Change le statut de l'équipement et de l'intervention (permet aussi l'envoi de pièces/justificatifs).
     * Particularité de réponse : Contrairement à l'action précédente, la structure de la réponse de ton contrôleur 
     * renvoie un objet global contenant une clé `.maintenance` spécifique (response.data.maintenance).
     */
    async declarerPerteMaintenance(id, formData) {
      this.loading = true
      try {
        // Contournement identique pour le traitement multipart avec la méthode PATCH sous Laravel
        formData.append('_method', 'PATCH')
        const response = await api.post(`/maintenances/${id}/declarer-perte`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        
        // Mise à jour de la ligne concernée dans l'interface utilisateur
        const index = this.maintenances.findIndex(m => m.id === id)
        if (index !== -1) {
          // Extraction ciblée de la maintenance mise à jour depuis l'enveloppe de réponse de l'API
          this.maintenances[index] = response.data.maintenance
        }
        return response.data
      } catch (error) {
        console.error(error)
        throw error
      } finally {
        this.loading = false
      }
    }
  }
})