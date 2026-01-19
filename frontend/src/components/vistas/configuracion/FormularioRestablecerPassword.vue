<template>
  <form @submit.prevent="handleSubmit" class="formulario-restablecer-password">
    <div class="form-group">
      <label for="password" class="form-label">
        Nueva Contraseña <span class="required">*</span>
      </label>
      <PasswordInput
        id="password"
        v-model="formulario.password"
        placeholder="Mínimo 8 caracteres"
        :error="errores.password"
        required
      />
    </div>
    
    <div class="form-group">
      <label for="password_confirmation" class="form-label">
        Confirmar Contraseña <span class="required">*</span>
      </label>
      <PasswordInput
        id="password_confirmation"
        v-model="formulario.password_confirmation"
        placeholder="Repite la contraseña"
        :error="errores.password_confirmation"
        required
      />
    </div>
    
    <div v-if="errorGeneral" class="error-general">
      {{ errorGeneral }}
    </div>
    
    <div class="form-acciones">
      <button
        type="submit"
        class="btn-guardar"
        :disabled="guardando"
      >
        <span v-if="guardando" class="btn-loading">
          <span class="spinner"></span>
          Restableciendo...
        </span>
        <span v-else>Restablecer Contraseña</span>
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, watch } from 'vue'
import PasswordInput from '@/components/globales/inputs/PasswordInput.vue'

const props = defineProps({
  guardando: {
    type: Boolean,
    default: false,
  },
  errores: {
    type: Object,
    default: () => ({}),
  },
  errorGeneral: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['submit'])

const formulario = reactive({
  password: '',
  password_confirmation: '',
})

function handleSubmit() {
  emit('submit', { ...formulario })
}

function reset() {
  formulario.password = ''
  formulario.password_confirmation = ''
}

defineExpose({
  reset,
})
</script>

<style scoped>
.formulario-restablecer-password {
  width: 100%;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-heading);
}

.required {
  color: #ef4444;
}

.error-general {
  padding: 1rem;
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  border-radius: 12px;
  font-size: 0.875rem;
  margin-bottom: 1.5rem;
}

.form-acciones {
  display: flex;
  justify-content: flex-end;
  padding-top: 1.5rem;
  border-top: 1px solid var(--color-border);
}

.btn-guardar {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 12px;
  background: var(--color-primary);
  color: white;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-guardar:hover:not(:disabled) {
  background: var(--color-primary-hover);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-guardar:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-loading {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 640px) {
  .form-acciones {
    justify-content: stretch;
  }
  
  .btn-guardar {
    width: 100%;
  }
}
</style>
