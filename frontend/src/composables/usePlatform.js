import { ref, onMounted, onUnmounted } from 'vue'

/**
 * Composable para detectar la plataforma y tamaño de pantalla
 * Detecta si es mobile, tablet o desktop
 */
export function usePlatform() {
  const isMobile = ref(false)
  const isTablet = ref(false)
  const isDesktop = ref(false)
  const platform = ref('web')

  const checkPlatform = () => {
    if (typeof window === 'undefined') return

    // Detectar Capacitor si está disponible
    try {
      const { Capacitor } = require('@capacitor/core')
      if (Capacitor && Capacitor.isNativePlatform()) {
        platform.value = Capacitor.getPlatform()
      }
    } catch (e) {
      // Capacitor no disponible, continuar con detección web
    }

    const width = window.innerWidth

    // Breakpoints
    // Mobile: < 768px
    // Tablet: 768px - 1024px
    // Desktop: > 1024px
    isMobile.value = width < 768
    isTablet.value = width >= 768 && width < 1024
    isDesktop.value = width >= 1024
  }

  onMounted(() => {
    checkPlatform()
    window.addEventListener('resize', checkPlatform)
  })

  onUnmounted(() => {
    if (typeof window !== 'undefined') {
      window.removeEventListener('resize', checkPlatform)
    }
  })

  return {
    isMobile,
    isTablet,
    isDesktop,
    platform,
  }
}
