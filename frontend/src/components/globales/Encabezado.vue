<template>
  <header :class="['app-header', { 'sidebar-collapsed': sidebarCollapsed }]">
    <div class="header-content">
      <div class="header-left">
        <button
          v-if="mostrarBotonIzquierdo"
          @click="handleBotonIzquierdo"
          class="header-button"
          :aria-label="botonIzquierdoLabel"
        >
          <ArrowLeftIcon v-if="mostrarRetroceder" class="header-icon" />
          <MenuIcon v-else class="header-icon" />
        </button>
      </div>

      <div class="header-center">
        <!-- Logo visible en todas las plataformas -->
        <img 
          :src="isDark ? '/icono-bco.png' : '/icono.png'" 
          alt="VUERP" 
          class="header-logo"
          @click="irAlInicio"
          role="button"
          tabindex="0"
          @keyup.enter="irAlInicio"
        />
      </div>

      <div class="header-right">
        <div class="header-actions">
          <!-- Botones solo en desktop -->
          <template v-if="isDesktop">
            <button 
              class="action-button" 
              aria-label="Chat"
              @click="irAChat"
            >
              <MessageCircleIcon class="header-icon" />
              <span v-if="chatBadge > 0" class="action-badge">
                {{ chatBadge > 99 ? '99+' : chatBadge }}
              </span>
            </button>
            <button class="action-button" aria-label="Notificaciones">
              <BellIcon class="header-icon" />
            </button>
            <button class="action-button" aria-label="Buscar">
              <SearchIcon class="header-icon" />
            </button>
          </template>
          <!-- Solo búsqueda en mobile/tablet -->
          <button 
            v-else 
            class="action-button" 
            aria-label="Buscar"
          >
            <SearchIcon class="header-icon" />
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useLayoutStore } from '@/stores/layout'
import { useThemeStore } from '@/stores/theme'
import { useChatStore } from '@/stores/chat'
import { usePlatform } from '@/composables/usePlatform'
import MenuIcon from './iconos/MenuIcon.vue'
import ArrowLeftIcon from './iconos/ArrowLeftIcon.vue'
import { SearchIcon, MessageCircleIcon, BellIcon } from './iconos'

const router = useRouter()
const route = useRoute()
const layoutStore = useLayoutStore()
const themeStore = useThemeStore()
const chatStore = useChatStore()
const { isMobile, isDesktop, isTablet } = usePlatform()

const sidebarCollapsed = computed(() => {
  return isDesktop.value && layoutStore.sidebarCollapsed
})

const isDark = computed(() => themeStore.currentTheme === 'dark')

const mostrarBotonIzquierdo = computed(() => {
  // Mostrar en mobile y tablet
  return isMobile.value || isTablet.value
})

const mostrarRetroceder = computed(() => {
  // Mostrar retroceder solo en mobile/tablet cuando hay historial
  if ((isMobile.value || isTablet.value) && typeof window !== 'undefined') {
    return window.history.length > 1
  }
  return false
})

const botonIzquierdoLabel = computed(() => {
  if (mostrarRetroceder.value) {
    return 'Retroceder'
  }
  return 'Abrir menú'
})

const handleBotonIzquierdo = () => {
  if (mostrarRetroceder.value) {
    router.back()
  } else {
    layoutStore.abrirMenuFlotante()
  }
}

const abrirMenu = () => {
  layoutStore.abrirMenuFlotante()
}

const irAlInicio = () => {
  router.push({ name: 'inicio' })
}

const chatBadge = computed(() => chatStore.totalMensajesNoLeidos)

const irAChat = () => {
  layoutStore.abrirChatDrawer()
}
</script>

<style scoped>
@import '@/assets/styles/components/Encabezado.css';
</style>
