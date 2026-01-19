<template>
  <footer class="footer-menu">
    <div class="footer-menu-wrapper">
      <nav class="footer-menu-nav">
        <button
          v-for="item in footerMenuItems"
          :key="item.id"
          :class="['footer-menu-item', { 'is-central': item.isCentral }]"
          @click="handleClick(item)"
          :aria-label="item.label"
        >
          <div class="footer-menu-icon-wrapper">
            <component :is="getIconComponent(item.icon)" class="footer-menu-icon" />
            <!-- Badge de notificaciones para chat -->
            <span 
              v-if="item.id === 'footer-chat' && chatBadge > 0" 
              class="footer-menu-badge"
            >
              {{ chatBadge > 99 ? '99+' : chatBadge }}
            </span>
          </div>
          <span class="footer-menu-label">{{ item.label }}</span>
        </button>
      </nav>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useLayoutStore } from '@/stores/layout'
import { useChatStore } from '@/stores/chat'
import { footerMenuItems } from '@/config/menuItems'
import * as icons from './iconos/index.js'

const router = useRouter()
const layoutStore = useLayoutStore()
const chatStore = useChatStore()

const getIconComponent = (iconName) => {
  return icons[iconName] || icons.HomeIcon
}

// Badge reactivo para chat
const chatBadge = computed(() => chatStore.totalMensajesNoLeidos)

const handleClick = (item) => {
  if (item.route) {
    router.push(item.route)
  } else if (item.id === 'footer-chat') {
    // Abrir chat drawer
    layoutStore.abrirChatDrawer()
  } else {
    // Si no tiene ruta, abre el menú flotante (caso del botón "Menú")
    layoutStore.abrirMenuFlotante()
  }
}
</script>

<style scoped>
@import '@/assets/styles/components/MenuPie.css';
</style>
