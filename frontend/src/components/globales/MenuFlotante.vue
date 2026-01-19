<template>
  <Teleport to="body">
    <Transition name="menu-flotante">
      <div v-if="abierto" class="menu-flotante-overlay" @click="cerrar">
        <div 
          class="menu-flotante-content" 
          @click.stop
          @touchstart="onTouchStart"
          @touchmove="onTouchMove"
          @touchend="onTouchEnd"
          :style="contentStyle"
        >
          <div class="drag-handle"></div>
          <div class="menu-flotante-header">
            <h2 class="menu-flotante-title">Menú</h2>
            <button
              @click="cerrar"
              class="menu-flotante-close"
              aria-label="Cerrar menú"
            >
              <XIcon class="icon" />
            </button>
          </div>

          <nav class="menu-flotante-nav">
            <ElementoMenu
              v-for="item in menuItems"
              :key="item.id"
              :item="item"
              :collapsed="false"
              @navigate="cerrar"
            />
          </nav>

          <div class="menu-flotante-footer">
            <button
              @click="toggleTheme"
              class="menu-flotante-footer-item theme-toggle-button"
              :aria-label="isDark ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'"
            >
              <AlternadorTema :standalone="false" />
              <span class="theme-label">Tema</span>
            </button>
            <button
              @click="irAConfiguracion"
              class="menu-flotante-footer-item config-button"
              aria-label="Configuración"
            >
              <component :is="SettingsIcon" class="config-icon" />
              <span class="config-label">Configuración</span>
            </button>
            <button
              @click="startLogout"
              :class="['logout-button', { 'confirming': confirmando }]"
              :aria-label="confirmando ? 'Confirmar cierre de sesión' : 'Cerrar sesión'"
            >
              <component :is="LogoutIcon" class="logout-icon" />
              <span class="logout-label">
                {{ confirmando ? '¿Estás seguro?' : 'Cerrar Sesión' }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useLayoutStore } from '@/stores/layout'
import { useThemeStore } from '@/stores/theme'
import { menuItems } from '@/config/menuItems'
import ElementoMenu from './ElementoMenu.vue'
import XIcon from './iconos/XIcon.vue'
import LogoutIcon from './iconos/LogoutIcon.vue'
import SettingsIcon from './iconos/SettingsIcon.vue'
import AlternadorTema from './AlternadorTema.vue'
import { useLogout } from '@/composables/useLogout'

const router = useRouter()
const authStore = useAuthStore()
const layoutStore = useLayoutStore()
const themeStore = useThemeStore()

const abierto = computed(() => layoutStore.menuFlotanteAbierto)
const isDark = computed(() => themeStore.currentTheme === 'dark')

const { confirmando, startLogout } = useLogout()

// Swipe logic
const startY = ref(0)
const startX = ref(0)
const offsetY = ref(0)
const offsetX = ref(0)
const isDragging = ref(false)

const contentStyle = computed(() => {
  if (!isDragging.value) return {}
  return {
    transform: `translate(${offsetX.value}px, ${offsetY.value}px)`,
    transition: 'none',
    opacity: Math.max(0.5, 1 - (Math.abs(offsetY.value) + Math.abs(offsetX.value)) / 400)
  }
})

const onTouchStart = (e) => {
  const nav = e.currentTarget.querySelector('.menu-flotante-nav')
  const isAtTop = nav ? nav.scrollTop <= 0 : true
  const isHeader = e.target.closest('.menu-flotante-header') || e.target.closest('.drag-handle')
  
  if (isHeader || isAtTop) {
    startY.value = e.touches[0].clientY
    startX.value = e.touches[0].clientX
    isDragging.value = true
  }
}

const onTouchMove = (e) => {
  if (!isDragging.value) return
  
  const currentY = e.touches[0].clientY
  const currentX = e.touches[0].clientX
  
  const deltaY = currentY - startY.value
  const deltaX = currentX - startX.value
  
  // Only drag if moving down or right
  if (deltaY > 0 || deltaX > 0) {
    if (e.cancelable) e.preventDefault()
    offsetY.value = Math.max(0, deltaY)
    offsetX.value = Math.max(0, deltaX)
  } else {
    // If moving up and we were dragging, reset
    offsetY.value = 0
    offsetX.value = 0
  }
}

const onTouchEnd = () => {
  isDragging.value = false
  
  // Threshold to close
  if (offsetY.value > 100 || offsetX.value > 100) {
    cerrar()
  }
  
  // Reset offsets
  offsetY.value = 0
  offsetX.value = 0
}

const cerrar = () => {
  layoutStore.cerrarMenuFlotante()
}

const toggleTheme = () => {
  themeStore.toggleTheme()
  cerrar()
}

const irAConfiguracion = () => {
  router.push('/configuracion')
  cerrar()
}

// El cierre de sesión se maneja en useLogout
</script>

<style scoped>
@import '@/assets/styles/components/MenuFlotante.css';
</style>
