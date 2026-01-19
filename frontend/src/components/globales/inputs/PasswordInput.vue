<template>
  <TextInput
    :id="id"
    :model-value="modelValue"
    :type="showPassword ? 'text' : 'password'"
    :placeholder="placeholder"
    :icon="icon"
    :right-icon="showPassword ? EyeOffIcon : EyeIcon"
    :error="error"
    :disabled="disabled"
    :required="required"
    @update:model-value="$emit('update:modelValue', $event)"
    @right-icon-click="togglePassword"
    @blur="$emit('blur', $event)"
    @focus="$emit('focus', $event)"
  />
</template>

<script setup>
import { ref } from 'vue'
import TextInput from './TextInput.vue'
import EyeIcon from '@/components/globales/iconos/EyeIcon.vue'
import EyeOffIcon from '@/components/globales/iconos/EyeOffIcon.vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  id: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Password',
  },
  icon: {
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

defineEmits(['update:modelValue', 'blur', 'focus'])

const showPassword = ref(false)

const togglePassword = () => {
  if (!props.disabled) {
    showPassword.value = !showPassword.value
  }
}
</script>
