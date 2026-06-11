# 📘 Guide Débutant : Comprendre la Gestion des Équipements

Ce guide explique comment fonctionne la gestion des équipements dans l'application, du serveur (Backend) jusqu'à l'interface utilisateur (Frontend).

---

## 1. Le Cœur : Le Modèle (Backend)
**Fichier :** `backend/app/Models/Equipement.php`

Le modèle est la représentation de la table dans la base de données.
- **`$fillable`** : C'est une sécurité. On y liste les colonnes que l'on a le droit de modifier via un formulaire.
- **`$casts`** : Transforme automatiquement les données (ex: une date stockée en texte devient un objet Date utilisable en PHP).
- **Relations (`categorie`, `images`)** : Permet d'appeler facilement les données liées. Par exemple, `$equipement->categorie->nom` donne le nom de la catégorie sans faire de nouvelle requête manuelle.

---

## 2. Le Cerveau : Le Contrôleur (Backend)
**Fichier :** [EquipementController.php](file:///c:/Users/raouf.raimi/Desktop/Projet_tracker/EquipmentManager/backend/app/Http/Controllers/EquipementController.php)

C'est ici que la logique métier se trouve. Voici les étapes d'une action classique :

### Action : `index()` (Lister les équipements)
1. **Appel** : Le Frontend demande la liste via une requête `GET`.
2. **Traitement** : 
   - On récupère l'utilisateur connecté.
   - Si c'est un **Agent**, on filtre pour ne montrer que les équipements qui lui sont **affectés** et qui ne sont pas **perdus**.
   - Si c'est un **Admin**, il voit tout.
3. **Envoi** : Le contrôleur renvoie un fichier **JSON** (une liste structurée de données).

### Action : `store()` (Créer un équipement)
1. **Validation** : Le code vérifie que les données envoyées sont correctes (ex: la référence doit être unique).
2. **Création** : Si tout est bon, il crée l'entrée en base de données.
3. **Photos** : S'il y a des images, il les enregistre dans le dossier `storage/equipements` et crée un lien en base de données.

---

## 3. Le Facteur : Le Store Pinia (Frontend)
**Fichier :** [equipement.js](file:///c:/Users/raouf.raimi/Desktop/Projet_tracker/EquipmentManager/frontend/src/stores/equipement.js)

Le Store fait le pont entre le serveur et l'écran.
- **`state`** : C'est la mémoire de l'application. On y stocke la liste `equipements`.
- **`actions`** : Ce sont les fonctions qui parlent à l'API.
  - `fetchEquipements()` : Utilise `axios` pour appeler l'URL `/equipements`, reçoit les données JSON, et les range dans le `state`.

---

## 4. L'Écran : La Vue (Frontend)
**Fichier (Agent) :** [EquipementsView.vue](file:///c:/Users/raouf.raimi/Desktop/Projet_tracker/EquipmentManager/frontend/src/views/agent/EquipementsView.vue)

C'est ce que l'utilisateur voit. Elle est composée de 3 parties :

### A. Le Script (`<script setup>`)
- **`onMounted`** : Dès que la page s'affiche, on appelle `fetchEquipements()` du Store.
- **`computed`** : On crée des variables réactives qui se mettent à jour toutes seules si les données changent.
- **`getEtatClass`** : Une petite fonction qui renvoie une couleur (CSS) selon l'état de l'équipement (ex: vert pour "En service").

### B. Le Template (`<template>`)
- **`v-for`** : C'est une boucle. Pour chaque équipement reçu du Store, on crée une "carte" à l'écran.
- **`{{ equip.marque }}`** : On affiche le texte directement entre des doubles accolades.
- **`@click`** : Déclenche une action (comme ouvrir un formulaire) quand on clique sur un bouton.

### C. Le Style (CSS)
- On utilise **Tailwind CSS** (ex: `bg-blue-50`) pour mettre en forme rapidement sans écrire de fichiers CSS séparés.

---

## Résumé du flux de données
1. **Utilisateur** ouvre la page "Mes équipements".
2. **Vue** appelle l'action `fetchEquipements()` du **Store**.
3. **Store** envoie une requête HTTP au **Contrôleur** (Backend).
4. **Contrôleur** interroge la **Base de données** via le **Modèle**.
5. **Données** reviennent au Store qui met à jour sa mémoire.
6. **Vue** détecte le changement et affiche les équipements à l'écran.
