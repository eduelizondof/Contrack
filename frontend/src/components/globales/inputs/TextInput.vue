<template>
  <div class="text-input-wrapper">
    <div class="input-container" :class="{ 'has-error': error, 'disabled': disabled }">
      <div class="input-icon" v-if="icon">
        <component :is="icon" class="icon" />
      </div>
      <input
        :id="id"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :class="['text-input', { 'has-icon': icon, 'has-right-icon': rightIcon }]"
        @input="$emit('update:modelValue', $event.target.value)"
        @blur="$emit('blur', $event)"
        @focus="$emit('focus', $event)"
      />
      <div class="input-right-icon" v-if="rightIcon" @click="$emit('rightIconClick')">
        <component :is="rightIcon" class="icon" />
      </div>
    </div>
    <span v-if="error" class="error-text">{{ error }}</span>
  </div>
</template>

<script setup>
defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  id: {
    type: String,
    default: '',
  },
  type: {
    type: String,
    default: 'text',
  },
  placeholder: {
    type: String,
    default: '',
  },
  icon: {
    type: [Object, String],
    default: null,
  },
  rightIcon: {
    type: [Object, String],
    default: null,
  },
  error: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['update:modelValue', 'blur', 'focus', 'rightIconClick'])
</script>

<style scoped>
.text-input-wrapper {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.input-container {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
  background: white;
  border: 1px solid #E5E7EB;
  border-radius: 12px;
  transition: all 0.2s;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.input-container:focus-within {
  border-color: #3B82F6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
}

.input-container.has-error {
  border-color: #e53e3e;
}

.input-container.disabled {
  background: #f7fafc;
  cursor: not-allowed;
  opacity: 0.6;
}

.input-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  padding-left: 1rem;
  padding-right: 0.75rem;
  color: #9CA3AF;
  transition: color 0.2s;
  flex-shrink: 0;
}

.input-container:focus-within .input-icon {
  color: #3B82F6;
}

.input-icon .icon {
  width: 20px;
  height: 20px;
}

.text-input {
  flex: 1;
  padding: 0.875rem 1rem;
  border: none;
  outline: none;
  background: transparent;
  font-size: 1rem;
  color: #2d3748;
  width: 100%;
}

.text-input.has-icon {
  padding-left: 0.75rem;
}

.text-input.has-right-icon {
  padding-right: 3rem; /* Más espacio para el icono derecho y autocompletado del navegador */
}

.text-input::placeholder {
  color: #9CA3AF;
}

.text-input:disabled {
  cursor: not-allowed;
}

.input-right-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  padding-right: 1rem;
  padding-left: 0.5rem; /* Espacio adicional a la izquierda del icono */
  color: #9CA3AF;
  cursor: pointer;
  transition: color 0.2s;
  flex-shrink: 0; /* Evitar que se comprima */
  z-index: 1; /* Asegurar que esté sobre el autocompletado */
}

.input-right-icon:hover {
  color: #6B7280;
}

.dark .input-right-icon:hover {
  color: #D1D5DB;
}

.input-right-icon .icon {
  width: 20px;
  height: 20px;
}

.error-text {
  font-size: 0.875rem;
  color: #e53e3e;
  padding-left: 0.5rem;
}

/* Dark mode */
.dark .input-container {
  background: #151725;
  border-color: #2A2D3E;
}

.dark .text-input {
  color: #FFFFFF;
}

.dark .text-input::placeholder {
  color: #9CA3AF;
}

.dark .input-container:focus-within {
  border-color: #3B82F6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
}

.dark .input-container.disabled {
  background: #0B0C15;
}

.dark .input-icon {
  color: #6B7280;
}

.dark .input-container:focus-within .input-icon {
  color: #3B82F6;
}

.dark .input-right-icon {
  color: #6B7280;
}
</style>
