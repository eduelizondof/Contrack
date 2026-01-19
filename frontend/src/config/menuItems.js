/**
 * Configuración centralizada del menú de navegación
 * Compatible con Sidebar y MenuFlotante
 */

export const menuItems = [
  {
    id: 'inicio',
    label: 'Inicio',
    icon: 'HomeIcon',
    route: '/inicio',
    badge: null,
  },
]

/**
 * Items para el FooterMenu (5 opciones: 2 + 1 central + 2)
 */
export const footerMenuItems = [
  {
    id: 'footer-inicio',
    label: 'Inicio',
    icon: 'HomeIcon',
    route: '/inicio',
    isCentral: false,
  },
  {
    id: 'footer-chat',
    label: 'Chat',
    icon: 'ChatIcon',
    route: null, // Se maneja como drawer, no como ruta
    isCentral: false,
  },
  {
    id: 'footer-apps',
    label: 'Apps',
    icon: 'AppsIcon',
    route: '/apps',
    isCentral: true, // Opción central destacada
  },
  {
    id: 'footer-notificaciones',
    label: 'Notificaciones',
    icon: 'BellIcon',
    route: '/notificaciones',
    isCentral: false,
  },
  {
    id: 'footer-menu',
    label: 'Menú',
    icon: 'MenuIcon',
    route: null, // Abre el menu flotante
    isCentral: false,
  },
]

export const logoutMessages = [
  'Guardando últimos cambios...',
  'Eliminando datos de la sesión...',
  'Limpiando caché del navegador...',
  'Cerrando conexión segura...',
  '¡Hasta pronto!'
]

export const loginMessages = [
  'Encriptando tu sesión...',
  'Cargando datos del usuario...',
  'Verificando permisos...',
  'Preparando tu espacio de trabajo...',
  '¡Bienvenido!'
]
