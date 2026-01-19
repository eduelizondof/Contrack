<script setup>
import { Teleport, watch, onUnmounted } from 'vue'
import XIcon from '@/components/globales/iconos/XIcon.vue'
import ConversacionNueva from './sidebar/ConversacionNueva.vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:isOpen', 'creada', 'cancelar'])

function close() {
  emit('update:isOpen', false)
  emit('cancelar')
}

function handleCreada(conv) {
  emit('creada', conv)
  close()
}

// Prevenir scroll del body cuando el drawer está abierto
watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

onUnmounted(() => {
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <!-- Overlay -->
    <Transition name="fade">
      <div
        v-if="isOpen"
        class="drawer-overlay"
        @click.self="close"
      />
    </Transition>

    <!-- Drawer -->
    <Transition name="slide-up">
      <aside
        v-if="isOpen"
        class="drawer-content"
      >
        <!-- Header del drawer -->
        <div class="drawer-header">
          <h2 class="drawer-title">Nueva Conversación</h2>
          <button
            @click="close"
            class="btn-close"
          >
            <XIcon class="h-6 w-6" />
          </button>
        </div>

        <!-- Body con scroll -->
        <div class="drawer-body">
          <ConversacionNueva 
            @cancelar="close"
            @creada="handleCreada"
          />
        </div>
      </aside>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* Overlay */
.drawer-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  z-index: 100;
}

/* Drawer */
.drawer-content {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 101;
  background: var(--color-background);
  width: 100%;
  max-height: 90vh;
  height: 75vh;
  border-radius: 28px 28px 0 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.2);
}

/* Header con gradiente sutil */
.drawer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid var(--color-border);
  background: linear-gradient(135deg, var(--color-muted) 0%, var(--color-background) 100%);
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

/* Body con scroll personalizado */
.drawer-body {
  flex: 1;
  overflow: hidden;
  background: var(--color-muted);
  display: flex;
  flex-direction: column;
}


/* Ajustes para el componente interno */
:deep(.nueva-conversacion) {
  background: transparent;
  height: 100%;
  display: flex;
  flex-direction: column;
}

:deep(.nueva-header) {
  display: none; /* Ocultar header interno ya que el drawer tiene su propio header */
}

:deep(.nueva-body) {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}

:deep(.nueva-body)::-webkit-scrollbar {
  width: 6px;
}

:deep(.nueva-body)::-webkit-scrollbar-track {
  background: transparent;
}

:deep(.nueva-body)::-webkit-scrollbar-thumb {
  background: var(--color-border);
  border-radius: 3px;
}

:deep(.nueva-body)::-webkit-scrollbar-thumb:hover {
  background: var(--color-muted-foreground);
}

:deep(.nueva-footer) {
  flex-shrink: 0;
  background: var(--color-background);
  border-top: 1px solid var(--color-border);
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
  transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s ease;
}

.slide-up-enter-from {
  transform: translateY(100%);
  opacity: 0;
}

.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}
</style>

