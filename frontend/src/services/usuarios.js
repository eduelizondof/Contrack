import api from './api'

/**
 * Servicio para gestión de usuarios
 * Conecta con el backend Laravel mediante axios
 */

/**
 * Extraer mensaje de error amigable en español desde un error de axios
 * @param {Error} error - Error de axios
 * @returns {Object} - Objeto con { titulo, mensaje }
 */
function extraerMensajeError(error) {
  let mensaje = 'Ocurrió un error inesperado.'
  let titulo = 'Error'
  
  if (error.response) {
    const status = error.response.status
    
    switch (status) {
      case 403:
        titulo = 'Acceso Denegado'
        mensaje = error.response.data?.mensaje || error.response.data?.message || 
          'No tienes permisos para realizar esta acción. Contacta al administrador del sistema.'
        break
        
      case 401:
        titulo = 'Sesión Expirada'
        mensaje = 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.'
        break
        
      case 422:
        // Errores de validación - el mensaje se maneja en el componente
        titulo = 'Error de Validación'
        const mensajesValidacion = error.response.data?.errors 
          ? Object.values(error.response.data.errors).flat()
          : []
        mensaje = mensajesValidacion.length > 0 
          ? mensajesValidacion.join(', ') 
          : (error.response.data?.mensaje || error.response.data?.message || 'Por favor, verifica los datos ingresados.')
        break
        
      case 404:
        titulo = 'No Encontrado'
        mensaje = error.response.data?.mensaje || error.response.data?.message || 
          'El recurso solicitado no fue encontrado.'
        break
        
      case 500:
        titulo = 'Error del Servidor'
        mensaje = 'Ocurrió un error en el servidor. Por favor, intenta nuevamente más tarde.'
        break
        
      default:
        if (error.response.data?.mensaje) {
          mensaje = error.response.data.mensaje
        } else if (error.response.data?.message) {
          mensaje = error.response.data.message
        }
    }
  } else if (error.request) {
    titulo = 'Error de Conexión'
    mensaje = 'No se pudo conectar con el servidor. Verifica tu conexión a internet e intenta nuevamente.'
  } else if (error.message) {
    mensaje = error.message
  }
  
  return { titulo, mensaje }
}

/**
 * Obtener lista de usuarios
 * @param {Object} params - Parámetros de consulta
 * @param {number} params.pagina - Número de página (default: 1)
 * @param {number} params.por_pagina - Resultados por página (default: 10)
 * @param {string} params.buscar - Texto de búsqueda
 * @param {number|string} params.rol - ID del rol para filtrar
 * @param {boolean} params.solo_activos - Si es true, solo muestra usuarios activos
 * @param {boolean} params.paginar - Si es false, devuelve todos los resultados sin paginar
 * @returns {Promise<Object>} - Datos de usuarios y meta de paginación
 */
export async function obtenerUsuarios(params = {}) {
  const queryParams = {}

  if (params.pagina) {
    queryParams.page = params.pagina
  }
  if (params.por_pagina) {
    queryParams.por_pagina = params.por_pagina
  }
  if (params.buscar) {
    queryParams.buscar = params.buscar
  }
  if (params.rol) {
    queryParams.rol = params.rol
  }
  if (params.solo_activos !== undefined && params.solo_activos) {
    queryParams.solo_activos = 'true'
  }
  if (params.paginar === false) {
    queryParams.paginar = 'false'
  }

  const response = await api.get('/configuracion/usuarios', { params: queryParams })
  return response.data
}

/**
 * Obtener un usuario por ID
 * @param {number|string} id - ID del usuario
 * @returns {Promise<Object>} - Datos del usuario
 */
export async function obtenerUsuario(id) {
  const response = await api.get(`/configuracion/usuarios/${id}`)
  return response.data.datos
}

/**
 * Crear nuevo usuario
 * @param {Object} datos - Datos del usuario
 * @param {string} datos.name - Nombre del usuario
 * @param {string} datos.email - Email del usuario
 * @param {string} datos.password - Contraseña
 * @param {string} datos.password_confirmation - Confirmación de contraseña
 * @param {number} datos.rol_id - ID del rol (opcional)
 * @returns {Promise<Object>} - Usuario creado
 * @throws {Error} - Error con información amigable en error.response.data
 */
export async function crearUsuario(datos) {
  try {
    const response = await api.post('/configuracion/usuarios', datos)
    return response.data.datos
  } catch (error) {
    // Agregar información de error amigable al objeto de error
    const errorInfo = extraerMensajeError(error)
    if (error.response) {
      error.response.data = {
        ...error.response.data,
        titulo: errorInfo.titulo,
        mensajeAmigable: errorInfo.mensaje,
      }
    }
    throw error
  }
}

/**
 * Actualizar usuario
 * @param {number|string} id - ID del usuario
 * @param {Object} datos - Datos a actualizar
 * @param {string} datos.name - Nombre del usuario
 * @param {string} datos.email - Email del usuario
 * @returns {Promise<Object>} - Usuario actualizado
 * @throws {Error} - Error con información amigable en error.response.data
 */
export async function actualizarUsuario(id, datos) {
  try {
    const response = await api.put(`/configuracion/usuarios/${id}`, datos)
    return response.data.datos
  } catch (error) {
    // Agregar información de error amigable al objeto de error
    const errorInfo = extraerMensajeError(error)
    if (error.response) {
      error.response.data = {
        ...error.response.data,
        titulo: errorInfo.titulo,
        mensajeAmigable: errorInfo.mensaje,
      }
    }
    throw error
  }
}

/**
 * Eliminar usuario
 * @param {number|string} id - ID del usuario
 * @returns {Promise<Object>} - Confirmación de eliminación
 * @throws {Error} - Error con información amigable en error.response.data
 */
export async function eliminarUsuario(id) {
  try {
    const response = await api.delete(`/configuracion/usuarios/${id}`)
    return response.data
  } catch (error) {
    // Agregar información de error amigable al objeto de error
    const errorInfo = extraerMensajeError(error)
    if (error.response) {
      error.response.data = {
        ...error.response.data,
        titulo: errorInfo.titulo,
        mensajeAmigable: errorInfo.mensaje,
      }
    }
    throw error
  }
}

/**
 * Obtener roles disponibles
 * @returns {Promise<Array>} - Lista de roles
 */
export async function obtenerRoles() {
  const response = await api.get('/configuracion/roles')
  return response.data.datos
}

/**
 * Actualizar rol de usuario
 * @param {number|string} usuarioId - ID del usuario
 * @param {number|string} rolId - ID del nuevo rol
 * @returns {Promise<Object>} - Usuario actualizado
 */
export async function actualizarRolUsuario(usuarioId, rolId) {
  const response = await api.put(`/configuracion/usuarios/${usuarioId}/rol`, { rol_id: rolId })
  return response.data.datos
}

/**
 * Actualizar horario de usuario
 * @param {number|string} usuarioId - ID del usuario
 * @param {Array} horarios - Array de horarios
 * @returns {Promise<Object>} - Confirmación de actualización
 */
export async function actualizarHorarioUsuario(usuarioId, horarios) {
  const response = await api.put(`/configuracion/usuarios/${usuarioId}/horario`, { horarios })
  return response.data
}

/**
 * Restablecer contraseña de usuario
 * @param {number|string} usuarioId - ID del usuario
 * @param {Object} datos - Datos de la nueva contraseña
 * @param {string} datos.password - Nueva contraseña
 * @param {string} datos.password_confirmation - Confirmación de contraseña
 * @returns {Promise<Object>} - Confirmación de actualización
 */
export async function restablecerPassword(usuarioId, datos) {
  const response = await api.put(`/configuracion/usuarios/${usuarioId}/password`, datos)
  return response.data
}

// Exportar funciones
export default {
  obtenerUsuarios,
  obtenerUsuario,
  crearUsuario,
  actualizarUsuario,
  eliminarUsuario,
  obtenerRoles,
  actualizarRolUsuario,
  actualizarHorarioUsuario,
  restablecerPassword,
}
