import { defineStore } from 'pinia'
// Importation de ton client Axios personnalisé (provenance : dossier local services)
// C'est lui qui centralise la configuration des requêtes (URL de base, intercepteurs de tokens, etc.)
import api from '../services/axios'

// Définition globale du store Pinia nommé 'sinistre'
export const useSinistreStore = defineStore('sinistre', {
  
  //  ÉTAT (STATE) : Variables réactives globales pour stocker les données de sinistres
  state: () => ({
    sinistres: [], // Tableau principal regroupant tous les sinistres (accidents, dégâts matériels, etc.) suivis
    loading: false, // Indicateur d'état pour gérer le chargement (spinners ou overlays dans l'UI)
    error: null,    // Conteneur de chaîne pour afficher les éventuels messages d'erreur à l'utilisateur
  }),

  // ⚡ ACTIONS : Fonctions de traitement asynchrones (requêtes API + gestion de l'état synchrone)
  actions: {
    
    /**
     * Récupère la liste de tous les sinistres enregistrés.
     * Provenance API : GET /api/sinistres
     * Utilité : Charger les données pour alimenter le tableau de bord d'administration ou la liste utilisateur.
     */
    async fetchSinistres() {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/sinistres')
        // Met à jour directement le state local avec le tableau de données renvoyé par le contrôleur Laravel
        this.sinistres = response.data
      } catch (error) {
        this.error = "Erreur lors de la récupération des sinistres"
        console.error(error)
      } finally {
        this.loading = false
      }
    },

    /**
     * Déclare un nouveau sinistre sur le serveur.
     * Provenance API : POST /api/sinistres
     * Utilité : Envoyer le formulaire de déclaration (généralement géré par un utilisateur ou agent sur le terrain).
     * Spécificité : Le header 'multipart/form-data' est utilisé ici, ce qui est indispensable
     * pour joindre des fichiers multimédias, comme des photos des dégâts ou des rapports de sinistres au format PDF.
     */
    async declareSinistre(formData) {
      this.loading = true
      try {
        const response = await api.post('/sinistres', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        // unshift() insère le sinistre fraîchement créé au tout début (index 0) du tableau local.
        // Cela met à jour instantanément la liste à l'écran sans forcer un rechargement complet depuis la BDD.
        this.sinistres.unshift(response.data)
        return response.data
      } catch (error) {
        console.error(error)
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Valide et approuve un sinistre signalé.
     * Provenance API : PATCH /api/sinistres/{id}/valider
     * Utilité : Permet à un administrateur ou à une autorité compétente de confirmer la prise en charge du sinistre.
     */
    async validerSinistre(id) {
      this.loading = true
      try {
        const response = await api.patch(`/sinistres/${id}/valider`)
        // Recherche l'emplacement du sinistre ciblé dans le tableau réactif
        const index = this.sinistres.findIndex(s => s.id === id)
        if (index !== -1) {
          // Remplace l'ancien état du sinistre par ses nouvelles données retournées par l'API (ex: statut mis à jour)
          this.sinistres[index] = response.data
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
     * Rejette ou décline un sinistre signalé en fournissant une explication.
     * Provenance API : PATCH /api/sinistres/{id}/rejeter
     * Utilité : Clôturer un dossier non conforme ou erroné, tout en transmettant la raison au demandeur.
     * Payload : Un objet JSON contenant la clé `{ motif }` est transmis dans le corps (body) de la requête.
     */
    async rejeterSinistre(id, motif) {
      this.loading = true
      try {
        const response = await api.patch(`/sinistres/${id}/rejeter`, { motif })
        // Recherche et met à jour à la volée le sinistre dans le state local avec le nouveau statut et le motif appliqué
        const index = this.sinistres.findIndex(s => s.id === id)
        if (index !== -1) {
          this.sinistres[index] = response.data
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