import { useAlerta } from './useAlerta'

/**
 * Composable wrapper para useAlerta con sintaxis simplificada tipo SweetAlert
 * Permite usar success('mensaje') y error('mensaje') directamente con strings
 * 
 * @returns {Object} Métodos para mostrar alertas de éxito y error
 */
export function useSweetAlert() {
  const { success: successAlerta, error: errorAlerta } = useAlerta()

  /**
   * Mostrar alerta de éxito
   * @param {string|Object} mensaje - Mensaje de éxito o objeto con opciones
   * @param {string} [mensaje.titulo] - Título (opcional)
   * @param {string} [mensaje.mensaje] - Mensaje
   * @param {number} [mensaje.duracion] - Duración en ms (default: 3000)
   */
  function success(mensaje) {
    if (typeof mensaje === 'string') {
      successAlerta({
        titulo: 'Éxito',
        mensaje: mensaje,
        duracion: 3000
      })
    } else {
      successAlerta({
        titulo: mensaje.titulo || 'Éxito',
        mensaje: mensaje.mensaje || '',
        duracion: mensaje.duracion ?? 3000
      })
    }
  }

  /**
   * Mostrar alerta de error
   * @param {string|Object} mensaje - Mensaje de error o objeto con opciones
   * @param {string} [mensaje.titulo] - Título (opcional)
   * @param {string} [mensaje.mensaje] - Mensaje
   * @param {number} [mensaje.duracion] - Duración en ms (default: 4000)
   */
  function error(mensaje) {
    if (typeof mensaje === 'string') {
      errorAlerta({
        titulo: 'Error',
        mensaje: mensaje,
        duracion: 4000
      })
    } else {
      errorAlerta({
        titulo: mensaje.titulo || 'Error',
        mensaje: mensaje.mensaje || '',
        duracion: mensaje.duracion ?? 4000
      })
    }
  }

  return {
    success,
    error
  }
}
