import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api/axios'

export const useAuthStore = defineStore('platform_auth', () => {
  const admin = ref(JSON.parse(localStorage.getItem('platform_admin') || 'null'))
  const token = ref(localStorage.getItem('platform_token') || null)

  const isLoggedIn = computed(() => !!token.value)
  const isOwner = computed(() => admin.value?.role === 'owner')
  const isAdmin = computed(() => admin.value?.role === 'admin' || admin.value?.role === 'owner')
  const isSupport = computed(() => admin.value?.role === 'support')

  async function login(email, password) {
    const res = await api.post('/login', { email, password })
    token.value = res.data.token
    admin.value = res.data.admin
    localStorage.setItem('platform_token', token.value)
    localStorage.setItem('platform_admin', JSON.stringify(admin.value))
  }

  async function logout() {
    try {
      await api.post('/logout')
    } finally {
      token.value = null
      admin.value = null
      localStorage.removeItem('platform_token')
      localStorage.removeItem('platform_admin')
    }
  }

  return { admin, token, isLoggedIn, isOwner, isAdmin, isSupport, login, logout }
})
