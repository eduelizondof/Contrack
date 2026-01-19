import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useThemeStore = defineStore('theme', () => {
  const theme = ref('system') // 'light', 'dark', 'system'
  const currentTheme = ref('light') // 'light' o 'dark' (resuelto)
  let systemThemeListener = null

  // Detectar preferencia del sistema
  const getSystemTheme = () => {
    if (typeof window !== 'undefined' && window.matchMedia) {
      return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }
    return 'light'
  }

  // Aplicar tema al documento
  const applyTheme = (themeValue) => {
    if (typeof document === 'undefined') return

    const root = document.documentElement
    const resolvedTheme = themeValue === 'system' ? getSystemTheme() : themeValue
    
    // Actualizar el valor reactivo
    currentTheme.value = resolvedTheme

    // Aplicar clase dark o removerla según el tema resuelto
    // Solo necesitamos la clase 'dark' en el root, no 'light'
    if (resolvedTheme === 'dark') {
      root.classList.add('dark')
      root.classList.remove('light')
      // También aplicar al body para compatibilidad
      if (document.body) {
        document.body.classList.add('dark')
        document.body.classList.remove('light')
      }
    } else {
      root.classList.remove('dark')
      root.classList.remove('light')
      // También remover del body
      if (document.body) {
        document.body.classList.remove('dark')
        document.body.classList.remove('light')
      }
    }
    
    // Forzar repaint para asegurar que los estilos se apliquen
    void root.offsetHeight
  }

  // Inicializar tema desde localStorage o sistema
  const initializeTheme = () => {
    if (typeof window === 'undefined' || typeof document === 'undefined') return
    
    const savedTheme = localStorage.getItem('theme') || 'system'
    theme.value = savedTheme
    
    // Aplicar tema inmediatamente si el DOM está listo
    if (document.documentElement) {
      applyTheme(savedTheme)
      setupSystemThemeListener()
    } else {
      // Si el DOM no está listo, esperar a que lo esté
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
          applyTheme(savedTheme)
          setupSystemThemeListener()
        })
      } else {
        // DOM ya está listo, aplicar inmediatamente
        applyTheme(savedTheme)
        setupSystemThemeListener()
      }
    }
  }

  // Cambiar tema
  const setTheme = (newTheme) => {
    if (!['light', 'dark', 'system'].includes(newTheme)) {
      console.warn('Tema inválido:', newTheme)
      return
    }
    
    // Actualizar localStorage primero
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem('theme', newTheme)
    }
    
    // Actualizar el valor reactivo (esto disparará el watch)
    theme.value = newTheme
    
    // Aplicar inmediatamente (el watch también lo hará, pero es seguro)
    applyTheme(newTheme)
  }

  // Toggle entre light y dark (ignora system)
  const toggleTheme = () => {
    const resolved = theme.value === 'system' ? getSystemTheme() : theme.value
    const newTheme = resolved === 'dark' ? 'light' : 'dark'
    setTheme(newTheme)
  }

  // Escuchar cambios en la preferencia del sistema
  const setupSystemThemeListener = () => {
    if (typeof window === 'undefined' || !window.matchMedia) return

    // Limpiar listener anterior si existe
    if (systemThemeListener) {
      const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
      if (mediaQuery.removeEventListener) {
        mediaQuery.removeEventListener('change', systemThemeListener)
      } else if (mediaQuery.removeListener) {
        mediaQuery.removeListener(systemThemeListener)
      }
    }
    
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    
    systemThemeListener = () => {
      if (theme.value === 'system') {
        applyTheme('system')
      }
    }

    // Método moderno
    if (mediaQuery.addEventListener) {
      mediaQuery.addEventListener('change', systemThemeListener)
    } else {
      // Fallback para navegadores antiguos
      mediaQuery.addListener(systemThemeListener)
    }
  }

  // Watch para cambios en el tema (solo si cambia externamente, no desde setTheme)
  // Nota: setTheme ya llama a applyTheme, así que el watch es principalmente
  // para cambios externos o cuando se restaura desde localStorage
  watch(theme, (newTheme) => {
    // No aplicar aquí porque setTheme ya lo hace
    // Este watch es principalmente para reactividad de los componentes
  })

  return {
    theme,
    currentTheme,
    setTheme,
    toggleTheme,
    initializeTheme,
  }
})
