<template>
  <div class="modal-overlay" @click.self="$emit('cerrar')">
    <div class="modal-content modal-confirmacion">
      <h3>{{ titulo }}</h3>
      <p><slot>{{ mensaje }}</slot></p>
      <div class="modal-acciones">
        <button @click="$emit('cerrar')" class="btn-cancelar" :disabled="procesando">
          {{ textoCancelar }}
        </button>
        <button 
          @click="$emit('confirmar')" 
          class="btn-confirmar"
          :class="{ 'btn-peligro': peligro }"
          :disabled="procesando"
        >
          {{ procesando ? textoConfirmandoProcesando : textoConfirmar }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  titulo: {
    type: String,
    default: '¿Estás seguro?',
  },
  mensaje: {
    type: String,
    default: 'Esta acción no se puede deshacer.',
  },
  textoConfirmar: {
    type: String,
    default: 'Confirmar',
  },
  textoConfirmandoProcesando: {
    type: String,
    default: 'Procesando...',
  },
  textoCancelar: {
    type: String,
    default: 'Cancelar',
  },
  procesando: {
    type: Boolean,
    default: false,
  },
  peligro: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['confirmar', 'cerrar'])
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
  backdrop-filter: blur(4px);
}

.modal-content {
  background: var(--color-surface-bg);
  border-radius: 16px;
  width: 100%;
  max-width: 450px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-confirmacion {
  padding: 1.5rem;
}

.modal-confirmacion h3 {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-heading);
  margin: 0 0 1rem 0;
}

.modal-confirmacion p {
  color: var(--color-text);
  margin: 0 0 1.5rem 0;
  line-height: 1.6;
}

.modal-acciones {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.btn-cancelar {
  padding: 0.75rem 1.5rem;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  background: var(--color-surface-bg);
  color: var(--color-text);
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cancelar:hover:not(:disabled) {
  border-color: var(--color-text-muted);
  background: var(--color-background-mute);
}

.btn-confirmar {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 12px;
  background: var(--color-primary);
  color: #ffffff;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-confirmar:hover:not(:disabled) {
  background: var(--color-primary-hover);
  transform: translateY(-1px);
}

.btn-confirmar.btn-peligro {
  background: #ef4444;
}

.btn-confirmar.btn-peligro:hover:not(:disabled) {
  background: #dc2626;
}

.btn-cancelar:disabled,
.btn-confirmar:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 640px) {
  .modal-acciones {
    flex-direction: column-reverse;
  }
  
  .btn-cancelar,
  .btn-confirmar {
    width: 100%;
  }
}

html.dark .modal-overlay {
  background: rgba(0, 0, 0, 0.7);
}

html.dark .modal-content {
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}
</style>
