# EquipTrack — Documentation complète du projet

> Gestion & Traçabilité des Équipements · Laravel 12 + Vue 3 + MySQL

---

## Table des matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Stack technique](#2-stack-technique)
3. [Architecture générale](#3-architecture-générale)
4. [Base de données — Schéma complet](#4-base-de-données--schéma-complet)
5. [Backend — Laravel](#5-backend--laravel)
6. [Frontend — Vue 3](#6-frontend--vue-3)
7. [Rôles & permissions](#7-rôles--permissions)
8. [Flux logiques métier](#8-flux-logiques-métier)
9. [Système d'authentification OTP](#9-système-dauthentification-otp)
10. [Gestion des fichiers & uploads](#10-gestion-des-fichiers--uploads)
11. [Journal automatique (Observers)](#11-journal-automatique-observers)
12. [Routes API complètes](#12-routes-api-complètes)
13. [Stores Pinia (état frontend)](#13-stores-pinia-état-frontend)
14. [Navigation & routing frontend](#14-navigation--routing-frontend)
15. [Composants layout partagés](#15-composants-layout-partagés)
16. [Seeders & données initiales](#16-seeders--données-initiales)

---

## 1. Vue d'ensemble

EquipTrack est une plateforme web de gestion du cycle de vie complet des équipements informatiques d'entreprise (PDA, smartphones, tablettes, scanners, ordinateurs portables, accessoires).

**Problème résolu :** suivi manuel → perte de traçabilité, impossibilité de connaître l'état réel du parc, absence d'historique fiable.

**Solution :** application centralisée couvrant acquisition → affectation → panne → maintenance → retour → réforme/perte, avec journal automatique de toutes les actions.

---

## 2. Stack technique

| Couche | Technologie | Rôle |
|---|---|---|
| **Backend** | Laravel 12 | Framework PHP, logique métier, API REST |
| **Auth** | Laravel Sanctum | Tokens Bearer SPA |
| **Base de données** | MySQL 8+ | Stockage principal |
| **Frontend** | Vue.js 3 (Composition API) | SPA reactive |
| **État** | Pinia | State management global |
| **Router** | Vue Router 4 | Navigation SPA protégée |
| **HTTP Client** | Axios | Requêtes API avec intercepteurs |
| **UI** | Tailwind CSS | Styles utilitaires |
| **Composants** | PrimeVue (Aura) | Toast, Confirmation dialog |
| **Icônes** | Lucide Vue Next | Icônes SVG |
| **Build** | Vite | Bundler frontend |

---

## 3. Architecture générale

```
equipements/
├── backend/                    # Laravel 12
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/    # 13 contrôleurs
│   │   │   └── Middleware/     # CheckUserStatus
│   │   ├── Models/             # 11 modèles Eloquent
│   │   ├── Observers/          # 4 observers (journal auto)
│   │   ├── Mail/               # OTPMail
│   │   └── Providers/          # AppServiceProvider (enregistre observers)
│   ├── database/
│   │   ├── migrations/         # 20 fichiers
│   │   ├── seeders/            # DatabaseSeeder, CategorieSeeder, AgentSeeder, EquipementSeeder
│   │   └── factories/          # CategorieFactory, EquipementFactory
│   └── routes/
│       └── api.php             # Toutes les routes API
│
└── frontend/                   # Vue 3 + Vite
    └── src/
        ├── assets/             # index.css (Tailwind)
        ├── components/
        │   ├── layout/         # Sidebar, Header, PageHeader, SideModal
        │   └── dashboard/      # StatCard
        ├── layouts/            # AdminLayout.vue
        ├── router/             # index.js (routes par rôle)
        ├── services/           # axios.js (instance configurée)
        ├── stores/             # 13 stores Pinia
        └── views/
            ├── admin/          # 11 vues admin
            ├── gestionnaire/   # 1 vue dashboard
            ├── agent/          # 4 vues agent
            ├── auth/           # Login, OTP
            └── shared/         # ProfileView (partagée)
```

---

## 4. Base de données — Schéma complet

### Table `users`
| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| `id` | bigint PK | AUTO_INCREMENT | — |
| `name` | varchar(255) | NOT NULL | Nom complet |
| `email` | varchar(255) | UNIQUE NOT NULL | Email de connexion |
| `password` | varchar(255) | NOT NULL (hashed) | Mot de passe bcrypt |
| `role` | enum | `admin/gestionnaire/agent` | Rôle de l'utilisateur |
| `is_active` | boolean | DEFAULT true | Activation du compte |
| `avatar` | varchar | NULLABLE | Chemin relatif de l'avatar uploadé |
| `remember_token` | varchar(100) | NULLABLE | Token remember-me |
| `created_at / updated_at` | timestamps | AUTO | — |

### Table `agents`
| Colonne | Type | Contraintes | Description |
|---|---|---|---|
| `id` | bigint PK | — | — |
| `user_id` | bigint FK | NULLABLE → users | Compte utilisateur associé |
| `matricule` | varchar(50) | UNIQUE NOT NULL | Format `MAT-YYYY-XXXXX` (auto-généré) |
| `nom` | varchar(100) | NOT NULL | — |
| `prenom` | varchar(100) | NOT NULL | — |
| `telephone` | varchar(20) | NULLABLE | — |
| `email` | varchar(255) | NULLABLE | Email professionnel |
| `direction` | varchar(150) | NULLABLE | Direction RH |
| `service` | varchar(150) | NULLABLE | Service RH |
| `poste` | varchar(150) | NULLABLE | Intitulé du poste |
| `statut` | enum | `actif/inactif` | Statut de l'agent |
| `photo` | varchar | NULLABLE | Chemin relatif de la photo uploadée |

**Champs calculés (Accessors Eloquent, non stockés) :**
- `is_nouveau` : `created_at >= now() - 30 jours`
- `nb_affectes` : `COUNT(affectations WHERE statut = 'en_cours')`
- `nb_perdus` : `COUNT(pertes_casses WHERE statut = 'cloturee')`

### Table `categories`
| Colonne | Description |
|---|---|
| `id` | PK |
| `nom` | UNIQUE (PDA, Smartphone, Tablette, Scanner, Ordinateur portable, Accessoire) |
| `description` | Texte libre |

### Table `equipements` ⭐ Table pivot centrale
| Colonne | Type | Description |
|---|---|---|
| `id` | bigint PK | — |
| `categorie_id` | FK categories | Type d'équipement |
| `reference` | varchar UNIQUE | Référence interne |
| `numero_serie` | varchar UNIQUE | N° de série constructeur |
| `imei` | varchar UNIQUE NULLABLE | Pour appareils GSM |
| `code_inventaire` | varchar UNIQUE | Code QR/barres |
| `marque` | varchar | Zebra, Apple, Dell… |
| `modele` | varchar | Modèle précis |
| `fournisseur` | varchar NULLABLE | — |
| `date_acquisition` | date NULLABLE | Date d'achat |
| `prix_achat` | decimal(10,2) NULLABLE | Prix HT |
| `garantie_fin` | date NULLABLE | Fin de garantie |
| `etat` | enum | `neuf / en_service / en_panne / en_maintenance / en_attente_sinistre / reforme / perdu` |
| `localisation` | varchar NULLABLE | Entrepôt, agence… |
| `notes` | text NULLABLE | Remarques libres |
| `is_archived` | boolean DEFAULT false | Archivage logique |

**Champ calculé :** `is_nouveau` : `date_acquisition >= now() - 30 jours`

### Table `affectations`
| Colonne | Description |
|---|---|
| `equipement_id` FK | Équipement concerné |
| `agent_id` FK | Agent destinataire |
| `affecte_par` FK users | Auteur de l'affectation |
| `date_affectation` | Date de remise |
| `photo_remise` | **OBLIGATOIRE** — photo prise à la remise |
| `date_retour` | NULL = affectation toujours active |
| `etat_retour` | État constaté au retour |
| `photo_retour` | **OBLIGATOIRE au retour** |
| `observations` | Commentaires libres |
| `statut` | `en_cours / retourne / renouvele` |

**Règle métier :** Un équipement ne peut avoir qu'une seule affectation `en_cours` à la fois.
**Photos exposées en URL :** `photo_remise_url` et `photo_retour_url` (Accessors `asset('storage/'.$path)`)

### Table `mouvements` (journal immuable)
| Colonne | Description |
|---|---|
| `equipement_id` FK | — |
| `user_id` FK | Auteur de l'action |
| `type_mouvement` | `affectation / retour / changement_etat / panne_declaree / maintenance_debut / maintenance_fin / reforme / perte_declaree / renouvellement` |
| `ancienne_valeur` | JSON — état avant |
| `nouvelle_valeur` | JSON — état après |
| `reference_id` | ID polymorphe de la ressource source |
| `reference_type` | Classe Eloquent source |
| `created_at` | **NE JAMAIS MODIFIER** — pas de `updated_at` |

### Table `pannes`
| Colonne | Description |
|---|---|
| `equipement_id` FK | — |
| `declare_par` FK users | Auteur de la déclaration |
| `valide_par` FK users NULLABLE | Valideur (admin/gestionnaire) |
| `date_declaration` | — |
| `date_validation` | — |
| `description` | Détail du problème |
| `gravite` | `faible / moyenne / critique` |
| `statut` | `declaree → en_cours → en_maintenance → resolue / irrecuperable` |
| `photos` | JSON — tableau de chemins |

### Table `maintenances`
| Colonne | Description |
|---|---|
| `equipement_id` FK | — |
| `panne_id` FK NULLABLE | Panne déclenchant la maintenance |
| `type` | `preventive / corrective` |
| `technicien` | Nom du technicien |
| `responsable_id` FK users | Planificateur |
| `diagnostic` | — |
| `actions_effectuees` | Détail des travaux |
| `cout` | decimal NULLABLE |
| `date_debut` | — |
| `date_fin` | NULL = maintenance en cours |
| `photos_retour` | JSON — photos après intervention |

### Table `pertes_casses`
| Colonne | Description |
|---|---|
| `equipement_id` FK | — |
| `declare_par` FK users | Déclarant |
| `type` | `perte / vol / casse` |
| `date_declaration` | — |
| `description` | Circonstances |
| `statut` | `en_attente_validation → validee → cloturee` ou `rejetee` |
| `valide_par` FK users NULLABLE | Admin ou gestionnaire ayant statué |
| `date_validation` | — |
| `motif_rejet` | Obligatoire si rejet |
| `photos` | JSON |

### Table `user_otps`
| Colonne | Description |
|---|---|
| `user_id` FK | — |
| `code` | Code à 6 chiffres |
| `expires_at` | Expiration : now + 15 min |
| `verified` | boolean — `true` après validation |

### Table `equipement_images`
| Colonne | Description |
|---|---|
| `equipement_id` FK | — |
| `path` | Chemin relatif dans `storage/public/equipements/` |

---

## 5. Backend — Laravel

### Contrôleurs

#### `AuthController`
Gère le flux d'authentification complet :

- **`login()`** : Vérifie email/mot de passe + `is_active`. Si l'utilisateur a déjà un OTP vérifié en base → délivre directement un token Sanctum. Sinon → génère un OTP 6 chiffres (expiration 15 min), envoie par email, retourne `requires_otp: true`.
- **`verifyOtp()`** : Vérifie le code soumis (non expiré, non utilisé), marque `verified = true`, délivre le token Sanctum.
- **`resendOtp()`** : Génère un nouveau code et renvoie l'email.
- **`logout()`** : Révoque le token courant (`currentAccessToken()->delete()`).
- **`me()`** : Retourne l'utilisateur authentifié.

#### `UserController`
Gestion complète des comptes utilisateurs (admin uniquement en pratique) :

- **`index()`** : Liste tous les users avec leur agent lié + `avatar_url` calculée via `Storage::url()`.
- **`store()`** : Crée un compte. Si rôle = `agent` et `agent_id` fourni → vérifie que l'agent n'a pas déjà un compte puis lie le `user_id` à l'agent.
- **`show()`** : Détail d'un user avec agent + affectations + équipements, `last_login_at` (dernier token `last_used_at`), `tokens_count`.
- **`update()`** : Mise à jour partielle (nom, email, rôle, password optionnel).
- **`toggleStatus()`** : Active/désactive un compte. **Protection** : un utilisateur ne peut pas se désactiver lui-même (403). Synchronise aussi le `statut` de l'agent lié.
- **`updateProfile()`** : Mise à jour du profil connecté (nom, email, avatar). Upload avatar en `users/avatars/`. Supprime l'ancien avatar si existant. Retourne `avatar_url`.
- **`changePassword()`** : Vérifie l'ancien mot de passe avec `Hash::check()` avant de mettre à jour. Validation `confirmed`.
- **Méthode privée `withAvatarUrl()`** : Transforme un objet User en array en ajoutant `avatar_url` (URL absolue publique).

#### `AgentController`
Gestion des agents (réservé aux admins pour création/modification/désactivation) :

- **`index()`** : Liste tous les agents avec `user`, `affectations.equipement`. Retourne `photo_url` via `withPhotoUrl()`.
- **`store()`** : Pas de matricule dans le formulaire — **généré automatiquement** : `MAT-{YEAR}-{XXXXX}` (séquence basée sur le dernier agent de l'année). Upload photo optionnel en `agents/photos/`.
- **`show()`** : Détail avec affectations et équipements.
- **`update()`** : Mise à jour partielle avec remplacement de photo (supprime l'ancienne via `Storage::disk('public')->delete()`).
- **`desactiver()`** / **`reactiver()`** : Change `statut` + synchronise `is_active` du compte user lié.
- **Méthode privée `withPhotoUrl()`** : Ajoute `photo_url` (URL absolue) à chaque agent.

#### `EquipementController`
- **`index()`** : Filtre par rôle. Agent → uniquement ses équipements affectés en cours. Retourne avec images et affectation courante.
- **`store()`** : Crée l'équipement + upload multiple d'images (`equipements/`).
- **`archive()`** / **`unarchive()`** : Soft-delete logique via `is_archived`.
- **`fetchArchives()`** : Liste les équipements archivés. Route déclarée **avant** `apiResource` pour éviter les conflits.

#### `AffectationController`
- **`store()`** : Valide que l'équipement est à l'état `neuf`. Upload `photo_remise` **obligatoire**. Passe l'équipement en `en_service`. L'`AffectationObserver` crée le mouvement.
- **`update()`** : Deux modes — retour (si `date_retour` présent + `statut = en_cours`) avec `photo_retour` **obligatoire** et passage `statut = retourne` ; modification générale sinon.

#### `PanneController`
- **`index()`** : Filtre par rôle (agent → ses pannes, gestionnaire → par catégorie).
- **`store()`** : Crée la panne, passe l'équipement en `en_panne`. Upload photos. Le `PanneObserver` crée le mouvement.
- **`valider()`** : Passe statut → `en_cours` (prêt pour maintenance).
- **`rejeter()`** : Passe statut → `irrecuperable`, remet l'équipement `en_service`.

#### `MaintenanceController`
- **`store()`** : Crée la maintenance à partir d'une panne (`panne_id` requis). Passe panne → `en_maintenance`, équipement → `en_maintenance`. Dans une transaction DB.
- **`cloturer()`** : Enregistre `date_fin`, actions, coût, photos retour. Passe panne → `resolue`, équipement → `repare`. Transaction DB.
- **`declarerPerte()`** : Clôture la maintenance + passe panne → `irrecuperable` + crée automatiquement un `PerteCasse` + passe équipement → `perdu`. Transaction DB.

#### `PerteCasseController`
- **`store()`** : Crée le sinistre, passe équipement → `en_attente_sinistre`.
- **`valider()`** : Passe statut → `validee`. Si perte/vol → équipement `perdu`, si casse → `reforme`.
- **`rejeter()`** : Passe statut → `rejetee` (motif obligatoire), remet équipement → `en_service`.

#### `DashboardController`
Statistiques contextuelles selon le rôle :
- **Admin/Gestionnaire** : Total équipements, répartition par état, stats par catégorie, évolution 12 mois (affectations + retours), activité récente (10 mouvements), alertes (pannes critiques, maintenances en cours).
- **Agent** : Ses équipements affectés en cours + ses pannes actives.

#### `RapportController`
Statistiques globales avec période configurable (`?periode=30`) :
- KPIs : total, en_maintenance, opérationnels, mouvements sur période
- Répartition par état et par catégorie (avec barres de progression côté front)
- Évolution 12 mois affectations/retours par mois
- Pannes par gravité (faible/moyenne/critique)
- Maintenances : en cours, clôturées sur période, coût total
- Sinistres : en attente, validés
- Top 5 agents (plus de matériels affectés)
- Activité récente (8 derniers mouvements)

#### `MouvementController`
- **`index()`** : Lecture seule — retourne tous les mouvements avec équipement, user et référence polymorphe. Triés par `created_at` desc.

#### `CategorieController`
CRUD complet standard (nom unique, description).

### Middleware

#### `CheckUserStatus`
Appliqué sur toutes les routes protégées (`auth:sanctum, check.active`).
- Vérifie `$request->user()->is_active`
- Si `false` → révoque **tous** les tokens de l'utilisateur (`tokens()->delete()`) + retourne 403
- Empêche qu'un compte désactivé reste connecté avec un token existant

### Observers (journal automatique)

#### `AffectationObserver`
- **`created`** → crée un mouvement de type `affectation` (nouvelle_valeur : agent_id + date)
- **`updated`** (si `statut` change vers `retourne`) → crée mouvement `retour` (date_retour, etat_retour) + repasse l'équipement en `neuf`

#### `EquipementObserver`
- **`updated`** (si `etat` change) → crée mouvement `changement_etat` avec mapping : `en_attente_sinistre` → `sinistre_declare`, `perdu` → `perte_declaree`, `reforme` → `reforme`, autres → `changement_etat`

#### `PanneObserver`
- **`created`** → crée mouvement `panne`, passe équipement → `en_panne`
- **`updated`** (si `statut` change) → crée mouvement avec ancien/nouveau statut ; si `resolue` → vérifie autres pannes actives, repasse équipement `en_service` ; si `irrecuperable` → `reforme` ; si `en_maintenance` → `en_maintenance`

#### `MaintenanceObserver`
- **`created`** → crée mouvement `maintenance_debut` (technicien, type, date_debut)
- **`updated`** (si `date_fin` passe de NULL à valeur) → crée mouvement `maintenance_fin` (date_fin, diagnostic, actions)

### AppServiceProvider
Enregistre les 4 observers dans `boot()` :
```php
Equipement::observe(EquipementObserver::class);
Affectation::observe(AffectationObserver::class);
Panne::observe(PanneObserver::class);
Maintenance::observe(MaintenanceObserver::class);
```

---

## 6. Frontend — Vue 3

### Service Axios (`services/axios.js`)
Instance Axios configurée avec :
- `baseURL` : `VITE_API_URL` (variable d'environnement)
- `withCredentials: true` + `withXSRFToken: true` (Sanctum CSRF)
- **Intercepteur requête** : injecte `Authorization: Bearer {token}` depuis localStorage
- **Intercepteur réponse** : si 401 → nettoie localStorage + redirige vers `/login`

### Entrée principale (`main.js`)
- Crée l'app Vue
- Enregistre Pinia, Vue Router
- Enregistre PrimeVue (thème Aura), ToastService, ConfirmationService
- Enregistre la **directive globale `v-click-outside`** : ferme les dropdowns au clic extérieur (utilisée dans Header pour le menu profil)

### Layout principal (`layouts/AdminLayout.vue`)
Utilisé par tous les rôles (admin, gestionnaire, agent) :
- Gère l'état `isSidebarCollapsed` (desktop) et `isSidebarOpenMobile` (mobile)
- Bascule automatiquement entre collapse desktop et overlay mobile selon `window.innerWidth < 1024`
- Ferme le sidebar mobile automatiquement au changement de route (watch `route.path`)
- Contient `<Sidebar>`, `<Header>`, et `<router-view>` avec transition `fade`

### `Sidebar.vue`
**Menu dynamique par rôle** — lit `authStore.user?.role` via `computed` :

| Admin | Gestionnaire | Agent |
|---|---|---|
| Tableau de bord | Tableau de bord | Mon espace |
| Agents | Agents | Mes équipements |
| Équipements | Équipements | Mes incidents |
| Affectations | Affectations | Mon profil |
| Mouvements | Pannes | — |
| Pannes | Maintenances | — |
| Maintenances | Pertes & Casses | — |
| Sinistres | Rapports | — |
| Archives | Mon profil | — |
| Rapports | — | — |
| Administration | — | — |
| Mon profil | — | — |

- **Item actif** : fond bleu vif (`bg-primary-600 text-white shadow-lg`) — texte blanc lisible
- **Mode réduit** : icône seule + tooltip au survol
- **Mobile** : overlay sombre + slide depuis la gauche
- **Bas de sidebar** : bouton "Besoin d'aide ?" avec cercle `?` + texte

### `Header.vue`
- Bouton hamburger (toggle sidebar)
- Cloche notifications (badge rouge)
- **Dropdown profil** (`v-click-outside`) :
  - Avatar rond (image si `avatar_url`, initiale sinon)
  - Nom + rôle
  - Chevron animé
  - Menu : "Mon profil" → `router.push('/{role}/profile')`, "Se déconnecter" → `authStore.logout()`

### `SideModal.vue`
Composant modal réutilisable avec deux modes :
- `mode="side"` (default) : panel glissant depuis la droite
- `mode="center"` : modal centré avec zoom-in
- Backdrop semi-transparent (`bg-slate-900/20 backdrop-blur-sm`)
- Fermeture au clic sur le backdrop ou bouton X

### `ProfileView.vue` (vue partagée)
Accessible à tous les rôles via `/{role}/profile` :

**Colonne gauche :**
1. **Informations personnelles** : nom (éditable), email (éditable), rôle (lecture seule), statut (lecture seule)
2. **Sécurité & mot de passe** :
   - Champ "mot de passe actuel" (requis, vérification backend)
   - Nouveau mot de passe + confirmation
   - **Validation en temps réel** : 4 règles visuelles (≥8 caractères, majuscule, chiffre, identiques)
   - Bouton désactivé tant que les règles ne sont pas toutes remplies
3. **Zone de danger** : "Se déconnecter de partout" → `authStore.logout()`

**Colonne droite :**
- **Avatar** : cercle avec photo ou initiale. Bouton caméra (`-bottom-1 -right-1`) pour changer la photo. Avertissement amber si photo sélectionnée non encore sauvegardée.
- **Informations du compte** : membre depuis, niveau d'accès, statut, authentification OTP

**Logique d'envoi :**
- Si avatar sélectionné → `FormData` avec `_method=PUT` (method spoofing Laravel) via `POST /profile`
- Sinon → JSON via `PUT /profile`
- Après succès → sync `authStore.user` + localStorage + mise à jour de `avatarPreview` avec `avatar_url` retournée

---

## 7. Rôles & permissions

| Action | Admin | Gestionnaire | Agent |
|---|:---:|:---:|:---:|
| Gestion des utilisateurs | ✅ | ❌ | ❌ |
| Gestion des catégories | ✅ | ❌ | ❌ |
| Créer/modifier un agent | ✅ | ❌ | ❌ |
| Désactiver/réactiver un agent | ✅ | ❌ | ❌ |
| Ajouter/modifier un équipement | ✅ | ✅ | ❌ |
| Archiver un équipement | ✅ | ❌ | ❌ |
| Affecter un équipement | ✅ | ✅ | ❌ |
| Enregistrer un retour | ✅ | ✅ | ❌ |
| Déclarer une panne | ✅ | ✅ | ✅ |
| Valider/rejeter une panne | ✅ | ✅ | ❌ |
| Créer une maintenance | ✅ | ✅ | ❌ |
| Clôturer une maintenance | ✅ | ✅ | ❌ |
| Déclarer un sinistre | ✅ | ✅ | ✅ |
| Valider/rejeter un sinistre | ✅ | ✅ | ❌ |
| Consulter tous les équipements | ✅ | ✅ | ❌ |
| Consulter ses équipements | ✅ | ✅ | ✅ |
| Rapports & statistiques | ✅ | ✅ | ❌ |
| Se désactiver soi-même | ❌ | ❌ | ❌ |

**Implémentation :**
- **Backend** : `CheckUserStatus` middleware + vérifications dans chaque contrôleur selon `Auth::user()->role`
- **Frontend** : Guard de navigation dans `router/index.js` + propriété `isAdmin` dans les vues + sidebar dynamique

---

## 8. Flux logiques métier

### Cycle de vie d'un équipement
```
[Acquisition] → etat: neuf
      ↓ Affectation
[En service] → etat: en_service
      ↓ Déclaration panne
[En panne] → etat: en_panne
      ↓ Validation panne
[Panne validée] → panne.statut: en_cours
      ↓ Création maintenance
[En maintenance] → etat: en_maintenance
      ↓ Clôture maintenance
[Réparé] → etat: repare (retour à en_service via retour affectation → neuf)
      
      OU ↓ Déclaration perte depuis maintenance
[Sinistre] → etat: en_attente_sinistre
      ↓ Validation sinistre
[Perdu/Réformé] → etat: perdu ou reforme

      OU ↓ Retour affectation
[Neuf] → disponible pour une nouvelle affectation
```

### Workflow affectation
1. Admin/Gestionnaire vérifie que l'équipement est à l'état `neuf`
2. Prend une photo à la remise (`photo_remise` obligatoire)
3. Crée l'affectation → équipement passe en `en_service`
4. `AffectationObserver.created` → crée mouvement `affectation`

### Workflow retour
1. Enregistre la date de retour + état constaté + photo de retour (`photo_retour` obligatoire)
2. Affectation passe en `retourne`
3. `AffectationObserver.updated` → crée mouvement `retour` + équipement repasse en `neuf`

### Workflow sinistre
1. Déclaration → `en_attente_validation` + équipement → `en_attente_sinistre`
2. Admin/Gestionnaire valide → `validee` + équipement → `perdu` (perte/vol) ou `reforme` (casse)
3. Admin/Gestionnaire rejette → `rejetee` (motif obligatoire) + équipement → `en_service`

---

## 9. Système d'authentification OTP

**Principe :** Première connexion obligatoirement par OTP, connexions suivantes directes.

**Flux détaillé :**
1. POST `/auth/login` → vérifie credentials + `is_active`
2. Cherche si un `UserOtp` avec `verified = true` existe pour cet utilisateur
3. **Si OUI** (connexion déjà vérifiée) → délivre token Sanctum immédiatement
4. **Si NON** → génère code 6 chiffres, crée `UserOtp` (expiration 15 min), envoie email via `OTPMail`
5. Frontend redirige vers `/otp`
6. POST `/auth/verify-otp` → vérifie code non expiré, non déjà utilisé (`verified = false`), marque `verified = true`, délivre token
7. Token stocké dans `localStorage` + configuré dans headers Axios

**Email :** Classe `OTPMail` avec template Markdown `emails.otp`, sujet "Votre code de vérification EquipTrack".

**Store `auth.js` :**
- `user` et `token` persistés en `localStorage`
- `tempUser` : stocke email + nom pendant la phase OTP
- `setAuth(data)` : synchronise store + localStorage + header Axios
- `logout()` : révoque token backend + nettoie tout côté frontend

---

## 10. Gestion des fichiers & uploads

### Stockage
Tous les fichiers sont stockés dans `storage/app/public/` via le disk `public`.
Lien symbolique `public/storage → storage/app/public` créé avec `php artisan storage:link`.

### Types de fichiers uploadés
| Contexte | Champ | Dossier | Format |
|---|---|---|---|
| Avatar utilisateur | `avatar` | `users/avatars/` | JPEG, PNG, WebP ≤ 2 Mo |
| Photo agent | `photo` | `agents/photos/` | JPEG, PNG, WebP ≤ 2 Mo |
| Photo remise affectation | `photo_remise` | `affectations/` | JPEG, PNG, WebP ≤ 5 Mo |
| Photo retour affectation | `photo_retour` | `affectations/` | JPEG, PNG, WebP ≤ 5 Mo |
| Photos panne | `images[]` | `pannes/` | JPEG, PNG, WebP ≤ 5 Mo |
| Photos maintenance | `images[]` | `maintenances/` | JPEG, PNG, WebP ≤ 5 Mo |
| Photos sinistre | `images[]` | `sinistres/` | JPEG, PNG, WebP ≤ 5 Mo |
| Images équipement | `images[]` | `equipements/` | JPEG, PNG, WebP ≤ 5 Mo |

### Pattern d'URL publique
Le backend retourne toujours l'**URL absolue** via `Storage::url($path)` dans les champs `avatar_url` et `photo_url`. Le frontend n'a jamais à reconstruire de chemin.

### Method spoofing Laravel (multipart + PUT/PATCH)
Laravel ne lit pas les fichiers multipart sur les méthodes PUT/PATCH. Solution utilisée partout dans le projet :
- Envoyer en `POST` avec `_method=PUT` dans le FormData
- Le frontend ajoute `formData.append('_method', 'PUT')` avant d'envoyer

---

## 12. Routes API complètes

```
# Auth (public)
POST   /api/auth/login
POST   /api/auth/verify-otp
POST   /api/auth/resend-otp

# Auth (protégé)
GET    /api/auth/me
POST   /api/auth/logout

# Profil connecté (avant apiResource pour éviter conflits de routing)
PUT    /api/profile
POST   /api/profile            ← FormData method spoofing
PUT    /api/profile/password

# Utilisateurs
GET    /api/users
POST   /api/users
GET    /api/users/{id}
PUT    /api/users/{id}
DELETE /api/users/{id}
PATCH  /api/users/{id}/toggle-status

# Dashboard
GET    /api/dashboard

# Catégories
GET    /api/categories
POST   /api/categories
GET    /api/categories/{id}
PUT    /api/categories/{id}
DELETE /api/categories/{id}

# Équipements
GET    /api/equipements/archives   ← AVANT apiResource
PATCH  /api/equipements/{id}/archive
PATCH  /api/equipements/{id}/unarchive
GET    /api/equipements
POST   /api/equipements
GET    /api/equipements/{id}
PUT    /api/equipements/{id}
DELETE /api/equipements/{id}

# Affectations
GET    /api/affectations
POST   /api/affectations
GET    /api/affectations/{id}
PUT/POST /api/affectations/{id}

# Pannes
PATCH  /api/pannes/{id}/valider
PATCH  /api/pannes/{id}/rejeter
GET    /api/pannes
POST   /api/pannes
GET    /api/pannes/{id}
PUT    /api/pannes/{id}
DELETE /api/pannes/{id}

# Maintenances
PATCH  /api/maintenances/{id}/cloturer
PATCH  /api/maintenances/{id}/declarer-perte
GET    /api/maintenances
POST   /api/maintenances
GET    /api/maintenances/{id}
PUT    /api/maintenances/{id}

# Sinistres (Pertes & Casses)
GET    /api/sinistres
POST   /api/sinistres
PATCH  /api/sinistres/{id}/valider
PATCH  /api/sinistres/{id}/rejeter

# Agents
GET    /api/agents/sans-compte   ← déclaré avant apiResource (supprimé, filtrage côté frontend)
GET    /api/agents
POST   /api/agents
GET    /api/agents/{id}
POST   /api/agents/{id}          ← method spoofing PUT pour upload photo
PUT    /api/agents/{id}
PATCH  /api/agents/{id}/desactiver
PATCH  /api/agents/{id}/reactiver

# Mouvements
GET    /api/mouvements

# Rapports
GET    /api/rapports/stats?periode=30
```

**Middlewares actifs sur toutes les routes protégées :** `auth:sanctum` + `check.active`

---

## 13. Stores Pinia (état frontend)

### `auth.js`
**État :** `user`, `token`, `isAuthenticated`, `requiresOTP`, `tempUser`
**Persistance :** `user` et `token` dans `localStorage`
**Actions :** `login`, `verifyOTP`, `resendOTP`, `googleLogin`, `setAuth`, `logout`

### `user.js`
**État :** `users[]`, `selectedUser`, `loading`, `loadingDetail`
**Actions :** `fetchUsers`, `fetchUser(id)`, `createUser`, `updateUser`, `toggleStatus`, `updateProfile` (FormData ou JSON), `changePassword`

### `agent.js`
**État :** `agents[]`, `selectedAgent`, `loading`
**Getters :** `agentsSansCompte` (filtre local `user_id === null`), `agentsActifs`, `agentsInactifs`, `totalAgents`
**Actions :** `fetchAgents`, `fetchAgent(id)`, `createAgent(FormData)`, `updateAgent(id, FormData)`, `toggleStatut`

### `affectation.js`
**État :** `affectations[]`, `currentAffectation`, `loading`, `error`, `successMessage`
**Actions :** `fetchAffectations`, `fetchAffectationById`, `createAffectation`, `updateAffectation`, `returnAffectation`

### `equipement.js`
**État :** `equipements[]`, `loading`
**Actions :** `fetchEquipements`, `fetchEquipement`, `createEquipement(FormData)`, `updateEquipement(id, FormData)`, `archiveEquipement`, `unarchiveEquipement`, `fetchArchives`

### `panne.js`
**État :** `pannes[]`, `loading`, `successMessage`
**Actions :** `fetchPannes`, `createPanne(FormData)`, `validerPanne`, `rejeterPanne`, `deletePanne`

### `maintenance.js`
**État :** `maintenances[]`, `loading`
**Actions :** `fetchMaintenances`, `createMaintenance`, `cloturerMaintenance(id, FormData)`, `declarerPerteMaintenance(id, FormData)`

### `sinistre.js`
**État :** `sinistres[]`, `loading`
**Actions :** `fetchSinistres`, `declareSinistre(FormData)`, `validerSinistre`, `rejeterSinistre(id, motif)`

### `mouvement.js`
**État :** `mouvements[]`, `loading` — lecture seule
**Actions :** `fetchMouvements`

### `dashboard.js`
**État :** `stats`, `loading`
**Actions :** `fetchStats` → `GET /dashboard`

### `rapport.js`
**État :** `kpis[]`, `repartitionEtat[]`, `parCategorie[]`, `evolution{}`, `pannesParGravite{}`, `maintenances{}`, `sinistres{}`, `topAgents[]`, `activiteRecente[]`, `periode` (défaut 30)
**Actions :** `fetchStats` (avec `?periode=X`), `setPeriode(jours)` (change + recharge)

### `agents.js` *(doublon historique)*
Store en Composition API avec les mêmes actions que `agent.js`. Utilisé dans `AgentsView.vue` d'une version antérieure. `agent.js` (Options API) est la version canonique actuelle.

---

## 14. Navigation & routing frontend

### Structure des routes
```
/login                          ← guest only
/otp                            ← guest only

/admin                          ← AdminLayout, role: admin
  /admin/dashboard
  /admin/agents
  /admin/equipements
  /admin/affectations
  /admin/mouvements
  /admin/pannes
  /admin/maintenances
  /admin/sinistres
  /admin/archives
  /admin/rapports
  /admin/administration
  /admin/profile

/gestionnaire                   ← AdminLayout, role: gestionnaire
  /gestionnaire/dashboard
  /gestionnaire/agents
  /gestionnaire/equipements
  /gestionnaire/affectations
  /gestionnaire/pannes
  /gestionnaire/maintenances
  /gestionnaire/sinistres
  /gestionnaire/rapports
  /gestionnaire/profile

/agent                          ← AdminLayout, role: agent
  /agent/dashboard
  /agent/equipements
  /agent/incidents
  /agent/profile

/                               ← redirect vers /{role}/dashboard
/:pathMatch(.*)                 ← catch-all → /
```

### Guards de navigation (`beforeEach`)
1. Route avec `meta.auth` + non authentifié → `/login`
2. Route avec `meta.guest` + authentifié → `/{role}/dashboard`
3. Route avec `meta.role` + rôle différent → `/{role}/dashboard`

### Réutilisation des vues
Les vues admin sont réutilisées directement pour le gestionnaire (ex: `AgentsView`, `EquipementsView`, `AffectationsView` sont les mêmes composants). La différence d'accès est gérée dans chaque vue via `authStore.user.role`.

---

## 15. Composants layout partagés

### `PageHeader.vue`
Composant titre de page avec slot `#actions` pour les boutons d'action.

### `StatCard.vue`
Carte statistique utilisée dans les dashboards avec `label`, `value`, `icon`, `colorClass`.

### `SideModal.vue`
- Props : `show`, `title`, `mode` ('side' | 'center')
- Émit : `close`
- Backdrop : `bg-slate-900/20 backdrop-blur-sm` — léger flou
- Headers : `bg-white` (solide, pas de glassmorphism)
- Animations : slide-in-from-right (side), zoom-in-95 (center)

---

## 16. Seeders & données initiales

### `DatabaseSeeder`
Ordre d'exécution :
1. Création manuelle des comptes système (admin + gestionnaire)
2. `CategorieSeeder` → 6 catégories métier
3. `AgentSeeder` → 10 agents + leurs comptes `users` liés
4. `EquipementSeeder` → 30 équipements (5 par catégorie)

### `CategorieSeeder`
Utilise `firstOrCreate` (idempotent) pour les 6 catégories :
PDA, Smartphone, Tablette, Scanner, Ordinateur portable, Accessoire.

### `AgentSeeder`
10 agents réalistes (Amélie Dubois, Thomas Martin…) avec direction, service, poste.
Pour chaque agent → crée un `User` lié (rôle `agent`, mot de passe `password`).

### `EquipementSeeder`
Équipements réalistes par catégorie : vrais modèles Zebra TC52, Samsung Galaxy A54, Dell Latitude 5540, Apple iPad 10…
Numérotation : `REF-80000` à `REF-80029`, `INV-0001` à `INV-0030`.
IMEI généré uniquement pour PDA et Smartphone.

### Comptes de test
| Email | Mot de passe | Rôle |
|---|---|---|
| `admin@equip.com` | `password` | Administrateur |
| `gestion@equip.com` | `password` | Gestionnaire |
| `a.dubois@entreprise.fr` | `password` | Agent (Amélie Dubois) |
| *(+ 9 autres agents)* | `password` | Agent |

---

*Document généré le 9 juin 2026*
