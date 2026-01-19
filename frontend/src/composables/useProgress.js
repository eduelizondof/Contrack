import NProgress from 'nprogress'

/**
 * Composable para manejar la barra de progreso
 * Útil para mostrar progreso en operaciones asíncronas largas
 * 
 * @example
 * // Uso básico en un componente
 * import { useProgress } from '@/composables/useProgress'
 * 
 * const { start, done } = useProgress()
 * 
 * async function cargarDatos() {
 *   start()
 *   try {
 *     const datos = await api.get('/endpoint-lento')
 *     // ... procesar datos
 *   } finally {
 *     done()
 *   }
 * }
 * 
 * @example
 * // Uso con progreso manual
 * const { start, set, inc, done } = useProgress()
 * 
 * async function cargarConProgreso() {
 *   start(0.1)
 *   await paso1()
 *   set(0.3)
 *   await paso2()
 *   inc(0.2) // incrementa a 0.5
 *   await paso3()
 *   done()
 * }
 */
export function useProgress() {
  /**
   * Inicia la barra de progreso
   * @param {number} amount - Porcentaje inicial (0-1), por defecto 0
   */
  function start(amount = 0) {
    NProgress.start()
    if (amount > 0) {
      NProgress.set(amount)
    }
  }

  /**
   * Establece el progreso manualmente
   * @param {number} amount - Porcentaje (0-1)
   */
  function set(amount) {
    NProgress.set(amount)
  }

  /**
   * Incrementa el progreso
   * @param {number} amount - Cantidad a incrementar (0-1), por defecto 0.1
   */
  function inc(amount = 0.1) {
    NProgress.inc(amount)
  }

  /**
   * Completa y oculta la barra de progreso
   */
  function done() {
    NProgress.done()
  }

  /**
   * Remueve la barra de progreso sin animación
   */
  function remove() {
    NProgress.remove()
  }

  return {
    start,
    set,
    inc,
    done,
    remove
  }
}
