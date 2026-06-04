import axios from 'axios'

const instance = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/backoffice',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

instance.interceptors.request.use((config) => {
  const token = localStorage.getItem('platform_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
}, (error) => {
  return Promise.reject(error)
})

instance.interceptors.response.use((response) => {
  return response
}, (error) => {
  if (error.response && error.response.status === 401) {
    localStorage.removeItem('platform_token')
    localStorage.removeItem('platform_admin')
    // Redirect only if not already on the login page
    if (!window.location.pathname.endsWith('/login')) {
      window.location.href = '/login'
    }
  }
  return Promise.reject(error)
})

export default instance
