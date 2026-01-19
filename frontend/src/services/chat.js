import api from './api'
import { getCsrfToken } from './csrf'

/**
 * Servicio para gestión de chat
 * Conecta con el backend Laravel mediante axios
 */

// ==================== CONVERSACIONES ====================

/**
 * Obtener lista de conversaciones
 * @param {boolean} archivadas - Si es true, devuelve solo las archivadas
 * @returns {Promise<Array>} - Lista de conversaciones
 */
export async function obtenerConversaciones(archivadas = false) {
  const params = {}
  // Solo agregar el parámetro si es true, para que Laravel use el default false
  if (archivadas) {
    params.archivadas = '1'
  }
  const response = await api.get('/chat/conversaciones', { params })
  return response.data.datos
}

/**
 * Obtener una conversación por ID
 * @param {number|string} id - ID de la conversación
 * @returns {Promise<Object>} - Datos de la conversación
 */
export async function obtenerConversacion(id) {
  const response = await api.get(`/chat/conversaciones/${id}`)
  return response.data.datos
}

/**
 * Obtener estado ligero del chat (para polling sin carga completa)
 * @returns {Promise<Object>} - Estado con total_no_leidos y timestamp
 */
export async function obtenerEstado() {
  const response = await api.get('/chat/estado')
  return response.data
}

/**
 * Crear nueva conversación
 * @param {Object} datos - Datos de la conversación
 * @param {string} datos.nombre - Nombre de la conversación (opcional)
 * @param {Array<number>} datos.usuarios - IDs de los usuarios participantes
 * @returns {Promise<Object>} - Conversación creada
 */
export async function crearConversacion(datos) {
  await getCsrfToken()
  const response = await api.post('/chat/conversaciones', datos)
  return response.data.datos
}

/**
 * Eliminar conversación
 * @param {number|string} id - ID de la conversación
 * @returns {Promise<Object>} - Confirmación de eliminación
 */
export async function eliminarConversacion(id) {
  await getCsrfToken()
  const response = await api.delete(`/chat/conversaciones/${id}`)
  return response.data
}

/**
 * Archivar conversación
 * @param {number|string} id - ID de la conversación
 * @returns {Promise<Object>} - Confirmación
 */
export async function archivarConversacion(id) {
  await getCsrfToken()
  const response = await api.post(`/chat/conversaciones/${id}/archivar`)
  return response.data
}

/**
 * Desarchivar conversación
 * @param {number|string} id - ID de la conversación
 * @returns {Promise<Object>} - Confirmación
 */
export async function desarchivarConversacion(id) {
  await getCsrfToken()
  const response = await api.post(`/chat/conversaciones/${id}/desarchivar`)
  return response.data
}

/**
 * Salir de una conversación
 * @param {number|string} id - ID de la conversación
 * @returns {Promise<Object>} - Confirmación con flag grupo_eliminado
 */
export async function salirConversacion(id) {
  await getCsrfToken()
  const response = await api.post(`/chat/conversaciones/${id}/salir`)
  return response.data
}

/**
 * Agregar miembro a conversación
 * @param {number|string} conversacionId - ID de la conversación
 * @param {number|string} userId - ID del usuario a agregar
 * @returns {Promise<Object>} - Usuario agregado
 */
export async function agregarMiembro(conversacionId, userId) {
  await getCsrfToken()
  const response = await api.post(`/chat/conversaciones/${conversacionId}/miembros`, {
    user_id: userId
  })
  return response.data.datos
}

/**
 * Remover miembro de conversación
 * @param {number|string} conversacionId - ID de la conversación
 * @param {number|string} userId - ID del usuario a remover
 * @returns {Promise<Object>} - Confirmación
 */
export async function removerMiembro(conversacionId, userId) {
  await getCsrfToken()
  const response = await api.delete(`/chat/conversaciones/${conversacionId}/miembros/${userId}`)
  return response.data
}

/**
 * Actualizar rol de admin
 * @param {number|string} conversacionId - ID de la conversación
 * @param {number|string} userId - ID del usuario
 * @param {boolean} esAdmin - Si es admin o no
 * @returns {Promise<Object>} - Confirmación
 */
export async function actualizarAdmin(conversacionId, userId, esAdmin) {
  await getCsrfToken()
  const response = await api.put(`/chat/conversaciones/${conversacionId}/admin`, {
    user_id: userId,
    es_admin: esAdmin
  })
  return response.data
}

/**
 * Buscar usuarios para agregar a conversación
 * @param {string} query - Texto de búsqueda
 * @returns {Promise<Array>} - Lista de usuarios
 */
export async function buscarUsuarios(query) {
  const response = await api.get('/chat/usuarios/buscar', {
    params: { q: query }
  })
  return response.data.datos
}

// ==================== MENSAJES ====================

/**
 * Obtener mensajes de una conversación
 * @param {number|string} conversacionId - ID de la conversación
 * @param {Object} params - Parámetros opcionales
 * @param {number} params.per_page - Mensajes por página (default: 50)
 * @param {number} params.antes - ID del mensaje para paginación hacia atrás
 * @returns {Promise<Object>} - Lista de mensajes y meta
 */
export async function obtenerMensajes(conversacionId, params = {}) {
  const queryParams = {}
  if (params.per_page) queryParams.per_page = params.per_page
  if (params.antes) queryParams.antes = params.antes

  const response = await api.get(`/chat/conversaciones/${conversacionId}/mensajes`, {
    params: queryParams
  })
  return {
    datos: response.data.datos,
    tiene_mas: response.data.tiene_mas
  }
}

/**
 * Polling para nuevos mensajes
 * @param {number|string} conversacionId - ID de la conversación
 * @param {number} despues - ID del último mensaje recibido
 * @returns {Promise<Object>} - Nuevos mensajes
 */
export async function pollingMensajes(conversacionId, despues) {
  const response = await api.get(`/chat/conversaciones/${conversacionId}/mensajes/polling`, {
    params: { despues }
  })
  return {
    datos: response.data.datos,
    nuevos: response.data.nuevos
  }
}

/**
 * Enviar mensaje
 * @param {number|string} conversacionId - ID de la conversación
 * @param {Object} datos - Datos del mensaje
 * @param {string} datos.contenido - Contenido del mensaje
 * @param {string} datos.tipo - Tipo de mensaje (texto, link)
 * @param {number} datos.responde_a_id - ID del mensaje al que responde (opcional)
 * @returns {Promise<Object>} - Mensaje enviado
 */
export async function enviarMensaje(conversacionId, datos) {
  await getCsrfToken()
  const response = await api.post(`/chat/conversaciones/${conversacionId}/mensajes`, datos)
  return response.data.datos
}

/**
 * Editar mensaje
 * @param {number|string} mensajeId - ID del mensaje
 * @param {string} contenido - Nuevo contenido
 * @returns {Promise<Object>} - Mensaje editado
 */
export async function editarMensaje(mensajeId, contenido) {
  await getCsrfToken()
  const response = await api.put(`/chat/mensajes/${mensajeId}`, { contenido })
  return response.data.datos
}

/**
 * Eliminar mensaje
 * @param {number|string} mensajeId - ID del mensaje
 * @returns {Promise<Object>} - Confirmación
 */
export async function eliminarMensaje(mensajeId) {
  await getCsrfToken()
  const response = await api.delete(`/chat/mensajes/${mensajeId}`)
  return response.data
}

/**
 * Buscar mensajes en una conversación
 * @param {number|string} conversacionId - ID de la conversación
 * @param {string} query - Texto de búsqueda
 * @returns {Promise<Array>} - Lista de mensajes encontrados
 */
export async function buscarMensajes(conversacionId, query) {
  const response = await api.get(`/chat/conversaciones/${conversacionId}/mensajes/buscar`, {
    params: { q: query }
  })
  return response.data.datos
}

/**
 * Marcar mensajes como vistos
 * @param {number|string} conversacionId - ID de la conversación
 * @returns {Promise<Object>} - Confirmación
 */
export async function marcarVisto(conversacionId) {
  await getCsrfToken()
  const response = await api.post(`/chat/conversaciones/${conversacionId}/visto`)
  return response.data
}

// ==================== ADJUNTOS ====================

/**
 * Subir adjunto
 * @param {number|string} conversacionId - ID de la conversación
 * @param {File} archivo - Archivo a subir
 * @param {Object} datos - Datos adicionales
 * @param {string} datos.contenido - Mensaje adicional (opcional)
 * @param {number} datos.responde_a_id - ID del mensaje al que responde (opcional)
 * @returns {Promise<Object>} - Mensaje con adjunto
 */
export async function subirAdjunto(conversacionId, archivo, datos = {}) {
  await getCsrfToken()
  
  const formData = new FormData()
  formData.append('archivo', archivo)
  if (datos.contenido) formData.append('contenido', datos.contenido)
  if (datos.responde_a_id) formData.append('responde_a_id', datos.responde_a_id)

  const response = await api.post(`/chat/conversaciones/${conversacionId}/adjuntos`, formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
  return response.data.datos
}

/**
 * Eliminar adjunto
 * @param {number|string} adjuntoId - ID del adjunto
 * @returns {Promise<Object>} - Confirmación
 */
export async function eliminarAdjunto(adjuntoId) {
  await getCsrfToken()
  const response = await api.delete(`/chat/adjuntos/${adjuntoId}`)
  return response.data
}

// Exportar funciones
export default {
  // Conversaciones
  obtenerConversaciones,
  obtenerConversacion,
  obtenerEstado,
  crearConversacion,
  eliminarConversacion,
  archivarConversacion,
  desarchivarConversacion,
  salirConversacion,
  agregarMiembro,
  removerMiembro,
  actualizarAdmin,
  buscarUsuarios,
  // Mensajes
  obtenerMensajes,
  pollingMensajes,
  enviarMensaje,
  editarMensaje,
  eliminarMensaje,
  buscarMensajes,
  marcarVisto,
  // Adjuntos
  subirAdjunto,
  eliminarAdjunto,
}
