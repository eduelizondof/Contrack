import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import NProgress from 'nprogress'

// Configurar nprogress
NProgress.configure({
  showSpinner: false, // Ocultar spinner, solo mostrar barra
  minimum: 0.08, // Mínimo para iniciar (evita parpadeos)
  easing: 'ease',
  speed: 400,
  trickle: true, // Incremento automático gradual
  trickleRate: 0.02, // Tasa de incremento (0.02 = 2% por tick)
  trickleSpeed: 200 // Velocidad del incremento en ms
})

const DEFAULT_TITLE = 'CNERP - Sistema ERP'

/**
 * Actualiza el título de la página basado en la ruta
 */
function updatePageTitle(route) {
  const title = route.meta?.title || DEFAULT_TITLE
  document.title = title
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/inicio',
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/autenticacion/VistaLogin.vue'),
      meta: {
        requiereAuth: false,
        title: 'Iniciar Sesión - CNERP'
      },
    },
    {
      path: '/inicio',
      name: 'inicio',
      component: () => import('@/views/app/VistaInicio.vue'),
      meta: {
        requiereAuth: true,
        title: 'Inicio - CNERP'
      },
    },
    {
      path: '/configuracion',
      name: 'configuracion',
      component: () => import('@/views/app/configuracion/VistaConfiguracion.vue'),
      meta: {
        requiereAuth: true,
        title: 'Configuración - CNERP'
      },
    },
    {
      path: '/configuracion/usuarios',
      name: 'usuarios',
      component: () => import('@/views/app/configuracion/VistaUsuarios.vue'),
      meta: {
        requiereAuth: true,
        title: 'Usuarios - CNERP'
      },
    },
    {
      path: '/configuracion/usuarios/:id',
      name: 'detalle-usuario',
      component: () => import('@/views/app/configuracion/VistaDetalleUsuario.vue'),
      meta: {
        requiereAuth: true,
        title: 'Detalle Usuario - CNERP'
      },
    },
    {
      path: '/configuracion/roles',
      name: 'roles',
      component: () => import('@/views/app/configuracion/VistaRoles.vue'),
      meta: {
        requiereAuth: true,
        title: 'Roles y Permisos - CNERP'
      },
    },
    {
      path: '/configuracion/roles/:id',
      name: 'detalle-rol',
      component: () => import('@/views/app/configuracion/VistaDetalleRol.vue'),
      meta: {
        requiereAuth: true,
        title: 'Detalle Rol - CNERP'
      },
    },
    // Ruta catch-all para 404 (debe ser la última)
    {
      path: '/:pathMatch(.*)*',
      name: '404',
      component: () => import('@/views/app/VistaError.vue'),
      props: () => ({
        tipo: '404',
        mensaje: 'La página que buscas no existe o ha sido movida. Verifica la URL e intenta nuevamente.'
      }),
      meta: {
        requiereAuth: false, // Permitir acceso sin autenticación para mostrar el error
        title: 'Página no encontrada - CNERP'
      },
    },
  ],
})

// Guard de navegación
router.beforeEach(async (to, from, next) => {
  // Iniciar barra de progreso solo si es una navegación real (no la primera carga)
  if (from.name !== null) {
    NProgress.start()
  }

  const authStore = useAuthStore()

  // Actualizar título de la página
  updatePageTitle(to)

  const requiereAuth = to.meta.requiereAuth

  // Si la ruta requiere autenticación, inicializar auth si es necesario
  if (requiereAuth) {
    // Solo inicializar si no hay usuario y no está cargando
    if (!authStore.usuario && !authStore.cargando) {
      await authStore.inicializar()
    }

    if (!authStore.estaAutenticado) {
      // Redirigir al login si no está autenticado
      next({ name: 'login', query: { redirect: to.fullPath } })
      return
    }

    // Verificar permisos si están definidos en meta
    if (to.meta.permisos) {
      const tienePermiso = authStore.tienePermiso(to.meta.permisos)
      if (!tienePermiso) {
        // Redirigir a inicio si no tiene permisos (o crear página de acceso denegado)
        next({ name: 'inicio' })
        return
      }
    }

    // Verificar roles si están definidos en meta
    if (to.meta.roles) {
      const tieneRol = authStore.tieneRol(to.meta.roles)
      if (!tieneRol) {
        // Redirigir a inicio si no tiene el rol requerido
        next({ name: 'inicio' })
        return
      }
    }

    // Usuario autenticado y con permisos/roles necesarios
    next()
  } else {
    // Si la ruta es login y ya está autenticado, redirigir al inicio
    if (to.name === 'login' && authStore.estaAutenticado) {
      // Verificar autenticación antes de redirigir
      if (!authStore.usuario && !authStore.cargando) {
        await authStore.inicializar()
      }
      if (authStore.estaAutenticado) {
        next({ name: 'inicio' })
      } else {
        next()
      }
    } else {
      // No inicializar auth en rutas públicas como login
      next()
    }
  }
})

// Completar barra de progreso después de la navegación
router.afterEach(() => {
  NProgress.done()
})

// Manejar errores de navegación
router.onError((error) => {
  NProgress.done()
  console.error('Error de navegación:', error)
})

export default router
