<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="visible"
        class="notificacion-toast"
      >
        <div class="toast-content">
          {{ mensaje }}
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { watch } from 'vue'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  mensaje: {
    type: String,
    default: ''
  },
  duracion: {
    type: Number,
    default: 2000
  }
})

const emit = defineEmits(['update:visible'])

// Auto-ocultar después de la duración
watch(() => props.visible, (nuevoValor) => {
  if (nuevoValor && props.duracion > 0) {
    setTimeout(() => {
      emit('update:visible', false)
    }, props.duracion)
  }
})
</script>

<style scoped>
.notificacion-toast {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 9999;
  pointer-events: none;
}

.toast-content {
  padding: 12px 24px;
  background: rgba(0, 0, 0, 0.75);
  color: white;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 500;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  white-space: nowrap;
}

html.dark .toast-content {
  background: rgba(255, 255, 255, 0.9);
  color: var(--color-foreground);
}

/* Transiciones */
.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: translate(-50%, -50%) scale(0.9);
}

.fade-leave-to {
  opacity: 0;
  transform: translate(-50%, -50%) scale(0.9);
}
</style>
