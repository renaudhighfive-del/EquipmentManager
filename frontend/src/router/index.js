import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // ─── Auth ─────────────────────────────────────────────────
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/auth/LoginView.vue'),
      meta: { guest: true }
    },
    {
      path: '/otp',
      name: 'otp',
      component: () => import('../views/auth/OTPView.vue'),
      meta: { guest: true }
    },

    // ─── Admin ────────────────────────────────────────────────
    {
      path: '/admin',
      component: () => import('../layouts/AdminLayout.vue'),
      meta: { auth: true, role: 'admin' },
      children: [
        { path: 'dashboard',      name: 'admin-dashboard',      component: () => import('../views/admin/DashboardView.vue') },
        { path: 'agents',         name: 'admin-agents',         component: () => import('../views/admin/AgentsView.vue') },
        { path: 'equipements',    name: 'admin-equipements',    component: () => import('../views/admin/EquipementsView.vue') },
        { path: 'affectations',   name: 'admin-affectations',   component: () => import('../views/admin/AffectationsView.vue') },
        { path: 'mouvements',     name: 'admin-mouvements',     component: () => import('../views/admin/MouvementsView.vue') },
        { path: 'pannes',         name: 'admin-pannes',         component: () => import('../views/admin/PannesView.vue') },
        { path: 'maintenances',   name: 'admin-maintenances',   component: () => import('../views/admin/MaintenancesView.vue') },
        { path: 'sinistres',      name: 'admin-sinistres',      component: () => import('../views/admin/SinistresView.vue') },
        { path: 'archives',       name: 'admin-archives',       component: () => import('../views/admin/ArchivesView.vue') },
        { path: 'rapports',       name: 'admin-rapports',       component: () => import('../views/admin/RapportsView.vue') },
        { path: 'administration', name: 'admin-administration', component: () => import('../views/admin/AdministrationView.vue') },
        { path: 'profile',       name: 'admin-profile',       component: () => import('../views/shared/ProfileView.vue') },
      ]
    },

    // ─── Gestionnaire ─────────────────────────────────────────
    {
      path: '/gestionnaire',
      component: () => import('../layouts/AdminLayout.vue'),
      meta: { auth: true, role: 'gestionnaire' },
      children: [
        { path: 'dashboard',    name: 'gestionnaire-dashboard',    component: () => import('../views/gestionnaire/DashboardView.vue') },
        // Vues partagées avec admin (réutilisation directe)
        { path: 'agents',       name: 'gestionnaire-agents',       component: () => import('../views/admin/AgentsView.vue') },
        { path: 'equipements',  name: 'gestionnaire-equipements',  component: () => import('../views/admin/EquipementsView.vue') },
        { path: 'affectations', name: 'gestionnaire-affectations', component: () => import('../views/admin/AffectationsView.vue') },
        { path: 'mouvements',   name: 'gestionnaire-mouvements',   component: () => import('../views/admin/MouvementsView.vue') },
        { path: 'pannes',       name: 'gestionnaire-pannes',       component: () => import('../views/admin/PannesView.vue') },
        { path: 'maintenances', name: 'gestionnaire-maintenances', component: () => import('../views/admin/MaintenancesView.vue') },
        { path: 'sinistres',    name: 'gestionnaire-sinistres',    component: () => import('../views/admin/SinistresView.vue') },
        { path: 'rapports',     name: 'gestionnaire-rapports',     component: () => import('../views/admin/RapportsView.vue') },
        { path: 'profile',      name: 'gestionnaire-profile',      component: () => import('../views/shared/ProfileView.vue') },
      ]
    },

    // ─── Agent ────────────────────────────────────────────────
    {
      path: '/agent',
      component: () => import('../layouts/AdminLayout.vue'),
      meta: { auth: true, role: 'agent' },
      children: [
        { path: 'dashboard',   name: 'agent-dashboard',   component: () => import('../views/agent/DashboardView.vue') },
        { path: 'equipements', name: 'agent-equipements', component: () => import('../views/agent/EquipementsView.vue') },
        { path: 'incidents',   name: 'agent-incidents',   component: () => import('../views/agent/IncidentsView.vue') },
        { path: 'profile',     name: 'agent-profile',     component: () => import('../views/shared/ProfileView.vue') },
        { path: 'mouvements',  name: 'agent-mouvements',  component: () => import('../views/admin/MouvementsView.vue') },
      ]
    },

    // ─── Redirect racine ──────────────────────────────────────
    {
      path: '/',
      redirect: () => {
        const authStore = useAuthStore()
        if (!authStore.isAuthenticated) return '/login'
        return `/${authStore.user.role}/dashboard`
      }
    },
    // Catch-all
    {
      path: '/:pathMatch(.*)*',
      redirect: '/'
    }
  ]
})

router.beforeEach(async (to, from) => {
  const authStore = useAuthStore()

  //  Si l'utilisateur n'a pas encore été chargé ET qu'on n'est PAS sur la page login, le faire d'abord !
  if (!authStore.hasFetchedUser && to.name !== 'login' && to.name !== 'otp') {
    try {
      await authStore.fetchUser()
    } catch (error) {
      // Pas connecté, c'est okay
    }
  }

  // Maintenant on peut vérifier normalement
  if (to.meta.auth && !authStore.isAuthenticated) {
    return '/login'
  }

  if (to.meta.guest && authStore.isAuthenticated) {
    return `/${authStore.user.role}/dashboard`
  }

  if (to.meta.role && authStore.user?.role !== to.meta.role) {
    return `/${authStore.user?.role}/dashboard`
  }
})

export default router
