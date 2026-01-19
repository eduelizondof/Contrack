<template>
  <div class="zona-peligro">
    <p class="peligro-texto">
      <slot name="mensaje">
        {{ mensaje }}
      </slot>
    </p>
    <button
      @click="abrirConfirmacion"
      class="btn-eliminar"
      :disabled="eliminando || deshabilitado"
    >
      <TrashIcon class="icon" />
      <span>{{ eliminando ? textoEliminando : textoBoton }}</span>
    </button>
    
    <ModalConfirmacion
      v-if="mostrarConfirmacion"
      :titulo="tituloConfirmacion"
      :mensaje="mensajeConfirmacion"
      :texto-confirmar="textoConfirmarModal"
      :texto-confirmando-procesando="textoEliminando"
      :procesando="eliminando"
      :peligro="true"
      @confirmar="handleConfirmar"
      @cerrar="cerrarConfirmacion"
    >
      <slot name="confirmacion">{{ mensajeConfirmacion }}</slot>
    </ModalConfirmacion>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { TrashIcon } from '@/components/globales/iconos'
import ModalConfirmacion from '@/components/globales/modales/ModalConfirmacion.vue'

const props = defineProps({
  mensaje: {
    type: String,
    default: 'Al eliminar este elemento, se eliminará permanentemente y no se podrá recuperar.',
  },
  textoBoton: {
    type: String,
    default: 'Eliminar',
  },
  textoEliminando: {
    type: String,
    default: 'Eliminando...',
  },
  tituloConfirmacion: {
    type: String,
    default: '¿Eliminar elemento?',
  },
  mensajeConfirmacion: {
    type: String,
    default: 'Esta acción no se puede deshacer.',
  },
  textoConfirmarModal: {
    type: String,
    default: 'Eliminar',
  },
  deshabilitado: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['eliminar'])

const mostrarConfirmacion = ref(false)
const eliminando = ref(false)

function abrirConfirmacion() {
  mostrarConfirmacion.value = true
}

function cerrarConfirmacion() {
  mostrarConfirmacion.value = false
}

async function handleConfirmar() {
  eliminando.value = true
  emit('eliminar')
}

// Exponer funciones para control externo
defineExpose({
  cerrar: cerrarConfirmacion,
  finalizarEliminacion: () => {
    eliminando.value = false
    mostrarConfirmacion.value = false
  },
  setEliminando: (valor) => {
    eliminando.value = valor
  },
})
</script>

<style scoped>
.zona-peligro {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  padding: 1rem 0;
}

.peligro-texto {
  color: var(--color-text-muted);
  font-size: 0.9rem;
  line-height: 1.6;
  margin: 0;
}

.btn-eliminar {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 12px;
  background: #ef4444;
  color: #ffffff;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  max-width: 300px;
}

.btn-eliminar:hover:not(:disabled) {
  background: #dc2626;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-eliminar:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-eliminar .icon {
  width: 18px;
  height: 18px;
}

@media (max-width: 640px) {
  .btn-eliminar {
    width: 100%;
    max-width: none;
  }
}
</style>
