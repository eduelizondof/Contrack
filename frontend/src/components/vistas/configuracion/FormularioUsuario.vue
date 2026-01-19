<template>
  <form @submit.prevent="handleSubmit" class="formulario-usuario">
      <div class="form-grid">
        <div class="form-group">
          <label for="nombre" class="form-label">
            Nombre completo <span class="required">*</span>
          </label>
          <TextInput
            id="nombre"
            v-model="formulario.name"
            placeholder="Ej: Juan Pérez"
            :error="errores.name"
            required
          />
        </div>
        
        <div class="form-group">
          <label for="email" class="form-label">
            Email <span class="required">*</span>
          </label>
          <TextInput
            id="email"
            v-model="formulario.email"
            type="email"
            placeholder="usuario@empresa.com"
            :error="errores.email"
            required
          />
        </div>
      </div>
      
      <input type="submit" style="display: none;" />
    
    <div v-if="!esEdicion" class="form-grid">
      <div class="form-group">
        <label for="password" class="form-label">
          Contraseña <span class="required">*</span>
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
          Confirmar contraseña <span class="required">*</span>
        </label>
        <PasswordInput
          id="password_confirmation"
          v-model="formulario.password_confirmation"
          placeholder="Repite la contraseña"
          :error="errores.password_confirmation"
          required
        />
      </div>
    </div>
    
    <div v-if="errorGeneral" class="error-general">
      {{ errorGeneral }}
    </div>
    
  </form>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import TextInput from '@/components/globales/inputs/TextInput.vue'
import PasswordInput from '@/components/globales/inputs/PasswordInput.vue'

const props = defineProps({
  usuario: {
    type: Object,
    default: null,
  },
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

const esEdicion = computed(() => !!props.usuario)

const formulario = reactive({
  name: props.usuario?.name || '',
  email: props.usuario?.email || '',
  password: '',
  password_confirmation: '',
})

watch(() => props.usuario, (nuevoUsuario) => {
  if (nuevoUsuario) {
    formulario.name = nuevoUsuario.name || ''
    formulario.email = nuevoUsuario.email || ''
    formulario.password = ''
    formulario.password_confirmation = ''
  }
}, { immediate: true })

function handleSubmit() {
  const datos = { ...formulario }
  
  // Si es edición y no hay contraseña, no enviarla
  if (esEdicion.value && !datos.password) {
    delete datos.password
    delete datos.password_confirmation
  }
  
  emit('submit', datos)
}

function getFormData() {
  const datos = { ...formulario }
  
  // Si es edición y no hay contraseña, no enviarla
  if (esEdicion.value && !datos.password) {
    delete datos.password
    delete datos.password_confirmation
  }
  
  return datos
}

function reset() {
  formulario.name = props.usuario?.name || ''
  formulario.email = props.usuario?.email || ''
  formulario.password = ''
  formulario.password_confirmation = ''
}

defineExpose({
  getFormData,
  reset,
})
</script>

<style scoped>
.formulario-usuario {
  width: 100%;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

@media (min-width: 768px) {
  .form-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
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
  gap: 1rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--color-border);
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

.btn-cancelar:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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

/* Responsive */
@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .form-acciones {
    flex-direction: column-reverse;
  }
  
  .btn-cancelar,
  .btn-guardar {
    width: 100%;
  }
}
</style>
