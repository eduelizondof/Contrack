import { ref, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const MIN_DISPLAY_TIME = 1200 // Tiempo mínimo de visualización en ms
const MAX_WAIT_TIME = 10000 // Tiempo máximo de espera (10 segundos) - fallback
const POLLING_INTERVAL = 50 // Intervalo para verificar estado de carga (ms)

/**
 * Composable para manejar la lógica del loader de página inicial
 * @returns {Object} Objetos y funciones reactivas para controlar el loader
 */
export function usePageLoader() {
  const router = useRouter()
  const authStore = useAuthStore()
  
  const showLoader = ref(true)
  const loaderRef = ref(null)
  
  let hideLoaderTimer = null
  let maxWaitTimer = null
  const startTime = Date.now()

  /**
   * Verifica si todo está listo para ocultar el loader
   * Espera a que el router, stores y DOM estén completamente cargados
   * @returns {Promise<boolean>}
   */
  async function checkIfReady() {
    // Esperar a que el router esté listo
    await router.isReady()
    
    // Esperar un tick para que el DOM se actualice
    await nextTick()
    
    // Si la ruta requiere autenticación, esperar a que auth termine de cargar
    const currentRoute = router.currentRoute.value
    if (currentRoute.meta?.requiereAuth) {
      // Esperar a que auth termine de inicializarse si está cargando
      while (authStore.cargando) {
        await new Promise(resolve => setTimeout(resolve, POLLING_INTERVAL))
      }
      // Esperar un tick adicional después de que auth termine
      await nextTick()
    }
    
    // Esperar a que el DOM esté completamente renderizado
    await nextTick()
    
    return true
  }

  /**
   * Oculta el loader después de verificar que todo está listo
   * Respeta el tiempo mínimo de visualización y tiene un timeout máximo
   */
  async function hideLoader() {
    // Cancelar cualquier timer previo
    if (hideLoaderTimer) clearTimeout(hideLoaderTimer)
    if (maxWaitTimer) clearTimeout(maxWaitTimer)
    
    try {
      // Esperar a que todo esté realmente listo
      await checkIfReady()
      
      // Calcular tiempo transcurrido desde el inicio
      const elapsed = Date.now() - startTime
      const remainingTime = Math.max(0, MIN_DISPLAY_TIME - elapsed)
      
      hideLoaderTimer = setTimeout(() => {
        if (loaderRef.value && showLoader.value) {
          loaderRef.value.hide()
          // Ocultar después de la animación de fade (1s)
          setTimeout(() => {
            showLoader.value = false
          }, 1000)
        }
      }, remainingTime)
    } catch (error) {
      console.error('Error al verificar si la app está lista:', error)
      // En caso de error, ocultar después del tiempo mínimo
      const elapsed = Date.now() - startTime
      const remainingTime = Math.max(0, MIN_DISPLAY_TIME - elapsed)
      hideLoaderTimer = setTimeout(() => {
        if (loaderRef.value && showLoader.value) {
          loaderRef.value.hide()
          setTimeout(() => {
            showLoader.value = false
          }, 1000)
        }
      }, remainingTime)
    }
  }

  /**
   * Inicializa el loader y configura los listeners necesarios
   * Debe llamarse en onMounted del componente
   */
  function initializeLoader() {
    // Timer de seguridad: ocultar después de MAX_WAIT_TIME como máximo
    maxWaitTimer = setTimeout(() => {
      if (showLoader.value) {
        console.warn('Loader visible por demasiado tiempo, forzando ocultación')
        hideLoader()
      }
    }, MAX_WAIT_TIME)
    
    // Intentar ocultar cuando el DOM esté listo
    if (document.readyState === 'complete') {
      hideLoader()
    } else {
      window.addEventListener('load', hideLoader)
      // También intentar después de un pequeño delay para asegurar que Vue esté montado
      setTimeout(() => {
        hideLoader()
      }, 100)
    }
  }

  /**
   * Limpia los timers y listeners
   * Debe llamarse en onBeforeUnmount del componente
   */
  function cleanupLoader() {
    if (hideLoaderTimer) clearTimeout(hideLoaderTimer)
    if (maxWaitTimer) clearTimeout(maxWaitTimer)
    window.removeEventListener('load', hideLoader)
  }

  return {
    showLoader,
    loaderRef,
    initializeLoader,
    cleanupLoader,
  }
}