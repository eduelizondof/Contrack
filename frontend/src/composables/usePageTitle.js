import { watch } from 'vue'
import { useRoute } from 'vue-router'

const DEFAULT_TITLE = 'CNERP - Sistema ERP'

/**
 * Composable para manejar títulos dinámicos de página
 * @param {string} defaultTitle - Título por defecto si no hay meta.title
 * @returns {object} - Objeto con función setTitle
 */
export function usePageTitle(defaultTitle = DEFAULT_TITLE) {
  const route = useRoute()

  /**
   * Establece el título de la página
   * @param {string} title - Título a establecer
   */
  function setTitle(title) {
    if (title) {
      document.title = title
    } else {
      document.title = defaultTitle
    }
  }

  // Observar cambios en la ruta y actualizar título automáticamente
  watch(
    () => route.meta?.title,
    (newTitle) => {
      if (newTitle) {
        setTitle(newTitle)
      } else {
        setTitle(defaultTitle)
      }
    },
    { immediate: true }
  )

  return {
    setTitle,
  }
}
