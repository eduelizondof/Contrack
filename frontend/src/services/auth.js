import api from './api'
import { getCsrfToken } from './csrf'

/**
 * Authentication service
 * Example usage for SPA authentication with Laravel Sanctum
 */

export const authService = {
  /**
   * Login user
   * @param {object} credenciales - { email, password, remember }
   */
  async login(credenciales) {
    // Get CSRF cookie first (required for SPA)
    await getCsrfToken()
    
    try {
      const response = await api.post('/login', {
        email: credenciales.email,
        password: credenciales.password,
        remember: credenciales.remember || false,
      })
      
      // If using token-based auth, store the token
      if (response.data.token) {
        localStorage.setItem('auth_token', response.data.token)
      }
      
      return response.data
    } catch (error) {
      throw error
    }
  },

  /**
   * Logout user
   */
  async logout() {
    try {
      await api.post('/logout')
      localStorage.removeItem('auth_token')
    } catch (error) {
      // Even if logout fails, clear local storage
      localStorage.removeItem('auth_token')
      throw error
    }
  },

  /**
   * Get current authenticated user
   */
  async getUser() {
    try {
      const response = await api.get('/user')
      // La respuesta puede ser { user: ... } o directamente el user
      return response.data.user || response.data
    } catch (error) {
      throw error
    }
  },

  /**
   * Register new user
   * @param {object} userData
   */
  async register(userData) {
    await getCsrfToken()
    
    try {
      const response = await api.post('/register', userData)
      
      if (response.data.token) {
        localStorage.setItem('auth_token', response.data.token)
      }
      
      return response.data
    } catch (error) {
      throw error
    }
  },
}
