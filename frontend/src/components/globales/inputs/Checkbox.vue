<template>
  <label class="checkbox-wrapper" :class="{ disabled: disabled }">
    <input
      :id="id"
      v-model="checked"
      type="checkbox"
      :disabled="disabled"
      class="checkbox-input"
      @change="$emit('update:modelValue', checked)"
    />
    <span class="checkbox-custom" :class="{ checked: checked, disabled: disabled }">
      <svg v-if="checked" class="checkbox-checkmark" viewBox="0 0 20 20" fill="none">
        <path
          d="M16.7071 5.29289C17.0976 5.68342 17.0976 6.31658 16.7071 6.70711L8.70711 14.7071C8.31658 15.0976 7.68342 15.0976 7.29289 14.7071L3.29289 10.7071C2.90237 10.3166 2.90237 9.68342 3.29289 9.29289C3.68342 8.90237 4.31658 8.90237 4.70711 9.29289L8 12.5858L15.2929 5.29289C15.6834 4.90237 16.3166 4.90237 16.7071 5.29289Z"
          fill="currentColor"
        />
      </svg>
    </span>
    <span v-if="$slots.default || label" class="checkbox-label">
      <slot>{{ label }}</slot>
    </span>
  </label>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  id: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue'])

const checked = ref(props.modelValue)

watch(() => props.modelValue, (newValue) => {
  checked.value = newValue
})

watch(checked, (newValue) => {
  emit('update:modelValue', newValue)
})
</script>

<style scoped>
.checkbox-wrapper {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  user-select: none;
}

.checkbox-wrapper.disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.checkbox-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
  pointer-events: none;
}

.checkbox-custom {
  position: relative;
  width: 28px;
  height: 28px;
  min-width: 28px;
  min-height: 28px;
  border: 2px solid #cbd5e0;
  border-radius: 6px;
  background: white;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.checkbox-custom:hover:not(.disabled) {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.checkbox-custom.checked {
  background: #667eea;
  border-color: #667eea;
}

.checkbox-custom.checked:hover:not(.disabled) {
  background: #5568d3;
  border-color: #5568d3;
}

.checkbox-custom.disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.checkbox-checkmark {
  width: 14px;
  height: 14px;
  color: white;
  stroke-width: 2;
}

.checkbox-label {
  font-size: 1rem;
  color: #4a5568;
  line-height: 1.5;
}

/* Dark mode */
.dark .checkbox-custom {
  background: #2d3748;
  border-color: #4a5568;
}

.dark .checkbox-custom:hover:not(.disabled) {
  border-color: #667eea;
}

.dark .checkbox-custom.checked {
  background: #667eea;
  border-color: #667eea;
}

.dark .checkbox-label {
  color: #cbd5e0;
}
</style>
