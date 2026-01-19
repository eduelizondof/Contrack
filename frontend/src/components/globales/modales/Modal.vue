<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="modal-overlay" @click.self="handleCerrar">
        <div 
          class="modal-content" 
          :class="[`modal-${tamanio}`, { 'modal-fullscreen': fullscreen }]"
          @click.stop
        >
          <div class="modal-header">
            <h2 class="modal-title">{{ titulo }}</h2>
            <button
              v-if="mostrarCerrar"
              @click="handleCerrar"
              class="modal-close"
              aria-label="Cerrar"
            >
              <XIcon class="icon" />
            </button>
          </div>

          <div class="modal-body">
            <slot />
          </div>

          <div v-if="$slots.footer" class="modal-footer">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import XIcon from '@/components/globales/iconos/XIcon.vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  titulo: {
    type: String,
    default: '',
  },
  tamanio: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value),
  },
  fullscreen: {
    type: Boolean,
    default: false,
  },
  mostrarCerrar: {
    type: Boolean,
    default: true,
  },
  cerrarAlClickOverlay: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(['update:modelValue', 'cerrar'])

function handleCerrar() {
  if (props.cerrarAlClickOverlay) {
    emit('update:modelValue', false)
    emit('cerrar')
  }
}
</script>

<style scoped>
@import '@/assets/styles/components/Modal.css';
</style>
