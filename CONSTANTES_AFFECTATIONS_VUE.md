# Constantes et Variables - Vue `AffectationsView.vue`

Ce document décrit le rôle de chaque constante et variable utilisée dans la vue de gestion des affectations.

---

## 1. Initialisation & Stores

| Constante | Type | Rôle |
|-----------|------|------|
| `affectationStore` | `useAffectationStore()` | Instance du store Pinia pour gérer l'état des affectations (récupération, création, modification, retour). |
| `toast` | `useToast()` | Instance de la notification PrimeVue pour afficher les messages de succès/erreur. |

---

## 2. États Réactifs de Base (Store & Références)

| Constante/Variable | Type | Rôle |
|---------------------|------|------|
| `affectations` | `ref` (du store) | Liste de toutes les affectations récupérées depuis l'API. |
| `loading` | `ref` (du store) | Indique si le chargement des données du store est en cours (pour afficher un spinner). |
| `storeError` | `ref` (du store) | Message d'erreur provenant du store. |
| `currentAffectation` | `ref` (du store) | L'affectation sélectionnée pour consultation des détails (fiche). |
| `successMessage` | `ref` (du store) | Message de succès renvoyé par le backend après une action. |

---

## 3. États de Modales

| Constante | Type | Rôle |
|-----------|------|------|
| `showReturnModal` | `ref<boolean>` | Contrôle l'ouverture/fermeture de la modal de retour d'équipement. |
| `showCreateModal` | `ref<boolean>` | Contrôle l'ouverture/fermeture de la modal de création d'une nouvelle affectation. |
| `showEditModal` | `ref<boolean>` | Contrôle l'ouverture/fermeture de la modal de modification d'une affectation. |
| `showFiche` | `ref<boolean>` | Contrôle l'ouverture/fermeture de la modal de détails (fiche) d'une affectation. |

---

## 4. États de Données Sélectionnées

| Constante | Type | Rôle |
|-----------|------|------|
| `selectedAffectation` | `ref<Object\|null>` | Stocke l'objet affectation actuellement sélectionné pour modification ou retour. |
| `availableEquipements` | `ref<Array>` | Liste des équipements disponibles (état `neuf`) à affecter. |
| `agents` | `ref<Array>` | Liste des agents **actifs** sélectionnables pour une affectation. |

---

## 5. États des Formulaires

| Constante | Type | Rôle |
|-----------|------|------|
| `newAffectation` | `ref<Object>` | Données du formulaire de création d'une nouvelle affectation (equipement_id, agent_id, date, observations). |
| `editForm` | `ref<Object>` | Données du formulaire de modification d'une affectation (agent_id, date, observations). |
| `returnForm` | `ref<Object>` | Données du formulaire de retour d'équipement (date_retour, etat_retour, observations). |
| `submitting` | `ref<boolean>` | Indique si un formulaire est en cours de soumission (désactive les boutons et affiche un spinner). |
| `localError` | `ref<string\|null>` | Message d'erreur local au formulaire (ex : photo manquante). |

---

## 6. États pour les Photos (Fichiers & Prévisualisation)

### Création d'Affectation
| Constante | Type | Rôle |
|-----------|------|------|
| `photoInput` | `ref<HTMLInputElement\|null>` | Référence DOM vers le champ de type `file` pour la photo de remise (création). |
| `photoPreview` | `ref<string\|null>` | URL base64 de la photo sélectionnée pour prévisualisation (création). |
| `photoFile` | `ref<File\|null>` | Fichier photo brut pour la photo de remise (création), avant envoi au serveur. |

### Retour d'Équipement
| Constante | Type | Rôle |
|-----------|------|------|
| `returnPhotoInput` | `ref<HTMLInputElement\|null>` | Référence DOM vers le champ de type `file` pour la photo de retour. |
| `returnPhotoPreview` | `ref<string\|null>` | URL base64 de la photo de retour pour prévisualisation. |
| `returnPhotoFile` | `ref<File\|null>` | Fichier photo brut pour la photo de retour. |

### Modification d'Affectation
| Constante | Type | Rôle |
|-----------|------|------|
| `editPhotoInput` | `ref<HTMLInputElement\|null>` | Référence DOM vers le champ de type `file` pour la photo de remise (modification). |
| `editPhotoPreview` | `ref<string\|null>` | URL base64 de la photo de remise pour prévisualisation (modification). |
| `editPhotoFile` | `ref<File\|null>` | Fichier photo brut pour la photo de remise (modification). |

---

## 7. États Utilitaires & Dates

| Constante | Type | Rôle |
|-----------|------|------|
| `today` | `string` | Date du jour au format `YYYY-MM-DD`, utilisée pour définir la valeur par défaut des champs date et empêcher les dates futures. |

---

## 8. Fonctions (Actions)

| Fonction | Rôle |
|----------|------|
| `triggerFileInput()` | Ouvre la fenêtre de sélection de fichier pour la photo de remise (création). |
| `triggerReturnFileInput()` | Ouvre la fenêtre de sélection de fichier pour la photo de retour. |
| `triggerEditFileInput()` | Ouvre la fenêtre de sélection de fichier pour la photo de remise (modification). |
| `onFileChange(e)` | Gère la sélection d'une photo de remise (création) : lit le fichier, stocke le file et génère la preview. |
| `onReturnFileChange(e)` | Gère la sélection d'une photo de retour. |
| `onEditFileChange(e)` | Gère la sélection d'une photo de remise (modification). |
| `showToast(severity, summary, detail)` | Affiche une notification (toast) avec le niveau de gravité (success, error, etc.). |
| `fetchInitialData()` | Récupère la liste des équipements disponibles et des agents actifs depuis l'API. |
| `onMounted()` | Hooks : déclenche `fetchAffectations()` et `fetchInitialData()` au chargement de la vue. |
| `openCreateModal()` | Ouvre la modal de création et recharge les données initiales. |
| `openEditModal(aff)` | Ouvre la modal de modification, charge les données de l'affectation `aff` et réinitialise les champs. |
| `openReturnModal(aff)` | Ouvre la modal de retour, charge les données de l'affectation `aff` et réinitialise les champs. |
| `openFiche(id)` | Ouvre la modal de détails et récupère les informations complètes de l'affectation via son ID. |
| `submitAffectation()` | Valide et soumet le formulaire de création (vérifie la photo, construit le `FormData` et appelle le store). |
| `submitEdit()` | Valide et soumet le formulaire de modification. |
| `submitReturn()` | Valide et soumet le formulaire de retour (vérifie la photo obligatoire). |
