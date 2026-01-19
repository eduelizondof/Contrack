import { ref } from 'vue'

// Estado global reactivo
const estado = ref({
  visible: false,
  tipo: null, // 'success', 'error', 'warning', 'info', 'confirmacion', 'modal'
  titulo: '',
  mensaje: '',
  duracion: null, // en ms, null = sin auto-cierre
  mostrarIcono: true,
  mostrarAcciones: false,
  textoConfirmar: 'Confirmar',
  textoCancelar: 'Cancelar',
  peligro: false, // para botón de confirmar en rojo
  resolver: null, // función para resolver la Promise en confirmaciones
})

/**
 * Composable para mostrar alertas globales
 * @returns {Object} Métodos para mostrar diferentes tipos de alertas
 */
export function useAlerta() {
  /**
   * Función base para mostrar una alerta
   * @param {Object} opciones - Opciones de la alerta
   * @param {string} opciones.tipo - Tipo de alerta
   * @param {string} opciones.titulo - Título de la alerta
   * @param {string} opciones.mensaje - Mensaje de la alerta
   * @param {number|null} opciones.duracion - Duración en ms (null = sin auto-cierre)
   * @param {boolean} opciones.mostrarIcono - Mostrar icono (default: true)
   * @param {boolean} opciones.mostrarAcciones - Mostrar botones de acción (default: false)
   * @param {string} opciones.textoConfirmar - Texto del botón confirmar
   * @param {string} opciones.textoCancelar - Texto del botón cancelar
   * @param {boolean} opciones.peligro - Botón de confirmar en rojo (default: false)
   * @returns {Promise|void} Promise si es confirmación, void si es notificación
   */
  function mostrar(opciones) {
    // Cerrar alerta anterior si existe
    if (estado.value.visible && estado.value.resolver) {
      estado.value.resolver(false)
    }

    estado.value = {
      visible: true,
      tipo: opciones.tipo || 'modal',
      titulo: opciones.titulo || '',
      mensaje: opciones.mensaje || '',
      duracion: opciones.duracion ?? null,
      mostrarIcono: opciones.mostrarIcono !== false,
      mostrarAcciones: opciones.mostrarAcciones || false,
      textoConfirmar: opciones.textoConfirmar || 'Confirmar',
      textoCancelar: opciones.textoCancelar || 'Cancelar',
      peligro: opciones.peligro || false,
      resolver: null,
    }

    // Si es confirmación, retornar Promise
    if (opciones.tipo === 'confirmacion' || opciones.mostrarAcciones) {
      return new Promise((resolve) => {
        estado.value.resolver = resolve
      })
    }

    // Si tiene duración, cerrar automáticamente
    if (estado.value.duracion && estado.value.duracion > 0) {
      setTimeout(() => {
        cerrar()
      }, estado.value.duracion)
    }
  }

  /**
   * Cerrar la alerta actual
   */
  function cerrar() {
    if (estado.value.resolver) {
      estado.value.resolver(false)
    }
    estado.value.visible = false
    estado.value.resolver = null
  }

  /**
   * Confirmar acción (para alertas de confirmación)
   * Función interna llamada cuando el usuario hace clic en confirmar
   */
  function aceptarConfirmacion() {
    if (estado.value.resolver) {
      estado.value.resolver(true)
    }
    estado.value.visible = false
    estado.value.resolver = null
  }

  /**
   * Mostrar alerta de éxito
   * @param {Object} opciones - Opciones de la alerta
   * @param {string} opciones.titulo - Título
   * @param {string} opciones.mensaje - Mensaje
   * @param {number} opciones.duracion - Duración en ms (default: 3000)
   */
  function success(opciones) {
    mostrar({
      tipo: 'success',
      titulo: opciones.titulo || 'Éxito',
      mensaje: opciones.mensaje || '',
      duracion: opciones.duracion ?? 3000,
      mostrarIcono: true,
      mostrarAcciones: false,
    })
  }

  /**
   * Mostrar alerta de error
   * @param {Object} opciones - Opciones de la alerta
   * @param {string} opciones.titulo - Título
   * @param {string} opciones.mensaje - Mensaje
   * @param {number} opciones.duracion - Duración en ms (default: 4000)
   */
  function error(opciones) {
    mostrar({
      tipo: 'error',
      titulo: opciones.titulo || 'Error',
      mensaje: opciones.mensaje || '',
      duracion: opciones.duracion ?? 4000,
      mostrarIcono: true,
      mostrarAcciones: false,
    })
  }

  /**
   * Mostrar alerta de advertencia
   * @param {Object} opciones - Opciones de la alerta
   * @param {string} opciones.titulo - Título
   * @param {string} opciones.mensaje - Mensaje
   * @param {number} opciones.duracion - Duración en ms (default: 4000)
   */
  function warning(opciones) {
    mostrar({
      tipo: 'warning',
      titulo: opciones.titulo || 'Advertencia',
      mensaje: opciones.mensaje || '',
      duracion: opciones.duracion ?? 4000,
      mostrarIcono: true,
      mostrarAcciones: false,
    })
  }

  /**
   * Mostrar alerta informativa
   * @param {Object} opciones - Opciones de la alerta
   * @param {string} opciones.titulo - Título
   * @param {string} opciones.mensaje - Mensaje
   * @param {number} opciones.duracion - Duración en ms (default: 3000)
   */
  function info(opciones) {
    mostrar({
      tipo: 'info',
      titulo: opciones.titulo || 'Información',
      mensaje: opciones.mensaje || '',
      duracion: opciones.duracion ?? 3000,
      mostrarIcono: true,
      mostrarAcciones: false,
    })
  }

  /**
   * Mostrar alerta de confirmación (retorna Promise)
   * @param {Object} opciones - Opciones de la alerta
   * @param {string} opciones.titulo - Título
   * @param {string} opciones.mensaje - Mensaje
   * @param {string} opciones.tipo - Tipo para el backdrop (default: 'warning')
   * @param {string} opciones.textoConfirmar - Texto del botón confirmar
   * @param {string} opciones.textoCancelar - Texto del botón cancelar
   * @param {boolean} opciones.peligro - Botón de confirmar en rojo
   * @returns {Promise<boolean>} Promise que resuelve true si se confirma, false si se cancela
   */
  function confirmar(opciones) {
    return mostrar({
      tipo: opciones.tipo || 'warning',
      titulo: opciones.titulo || '¿Estás seguro?',
      mensaje: opciones.mensaje || 'Esta acción no se puede deshacer.',
      duracion: null, // Sin auto-cierre
      mostrarIcono: true,
      mostrarAcciones: true,
      textoConfirmar: opciones.textoConfirmar || 'Confirmar',
      textoCancelar: opciones.textoCancelar || 'Cancelar',
      peligro: opciones.peligro || false,
    })
  }

  /**
   * Mostrar modal simple (sin tipo, sin icono, sin timer)
   * @param {Object} opciones - Opciones del modal
   * @param {string} opciones.titulo - Título
   * @param {string} opciones.mensaje - Mensaje
   */
  function modal(opciones) {
    mostrar({
      tipo: 'modal',
      titulo: opciones.titulo || '',
      mensaje: opciones.mensaje || '',
      duracion: null,
      mostrarIcono: false,
      mostrarAcciones: false,
    })
  }

  return {
    estado,
    mostrar,
    cerrar,
    aceptarConfirmacion,
    confirmar, // Método público para mostrar alerta de confirmación
    success,
    error,
    warning,
    info,
    modal,
  }
}
