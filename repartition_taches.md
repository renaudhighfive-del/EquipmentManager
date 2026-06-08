# Répartition des Tâches — 4 Développeurs

**Projet :** Gestion & Traçabilité des Équipements  
**Stack :** Laravel 12 + Vue.js 3 + MySQL  
**Version :** 2.1  
**Date :** Juin 2026

---

## Principes de répartition

- Chaque développeur travaille **à la fois sur le Backend (Laravel)** et le **Frontend (Vue.js)** de ses modules.
- Charge de travail équilibrée.
- Autonomie maximale par personne.
- Modules bien découpés pour minimiser les dépendances.
- Collaboration sur les parties communes (Auth, Composants réutilisables, Configuration).

---

## Répartition des Modules

### **Développeur 1 — Authentification & Administration**

**Backend (Laravel)**
- Migration et mise à jour de la table `users` (`must_change_password`)
- Model `User` + Accessors / Mutators
- Configuration Laravel Sanctum
- CRUD Utilisateurs (création avec rôle + mot de passe initial)
- Réinitialisation de mot de passe
- Middleware `ForcePasswordChange`
- Policies et Form Requests pour utilisateurs
- Routes d’authentification (`/api/auth/*`) et `/api/users`

**Frontend (Vue.js)**
- `views/auth/LoginView.vue`
- `views/auth/ChangePasswordView.vue`
- `views/admin/UsersView.vue` (CRUD complet)
- Store Pinia `stores/auth.js`
- Guards de navigation (rôles + `must_change_password`)
- Composants communs : `AppHeader.vue`, `AppSidebar.vue`

**Responsabilités transverses** : Sécurité globale, gestion des rôles et permissions.

---

### **Développeur 2 — Agents & Équipements**

**Backend (Laravel)**
- Migrations : `agents`, `categories`, `equipements`
- Models + Accessors (`is_nouveau`, `nb_affectes`, `nb_perdus`)
- CRUD Agents et Équipements
- Gestion des catégories (CRUD)
- Scan QR / Code-barres
- Filtres avancés, pagination, recherche
- Archivage logique des équipements
- Routes : `/api/agents`, `/api/equipements`, `/api/categories`

**Frontend (Vue.js)**
- `views/agents/AgentsListView.vue`, `AgentFormView.vue`, `AgentDetailView.vue`
- `views/equipements/EquipementsListView.vue`, `EquipementFormView.vue`, `EquipementDetailView.vue`
- `components/common/NouveauBadge.vue`
- `components/equipements/QrScannerModal.vue`
- Services API correspondants

**Focus** : Référentiel de base (agents + parc matériel).

---

### **Développeur 3 — Affectations & Mouvements**

**Backend (Laravel)**
- Migration et Model `affectations` (gestion photos remise/retour)
- Table et Model `mouvements`
- Observers pour journalisation automatique
- Logique métier : vérifications avant affectation/retour + photos obligatoires
- Stockage fichiers (Laravel Storage)
- Renouvellement d’affectation
- Routes : `/api/affectations`, `/api/mouvements`

**Frontend (Vue.js)**
- `views/affectations/AffectationsListView.vue`
- `components/affectations/AffectationFormModal.vue` (upload photo remise)
- `components/affectations/RetourFormModal.vue` (upload photo retour)
- `components/affectations/PhotoUploadField.vue`
- `views/mouvements/MouvementsListView.vue`
- Miniatures photos + onglets mouvements

**Focus** : Cycle de vie affectation/retour + traçabilité.

---

### **Développeur 4 — Pannes, Maintenances, Sinistres & Dashboard**

**Backend (Laravel)**
- Migrations : `pannes`, `maintenances`, `pertes_casses`
- Workflow complet des sinistres (`en_attente_sinistre`, validation, rejet, clôture)
- Observers liés aux changements d’état
- Logique de blocage/déblocage équipements
- Tableau de bord (KPIs + stats)
- Rapports & Exports (PDF + Excel)
- Routes : `/api/pannes`, `/api/maintenances`, `/api/pertes-casses`, `/api/dashboard`, `/api/rapports`

**Frontend (Vue.js)**
- `views/pannes/*`, `views/maintenances/*`
- `views/pertes-casses/PertesCassesListView.vue` + `SinistreValidationModal.vue`
- `components/equipements/SinistreAlerteBanner.vue`
- `views/dashboard/DashboardView.vue` + charts
- `views/rapports/RapportsView.vue`

**Focus** : Gestion des incidents, workflow sinistre et reporting.

---

## Tableau Récapitulatif

| Développeur | Modules Principaux                          | Backend Principal                  | Frontend Principal                     |
|-------------|---------------------------------------------|------------------------------------|----------------------------------------|
| **Dev 1**   | Auth + Utilisateurs                         | Auth, Users, Middleware            | Login, Change MDP, Users CRUD          |
| **Dev 2**   | Agents + Équipements                        | Agents, Catégories, Equipements    | Listes & Formulaires Agents/Équipements|
| **Dev 3**   | Affectations + Mouvements + Photos          | Affectations, Mouvements, Storage  | Uploads photos, Journal                |
| **Dev 4**   | Pannes + Maintenances + Sinistres + Dash   | Workflow sinistre, Dashboard       | Dashboard, Charts, Rapports            |

---

## Organisation & Bonnes Pratiques

- **Dossiers communs** (gérés collectivement ou par Dev 1) :
  - `components/common/` (DataTable, StatusBadge, ConfirmModal, etc.)
  - Configuration globale (Axios, Pinia, PrimeVue)
  - Policies, Observers partagés

- **Méthodologie recommandée** :
  - Git Flow : branche `feature/nom-module` par développeur
  - Daily stand-up (15 min)
  - Revue de code systématique avant merge
  - Tests unitaires / feature sur chaque module

- **Ordre de priorité suggéré** :
  1. Dev 1 (Auth & Users) → base indispensable
  2. Dev 2 (Agents + Équipements)
  3. Dev 3 (Affectations)
  4. Dev 4 (Incidents + Dashboard)

---

**Fichier généré :** `repartition_taches.md`

Vous pouvez copier ce contenu ou le télécharger directement depuis l'interface.
