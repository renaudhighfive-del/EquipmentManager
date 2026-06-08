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
        { path: 'rapports',       name: 'admin-rapports',       component: () => import('../views/admin/RapportsView.vue') },
        { path: 'administration', name: 'admin-administration', component: () => import('../views/admin/AdministrationView.vue') },
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
        { path: 'pannes',       name: 'gestionnaire-pannes',       component: () => import('../views/admin/PannesView.vue') },
        { path: 'maintenances', name: 'gestionnaire-maintenances', component: () => import('../views/admin/MaintenancesView.vue') },
        { path: 'sinistres',    name: 'gestionnaire-sinistres',    component: () => import('../views/admin/SinistresView.vue') },
        { path: 'rapports',     name: 'gestionnaire-rapports',     component: () => import('../views/admin/RapportsView.vue') },
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
        { path: 'pannes',      name: 'agent-pannes',      component: () => import('../views/agent/PannesView.vue') },
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

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.auth && !authStore.isAuthenticated) {
    return next('/login')
  }

  if (to.meta.guest && authStore.isAuthenticated) {
    return next(`/${authStore.user.role}/dashboard`)
  }

  if (to.meta.role && authStore.user?.role !== to.meta.role) {
    return next(`/${authStore.user?.role}/dashboard`)
  }

  next()
})

export default router
