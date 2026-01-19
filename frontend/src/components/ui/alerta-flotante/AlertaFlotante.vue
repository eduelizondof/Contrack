<template>
  <Teleport to="body">
    <Transition name="slide-up">
      <div
        v-if="isOpen"
        class="alerta-flotante-overlay"
        @click.self="cerrar"
      >
        <div
          class="alerta-flotante"
          :class="[`alerta-flotante-${type}`]"
        >
          <!-- Icono -->
          <div
            v-if="iconoComponente"
            class="alerta-flotante-icono"
            :class="[`alerta-flotante-icono-${type}`]"
          >
            <component :is="iconoComponente" class="w-5 h-5" />
          </div>

          <!-- Contenido -->
          <div class="alerta-flotante-contenido">
            <h3 v-if="title" class="alerta-flotante-titulo">
              {{ title }}
            </h3>
            <p v-if="message" class="alerta-flotante-mensaje">
              {{ message }}
            </p>
          </div>

          <!-- Acciones -->
          <div class="alerta-flotante-acciones">
            <button
              class="alerta-flotante-btn alerta-flotante-btn-cancelar"
              :disabled="loading"
              @click="cerrar"
            >
              Cancelar
            </button>
            <button
              class="alerta-flotante-btn alerta-flotante-btn-confirmar"
              :class="[`alerta-flotante-btn-${type}`]"
              :disabled="loading"
              @click="confirmar"
            >
              <span v-if="loading" class="alerta-flotante-spinner"></span>
              <span v-else>{{ confirmText || 'Confirmar' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import AlertCircleIcon from '@/components/globales/iconos/AlertCircleIcon.vue'
import AlertTriangleIcon from '@/components/globales/iconos/AlertTriangleIcon.vue'
import InfoCircleIcon from '@/components/globales/iconos/InfoCircleIcon.vue'
import CheckCircleIcon from '@/components/globales/iconos/CheckCircleIcon.vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  type: {
    type: String,
    default: 'error',
    validator: (value) => ['error', 'warning', 'info', 'success'].includes(value)
  },
  title: {
    type: String,
    default: ''
  },
  message: {
    type: String,
    default: ''
  },
  confirmText: {
    type: String,
    default: 'Confirmar'
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:isOpen', 'confirm'])

const iconoComponente = computed(() => {
  const iconos = {
    error: AlertCircleIcon,
    warning: AlertTriangleIcon,
    info: InfoCircleIcon,
    success: CheckCircleIcon
  }
  return iconos[props.type] || AlertCircleIcon
})

function cerrar() {
  if (!props.loading) {
    emit('update:isOpen', false)
  }
}

function confirmar() {
  if (!props.loading) {
    emit('confirm')
  }
}
</script>

<style scoped>
.alerta-flotante-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
}

.alerta-flotante {
  background: var(--color-background);
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  max-width: 90vw;
  width: 100%;
  max-width: 400px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  border: 1px solid var(--color-border);
}

.alerta-flotante-icono {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.alerta-flotante-icono-error {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.alerta-flotante-icono-warning {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.alerta-flotante-icono-info {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.alerta-flotante-icono-success {
  background: rgba(34, 197, 94, 0.1);
  color: #22c55e;
}

.alerta-flotante-contenido {
  flex: 1;
  min-width: 0;
}

.alerta-flotante-titulo {
  margin: 0 0 8px 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-heading);
  line-height: 1.4;
}

.alerta-flotante-mensaje {
  margin: 0;
  font-size: 0.9375rem;
  color: var(--color-text);
  line-height: 1.5;
}

.alerta-flotante-acciones {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 4px;
}

.alerta-flotante-btn {
  padding: 10px 20px;
  border-radius: 8px;
  border: none;
  font-size: 0.9375rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-width: 100px;
}

.alerta-flotante-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.alerta-flotante-btn-cancelar {
  background: var(--color-background-mute);
  color: var(--color-text);
}

.alerta-flotante-btn-cancelar:hover:not(:disabled) {
  background: var(--color-background-soft);
}

.alerta-flotante-btn-confirmar {
  color: white;
}

.alerta-flotante-btn-error {
  background: #ef4444;
}

.alerta-flotante-btn-error:hover:not(:disabled) {
  background: #dc2626;
}

.alerta-flotante-btn-warning {
  background: #f59e0b;
}

.alerta-flotante-btn-warning:hover:not(:disabled) {
  background: #d97706;
}

.alerta-flotante-btn-info {
  background: #3b82f6;
}

.alerta-flotante-btn-info:hover:not(:disabled) {
  background: #2563eb;
}

.alerta-flotante-btn-success {
  background: #22c55e;
}

.alerta-flotante-btn-success:hover:not(:disabled) {
  background: #16a34a;
}

.alerta-flotante-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Transiciones */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-up-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}

.slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}
</style>
