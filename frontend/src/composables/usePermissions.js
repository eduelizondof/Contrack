import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

/**
 * Composable para verificación de permisos y roles
 * 
 * @example
 * import { usePermissions } from '@/composables/usePermissions'
 * 
 * const { tienePermiso, tieneRol, puedeAcceder } = usePermissions()
 * 
 * if (tienePermiso('clientes.crear.contacto')) {
 *   // mostrar botón crear
 * }
 */
export function usePermissions() {
  const authStore = useAuthStore()

  /**
   * Verifica si el usuario tiene un permiso específico
   * @param {string|string[]} permisos - Permiso o array de permisos
   * @returns {boolean}
   */
  function tienePermiso(permisos) {
    if (!authStore.usuario || !authStore.usuario.permisos) return false
    
    const permisosArray = Array.isArray(permisos) ? permisos : [permisos]
    const usuarioPermisos = authStore.usuario.permisos || []
    
    return permisosArray.some(permiso => usuarioPermisos.includes(permiso))
  }

  /**
   * Verifica si el usuario tiene un rol específico
   * @param {string|string[]} roles - Rol o array de roles
   * @returns {boolean}
   */
  function tieneRol(roles) {
    if (!authStore.usuario || !authStore.usuario.roles) return false
    
    const rolesArray = Array.isArray(roles) ? roles : [roles]
    const usuarioRoles = authStore.usuario.roles || []
    
    return rolesArray.some(rol => usuarioRoles.includes(rol))
  }

  /**
   * Verifica si el usuario puede acceder a una ruta basado en permisos y roles
   * @param {object} meta - Meta de la ruta con permisos y roles
   * @returns {boolean}
   */
  function puedeAcceder(meta) {
    if (!meta) return true
    
    if (meta.permisos && !tienePermiso(meta.permisos)) {
      return false
    }
    
    if (meta.roles && !tieneRol(meta.roles)) {
      return false
    }
    
    return true
  }

  return {
    tienePermiso,
    tieneRol,
    puedeAcceder,
  }
}
