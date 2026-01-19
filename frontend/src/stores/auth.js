import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/auth'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
  const usuario = ref(null)
  const cargando = ref(false)
  const autenticado = ref(false)
  const isLoggingOut = ref(false)
  const logoutMessage = ref('')
  const isLoggingIn = ref(false)
  const loginMessage = ref('')

  // Getters
  const estaAutenticado = computed(() => autenticado.value && usuario.value !== null)

  /**
   * Verificar si el usuario tiene un permiso específico
   * @param {string|string[]} permisos - Permiso o array de permisos
   * @returns {boolean}
   */
  function tienePermiso(permisos) {
    if (!usuario.value || !usuario.value.permisos) return false

    const permisosArray = Array.isArray(permisos) ? permisos : [permisos]
    const usuarioPermisos = usuario.value.permisos || []

    return permisosArray.some(permiso => usuarioPermisos.includes(permiso))
  }

  /**
   * Verificar si el usuario tiene un rol específico
   * @param {string|string[]} roles - Rol o array de roles
   * @returns {boolean}
   */
  function tieneRol(roles) {
    if (!usuario.value || !usuario.value.roles) return false

    const rolesArray = Array.isArray(roles) ? roles : [roles]
    const usuarioRoles = usuario.value.roles || []

    return rolesArray.some(rol => usuarioRoles.includes(rol))
  }

  // Inicializar: verificar si hay sesión activa
  async function inicializar() {
    cargando.value = true
    try {
      const user = await authService.getUser()
      usuario.value = user
      autenticado.value = true
    } catch (error) {
      usuario.value = null
      autenticado.value = false
      // Limpiar token si existe
      localStorage.removeItem('auth_token')
    } finally {
      cargando.value = false
    }
  }

  // Login
  async function login(credenciales) {
    cargando.value = true
    try {
      const response = await authService.login(credenciales)
      usuario.value = response.user
      autenticado.value = true
      return { exito: true, mensaje: 'Inicio de sesión exitoso' }
    } catch (error) {
      // Manejar diferentes formatos de error de Laravel
      let mensaje = 'Error al iniciar sesión. Verifica tus credenciales.'

      if (error.response?.data) {
        // Error de validación (422) - Laravel ValidationException
        if (error.response.data.errors?.email) {
          mensaje = Array.isArray(error.response.data.errors.email)
            ? error.response.data.errors.email[0]
            : error.response.data.errors.email
        }
        // Mensaje general
        else if (error.response.data.message) {
          mensaje = error.response.data.message
        }
      } else if (error.message) {
        mensaje = error.message
      }

      return { exito: false, mensaje }
    } finally {
      cargando.value = false
    }
  }

  // Logout
  async function logout() {
    cargando.value = true
    try {
      await authService.logout()
    } catch (error) {
      console.error('Error al cerrar sesión:', error)
    } finally {
      usuario.value = null
      autenticado.value = false
      localStorage.removeItem('auth_token')
      cargando.value = false
    }
  }

  return {
    usuario,
    cargando,
    autenticado,
    estaAutenticado,
    tienePermiso,
    tieneRol,
    inicializar,
    login,
    logout,
    isLoggingOut,
    logoutMessage,
    isLoggingIn,
    loginMessage,
  }
})
