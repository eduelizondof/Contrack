/**
 * Configuración centralizada de permisos del sistema
 * 
 * Estructura: módulo => acción => recursos
 * Formato de permiso: modulo.accion.recurso
 * 
 * Ejemplo: 'clientes.crear.contacto'
 * 
 * IMPORTANTE: Mantener sincronizado con backend/config/permissions.php
 */

export const permissions = {
  configuracion: {
    ver: ['usuario', 'rol', 'permiso'],
    crear: ['usuario', 'rol', 'permiso'],
    editar: ['usuario', 'rol', 'permiso'],
    eliminar: ['usuario', 'rol', 'permiso'],
  },

  chat: {
    ver: ['conversacion', 'mensaje'],
    crear: ['conversacion', 'mensaje'],
    editar: ['conversacion', 'mensaje'],
    eliminar: ['conversacion', 'mensaje'],
  },

  notificaciones: {
    ver: ['notificacion'],
    crear: ['notificacion'],
    editar: ['notificacion'],
    eliminar: ['notificacion'],
  },
}

/**
 * Obtener todos los permisos como array plano
 * @returns {string[]}
 */
export function obtenerTodosLosPermisos() {
  const todosLosPermisos = []

  for (const [modulo, acciones] of Object.entries(permissions)) {
    for (const [accion, recursos] of Object.entries(acciones)) {
      for (const recurso of recursos) {
        todosLosPermisos.push(`${modulo}.${accion}.${recurso}`)
      }
    }
  }

  return todosLosPermisos
}

/**
 * Obtener permisos agrupados por módulo
 * @returns {Object}
 */
export function obtenerPermisosPorModulo() {
  const permisosAgrupados = {}

  for (const [modulo, acciones] of Object.entries(permissions)) {
    permisosAgrupados[modulo] = []
    for (const [accion, recursos] of Object.entries(acciones)) {
      for (const recurso of recursos) {
        permisosAgrupados[modulo].push(`${modulo}.${accion}.${recurso}`)
      }
    }
  }

  return permisosAgrupados
}

/**
 * Obtener permisos de un módulo específico
 * @param {string} modulo
 * @returns {string[]}
 */
export function obtenerPermisosDeModulo(modulo) {
  if (!permissions[modulo]) {
    return []
  }

  const permisosModulo = []
  for (const [accion, recursos] of Object.entries(permissions[modulo])) {
    for (const recurso of recursos) {
      permisosModulo.push(`${modulo}.${accion}.${recurso}`)
    }
  }

  return permisosModulo
}

/**
 * Obtener permisos de una acción específica en un módulo
 * @param {string} modulo
 * @param {string} accion
 * @returns {string[]}
 */
export function obtenerPermisosDeAccion(modulo, accion) {
  if (!permissions[modulo] || !permissions[modulo][accion]) {
    return []
  }

  const permisosAccion = []
  for (const recurso of permissions[modulo][accion]) {
    permisosAccion.push(`${modulo}.${accion}.${recurso}`)
  }

  return permisosAccion
}
