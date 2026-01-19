import api from './api'

/**
 * Servicio para gestión de roles y permisos
 */

/**
 * Obtener lista de roles
 * @returns {Promise<Array>} - Lista de roles
 */
export async function obtenerRoles() {
    const response = await api.get('/configuracion/roles')
    return response.data.datos
}

/**
 * Obtener un rol por ID
 * @param {number|string} id - ID del rol
 * @returns {Promise<Object>} - Datos del rol
 */
export async function obtenerRol(id) {
    const response = await api.get(`/configuracion/roles/${id}`)
    return response.data.datos
}

/**
 * Crear nuevo rol
 * @param {Object} datos - Datos del rol
 * @param {string} datos.nombre - Nombre del rol
 * @returns {Promise<Object>} - Rol creado
 */
export async function crearRol(datos) {
    const response = await api.post('/configuracion/roles', datos)
    return response.data.datos
}

/**
 * Actualizar rol
 * @param {number|string} id - ID del rol
 * @param {Object} datos - Datos a actualizar
 * @returns {Promise<Object>} - Rol actualizado
 */
export async function actualizarRol(id, datos) {
    const response = await api.put(`/configuracion/roles/${id}`, datos)
    return response.data.datos
}

/**
 * Eliminar rol
 * @param {number|string} id - ID del rol
 * @returns {Promise<Object>} - Confirmación
 */
export async function eliminarRol(id) {
    const response = await api.delete(`/configuracion/roles/${id}`)
    return response.data
}

/**
 * Actualizar permisos de un rol
 * @param {number|string} id - ID del rol
 * @param {Array<string>} permisos - Lista de nombres de permisos
 * @returns {Promise<Object>} - Rol actualizado
 */
export async function actualizarPermisosRol(id, permisos) {
    const response = await api.post(`/configuracion/roles/${id}/permisos`, { permisos })
    return response.data.datos
}

/**
 * Obtener permisos organizados por categorías
 * @returns {Promise<Array>} - Lista de categorías con permisos
 */
export async function obtenerPermisosCategorias() {
    const response = await api.get('/configuracion/permisos/categorias')
    return response.data.datos
}

export default {
    obtenerRoles,
    obtenerRol,
    crearRol,
    actualizarRol,
    eliminarRol,
    actualizarPermisosRol,
    obtenerPermisosCategorias,
}
