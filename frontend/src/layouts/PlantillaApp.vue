<template>
  <div class="app-layout">
    <BarraLateral />
    <MenuFlotante />
    
    <main :class="['app-main', { 'sidebar-collapsed': sidebarCollapsed }]">
      <Encabezado />
      <div class="app-content">
        <slot />
      </div>
    </main>

    <MenuPie />
    <CajonChat :is-open="layoutStore.chatDrawerAbierto" @update:is-open="handleChatDrawerUpdate" />
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue'
import { useLayoutStore } from '@/stores/layout'
import { useChatStore } from '@/stores/chat'
import { useAuthStore } from '@/stores/auth'
import { usePlatform } from '@/composables/usePlatform'
import Encabezado from '@/components/globales/Encabezado.vue'
import BarraLateral from '@/components/globales/BarraLateral.vue'
import MenuFlotante from '@/components/globales/MenuFlotante.vue'
import MenuPie from '@/components/globales/MenuPie.vue'
import CajonChat from '@/components/vistas/chat/CajonChat.vue'

const layoutStore = useLayoutStore()
const chatStore = useChatStore()
const authStore = useAuthStore()
const { isDesktop } = usePlatform()

const sidebarCollapsed = computed(() => {
  // Solo aplicar clase en desktop
  return isDesktop.value && layoutStore.sidebarCollapsed
})

const handleChatDrawerUpdate = (value) => {
  if (value) {
    layoutStore.abrirChatDrawer()
  } else {
    layoutStore.cerrarChatDrawer()
  }
}

// Iniciar polling ligero cuando el usuario está autenticado
watch(() => authStore.estaAutenticado, (autenticado) => {
  if (autenticado) {
    // Iniciar polling ligero global (solo estado, no carga completa)
    chatStore.iniciarPollingLigero()
    // Cargar estado inicial
    chatStore.pollingEstado()
  } else {
    // Detener polling cuando no está autenticado
    chatStore.detenerPollingLigero()
    chatStore.detenerPollingCompleto()
  }
}, { immediate: true })

onMounted(() => {
  // Si ya está autenticado al montar, iniciar polling ligero
  if (authStore.estaAutenticado) {
    chatStore.iniciarPollingLigero()
    chatStore.pollingEstado()
  }
})

onUnmounted(() => {
  // Detener todos los polling al desmontar
  chatStore.detenerPollingLigero()
  chatStore.detenerPollingCompleto()
})
</script>

<style scoped>
@import '@/assets/styles/layouts/PlantillaApp.css';
</style>
