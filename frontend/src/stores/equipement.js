import { defineStore } from 'pinia'
// Importation de l'instance Axios configurée (provenance : dossier local services)
// C'est elle qui gère l'URL de base, les tokens d'authentification et les requêtes HTTP
import api from '../services/axios'

// Définition globale du store Pinia nommé 'equipement'
export const useEquipementStore = defineStore('equipement', {
  
  // 📦 ÉTAT (STATE) : La source unique de vérité pour les données des équipements
  state: () => ({
    equipements: [], // Liste principale des équipements actifs affichés dans l'application
    loading: false,  // Indicateur visuel de chargement pour les requêtes asynchrones
    error: null,     // Stockage des messages d'erreur destinés à l'interface utilisateur
  }),

  // ⚡ ACTIONS : Fonctions asynchrones pour interagir avec l'API Laravel et mettre à jour le state
  actions: {
    
    /**
     * Récupère la liste de tous les équipements actifs depuis le serveur.
     * Provenance API : GET /api/equipements
     */
    async fetchEquipements() {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/equipements')
        // Met à jour directement le state avec le tableau reçu de Laravel
        this.equipements = response.data
      } catch (error) {
        this.error = "Erreur lors de la récupération des équipements"
        console.error(error)
      } finally {
        this.loading = false
      }
    },

    /**
     * Récupère les détails spécifiques d'un seul équipement par son ID.
     * Provenance API : GET /api/equipements/{id}
     * Utilité : Souvent utilisé pour charger les données avant d'ouvrir un formulaire d'édition.
     */
    async fetchEquipement(id) {
      try {
        const response = await api.get(`/equipements/${id}`)
        return response.data // Retourne les données directement au composant appelant
      } catch (error) {
        console.error(error)
        throw error // Renvoie l'erreur pour qu'elle puisse être gérée côté composant
      }
    },

    /**
     * Envoie les données pour créer un nouvel équipement (gère les fichiers/images).
     * Provenance API : POST /api/equipements
     * Spécificité : Utilise le Header 'multipart/form-data' indispensable pour l'envoi de fichiers.
     */
    async createEquipement(formData) {
      this.loading = true
      try {
        const response = await api.post('/equipements', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        })
        // unshift() insère le nouvel équipement tout en haut de la liste locale (State)
        // Évite d'avoir à refaire un fetch complet pour voir le changement immédiatement
        this.equipements.unshift(response.data)
        return response.data
      } catch (error) {
        console.error(error)
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Modifie un équipement existant.
     * Provenance API : POST /api/equipements/{id} (Émulé en PUT)
     * Particularité Laravel : PHP/Laravel ne sait pas lire nativement les requêtes PUT/PATCH 
     * lorsqu'elles contiennent du 'multipart/form-data' (fichiers). 
     * L'astuce consiste à tricher en envoyant un POST, mais en injectant le paramètre '_method' à 'PUT'.
     */
    async updateEquipement(id, formData) {
      this.loading = true
      try {
        // Contournement technique pour que Laravel intercepte correctement le formulaire de modification avec fichier
        formData.append('_method', 'PUT')
        const response = await api.post(`/equipements/${id}`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        })
        // Recherche l'équipement modifié dans la liste locale pour le mettre à jour à la volée
        const index = this.equipements.findIndex(e => e.id === id)
        if (index !== -1) {
          this.equipements[index] = response.data
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
     * Archive un équipement (Soft Delete / Désactivation).
     * Provenance API : PATCH /api/equipements/{id}/archive
     * Utilité : Supprime l'équipement visuel de l'écran sans le détruire définitivement en BDD.
     */
    async archiveEquipement(id) {
      try {
        await api.patch(`/equipements/${id}/archive`)
        // Filtre et retire l'équipement du state local pour qu'il disparaisse instantanément de l'affichage
        this.equipements = this.equipements.filter(e => e.id !== id)
      } catch (error) {
        console.error(error)
        throw error
      }
    },

    /**
     * Désarchive un équipement pour le rendre à nouveau actif.
     * Provenance API : PATCH /api/equipements/{id}/unarchive
     * Utilité : Restaure l'élément et le réinjecte dans la liste active.
     */
    async unarchiveEquipement(id) {
      try {
        const response = await api.patch(`/equipements/${id}/unarchive`)
        // push() réintègre l'équipement restauré à la fin de notre liste active du state
        this.equipements.push(response.data)
        return response.data
      } catch (error) {
        console.error(error)
        throw error
      }
    },

    /**
     * Récupère la liste spécifique des équipements qui ont été archivés.
     * Provenance API : GET /api/equipements/archives
     * Utilité : Permet d'alimenter une vue ou un onglet dédié à la corbeille / historique des archives.
     */
    async fetchArchives() {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/equipements/archives')
        return response.data // Retourne la liste complète des archives directement au composant
      } catch (error) {
        this.error = "Erreur lors de la récupération des archives"
        console.error(error)
        throw error
      } finally {
        this.loading = false
      }
    }
  }
})