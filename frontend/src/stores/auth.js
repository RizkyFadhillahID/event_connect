import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api/axios'
import { resetEcho } from './chat'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
  const token = ref(localStorage.getItem('token') || null)

  const isLoggedIn = computed(() => !!token.value)
  const isSuperAdmin = computed(() => user.value?.role === 'superadmin')
  const isProjectManager = computed(() => user.value?.role === 'project_manager')
  const canManageUsers = computed(() => isSuperAdmin.value)
  const canManageEvents = computed(() => isSuperAdmin.value || isProjectManager.value)
  const canManageTasks = computed(() => isSuperAdmin.value || isProjectManager.value)
  const organization = computed(() => user.value?.organization || null)
  const organizationName = computed(() => organization.value?.name || 'EventConnect')
  const organizationLogo = computed(() => organization.value?.logo || null)

  async function login(email, password) {
    const res = await api.post('/login', { email, password })
    token.value = res.data.token
    user.value = res.data.user
    localStorage.setItem('token', token.value)
    localStorage.setItem('user', JSON.stringify(user.value))
  }

  async function logout() {
    try {
      await api.post('/logout')
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      resetEcho()
    }
  }

  function updateCurrentUser(updatedUser) {
    user.value = updatedUser
    localStorage.setItem('user', JSON.stringify(updatedUser))
  }

  return { user, token, isLoggedIn, isSuperAdmin, isProjectManager, canManageUsers, canManageEvents, canManageTasks, organization, organizationName, organizationLogo, login, logout, updateCurrentUser }
})
