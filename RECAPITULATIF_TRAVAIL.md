# Récapitulatif du Développement — EquipmentManager

Ce document présente l'ordre chronologique et logique des fichiers travaillés pour mettre en place le système d'**Affectation des Équipements**.

---

## 🟢 Étape 1 : La Structure des Données (Backend)
*C'est ici que l'on définit ce qu'est une affectation et comment elle se comporte.*

1. **[Affectation.php](file:///backend/app/Models/Affectation.php)**
   - **Rôle** : Modèle Eloquent.
   - **Utilité** : Définit les champs de la table (équipement, agent, photo, statut) et les relations (une affectation appartient à un agent et à un équipement).

2. **[AffectationController.php](file:///backend/app/Http/Controllers/AffectationController.php)**
   - **Rôle** : Le "cerveau" de l'API.
   - **Utilité** : Contient la logique pour **lister** (`index`) et **créer** (`store`) une affectation. C'est ici que l'on vérifie si l'équipement est disponible et que l'on sauvegarde la photo envoyée.

3. **[AffectationObserver.php](file:///backend/app/Observers/AffectationObserver.php)**
   - **Rôle** : Automatisation.
   - **Utilité** : Surveille les changements. Quand une affectation est créée ou terminée, il crée automatiquement un enregistrement dans la table `mouvements` (historique immuable) et met à jour l'état de l'équipement (`en_service` ou `neuf`).

4. **[api.php](file:///backend/routes/api.php)**
   - **Rôle** : Les portes d'entrée.
   - **Utilité** : Expose les fonctions du contrôleur via des URLs (ex: `POST /api/affectations`) protégées par authentification.

---

## 🔵 Étape 2 : Le Pont de Communication
*Comment le Frontend parle au Backend.*

5. **[axios.js](file:///frontend/src/services/axios.js)**
   - **Rôle** : Client HTTP.
   - **Utilité** : Centralise la configuration des appels API. Il ajoute automatiquement le jeton d'authentification (`Bearer Token`) à chaque requête et gère l'URL de base du serveur.

---

## 🟡 Étape 3 : La Gestion d'État (Store)
*Le stockage temporaire des données côté client.*

6. **[affectation.js](file:///frontend/src/stores/affectation.js)**
   - **Rôle** : Store Pinia.
   - **Utilité** : Stocke la liste des affectations en mémoire. Il contient les fonctions `fetchAffectations` et `createAffectation` qui appellent Axios et mettent à jour l'interface en cas de succès ou d'erreur.

---

## 🔴 Étape 4 : L'Interface Utilisateur (Vue)
*Ce que l'utilisateur voit et manipule.*

7. **[AffectationsView.vue](file:///frontend/src/views/admin/AffectationsView.vue)**
   - **Rôle** : Vue principale.
   - **Utilité** :
     - Affiche le tableau des affectations.
     - Gère le **Modal** (fenêtre surgissante) pour la création.
     - Gère la **sélection de la photo** avec aperçu.
     - Utilise le Store Pinia pour envoyer les données au format `FormData` (nécessaire pour les fichiers).

---

## 🛠️ Résumé du flux de données
1. L'utilisateur choisit un agent et une photo dans **AffectationsView.vue**.
2. La vue envoie les données au Store **affectation.js**.
3. Le Store appelle le Backend via **axios.js**.
4. Le **AffectationController.php** valide les données et stocke l'image.
5. L'**AffectationObserver.php** enregistre le mouvement et change l'état du matériel.
6. Le Backend répond "Succès", le Store ajoute l'affectation à sa liste, et la vue rafraîchit le tableau.
