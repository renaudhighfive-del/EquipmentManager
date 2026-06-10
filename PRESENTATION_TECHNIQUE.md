# Guide des Dépendances - EquipmentManager

Ce document récapitule les technologies et bibliothèques utilisées dans le projet pour ta présentation.

## 🚀 Frontend (Vue.js 3)

Le frontend est construit avec **Vue.js 3** (Composition API) et **Vite** pour un développement ultra-rapide.

### Dépendances de production
| Bibliothèque | Rôle et Utilité |
| :--- | :--- |
| **Vue.js 3** | Le framework principal utilisé pour construire l'interface utilisateur réactive. |
| **Pinia** | Le magasin d'état (store) officiel pour Vue. Il gère les données partagées (auth, équipements, pannes) de manière centralisée. |
| **Vue Router** | Gère la navigation entre les différentes vues (Admin, Gestionnaire, Agent) et la protection des routes par rôle. |
| **Axios** | Client HTTP utilisé pour communiquer avec l'API Laravel (requêtes GET, POST, PATCH, DELETE). |
| **PrimeVue** | Bibliothèque de composants UI. Nous l'utilisons principalement pour son système de **Toasts** (notifications élégantes). |
| **Lucide Vue Next** | Une collection d'icônes modernes et légères utilisées dans toute l'application. |
| **Chart.js & Vue-Chartjs** | Utilisé pour générer les graphiques dynamiques du tableau de bord (évolution des affectations, répartition par catégorie). |
| **Tailwind CSS** | Framework CSS utilitaire utilisé pour tout le design et la mise en page (système de grille, espacements, couleurs). |
| **Clsx & Tailwind Merge** | Petits utilitaires pour gérer proprement les classes CSS conditionnelles. |

---

## ⚙️ Backend (Laravel 13)

Le backend est une API REST robuste construite avec **Laravel**.

### Dépendances principales
| Bibliothèque | Rôle et Utilité |
| :--- | :--- |
| **Laravel Framework** | Le moteur principal gérant les routes, les contrôleurs, et la logique métier. |
| **Laravel Sanctum** | Gère l'authentification sécurisée des utilisateurs via des jetons (tokens) API. |
| **Eloquent ORM** | Le système de base de données de Laravel qui permet de manipuler les données (Agents, Équipements, Affectations) comme des objets PHP. |
| **Carbon** | Bibliothèque puissante pour la manipulation des dates (utilisée pour les calculs du tableau de bord et les restrictions d'affectation). |

---

## 🛠️ Outils de Développement
- **Vite** : Serveur de développement et bundler pour le frontend.
- **PostCSS & Autoprefixer** : Pour garantir que le CSS fonctionne sur tous les navigateurs.
- **Artisan** : Interface en ligne de commande de Laravel pour les migrations de base de données et la génération de code.

## 📁 Architecture des Données
- **Migrations** : Définissent la structure de la base de données de manière versionnée.
- **Observers** : Utilisés pour automatiser des actions (ex: créer un mouvement dans le journal dès qu'une panne est déclarée).
- **Storage** : Système de fichiers configuré pour stocker les photos de remise et de retour des équipements.
