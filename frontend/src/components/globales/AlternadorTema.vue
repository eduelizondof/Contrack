<template>
  <component
    :is="standalone ? 'button' : 'span'"
    :type="standalone ? 'button' : undefined"
    class="theme-toggle"
    :class="{ 'is-dark': isDark, 'standalone': standalone }"
    @click="handleClick"
    :aria-label="isDark ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'"
  >
    <svg v-if="isDark" class="icon sun-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
    </svg>
    <svg v-else class="icon moon-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
    </svg>
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { useThemeStore } from '@/stores/theme'

const props = defineProps({
  standalone: {
    type: Boolean,
    default: true
  }
})

const themeStore = useThemeStore()

const isDark = computed(() => themeStore.currentTheme === 'dark')

const handleClick = (event) => {
  if (props.standalone) {
    themeStore.toggleTheme()
  }
  // No detenemos la propagación para que el padre pueda manejar el click si no es standalone
}
</script>

<style scoped>
@import '@/assets/styles/components/AlternadorTema.css';
</style>
