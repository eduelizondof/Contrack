import { ref } from 'vue'
import api from '@/services/api'

/**
 * Composable para manejar llamadas API con estados
 * Útil para múltiples componentes que hacen llamadas API
 * 
 * @example
 * import { useApi } from '@/composables/useApi'
 * 
 * const { datos, cargando, error, ejecutar } = useApi()
 * 
 * async function cargarUsuarios() {
 *   await ejecutar(() => api.get('/usuarios'))
 * }
 */
export function useApi() {
  const datos = ref(null)
  const cargando = ref(false)
  const error = ref(null)

  async function ejecutar(peticion) {
    cargando.value = true
    error.value = null
    try {
      const response = await peticion()
      datos.value = response.data
      return response.data
    } catch (err) {
      error.value = err.message || 'Error desconocido'
      throw err
    } finally {
      cargando.value = false
    }
  }

  return { datos, cargando, error, ejecutar }
}
