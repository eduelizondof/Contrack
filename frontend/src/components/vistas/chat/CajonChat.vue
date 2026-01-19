<script setup>
import { Teleport, onMounted, onUnmounted, watch } from 'vue'
import XIcon from '@/components/globales/iconos/XIcon.vue'
import LayoutChat from './LayoutChat.vue'
import { useChatStore } from '@/stores/chat'
import { useLayoutStore } from '@/stores/layout'
import { usePlatform } from '@/composables/usePlatform'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:isOpen'])
const chatStore = useChatStore()
const layoutStore = useLayoutStore()
const { isDesktop } = usePlatform()

function close() {
  emit('update:isOpen', false)
  layoutStore.cerrarChatDrawer()
}

// Manejar tecla ESC para cerrar en desktop
function handleKeydown(event) {
  if (event.key === 'Escape' && props.isOpen && isDesktop.value) {
    close()
  }
}

// Watcher para iniciar/detener polling cuando el drawer se abre/cierra
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    chatStore.iniciarPollingCompleto() // Polling completo cuando está en chat
    chatStore.detenerPollingLigero() // Detener polling ligero
    // Agregar listener de teclado cuando se abre
    if (isDesktop.value) {
      document.addEventListener('keydown', handleKeydown)
    }
  } else {
    chatStore.detenerPollingCompleto()
    chatStore.iniciarPollingLigero() // Reiniciar polling ligero cuando cierra
    // Remover listener cuando se cierra
    if (isDesktop.value) {
      document.removeEventListener('keydown', handleKeydown)
    }
  }
})

onMounted(() => {
  if (props.isOpen) {
    chatStore.iniciarPollingCompleto()
    chatStore.detenerPollingLigero()
    if (isDesktop.value) {
      document.addEventListener('keydown', handleKeydown)
    }
  }
})

onUnmounted(() => {
  chatStore.detenerPollingCompleto()
  // Limpiar listener al desmontar
  if (isDesktop.value) {
    document.removeEventListener('keydown', handleKeydown)
  }
})
</script>

<template>
  <Teleport to="body">
    <!-- Overlay -->
    <Transition name="fade">
      <div
        v-if="isOpen"
        class="chat-modal-overlay"
        @click.self="close"
      />
    </Transition>

    <!-- Drawer / Modal full screen -->
    <Transition name="slide-up">
      <aside
        v-if="isOpen"
        class="chat-drawer-content"
      >
        <!-- Header del drawer -->
        <div class="chat-drawer-header">
          <div class="flex items-center gap-3">
            <div class="rounded-xl bg-primary/10 p-2">
              <img src="/favicon.ico" alt="Logo" class="h-6 w-6" />
            </div>
            <h2 class="drawer-title">Centro de Mensajes</h2>
          </div>
          <button
            @click="close"
            class="btn-close"
          >
            <XIcon class="h-6 w-6" />
          </button>
        </div>

        <!-- Cuerpo del drawer con el LayoutChat -->
        <div class="chat-drawer-body">
          <LayoutChat />
        </div>
      </aside>
    </Transition>
  </Teleport>
</template>

<style scoped>
.chat-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(8px);
  z-index: 120;
}

.chat-drawer-content {
  position: fixed;
  inset: 0;
  z-index: 121;
  background: var(--color-background);
  width: 100%;
  height: 100vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.3);
}

.chat-drawer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 20px;
  border-bottom: 1px solid var(--color-border);
  background: var(--color-background);
  flex-shrink: 0;
}

.drawer-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-foreground);
  letter-spacing: -0.02em;
}

.btn-close {
  width: 44px;
  height: 44px;
  border: none;
  border-radius: 14px;
  background: var(--color-muted);
  color: var(--color-foreground);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-close:hover {
  background: var(--color-accent);
  transform: rotate(90deg) scale(1.1);
}

.chat-drawer-body {
  flex: 1;
  overflow: hidden;
  position: relative;
}

/* Transiciones */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.5s ease;
}

.slide-up-enter-from {
  transform: translateY(100%);
  opacity: 0;
}

.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

/* Ajustes para el LayoutChat interno */
:deep(.chat-layout) {
  height: 100% !important;
}
</style>
