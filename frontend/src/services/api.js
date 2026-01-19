import axios from 'axios'
import router from '@/router'

// Create axios instance with base configuration
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true, // Important for Sanctum SPA authentication
})

/**
 * Limpia las cookies de Sanctum (XSRF-TOKEN y sesión de Laravel)
 */
function clearSanctumCookies() {
  const domain = window.location.hostname
  const cookiesToClear = ['XSRF-TOKEN']
  
  cookiesToClear.forEach(cookieName => {
    document.cookie = `${cookieName}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`
    if (domain === 'localhost' || domain === '127.0.0.1') {
      document.cookie = `${cookieName}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=${domain};`
    }
  })
  
  // Limpiar localStorage de tokens
  localStorage.removeItem('auth_token')
}

// Request interceptor - Add CSRF token from cookie and auth token if available
api.interceptors.request.use(
  (config) => {
    // Obtener el token CSRF de la cookie XSRF-TOKEN
    const csrfToken = document.cookie
      .split('; ')
      .find(row => row.startsWith('XSRF-TOKEN='))
      ?.split('=')[1]
    
    if (csrfToken) {
      config.headers['X-XSRF-TOKEN'] = decodeURIComponent(csrfToken)
    }
    
    // Si hay token de autenticación, agregarlo
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    
    // Si es FormData, no establecer Content-Type (axios lo hace automáticamente)
    if (config.data instanceof FormData) {
      delete config.headers['Content-Type']
    }
    
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor - Handle errors globally
api.interceptors.response.use(
  (response) => {
    return response
  },
  async (error) => {
    if (error.response) {
      switch (error.response.status) {
        case 401:
          // No autenticado - limpiar cookies y redirigir
          clearSanctumCookies()
          if (router.currentRoute.value.name !== 'login') {
            router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } })
          }
          break
        case 419:
          // CSRF Token Mismatch
          console.warn('CSRF Token Mismatch detectado. Limpiando cookies...')
          clearSanctumCookies()
          if (router.currentRoute.value.name !== 'login') {
            router.push({ name: 'login', query: { error: 'csrf_mismatch' } })
          }
          break
        case 403:
          // No autorizado
          break
        case 422:
          // Errores de validación - no limpiar cookies
          break
      }
    } else if (error.request) {
      // Error de red
      console.error('Error de red detectado:', error.request)
    }
    
    return Promise.reject(error)
  }
)

export default api
