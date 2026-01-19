import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useLayoutStore = defineStore('layout', () => {
  // Estado del sidebar (colapsado o expandido)
  // Por defecto está colapsado (true)
  const sidebarCollapsed = ref(true)
  
  // Estado del menú flotante (abierto o cerrado)
  const menuFlotanteAbierto = ref(false)

  // Estado del chat drawer (abierto o cerrado)
  const chatDrawerAbierto = ref(false)

  // Cargar estado del sidebar desde localStorage
  const loadSidebarState = () => {
    if (typeof localStorage !== 'undefined') {
      const saved = localStorage.getItem('sidebarCollapsed')
      if (saved !== null) {
        sidebarCollapsed.value = saved === 'true'
      }
      // Si no hay valor guardado, mantener el valor por defecto (true = colapsado)
    }
  }

  // Guardar estado del sidebar en localStorage
  const saveSidebarState = () => {
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem('sidebarCollapsed', sidebarCollapsed.value.toString())
    }
  }

  // Inicializar desde localStorage
  loadSidebarState()

  // Toggle del sidebar
  function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value
    saveSidebarState()
  }

  // Colapsar sidebar
  function colapsarSidebar() {
    sidebarCollapsed.value = true
    saveSidebarState()
  }

  // Expandir sidebar
  function expandirSidebar() {
    sidebarCollapsed.value = false
    saveSidebarState()
  }

  // Abrir menú flotante
  function abrirMenuFlotante() {
    menuFlotanteAbierto.value = true
    // Prevenir scroll del body cuando el menú está abierto
    if (typeof document !== 'undefined') {
      document.body.style.overflow = 'hidden'
    }
  }

  // Cerrar menú flotante
  function cerrarMenuFlotante() {
    menuFlotanteAbierto.value = false
    // Restaurar scroll del body
    if (typeof document !== 'undefined') {
      document.body.style.overflow = ''
    }
  }

  // Abrir chat drawer
  function abrirChatDrawer() {
    chatDrawerAbierto.value = true
    // Prevenir scroll del body cuando el chat está abierto
    if (typeof document !== 'undefined') {
      document.body.style.overflow = 'hidden'
    }
  }

  // Cerrar chat drawer
  function cerrarChatDrawer() {
    chatDrawerAbierto.value = false
    // Restaurar scroll del body
    if (typeof document !== 'undefined') {
      document.body.style.overflow = ''
    }
  }

  // Watch para cerrar menú flotante cuando se cierra el sidebar en desktop
  watch(sidebarCollapsed, (colapsado) => {
    // Si el sidebar se expande en desktop, cerrar el menú flotante
    if (!colapsado && menuFlotanteAbierto.value) {
      cerrarMenuFlotante()
    }
  })

  return {
    sidebarCollapsed,
    menuFlotanteAbierto,
    chatDrawerAbierto,
    toggleSidebar,
    colapsarSidebar,
    expandirSidebar,
    abrirMenuFlotante,
    cerrarMenuFlotante,
    abrirChatDrawer,
    cerrarChatDrawer,
  }
})
