import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/LoginView.vue'),
    meta: { guest: true },
  },
  {
    path: '/',
    component: () => import('../layouts/DashboardLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('../views/DashboardView.vue'),
      },
      {
        path: 'users',
        name: 'Users',
        component: () => import('../views/UsersView.vue'),
        meta: { role: 'superadmin' },
      },
      {
        path: 'events',
        name: 'Events',
        component: () => import('../views/EventsView.vue'),
      },
      {
        path: 'tasks',
        name: 'Tasks',
        component: () => import('../views/TasksView.vue'),
      },
      {
        path: 'rundown',
        name: 'Rundown',
        component: () => import('../views/RundownView.vue'),
      },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    return next('/login')
  }
  if (to.meta.guest && auth.isLoggedIn) {
    return next('/')
  }
  if (to.meta.role === 'superadmin' && !auth.isSuperAdmin) {
    return next('/')
  }
  next()
})

export default router
