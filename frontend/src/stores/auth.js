import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api/axios'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
  const token = ref(localStorage.getItem('token') || null)

  const isLoggedIn = computed(() => !!token.value)
  const isSuperAdmin = computed(() => user.value?.role === 'superadmin')
  const isProjectManager = computed(() => user.value?.role === 'project_manager')
  const canManageUsers = computed(() => isSuperAdmin.value)
  const canManageEvents = computed(() => isSuperAdmin.value || isProjectManager.value)
  const canManageTasks = computed(() => isSuperAdmin.value || isProjectManager.value)

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
    }
  }

  return { user, token, isLoggedIn, isSuperAdmin, isProjectManager, canManageUsers, canManageEvents, canManageTasks, login, logout }
})
