# Projet — Gestion & Traçabilité des Équipements
> Cahier des charges technique complet · Laravel 12 + Vue.js 3 + MySQL

---

## Changelog

| Version | Date | Modifications |
|---|---|---|
| v1.0 | Initial | Cahier des charges initial |
| v2.0 | Mise à jour | ① Photos d'appui obligatoires à la remise et au retour · ② Étiquette dynamique "Nouveau" (30 jours) sur agents et équipements · ③ Statut "En attente de validation de sinistre" dans le workflow pertes/casses · ④ Compteurs matériels affectés et perdus dans la liste des agents |

---

## Table des matières

1. [Présentation & contexte](#1-présentation--contexte)
2. [Stack technique](#2-stack-technique)
3. [Types d'utilisateurs & permissions](#3-types-dutilisateurs--permissions)
4. [Architecture base de données](#4-architecture-base-de-données)
5. [Modules fonctionnels détaillés](#5-modules-fonctionnels-détaillés)
6. [Flux logiques & règles métier](#6-flux-logiques--règles-métier)
7. [API REST — Routes Laravel](#7-api-rest--routes-laravel)
8. [Structure frontend Vue.js](#8-structure-frontend-vuejs)
9. [Sécurité & exigences non fonctionnelles](#9-sécurité--exigences-non-fonctionnelles)
10. [Évolutions futures](#10-évolutions-futures)

> **Fonctionnalités v2.0 signalées par :** 🆕

---

## 1. Présentation & contexte

### Problème actuel
L'entreprise gère manuellement le suivi des équipements remis aux agents (PDA, smartphones, tablettes, scanners, ordinateurs portables, accessoires). Ce mode opératoire entraîne :

- Impossibilité de connaître l'état réel du parc en temps réel
- Perte de traçabilité des affectations
- Difficulté d'identifier les responsables en cas de perte ou de panne
- Absence d'historique fiable des mouvements
- Temps de recherche d'information excessif

### Solution
Plateforme web centralisée couvrant **l'intégralité du cycle de vie** d'un équipement :
acquisition → affectation → panne → maintenance → retour → réforme/perte.

### Objectifs mesurables
| Objectif | Indicateur |
|---|---|
| Traçabilité complète | 100 % des mouvements enregistrés automatiquement |
| Temps de recherche | < 30 secondes pour localiser un équipement |
| Réactivité | Temps de réponse API < 3 secondes |
| Disponibilité | Application accessible 24h/24, 7j/7 |

---

## 2. Stack technique

### Frontend
| Technologie | Rôle |
|---|---|
| **Vue.js 3** | Framework principal (Composition API) |
| **Vue Router 4** | Navigation SPA, routes protégées par rôle |
| **Pinia** | State management global (auth, équipements, notifications) |
| **Axios** | Client HTTP, intercepteurs JWT |
| **PrimeVue** | Bibliothèque de composants UI |

### Backend
| Technologie | Rôle |
|---|---|
| **Laravel 12** | Framework PHP, logique métier |
| **Laravel Sanctum** | Authentification SPA (tokens Bearer) |
| **API REST** | Communication JSON avec le frontend |
| **Laravel Observers** | Journalisation automatique des mouvements |
| **Laravel Policies** | Contrôle d'accès par ressource et par rôle |

### Base de données
| Technologie | Rôle |
|---|---|
| **MySQL 8+** | Stockage principal |
| **Migrations Laravel** | Versioning du schéma |
| **Seeders** | Données initiales (rôles, catégories) |

---

## 3. Types d'utilisateurs & permissions

### Matrice des permissions

| Action | Administrateur | Gestionnaire | Agent |
|---|:---:|:---:|:---:|
| Gestion des utilisateurs | ✅ | ❌ | ❌ |
| Gestion des catégories | ✅ | ❌ | ❌ |
| Ajouter un équipement | ✅ | ✅ | ❌ |
| Modifier un équipement | ✅ | ✅ | ❌ |
| Archiver un équipement | ✅ | ❌ | ❌ |
| Affecter un équipement | ✅ | ✅ | ❌ |
| Enregistrer un retour | ✅ | ✅ | ❌ |
| Déclarer une panne | ✅ | ✅ | ✅ |
| Signaler une perte | ✅ | ✅ | ✅ |
| Valider perte/casse | ✅ | ❌ | ❌ |
| 🆕 Valider / rejeter un sinistre | ✅ | ✅ | ❌ |
| Créer une maintenance | ✅ | ✅ | ❌ |
| Consulter ses équipements | ✅ | ✅ | ✅ |
| Consulter tous les équipements | ✅ | ✅ | ❌ |
| Consulter son historique | ✅ | ✅ | ✅ |
| Consulter tous les historiques | ✅ | ✅ | ❌ |
| Accès tableau de bord complet | ✅ | ✅ | ❌ |
| Générer des rapports | ✅ | ✅ | ❌ |

### Description des rôles

**Administrateur**
- Accès total à la plateforme
- Seul à pouvoir créer/désactiver des comptes utilisateurs
- Seul à pouvoir valider les déclarations de perte/vol/casse
- Seul à pouvoir archiver définitivement un équipement
- Consultation de tous les rapports et statistiques

**Gestionnaire du Parc**
- Responsable opérationnel du matériel
- Gère les entrées/sorties d'équipements
- Suit les maintenances et pannes
- 🆕 Peut valider ou rejeter une déclaration de sinistre (perte/vol/casse) pour débloquer l'équipement
- Ne peut pas gérer les utilisateurs ni archiver

**Agent**
- Utilisateur final qui reçoit les équipements
- Accès limité à ses propres équipements
- Peut déclarer une panne ou signaler une perte
- Consulte uniquement son historique personnel

---

## 4. Architecture base de données

### Schéma des relations

```
users (1) ──────────────── (N) agents
categories (1) ──────────── (N) equipements
equipements (1) ──────────── (N) affectations
agents (1) ──────────────── (N) affectations
equipements (1) ──────────── (N) mouvements
equipements (1) ──────────── (N) pannes
pannes (1) ───────────────── (N) maintenances
equipements (1) ──────────── (N) maintenances
equipements (1) ──────────── (N) pertes_casses

🆕 Champs calculés (pas de table supplémentaire) :
agents → is_nouveau         : calculé depuis agents.created_at (≤ 30 jours)
equipements → is_nouveau    : calculé depuis equipements.date_acquisition (≤ 30 jours)
agents → nb_affectes        : COUNT via affectations WHERE statut = 'en_cours'
agents → nb_perdus          : COUNT via pertes_casses WHERE statut = 'cloturee'
```

---

### Table : `users`
> Comptes d'accès à l'application (auth Sanctum)

| Champ | Type | Contraintes | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, AUTO_INCREMENT | Identifiant unique |
| `name` | `varchar(255)` | NOT NULL | Nom complet |
| `email` | `varchar(255)` | NOT NULL, UNIQUE | Email de connexion |
| `password` | `varchar(255)` | NOT NULL | Hash bcrypt |
| `role` | `enum` | NOT NULL | `admin` / `gestionnaire` / `agent` |
| `is_active` | `boolean` | DEFAULT true | Activation du compte |
| `remember_token` | `varchar(100)` | NULLABLE | Token "se souvenir de moi" |
| `created_at` | `timestamp` | AUTO | — |
| `updated_at` | `timestamp` | AUTO | — |

---

### Table : `agents`
> Profil métier d'un agent (peut exister sans compte `users`)

| Champ | Type | Contraintes | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, AUTO_INCREMENT | — |
| `user_id` | `bigint unsigned` | FK → users, NULLABLE | Lien vers compte (si existant) |
| `matricule` | `varchar(50)` | NOT NULL, UNIQUE | Identifiant RH |
| `nom` | `varchar(100)` | NOT NULL | — |
| `prenom` | `varchar(100)` | NOT NULL | — |
| `telephone` | `varchar(20)` | NULLABLE | — |
| `email` | `varchar(255)` | NULLABLE | Email professionnel |
| `direction` | `varchar(150)` | NULLABLE | Direction de rattachement |
| `service` | `varchar(150)` | NULLABLE | Service de rattachement |
| `poste` | `varchar(150)` | NULLABLE | Intitulé du poste |
| `statut` | `enum` | NOT NULL, DEFAULT 'actif' | `actif` / `inactif` |
| `created_at` | `timestamp` | AUTO | — |
| `updated_at` | `timestamp` | AUTO | — |

> 🆕 **Champs calculés exposés dans l'API (non stockés en base) :**
>
> | Attribut calculé | Logique | Utilisation |
> |---|---|---|
> | `is_nouveau` | `created_at >= NOW() - INTERVAL 30 DAY` | Affichage de l'étiquette "Nouveau" sur la fiche agent |
> | `nb_affectes` | `COUNT(affectations WHERE statut = 'en_cours')` | Compteur dans la liste des agents |
> | `nb_perdus` | `COUNT(pertes_casses WHERE statut = 'cloturee')` | Compteur dans la liste des agents |
>
> Ces trois attributs sont calculés dans l'**Accessor Eloquent** du Model `Agent` et inclus automatiquement dans chaque réponse JSON via `$appends = ['is_nouveau', 'nb_affectes', 'nb_perdus']`. Aucune colonne supplémentaire en base de données.

---

### Table : `categories`
> Types d'équipements gérés

| Champ | Type | Contraintes | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, AUTO_INCREMENT | — |
| `nom` | `varchar(100)` | NOT NULL, UNIQUE | Ex : PDA, Smartphone, Tablette |
| `description` | `text` | NULLABLE | Description libre |
| `created_at` | `timestamp` | AUTO | — |
| `updated_at` | `timestamp` | AUTO | — |

**Valeurs initiales (seeder) :** PDA, Smartphone, Tablette, Scanner, Ordinateur portable, Accessoire

---

### Table : `equipements` ⭐ Table pivot centrale
> Représente chaque équipement physique du parc

| Champ | Type | Contraintes | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, AUTO_INCREMENT | — |
| `categorie_id` | `bigint unsigned` | FK → categories, NOT NULL | Type d'équipement |
| `reference` | `varchar(100)` | NOT NULL, UNIQUE | Référence interne |
| `numero_serie` | `varchar(100)` | NOT NULL, UNIQUE | N° de série constructeur |
| `imei` | `varchar(20)` | NULLABLE, UNIQUE | Pour appareils GSM uniquement |
| `code_inventaire` | `varchar(100)` | NOT NULL, UNIQUE | Code QR / Code-barres |
| `marque` | `varchar(100)` | NOT NULL | Ex : Zebra, Honeywell, Apple |
| `modele` | `varchar(150)` | NOT NULL | Modèle précis |
| `fournisseur` | `varchar(150)` | NULLABLE | Fournisseur d'achat |
| `date_acquisition` | `date` | NULLABLE | Date d'achat |
| `prix_achat` | `decimal(10,2)` | NULLABLE | Prix HT |
| `garantie_fin` | `date` | NULLABLE | Date de fin de garantie |
| `etat` | `enum` | NOT NULL, DEFAULT 'neuf' | Voir valeurs ci-dessous |
| `localisation` | `varchar(200)` | NULLABLE | Magasin / Agence / Direction |
| `notes` | `text` | NULLABLE | Remarques libres |
| `is_archived` | `boolean` | DEFAULT false | Soft delete logique |
| `created_at` | `timestamp` | AUTO | — |
| `updated_at` | `timestamp` | AUTO | — |

**Valeurs de `etat` :** `neuf` · `en_service` · `en_panne` · `en_maintenance` · 🆕 `en_attente_sinistre` · `reforme` · `perdu`

> 🆕 **État `en_attente_sinistre` :** état de blocage temporaire déclenché automatiquement à la soumission d'une déclaration de perte, vol ou casse. L'équipement reste dans cet état et ne peut être ni affecté, ni archivé, ni modifié, tant qu'un administrateur ou gestionnaire n'a pas statué (validation → `perdu`, rejet → retour à l'état précédent).

> 🆕 **Champ calculé exposé dans l'API (non stocké en base) :**
>
> | Attribut calculé | Logique | Utilisation |
> |---|---|---|
> | `is_nouveau` | `date_acquisition >= NOW() - INTERVAL 30 DAY` | Affichage de l'étiquette "Nouveau" sur la fiche équipement |
>
> Calculé dans l'**Accessor Eloquent** du Model `Equipement` et inclus via `$appends = ['is_nouveau']`. Si `date_acquisition` est `NULL`, `is_nouveau` retourne `false`.

---

### Table : `affectations`
> Enregistre chaque mise à disposition d'un équipement à un agent

| Champ | Type | Contraintes | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, AUTO_INCREMENT | — |
| `equipement_id` | `bigint unsigned` | FK → equipements, NOT NULL | — |
| `agent_id` | `bigint unsigned` | FK → agents, NOT NULL | — |
| `affecte_par` | `bigint unsigned` | FK → users, NOT NULL | Gestionnaire/Admin ayant fait l'affectation |
| `date_affectation` | `date` | NOT NULL | — |
| 🆕 `photo_remise` | `varchar(500)` | NOT NULL | Chemin de la photo prise lors de la remise de l'équipement |
| `date_retour` | `date` | NULLABLE | `NULL` = affectation toujours active |
| `etat_retour` | `varchar(100)` | NULLABLE | État constaté au retour |
| 🆕 `photo_retour` | `varchar(500)` | NULLABLE | Chemin de la photo prise lors du retour (obligatoire à la saisie du retour) |
| `observations` | `text` | NULLABLE | Commentaires libres |
| `statut` | `enum` | NOT NULL, DEFAULT 'en_cours' | `en_cours` / `retourne` / `renouvele` |
| `created_at` | `timestamp` | AUTO | — |
| `updated_at` | `timestamp` | AUTO | — |

> **Règle métier :** Un équipement ne peut avoir qu'une seule affectation avec `statut = 'en_cours'` à la fois.

> 🆕 **Règle métier — photos obligatoires :**
> - `photo_remise` est **obligatoire** à la création de l'affectation. La requête `POST /api/affectations` est rejetée avec une erreur `422` si aucun fichier n'est joint.
> - `photo_retour` est **obligatoire** à l'enregistrement du retour. La requête `POST /api/affectations/{id}/retour` est rejetée avec une erreur `422` si aucun fichier n'est joint.
> - Format accepté : JPEG, PNG, WEBP — taille max 5 Mo par photo.
> - Stockage : `storage/app/public/affectations/{affectation_id}/remise.jpg` et `storage/app/public/affectations/{affectation_id}/retour.jpg`.
> - Les photos sont accessibles publiquement via un lien signé (Laravel `Storage::url()`).

---

### Table : `mouvements`
> Journal automatique et immuable de tous les événements

| Champ | Type | Contraintes | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, AUTO_INCREMENT | — |
| `equipement_id` | `bigint unsigned` | FK → equipements, NOT NULL | — |
| `user_id` | `bigint unsigned` | FK → users, NOT NULL | Auteur de l'action |
| `type_mouvement` | `enum` | NOT NULL | Voir valeurs ci-dessous |
| `ancienne_valeur` | `text` | NULLABLE | État/valeur avant (JSON recommandé) |
| `nouvelle_valeur` | `text` | NULLABLE | État/valeur après (JSON recommandé) |
| `reference_id` | `bigint unsigned` | NULLABLE | ID de la ressource source (affectation, panne…) |
| `reference_type` | `varchar(100)` | NULLABLE | Type de ressource (polymorphe) |
| `created_at` | `timestamp` | AUTO | Date de l'événement — **NE JAMAIS MODIFIER** |

**Valeurs de `type_mouvement` :** `affectation` · `retour` · `changement_etat` · `panne_declaree` · `maintenance_debut` · `maintenance_fin` · `reforme` · `perte_declaree` · `renouvellement`

> ⚠️ Cette table est en **lecture seule** une fois insérée. Alimentée exclusivement par les Observers Laravel.

---

### Table : `pannes`
> Déclarations de dysfonctionnements sur un équipement

| Champ | Type | Contraintes | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, AUTO_INCREMENT | — |
| `equipement_id` | `bigint unsigned` | FK → equipements, NOT NULL | — |
| `declare_par` | `bigint unsigned` | FK → users, NOT NULL | — |
| `date_declaration` | `date` | NOT NULL | — |
| `description` | `text` | NOT NULL | Description détaillée |
| `gravite` | `enum` | NOT NULL | `faible` / `moyenne` / `critique` |
| `statut` | `enum` | NOT NULL, DEFAULT 'declaree' | Voir valeurs ci-dessous |
| `photos` | `json` | NULLABLE | Tableau de chemins fichiers |
| `created_at` | `timestamp` | AUTO | — |
| `updated_at` | `timestamp` | AUTO | — |

**Valeurs de `statut` :** `declaree` → `en_cours` → `en_maintenance` → `resolue` / `irrecuperable`

---

### Table : `maintenances`
> Interventions préventives et correctives sur les équipements

| Champ | Type | Contraintes | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, AUTO_INCREMENT | — |
| `equipement_id` | `bigint unsigned` | FK → equipements, NOT NULL | — |
| `panne_id` | `bigint unsigned` | FK → pannes, NULLABLE | Lié à une panne pour corrective |
| `type` | `enum` | NOT NULL | `preventive` / `corrective` |
| `technicien` | `varchar(150)` | NOT NULL | Nom du technicien |
| `responsable_id` | `bigint unsigned` | FK → users, NULLABLE | Pour planification préventive |
| `diagnostic` | `text` | NULLABLE | — |
| `actions_effectuees` | `text` | NULLABLE | Détail de l'intervention |
| `cout` | `decimal(10,2)` | NULLABLE | Coût de l'intervention |
| `date_debut` | `date` | NOT NULL | — |
| `date_fin` | `date` | NULLABLE | `NULL` = maintenance en cours |
| `created_at` | `timestamp` | AUTO | — |
| `updated_at` | `timestamp` | AUTO | — |

---

### Table : `pertes_casses`
> Déclarations de perte, vol ou casse avec workflow de validation

| Champ | Type | Contraintes | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | PK, AUTO_INCREMENT | — |
| `equipement_id` | `bigint unsigned` | FK → equipements, NOT NULL | — |
| `declare_par` | `bigint unsigned` | FK → users, NOT NULL | — |
| `type` | `enum` | NOT NULL | `perte` / `vol` / `casse` |
| `date_declaration` | `date` | NOT NULL | — |
| `description` | `text` | NOT NULL | Circonstances |
| `statut` | `enum` | NOT NULL, DEFAULT 'en_attente_validation' | 🆕 Voir valeurs ci-dessous |
| `valide_par` | `bigint unsigned` | FK → users, NULLABLE | Admin ou gestionnaire ayant statué |
| `date_validation` | `date` | NULLABLE | — |
| 🆕 `motif_rejet` | `text` | NULLABLE | Motif saisi par le valideur en cas de rejet |
| `created_at` | `timestamp` | AUTO | — |
| `updated_at` | `timestamp` | AUTO | — |

> 🆕 **Valeurs de `statut` (workflow mis à jour) :**
>
> `en_attente_validation` → `validee` → `cloturee`
> `en_attente_validation` → `rejetee`
>
> | Statut | Signification | Qui peut agir |
> |---|---|---|
> | `en_attente_validation` | État initial obligatoire à la soumission. L'équipement est automatiquement passé en `en_attente_sinistre`. Aucune modification ni affectation possible. | — (bloqué) |
> | `validee` | Le sinistre est reconnu. L'équipement reste bloqué jusqu'à clôture. | Admin ou Gestionnaire |
> | `cloturee` | Dossier clôturé. `equipements.etat` → `perdu`. | Admin ou Gestionnaire |
> | `rejetee` | Déclaration non reconnue. `equipements.etat` revient à son état précédent. Le `motif_rejet` est obligatoire. | Admin ou Gestionnaire |

---

## 5. Modules fonctionnels détaillés

### Module 1 — Authentification

**Fonctionnalités :**
- Connexion par email/mot de passe → retourne un token Sanctum Bearer
- Déconnexion (révocation du token)
- Réinitialisation du mot de passe par email (Laravel Password Broker)
- Middleware de vérification du rôle sur chaque route protégée

**Composants Vue :**
- `LoginView.vue` — formulaire de connexion
- `ForgotPasswordView.vue` — demande de reset
- `ResetPasswordView.vue` — saisie nouveau mot de passe
- `store/auth.js` (Pinia) — état global : user, token, role

**Endpoints :**
```
POST /api/auth/login
POST /api/auth/logout
POST /api/auth/forgot-password
POST /api/auth/reset-password
GET  /api/auth/me
```

---

### Module 2 — Gestion des agents

**Fonctionnalités :**
- CRUD complet (Créer, Lire, Modifier, Désactiver)
- Recherche par matricule, nom, direction, service
- Pagination des résultats (20 par page)
- Export de la liste (PDF / Excel)
- Vue fiche agent avec équipements actuellement affectés
- 🆕 Étiquette "Nouveau" affichée automatiquement pendant 30 jours après la création de l'agent
- 🆕 Compteurs visibles directement dans la liste : matériels actuellement affectés et matériels perdus sous sa responsabilité

**Champs du formulaire :** Matricule · Nom · Prénom · Téléphone · Email · Direction · Service · Poste · Statut

🆕 **Colonnes de la liste des agents (mise à jour) :**

| Colonne | Source | Affichage |
|---|---|---|
| Matricule | `agents.matricule` | Texte |
| Nom complet | `agents.nom` + `agents.prenom` | Texte + badge "Nouveau" si `is_nouveau = true` |
| Direction / Service | `agents.direction`, `agents.service` | Texte |
| Statut | `agents.statut` | Badge coloré (vert = actif, gris = inactif) |
| Matériels affectés | `nb_affectes` (calculé) | Compteur avec icône, fond bleu clair — cliquable → filtre affectations de l'agent |
| Matériels perdus | `nb_perdus` (calculé) | Compteur avec icône, fond rouge clair si > 0, gris si 0 — cliquable → filtre pertes de l'agent |
| Actions | — | Voir · Modifier · Désactiver |

> 🆕 **Règle d'affichage de l'étiquette "Nouveau" :**
> - Calculée côté backend dans l'Accessor `getIsNouveauAttribute()` du Model `Agent`
> - `is_nouveau = (created_at >= Carbon::now()->subDays(30))`
> - Le frontend affiche un badge pill vert "Nouveau" à côté du nom si `is_nouveau === true`
> - Aucune intervention humaine requise pour faire disparaître le badge : il s'éteint automatiquement à J+30

> 🆕 **Règle d'affichage des compteurs :**
> - Les deux compteurs sont chargés via `withCount` Eloquent dans le Controller pour éviter le problème N+1
> - `nb_affectes` : `$query->withCount(['affectations' => fn($q) => $q->where('statut', 'en_cours')])`
> - `nb_perdus` : `$query->withCount(['pertesCasses' => fn($q) => $q->where('statut', 'cloturee')])`
> - Un compteur `nb_perdus > 0` affiche un fond rouge clair pour signaler visuellement les agents à risque

**Composants Vue :**
- `AgentsListView.vue` — tableau avec filtres, pagination et colonnes compteurs
- `AgentFormView.vue` — création / édition
- `AgentDetailView.vue` — fiche détaillée avec historique
- 🆕 `NouveauBadge.vue` — composant réutilisable badge "Nouveau" (utilisé aussi sur les équipements)

---

### Module 3 — Gestion des équipements

**Fonctionnalités :**
- CRUD complet avec validation des champs uniques (référence, n° série, IMEI, code inventaire)
- Gestion des catégories (PDA, Smartphone, Tablette, Scanner, Ordi portable, Accessoire)
- Scan de QR Code / Code-barres pour recherche rapide
- Filtres avancés : catégorie, état, localisation, marque, date acquisition
- Archivage logique (soft delete via `is_archived`)
- Vue fiche équipement avec historique complet des mouvements
- 🆕 Étiquette "Nouveau" affichée automatiquement pendant 30 jours après la date d'acquisition

**États possibles et transitions autorisées :**
```
neuf              → en_service           (affectation avec photo_remise obligatoire)
en_service        → en_panne             (déclaration panne)
en_service        → en_attente_sinistre  🆕 (déclaration perte/vol/casse soumise)
en_service        → disponible           (retour avec photo_retour obligatoire)
en_panne          → en_maintenance       (création maintenance)
en_maintenance    → en_service           (fin maintenance réussie)
en_maintenance    → reforme              (maintenance sans succès)
en_attente_sinistre → perdu             🆕 (sinistre validé + clôturé)
en_attente_sinistre → état précédent    🆕 (sinistre rejeté — retour automatique)
tout état         → reforme              (décision admin directe)
```

> 🆕 **Règle de blocage `en_attente_sinistre` :**
> Un équipement en `en_attente_sinistre` ne peut pas être :
> - affecté à un agent
> - archivé
> - modifié (champs métier gelés)
> Seule la validation ou le rejet du sinistre débloque l'équipement.

> 🆕 **Règle d'affichage de l'étiquette "Nouveau" :**
> - Calculée depuis `equipements.date_acquisition`
> - `is_nouveau = (date_acquisition >= Carbon::now()->subDays(30))`
> - Si `date_acquisition` est `NULL`, `is_nouveau = false`
> - Badge pill vert "Nouveau" visible sur la fiche et dans la liste

**Composants Vue :**
- `EquipementsListView.vue` — tableau avec filtres avancés
- `EquipementFormView.vue` — création / édition
- `EquipementDetailView.vue` — fiche complète + historique mouvements
- `QrScannerModal.vue` — scan QR/barcode via caméra
- 🆕 `NouveauBadge.vue` — composant badge "Nouveau" partagé avec le module Agents

---

### Module 4 — Gestion des affectations

**Fonctionnalités :**
- Affecter un équipement à un agent avec vérifications préalables
- Enregistrer le retour d'un équipement avec état constaté
- Renouveler une affectation (changer d'agent)
- Historique de toutes les affectations d'un équipement
- Liste des équipements actuellement affectés par agent
- 🆕 Photo d'appui obligatoire à la remise et au retour

**Vérifications avant affectation :**
1. `equipements.etat` doit être `neuf` ou disponible (pas `en_panne`, `en_maintenance`, `en_attente_sinistre`, `reforme`, `perdu`)
2. Aucune affectation active (`statut = 'en_cours'`) ne doit exister pour cet équipement
3. `agents.statut` doit être `actif`
4. 🆕 Une photo de remise (`photo_remise`) doit être jointe à la requête (validation `required|file|mimes:jpeg,png,webp|max:5120`)

**Déclencheurs automatiques après affectation :**
- `equipements.etat` → `en_service`
- 🆕 `affectations.photo_remise` → chemin du fichier stocké
- Insertion dans `mouvements` (type: `affectation`)

**Vérifications avant enregistrement du retour :**
1. L'affectation doit être en `statut = 'en_cours'`
2. 🆕 Une photo de retour (`photo_retour`) doit être jointe à la requête (validation `required|file|mimes:jpeg,png,webp|max:5120`)

**Déclencheurs automatiques après retour :**
- `affectations.statut` → `retourne`
- `affectations.date_retour` → date du jour
- 🆕 `affectations.photo_retour` → chemin du fichier stocké
- `equipements.etat` → selon état constaté au retour
- Insertion dans `mouvements` (type: `retour`)

> 🆕 **Gestion du stockage des photos :**
> - Remise : `storage/app/public/affectations/{id}/remise.jpg`
> - Retour  : `storage/app/public/affectations/{id}/retour.jpg`
> - Accessibles via `Storage::url("affectations/{id}/remise.jpg")`
> - Les deux photos sont affichées en miniature cliquable dans la fiche d'affectation et dans la fiche de l'équipement

**Composants Vue :**
- `AffectationsListView.vue` — toutes les affectations avec statut
- `AffectationFormModal.vue` — formulaire d'affectation avec 🆕 upload photo remise obligatoire
- `RetourFormModal.vue` — formulaire d'enregistrement de retour avec 🆕 upload photo retour obligatoire
- 🆕 `PhotoUploadField.vue` — composant réutilisable : zone de dépôt, prévisualisation, message d'erreur si manquant

---

### Module 5 — Mouvements (journal automatique)

**Principe :** Ce module est entièrement piloté par les **Observers Laravel**. Aucune action manuelle.

**Observer `EquipementObserver` déclenché sur :**
- Changement de `etat` → insère `type: 'changement_etat'`
- 🆕 Passage à `en_attente_sinistre` → insère `type: 'sinistre_declare'`
- 🆕 Retour depuis `en_attente_sinistre` (rejet) → insère `type: 'sinistre_rejete'`

**Observer `AffectationObserver` déclenché sur :**
- `created` → insère `type: 'affectation'`
- `updated` avec `date_retour` renseigné → insère `type: 'retour'`

**Observer `PanneObserver` déclenché sur :**
- `created` → insère `type: 'panne_declaree'`

**Observer `MaintenanceObserver` déclenché sur :**
- `created` → insère `type: 'maintenance_debut'`
- `updated` avec `date_fin` renseigné → insère `type: 'maintenance_fin'`

**Observer `PerteCasseObserver` déclenché sur :** 🆕
- `created` → insère `type: 'sinistre_declare'`
- `updated` avec `statut = 'validee'` → insère `type: 'sinistre_valide'`
- `updated` avec `statut = 'cloturee'` → insère `type: 'perte_declaree'`
- `updated` avec `statut = 'rejetee'` → insère `type: 'sinistre_rejete'`

**Valeurs de `type_mouvement` (mis à jour) :**
`affectation` · `retour` · `changement_etat` · `panne_declaree` · `maintenance_debut` · `maintenance_fin` · `reforme` · `perte_declaree` · `renouvellement` · 🆕 `sinistre_declare` · 🆕 `sinistre_valide` · 🆕 `sinistre_rejete`

**Composants Vue :**
- `MouvementsListView.vue` — journal global (admin/gestionnaire)
- `MouvementsEquipementTab.vue` — onglet dans fiche équipement
- `MouvementsAgentTab.vue` — onglet dans fiche agent

---

### Module 6 — Gestion des pannes

**Fonctionnalités :**
- Déclaration accessible aux agents, gestionnaires et admins
- Upload de photos (stockage `storage/app/public/pannes/`)
- Workflow de statuts piloté par le gestionnaire
- Lien automatique avec une maintenance corrective si nécessaire
- Notifications internes lors des changements de statut

**Workflow de statuts :**
```
declaree → en_cours → en_maintenance → resolue
                                     → irrecuperable
```

**Transitions autorisées :**
- `declaree` → `en_cours` : par gestionnaire ou admin
- `en_cours` → `en_maintenance` : à la création d'une maintenance liée
- `en_maintenance` → `resolue` : à la clôture de la maintenance (date_fin renseignée)
- `en_maintenance` → `irrecuperable` : décision admin

**Composants Vue :**
- `PannesListView.vue` — liste avec filtre par statut/gravité
- `PanneFormModal.vue` — déclaration avec upload photos
- `PanneDetailView.vue` — fiche panne avec historique

---

### Module 7 — Gestion des maintenances

**Fonctionnalités :**
- **Préventive** : planification calendaire (date prévue, responsable, observations)
- **Corrective** : liée à une panne existante (technicien, diagnostic, actions, coût, dates)
- Clôture de maintenance avec changement d'état automatique de l'équipement
- Calcul du coût total des maintenances par équipement et par période

**Effets à la clôture (`date_fin` renseignée) :**
- `equipements.etat` → `en_service` (si réparée) ou `reforme` (si irréparable)
- `pannes.statut` → `resolue` ou `irrecuperable` (si liée)
- Insertion dans `mouvements` (type: `maintenance_fin`)

**Composants Vue :**
- `MaintenancesListView.vue` — liste avec filtre type/statut
- `MaintenanceFormModal.vue` — création préventive ou corrective
- `MaintenanceDetailView.vue` — fiche avec coûts

---

### Module 8 — Pertes & Casse

**Fonctionnalités :**
- Déclaration accessible agents + gestionnaires
- 3 types : perte, vol, casse
- 🆕 Blocage automatique de l'équipement en `en_attente_sinistre` dès la soumission
- 🆕 Validation ou rejet du sinistre par l'administrateur **ou le gestionnaire**
- 🆕 Motif de rejet obligatoire en cas de rejet
- Clôture entraîne `equipements.etat` → `perdu`

🆕 **Workflow mis à jour :**
```
[Déclaration soumise]
         │
         ▼
en_attente_validation ──── équipement auto → en_attente_sinistre
         │
    ┌────┴────┐
    │         │
    ▼         ▼
 validee    rejetee ──── motif_rejet obligatoire
    │                   équipement auto → état précédent
    ▼
 cloturee ──── équipement auto → perdu
               affectation active → clôturée automatiquement
               mouvement → perte_declaree
```

**Transitions et acteurs :**

| Transition | Acteur autorisé | Effet sur l'équipement |
|---|---|---|
| soumission → `en_attente_validation` | Agent, Gestionnaire, Admin | `en_attente_sinistre` (bloqué) |
| `en_attente_validation` → `validee` | 🆕 Admin **ou Gestionnaire** | Reste `en_attente_sinistre` |
| `validee` → `cloturee` | 🆕 Admin **ou Gestionnaire** | → `perdu` |
| `en_attente_validation` → `rejetee` | 🆕 Admin **ou Gestionnaire** | → état précédent (débloqué) |

> 🆕 **Règle de récupération de l'état précédent en cas de rejet :**
> Avant de passer en `en_attente_sinistre`, l'état actuel de l'équipement est sauvegardé dans `mouvements.ancienne_valeur`. En cas de rejet du sinistre, le système lit ce champ et restaure l'équipement dans l'état qu'il avait avant la déclaration.

**Composants Vue :**
- `PertesCassesListView.vue` — liste avec workflow et badge statut sinistre
- `PerteCasseFormModal.vue` — déclaration
- 🆕 `SinistreValidationModal.vue` — interface admin/gestionnaire : bouton Valider + bouton Rejeter avec champ motif obligatoire
- 🆕 `SinistreAlerteBanner.vue` — bannière d'alerte orange sur la fiche équipement en `en_attente_sinistre`

---

### Module 9 — Tableau de bord

**KPIs affichés :**

*Parc global :*
- Nombre total d'équipements actifs (non archivés)
- Nombre disponibles (état neuf, non affectés)
- Nombre affectés (en_service avec affectation active)
- Nombre en panne
- Nombre en maintenance
- Nombre perdus/réformés

*Affectations :*
- Affectations du mois en cours
- Retours du mois en cours
- Taux d'utilisation du parc (%)

*Maintenance :*
- Équipements actuellement en maintenance
- Coût total des maintenances (mois / année)
- Pannes déclarées non résolues

*Graphiques :*
- Répartition par catégorie (camembert)
- Évolution des affectations sur 12 mois (courbe)
- Répartition par état (barres)
- Top 5 équipements les plus en panne

**Composants Vue :**
- `DashboardView.vue` — vue principale admin/gestionnaire
- `KpiCard.vue` — composant réutilisable carte indicateur
- `ChartEquipementsState.vue` — graphique états
- `ChartAffectationsEvolution.vue` — courbe temporelle

---

### Module 10 — Rapports & Exports

**Rapports disponibles :**

| Rapport | Format | Filtres disponibles |
|---|---|---|
| Inventaire complet du parc | PDF / Excel | Catégorie, état, localisation |
| Affectations par agent | PDF / Excel | Période, direction, service |
| Historique des mouvements | PDF / Excel | Équipement, type, période |
| Récapitulatif pannes | PDF / Excel | Période, gravité, statut |
| Coûts de maintenance | PDF / Excel | Période, technicien |
| Rapport pertes & casses | PDF | Période, type |

**Librairies backend :**
- PDF : `barryvdh/laravel-dompdf`
- Excel : `maatwebsite/excel`

---

## 6. Flux logiques & règles métier

### Cycle de vie complet d'un équipement

```
[Acquisition]
     │
     ▼
 ┌───────┐
 │  neuf │ ◄── État initial à la création
 └───────┘
     │ Affectation créée (+ photo_remise obligatoire)
     ▼
┌──────────┐
│en_service│ ◄── Retour d'affectation (+ photo_retour obligatoire)
└──────────┘
  │       │
  │       │ Déclaration perte/vol/casse                    🆕
  │       ▼
  │  ┌─────────────────────────┐
  │  │ en_attente_sinistre     │ ◄── Équipement BLOQUÉ
  │  └─────────────────────────┘
  │       │              │
  │   Validé+Clôturé   Rejeté (motif obligatoire)
  │       │              │
  │       ▼              └──────► retour état précédent
  │  ┌────────┐
  │  │ perdu  │
  │  └────────┘
  │
  │ Déclaration panne
  ▼
┌──────────┐
│ en_panne │
└──────────┘
     │ Création maintenance corrective
     ▼
┌───────────────┐      Fin maintenance OK     ┌──────────┐
│en_maintenance │ ──────────────────────────► │en_service│
└───────────────┘
     │ Fin maintenance KO
     ▼
┌─────────┐
│ reforme │ ◄── Également : décision directe admin
└─────────┘
```

### Règle : unicité d'affectation active

```
Avant INSERT affectation :
  SELECT COUNT(*) FROM affectations
  WHERE equipement_id = ? AND statut = 'en_cours'
  → Si COUNT > 0 : ERREUR "Équipement déjà affecté"
```

### Règle : agent actif obligatoire

```
Avant INSERT affectation :
  SELECT statut FROM agents WHERE id = ?
  → Si statut != 'actif' : ERREUR "Agent inactif"
```

### Règle : journalisation automatique (Observer)

```php
// Chaque modification d'un équipement
EquipementObserver::updated($equipement):
  IF $equipement->isDirty('etat'):
    Mouvement::create([
      'equipement_id'    => $equipement->id,
      'user_id'          => auth()->id(),
      'type_mouvement'   => 'changement_etat',
      'ancienne_valeur'  => $equipement->getOriginal('etat'),
      'nouvelle_valeur'  => $equipement->etat,
    ])
```

---

### 🆕 Règle : photo obligatoire à la remise

```
Avant INSERT affectation (POST /api/affectations) :
  IF request()->hasFile('photo_remise') === false
    → ERREUR 422 : "La photo de remise est obligatoire"
  ELSE
    Stocker dans storage/app/public/affectations/{id}/remise.jpg
    Sauvegarder le chemin dans affectations.photo_remise
```

### 🆕 Règle : photo obligatoire au retour

```
Avant UPDATE affectation (POST /api/affectations/{id}/retour) :
  IF request()->hasFile('photo_retour') === false
    → ERREUR 422 : "La photo de retour est obligatoire"
  ELSE
    Stocker dans storage/app/public/affectations/{id}/retour.jpg
    Sauvegarder le chemin dans affectations.photo_retour
```

### 🆕 Règle : étiquette "Nouveau" — calcul automatique

```php
// Model Agent — Accessor
public function getIsNouveauAttribute(): bool
{
    return $this->created_at >= Carbon::now()->subDays(30);
}

// Model Equipement — Accessor
public function getIsNouveauAttribute(): bool
{
    if (is_null($this->date_acquisition)) return false;
    return Carbon::parse($this->date_acquisition) >= Carbon::now()->subDays(30);
}

// Dans les deux Models, ajouter dans $appends :
protected $appends = ['is_nouveau'];
// Le frontend lit is_nouveau depuis l'API et affiche/masque le badge sans logique côté client
```

### 🆕 Règle : blocage automatique sur sinistre

```
À la création d'une pertes_casses (POST /api/pertes-casses) :
  1. Sauvegarder l'état actuel dans mouvements.ancienne_valeur
  2. equipements.etat → 'en_attente_sinistre'
  3. pertes_casses.statut = 'en_attente_validation'
  4. Bloquer toute affectation, archivage ou modification de l'équipement

À la validation (PATCH /api/pertes-casses/{id}/valider) :
  pertes_casses.statut → 'validee'
  pertes_casses.valide_par → auth()->id()
  pertes_casses.date_validation → today()
  (équipement reste en_attente_sinistre jusqu'à clôture)

À la clôture (PATCH /api/pertes-casses/{id}/cloturer) :
  pertes_casses.statut → 'cloturee'
  equipements.etat → 'perdu'
  Clôturer l'affectation active si existante (date_retour = today, statut = 'retourne')
  Insérer mouvement type 'perte_declaree'

Au rejet (PATCH /api/pertes-casses/{id}/rejeter) :
  IF request('motif_rejet') est vide → ERREUR 422
  pertes_casses.statut → 'rejetee'
  pertes_casses.motif_rejet → request('motif_rejet')
  equipements.etat → ancienne_valeur récupérée depuis mouvements
  Insérer mouvement type 'sinistre_rejete'
```

### 🆕 Règle : compteurs agents (requête optimisée)

```php
// Dans AgentController@index — éviter le N+1
$agents = Agent::query()
    ->withCount([
        'affectations as nb_affectes' => fn($q) => $q->where('statut', 'en_cours'),
        'pertesCasses as nb_perdus'   => fn($q) => $q->where('statut', 'cloturee'),
    ])
    ->paginate(20);

// Réponse JSON enrichie par agent :
// { ..., "nb_affectes": 3, "nb_perdus": 1, "is_nouveau": true }
```

---

## 7. API REST — Routes Laravel

### Authentification
```
POST   /api/auth/login              Connexion
POST   /api/auth/logout             Déconnexion (auth requis)
POST   /api/auth/forgot-password    Demande reset MDP
POST   /api/auth/reset-password     Nouveau mot de passe
GET    /api/auth/me                 Utilisateur connecté
```

### Utilisateurs (admin only)
```
GET    /api/users                   Liste paginée
POST   /api/users                   Créer
GET    /api/users/{id}              Détail
PUT    /api/users/{id}              Modifier
PATCH  /api/users/{id}/toggle       Activer/Désactiver
```

### Agents
```
GET    /api/agents                  Liste paginée + filtres
POST   /api/agents                  Créer
GET    /api/agents/{id}             Détail + équipements actuels
PUT    /api/agents/{id}             Modifier
PATCH  /api/agents/{id}/toggle      Activer/Désactiver
GET    /api/agents/{id}/mouvements  Historique de l'agent
```

### Catégories
```
GET    /api/categories              Liste complète
POST   /api/categories              Créer (admin)
PUT    /api/categories/{id}         Modifier (admin)
DELETE /api/categories/{id}         Supprimer si inutilisée (admin)
```

### Équipements
```
GET    /api/equipements             Liste paginée + filtres avancés
POST   /api/equipements             Créer
GET    /api/equipements/{id}        Détail complet
PUT    /api/equipements/{id}        Modifier
PATCH  /api/equipements/{id}/archive   Archiver (admin)
GET    /api/equipements/{id}/mouvements  Historique
GET    /api/equipements/scan/{code} Recherche par QR/barcode
```

### Affectations
```
GET    /api/affectations                    Liste paginée + filtres
POST   /api/affectations                    Créer une affectation (🆕 champ photo_remise obligatoire — multipart/form-data)
GET    /api/affectations/{id}               Détail
POST   /api/affectations/{id}/retour        Enregistrer retour (🆕 champ photo_retour obligatoire — multipart/form-data)
POST   /api/affectations/{id}/renouveler    Réaffecter à autre agent
```

### Mouvements
```
GET    /api/mouvements              Journal global (admin/gestionnaire)
GET    /api/mouvements?equipement_id=X   Filtré par équipement
GET    /api/mouvements?agent_id=X        Filtré par agent
```

### Pannes
```
GET    /api/pannes                  Liste + filtres (statut, gravité)
POST   /api/pannes                  Déclarer
GET    /api/pannes/{id}             Détail
PATCH  /api/pannes/{id}/statut      Changer statut (gestionnaire/admin)
POST   /api/pannes/{id}/photos      Upload photos
```

### Maintenances
```
GET    /api/maintenances            Liste + filtres
POST   /api/maintenances            Créer
GET    /api/maintenances/{id}       Détail
PUT    /api/maintenances/{id}       Modifier
PATCH  /api/maintenances/{id}/cloture   Clôturer (date_fin)
```

### Pertes & Casses
```
GET    /api/pertes-casses                       Liste + filtres
POST   /api/pertes-casses                       Déclarer (déclenche en_attente_sinistre)
GET    /api/pertes-casses/{id}                  Détail
PATCH  /api/pertes-casses/{id}/valider          🆕 Valider le sinistre (admin ou gestionnaire)
PATCH  /api/pertes-casses/{id}/cloturer         🆕 Clôturer → équipement perdu (admin ou gestionnaire)
PATCH  /api/pertes-casses/{id}/rejeter          🆕 Rejeter avec motif obligatoire → débloque l'équipement (admin ou gestionnaire)
```

### Tableau de bord
```
GET    /api/dashboard/stats         KPIs globaux du parc
GET    /api/dashboard/chart/etats   Données répartition par état
GET    /api/dashboard/chart/affectations-evolution  Courbe 12 mois
```

### Rapports
```
GET    /api/rapports/inventaire?format=pdf|excel&...filtres
GET    /api/rapports/affectations?format=pdf|excel&...filtres
GET    /api/rapports/mouvements?format=pdf|excel&...filtres
GET    /api/rapports/pannes?format=pdf|excel&...filtres
GET    /api/rapports/maintenances?format=pdf|excel&...filtres
GET    /api/rapports/pertes-casses?format=pdf|...filtres
```

---

## 8. Structure frontend Vue.js

### Arborescence des fichiers

```
src/
├── assets/
├── components/
│   ├── common/
│   │   ├── AppHeader.vue
│   │   ├── AppSidebar.vue
│   │   ├── DataTable.vue
│   │   ├── FilterBar.vue
│   │   ├── KpiCard.vue
│   │   ├── StatusBadge.vue
│   │   ├── ConfirmModal.vue
│   │   ├── ExportButton.vue
│   │   └── 🆕 NouveauBadge.vue         ← Badge "Nouveau" 30 jours (agents + équipements)
│   ├── equipements/
│   │   ├── EquipementCard.vue
│   │   ├── EquipementForm.vue
│   │   ├── QrScannerModal.vue
│   │   └── 🆕 SinistreAlerteBanner.vue  ← Bannière orange si en_attente_sinistre
│   ├── affectations/
│   │   ├── AffectationForm.vue
│   │   ├── RetourForm.vue
│   │   └── 🆕 PhotoUploadField.vue      ← Upload photo avec prévisualisation et erreur
│   ├── pannes/
│   │   ├── PanneForm.vue
│   │   └── PanneStatutBadge.vue
│   ├── pertes-casses/
│   │   └── 🆕 SinistreValidationModal.vue  ← Valider / Rejeter avec motif
│   └── charts/
│       ├── ChartEtats.vue
│       └── ChartEvolution.vue
├── views/
│   ├── auth/
│   │   ├── LoginView.vue
│   │   └── ForgotPasswordView.vue
│   ├── dashboard/
│   │   └── DashboardView.vue
│   ├── agents/
│   │   ├── AgentsListView.vue           ← 🆕 Colonnes nb_affectes + nb_perdus + badge Nouveau
│   │   ├── AgentFormView.vue
│   │   └── AgentDetailView.vue
│   ├── equipements/
│   │   ├── EquipementsListView.vue      ← 🆕 Badge Nouveau
│   │   ├── EquipementFormView.vue
│   │   └── EquipementDetailView.vue     ← 🆕 Bannière sinistre si bloqué
│   ├── affectations/
│   │   └── AffectationsListView.vue     ← 🆕 Miniatures photos remise/retour
│   ├── mouvements/
│   │   └── MouvementsListView.vue
│   ├── pannes/
│   │   ├── PannesListView.vue
│   │   └── PanneDetailView.vue
│   ├── maintenances/
│   │   └── MaintenancesListView.vue
│   ├── pertes-casses/
│   │   └── PertesCassesListView.vue     ← 🆕 Statut sinistre + boutons Valider/Rejeter
│   ├── rapports/
│   │   └── RapportsView.vue
│   └── admin/
│       └── UsersView.vue
├── router/
│   └── index.js         ← Guards par rôle
├── stores/
│   ├── auth.js
│   ├── equipements.js
│   ├── notifications.js
│   └── ui.js
├── services/
│   ├── api.js           ← Instance Axios + intercepteurs
│   ├── authService.js
│   ├── agentService.js
│   ├── equipementService.js
│   └── ...
└── utils/
    ├── permissions.js   ← Helpers vérification rôle
    ├── formatters.js    ← Formatage dates, prix, états
    └── constants.js     ← Enums partagés (🆕 inclut en_attente_sinistre)
```

### Guards de navigation (router/index.js)

```javascript
// Exemple de protection par rôle
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next('/login')
  }
  
  if (to.meta.roles && !to.meta.roles.includes(authStore.user.role)) {
    return next('/unauthorized')
  }
  
  next()
})

// Définition des routes protégées
{
  path: '/admin/users',
  component: UsersView,
  meta: { requiresAuth: true, roles: ['admin'] }
},
{
  path: '/equipements',
  component: EquipementsListView,
  meta: { requiresAuth: true, roles: ['admin', 'gestionnaire'] }
},
{
  path: '/mes-equipements',
  component: MesEquipementsView,
  meta: { requiresAuth: true, roles: ['agent'] }
}
```

---

## 9. Sécurité & exigences non fonctionnelles

### Sécurité

| Mesure | Implémentation |
|---|---|
| Authentification | Laravel Sanctum — token Bearer par requête |
| Autorisation | Laravel Policies par ressource + rôle |
| Protection CSRF | Gérée par Sanctum pour SPA |
| Validation des données | Form Requests Laravel côté backend |
| Validation frontend | Règles PrimeVue + composables Vue |
| Journalisation | Chaque action tracée dans `mouvements` |
| Mots de passe | Bcrypt, min. 8 caractères |
| Upload fichiers | Validation type/taille, stockage privé |

### Performance

| Exigence | Valeur cible |
|---|---|
| Temps de réponse API | < 3 secondes |
| Pagination des listes | 20 enregistrements par page |
| Recherche | Résultats < 1 seconde (index DB) |
| Index DB requis | `equipements.etat`, `agents.statut`, `affectations.statut`, `mouvements.equipement_id`, `mouvements.created_at` |

### Disponibilité

- Application web accessible 24h/24, 7j/7
- Sauvegarde quotidienne automatique de la base de données
- Gestion des erreurs avec messages utilisateur clairs (pas de stack trace exposée)

### Internationalisation
- Interface en **français**
- Formats dates : `DD/MM/YYYY`
- Formats monétaires : avec séparateur de milliers

---

## 10. Évolutions futures

Les fonctionnalités suivantes sont identifiées pour les versions futures, dans l'ordre de priorité suggéré :

| Priorité | Fonctionnalité | Description |
|:---:|---|---|
| 1 | **Signature électronique** | Signature lors des remises/retours d'équipements |
| 2 | **Application mobile** | App dédiée pour inventaires terrain (scan QR) |
| 3 | **Scan QR Code avancé** | Génération automatique des QR codes à l'ajout |
| 4 | **Alertes automatiques** | Notification renouvellement matériel, fin de garantie |
| 5 | **Géolocalisation** | Suivi GPS des équipements mobiles |
| 6 | **Intégration Active Directory** | Synchronisation comptes utilisateurs LDAP |
| 7 | **Gestion consommables** | Batteries, chargeurs, accessoires consommables |
| 8 | **API webhooks** | Intégration avec outils tiers (ITSM, ERP) |

---

*Document généré depuis le cahier des charges officiel du projet.*
*Stack : Laravel 12 · Vue.js 3 · MySQL · Laravel Sanctum*
