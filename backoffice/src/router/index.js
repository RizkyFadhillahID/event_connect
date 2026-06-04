import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import DashboardView from '../views/DashboardView.vue'
import LoginView from '../views/LoginView.vue'
import OrganizationsView from '../views/OrganizationsView.vue'
import AdminsView from '../views/AdminsView.vue'
import LandingEditorView from '../views/LandingEditorView.vue'
import ContactMessagesView from '../views/ContactMessagesView.vue'
import BackofficeLayout from '../layouts/BackofficeLayout.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'Login',
      component: LoginView,
      meta: { guest: true }
    },
    {
      path: '/',
      component: BackofficeLayout,
      meta: { auth: true },
      children: [
        {
          path: '',
          name: 'Dashboard',
          component: DashboardView
        },
        {
          path: 'organizations',
          name: 'Organizations',
          component: OrganizationsView
        },
        {
          path: 'admins',
          name: 'Admins',
          component: AdminsView
        },
        {
          path: 'landing-editor',
          name: 'LandingEditor',
          component: LandingEditorView
        },
        {
          path: 'contact-messages',
          name: 'ContactMessages',
          component: ContactMessagesView
        },
        {
          path: 'contact-messages/:id',
          name: 'ContactMessageDetail',
          component: () => import('../views/ContactMessageDetailView.vue')
        }
      ]
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/'
    }
  ]
})

router.beforeEach((to, from, next) => {
  const auth = useAuthStore()

  if (to.meta.auth && !auth.isLoggedIn) {
    next('/login')
  } else if (to.meta.guest && auth.isLoggedIn) {
    next('/')
  } else {
    next()
  }
})

export default router
