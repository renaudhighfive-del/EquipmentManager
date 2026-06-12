# 🛠️ Guide Débutant : Pannes & Maintenances

Ce guide explique comment l'application gère le cycle de vie d'un problème technique, de son signalement par un agent jusqu'à sa réparation par un administrateur.

---

## 1. Les Pannes : Le Signalement
Les pannes représentent un dysfonctionnement déclaré sur un équipement.

### Le Modèle (`backend/app/Models/Panne.php`)
- **Relations** : Une panne appartient à un `equipement`. Elle est liée à un utilisateur qui la `declarePar` et éventuellement un administrateur qui la `validePar`.
- **Statuts** : Une panne passe par plusieurs étapes : `declaree` → `en_cours` (validée) → `en_maintenance` → `resolue` ou `irrecuperable`.

### Le Contrôleur ([PanneController.php](file:///c:/Users/raouf.raimi/Desktop/Projet_tracker/EquipmentManager/backend/app/Http/Controllers/PanneController.php))
- **`store()`** : Quand un agent déclare une panne, le contrôleur crée la panne et change immédiatement l'état de l'équipement en `en_panne`.
- **`valider()`** : Un administrateur valide la panne. Son statut devient `en_cours`, ce qui la rend éligible pour être mise en maintenance.

---

## 2. La Maintenance : La Réparation
La maintenance est l'action technique entreprise pour résoudre une panne.

### Le Modèle ([Maintenance.php](file:///c:/Users/raouf.raimi/Desktop/Projet_tracker/EquipmentManager/backend/app/Models/Maintenance.php))
- **`technicien`** : Le nom de la personne ou société qui répare.
- **`cout`** : Le montant de la réparation.
- **`photos_retour`** : Les photos prises après la réparation pour prouver que l'objet fonctionne.

### Le Contrôleur ([MaintenanceController.php](file:///c:/Users/raouf.raimi/Desktop/Projet_tracker/EquipmentManager/backend/app/Http/Controllers/MaintenanceController.php))
- **`store()` (Début)** : 
  - Crée l'intervention.
  - Change l'état de l'équipement en `en_maintenance`.
  - Change le statut de la panne liée en `en_maintenance`.
- **`cloturer()` (Fin réussie)** : 
  - Enregistre la date de fin et les photos.
  - Marque la panne comme `resolue`.
  - Remet l'équipement en état `repare`.
- **`declarerPerte()` (Fin avec échec)** : 
  - Si l'objet n'est pas réparable, clôture la maintenance.
  - Marque l'équipement comme `perdu`.
  - Crée automatiquement une entrée dans la table des **Sinistres**.

---

## 3. Le Store Frontend ([maintenance.js](file:///c:/Users/raouf.raimi/Desktop/Projet_tracker/EquipmentManager/frontend/src/stores/maintenance.js))
Le store gère la communication complexe :
- **FormData** : Comme on envoie des photos, on utilise `FormData` au lieu du JSON classique.
- **Laravel Workaround** : Pour envoyer des fichiers avec une méthode `PATCH`, on utilise un petit artifice : on envoie en `POST` mais on ajoute `_method: 'PATCH'` dans les données.

---

## 4. Les Vues (Interface)
- **Agent ([IncidentsView.vue](file:///c:/Users/raouf.raimi/Desktop/Projet_tracker/EquipmentManager/frontend/src/views/agent/IncidentsView.vue))** : L'agent voit l'historique de ses pannes. Il ne peut que déclarer, pas modifier la maintenance.
- **Admin ([MaintenancesView.vue](file:///c:/Users/raouf.raimi/Desktop/Projet_tracker/EquipmentManager/frontend/src/views/admin/MaintenancesView.vue))** : L'administrateur gère tout le cycle. Il choisit une panne validée, lance la maintenance, puis utilise le bouton "Clôturer" pour terminer l'intervention.

---

## Cycle de vie résumé
1. **Agent** : "Mon téléphone ne s'allume plus" (Déclaration Panne).
2. **Système** : Équipement passe en état `en_panne`.
3. **Admin** : "Je valide, c'est bien une panne" (Validation).
4. **Admin** : "J'envoie l'appareil chez le réparateur X" (Lancement Maintenance).
5. **Système** : Équipement passe en état `en_maintenance`.
6. **Admin** : "C'est réparé, voici la photo et la facture" (Clôture).
7. **Système** : Équipement passe en état `repare` et est de nouveau disponible.
