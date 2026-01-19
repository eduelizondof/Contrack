<template>
  <div class="selector-wrapper" :class="{ 'multiple': multiple, 'disabled': disabled }">
    <div
      class="selector-trigger"
      :class="{ 'open': isOpen, 'has-value': hasValue, 'error': error }"
      @click="toggleDropdown"
      :tabindex="disabled ? -1 : 0"
      @keydown.enter.prevent="toggleDropdown"
      @keydown.escape="closeDropdown"
    >
      <div class="selector-content">
        <div v-if="selectedDisplay" class="selector-selected">
          <template v-if="multiple && selectedValues.length > 0">
            <div class="selected-pills">
              <span
                v-for="(value, index) in selectedValues.slice(0, maxPillsVisible)"
                :key="index"
                class="selected-pill"
              >
                {{ getOptionLabel(value) }}
                <button
                  v-if="!disabled"
                  @click.stop="removeSelection(value)"
                  class="pill-remove"
                  type="button"
                >
                  <XIcon class="pill-icon" />
                </button>
              </span>
              <span v-if="selectedValues.length > maxPillsVisible" class="pill-more">
                +{{ selectedValues.length - maxPillsVisible }}
              </span>
            </div>
          </template>
          <template v-else>
            <span class="selected-text">{{ selectedDisplay }}</span>
          </template>
        </div>
        <span v-else class="selector-placeholder">{{ placeholder }}</span>
      </div>
      <ChevronDownIcon class="selector-icon" :class="{ 'rotated': isOpen }" />
    </div>
    
    <Transition name="dropdown">
      <div v-if="isOpen" class="selector-dropdown" ref="dropdownRef">
        <div class="dropdown-content">
          <div
            v-for="(option, index) in options"
            :key="index"
            class="dropdown-item"
            :class="{
              'selected': isSelected(option.value),
              'disabled': option.disabled,
              'separator': option.separator
            }"
            @click="handleSelect(option)"
          >
            <div v-if="option.separator" class="separator-line"></div>
            <template v-else>
              <div v-if="option.icon" class="option-icon">
                <component :is="option.icon" class="icon" />
              </div>
              <div class="option-content">
                <span class="option-label">{{ option.label }}</span>
                <span v-if="option.description" class="option-description">{{ option.description }}</span>
              </div>
              <div v-if="isSelected(option.value)" class="option-check">
                <div class="check-mark"></div>
              </div>
            </template>
          </div>
          <div v-if="options.length === 0" class="dropdown-empty">
            {{ emptyText }}
          </div>
        </div>
      </div>
    </Transition>
    
    <span v-if="error" class="error-text">{{ error }}</span>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { ChevronDownIcon, XIcon } from '@/components/globales/iconos'

const props = defineProps({
  modelValue: {
    type: [String, Number, Array],
    default: null,
  },
  options: {
    type: Array,
    required: true,
    validator: (options) => {
      return options.every(opt => 
        opt.separator || (opt.value !== undefined && opt.label !== undefined)
      )
    },
  },
  placeholder: {
    type: String,
    default: 'Seleccionar...',
  },
  multiple: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
  emptyText: {
    type: String,
    default: 'No hay opciones disponibles',
  },
  maxPillsVisible: {
    type: Number,
    default: 3,
  },
})

const emit = defineEmits(['update:modelValue', 'change'])

const isOpen = ref(false)
const dropdownRef = ref(null)

const selectedValues = computed(() => {
  if (props.multiple) {
    return Array.isArray(props.modelValue) ? props.modelValue : []
  }
  return props.modelValue !== null && props.modelValue !== undefined ? [props.modelValue] : []
})

const hasValue = computed(() => {
  if (props.multiple) {
    return selectedValues.value.length > 0
  }
  return props.modelValue !== null && props.modelValue !== undefined
})

const selectedDisplay = computed(() => {
  if (props.multiple) {
    if (selectedValues.value.length === 0) return ''
    if (selectedValues.value.length === 1) {
      return getOptionLabel(selectedValues.value[0])
    }
    return `${selectedValues.value.length} seleccionados`
  } else {
    if (props.modelValue === null || props.modelValue === undefined) return ''
    return getOptionLabel(props.modelValue)
  }
})

function getOptionLabel(value) {
  const option = props.options.find(opt => opt.value === value)
  return option ? option.label : String(value)
}

function isSelected(value) {
  if (props.multiple) {
    return selectedValues.value.includes(value)
  }
  return props.modelValue === value
}

function toggleDropdown() {
  if (props.disabled) return
  isOpen.value = !isOpen.value
}

function closeDropdown() {
  isOpen.value = false
}

function handleSelect(option) {
  if (option.disabled || option.separator) return
  
  if (props.multiple) {
    const current = [...selectedValues.value]
    const index = current.indexOf(option.value)
    
    if (index > -1) {
      current.splice(index, 1)
    } else {
      current.push(option.value)
    }
    
    emit('update:modelValue', current)
    emit('change', current)
  } else {
    emit('update:modelValue', option.value)
    emit('change', option.value)
    closeDropdown()
  }
}

function removeSelection(value) {
  if (!props.multiple) return
  
  const current = [...selectedValues.value]
  const index = current.indexOf(value)
  if (index > -1) {
    current.splice(index, 1)
    emit('update:modelValue', current)
    emit('change', current)
  }
}

function handleClickOutside(event) {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    closeDropdown()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
@import '@/assets/styles/components/Selector.css';
</style>
