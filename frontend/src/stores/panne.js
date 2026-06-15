import { defineStore } from 'pinia'
// Importation de l'instance Axios configurée (provenance : dossier local services)
// C'est le client HTTP qui gère les requêtes vers le backend Laravel
import api from '../services/axios'

// Définition globale du store Pinia nommé 'panne'
export const usePanneStore = defineStore('panne', {
  
  // 📦 ÉTAT (STATE) : Données réactives centralisées pour la gestion des pannes
  state: () => ({
    pannes: [],            // Liste principale contenant toutes les pannes déclarées et suivies
    loading: false,        // Indicateur d'exécution d'une requête asynchrone (pour l'affichage des spinners)
    error: null,           // Stockage des messages d'erreur renvoyés par l'API ou génériques
    successMessage: null   // Stockage temporaire des messages de confirmation de réussite (ex: création, suppression)
  }),

  // ⚡ ACTIONS : Méthodes asynchrones chargées de dialoguer avec l'API et de mettre à jour le state
  actions: {
    
    /**
     * Récupère la liste complète des pannes.
     * Provenance API : GET /api/pannes
     * Utilité : Charger les pannes pour les afficher dans le tableau de bord ou un tableau de suivi.
     * Particularité : Retourne un booléen (true/false) pour notifier facilement le composant du succès.
     */
    async fetchPannes() {
      this.loading = true
      this.error = null
      this.successMessage = null
      try {
        const response = await api.get('/pannes')
        // Met à jour le state directement avec le tableau retourné par Laravel
        this.pannes = response.data
        return true
      } catch (error) {
        // Extraction du message personnalisé configuré dans le contrôleur Laravel s'il existe
        this.error = error.response?.data?.message || "Erreur lors de la récupération des pannes"
        console.error(error)
        return false
      } finally {
        this.loading = false
      }
    },

    /**
     * Déclare ou enregistre une nouvelle panne.
     * Provenance API : POST /api/pannes
     * Spécificité : Utilise le Header 'multipart/form-data', ce qui indique que l'action 
     * accepte l'envoi de fichiers (par exemple une photo ou une preuve visuelle de la panne).
     * Structure attendue : response.data.data pour le contenu et response.data.message pour le texte de succès.
     */
    async createPanne(formData) {
      this.loading = true
      this.error = null
      this.successMessage = null
      try {
        const response = await api.post('/pannes', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        })
        // unshift() injecte la nouvelle panne directement au début du tableau (index 0) du state
        this.pannes.unshift(response.data.data)
        this.successMessage = response.data.message
        return true
      } catch (error) {
        this.error = error.response?.data?.message || "Erreur lors de la déclaration"
        console.error(error)
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Valide ou approuve le signalement d'une panne.
     * Provenance API : PATCH /api/pannes/{id}/valider
     * Utilité : Permet à un administrateur ou technicien de confirmer la prise en compte de la panne.
     */
    async validerPanne(id) {
      this.loading = true
      try {
        const response = await api.patch(`/pannes/${id}/valider`)
        // Repère l'emplacement de la panne modifiée dans l'interface locale
        const index = this.pannes.findIndex(p => p.id === id)
        if (index !== -1) {
          // Remplace les anciennes données par celles renvoyées par l'API (ex: statut passé à "validé")
          this.pannes[index] = response.data
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
     * Supprime définitivement l'enregistrement d'une panne.
     * Provenance API : DELETE /api/pannes/{id}
     * Utilité : Retirer une fausse déclaration ou nettoyer l'historique.
     */
    async deletePanne(id) {
      this.loading = true
      try {
        const response = await api.delete(`/pannes/${id}`)
        // filter() exclut la panne supprimée du state local pour qu'elle disparaisse de l'écran immédiatement
        this.pannes = this.pannes.filter(p => p.id !== id)
        this.successMessage = response.data.message
        return true
      } catch (error) {
        console.error(error)
        return false
      } finally {
        this.loading = false
      }
    },

    /**
     * Rejette ou refuse le signalement d'une panne en spécifiant une raison.
     * Provenance API : PATCH /api/pannes/{id}/rejeter
     * Utilité : Permet d'annuler un signalement incorrect tout en transmettant un motif au demandeur.
     * Payload : Transmet un objet JSON contenant la clé `{ motif }`.
     */
    async rejeterPanne(id, motif) {
      this.loading = true
      try {
        const response = await api.patch(`/pannes/${id}/rejeter`, { motif })
        // Recherche et met à jour en local la panne avec son nouveau statut de rejet et son motif
        const index = this.pannes.findIndex(p => p.id === id)
        if (index !== -1) {
          this.pannes[index] = response.data
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