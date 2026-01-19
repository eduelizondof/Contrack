import axios from 'axios'

/**
 * Get CSRF cookie from Laravel
 * Call this before making POST/PUT/DELETE requests in SPA mode
 */
export async function getCsrfToken() {
  try {
    // Usar axios directamente para obtener el CSRF cookie sin el prefijo /api
    const baseURL = import.meta.env.VITE_API_BASE_URL?.replace('/api', '') || 'http://localhost:8000'
    
    await axios.get(`${baseURL}/sanctum/csrf-cookie`, {
      withCredentials: true,
      headers: {
        'Accept': 'application/json',
      },
    })
    return true
  } catch (error) {
    console.error('Failed to get CSRF token:', error)
    return false
  }
}
