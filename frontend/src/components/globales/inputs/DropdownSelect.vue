<template>
  <div class="dropdown-select-wrapper" :class="{ 'error': error, 'open': isOpen, 'multiple': multiple }">
    <label v-if="label" :for="inputId" class="dropdown-label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>
    
    <div 
      ref="dropdownRef"
      class="dropdown-select"
      :class="{ 'error': error, 'disabled': disabled }"
      @click="toggleDropdown"
      @keydown.enter.prevent="toggleDropdown"
      @keydown.escape="closeDropdown"
      @keydown.arrow-down.prevent="handleArrowDown"
      @keydown.arrow-up.prevent="handleArrowUp"
      tabindex="0"
      role="combobox"
      :aria-expanded="isOpen"
      :aria-haspopup="true"
      :aria-label="label || 'Seleccionar opción'"
    >
      <!-- Input display area -->
      <div class="dropdown-display">
        <!-- Multiple selection: show pills -->
        <template v-if="multiple">
          <div v-if="selectedValues.length === 0" class="dropdown-placeholder">
            {{ placeholder || 'Seleccione opciones' }}
          </div>
          <div v-else class="dropdown-pills">
            <span
              v-for="value in selectedValues"
              :key="value"
              class="dropdown-pill"
            >
              {{ getOptionLabel(value) }}
              <button
                type="button"
                @click.stop="removeSelection(value)"
                class="pill-remove"
                :aria-label="`Eliminar ${getOptionLabel(value)}`"
              >
                <XIcon class="icon" />
              </button>
            </span>
          </div>
        </template>
        
        <!-- Single selection: show selected value or placeholder -->
        <template v-else>
          <span v-if="selectedOption" class="dropdown-value">
            {{ getOptionLabel(selectedOption) }}
          </span>
          <span v-else class="dropdown-placeholder">
            {{ placeholder || 'Seleccione una opción' }}
          </span>
        </template>
      </div>
      
      <!-- Chevron icon -->
      <ChevronDownIcon 
        class="dropdown-chevron"
        :class="{ 'rotated': isOpen }"
      />
    </div>
    
    <!-- Dropdown menu -->
    <Transition name="dropdown">
      <div
        v-if="isOpen"
        ref="dropdownMenuRef"
        class="dropdown-menu"
        @click.stop
      >
        <div
          v-if="searchable"
          class="dropdown-search"
        >
          <input
            ref="searchInputRef"
            v-model="searchQuery"
            type="text"
            class="dropdown-search-input"
            placeholder="Buscar..."
            @click.stop
            @keydown.escape="closeDropdown"
          />
        </div>
        
        <div class="dropdown-options" role="listbox">
          <div
            v-for="(option, index) in filteredOptions"
            :key="getOptionValue(option)"
            :ref="el => optionRefs[index] = el"
            class="dropdown-option"
            :class="{
              'selected': isSelected(getOptionValue(option)),
              'highlighted': highlightedIndex === index
            }"
            @click="selectOption(option)"
            @mouseenter="highlightedIndex = index"
            role="option"
            :aria-selected="isSelected(getOptionValue(option))"
          >
            <input
              v-if="multiple"
              type="checkbox"
              :checked="isSelected(getOptionValue(option))"
              class="dropdown-checkbox"
              @click.stop
              @change="toggleOption(option)"
            />
            <span class="dropdown-option-label">{{ getOptionLabel(option) }}</span>
          </div>
          
          <div v-if="filteredOptions.length === 0" class="dropdown-no-results">
            No se encontraron opciones
          </div>
        </div>
      </div>
    </Transition>
    
    <!-- Error message -->
    <p v-if="error" class="error-mensaje">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import ChevronDownIcon from '@/components/globales/iconos/ChevronDownIcon.vue'
import XIcon from '@/components/globales/iconos/XIcon.vue'

const props = defineProps({
  modelValue: {
    type: [String, Number, Array],
    default: null,
  },
  options: {
    type: Array,
    required: true,
  },
  optionLabel: {
    type: [String, Function],
    default: 'label',
  },
  optionValue: {
    type: [String, Function],
    default: 'value',
  },
  placeholder: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  multiple: {
    type: Boolean,
    default: false,
  },
  searchable: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  error: {
    type: [String, Array],
    default: null,
  },
})

const emit = defineEmits(['update:modelValue', 'change'])

const isOpen = ref(false)
const searchQuery = ref('')
const highlightedIndex = ref(-1)
const optionRefs = ref([])

const dropdownRef = ref(null)
const dropdownMenuRef = ref(null)
const searchInputRef = ref(null)

const inputId = computed(() => `dropdown-${Math.random().toString(36).substr(2, 9)}`)

// Computed values
const selectedValues = computed(() => {
  if (props.multiple) {
    return Array.isArray(props.modelValue) ? props.modelValue : []
  }
  return props.modelValue !== null && props.modelValue !== '' ? [props.modelValue] : []
})

const selectedOption = computed(() => {
  if (props.multiple || !props.modelValue) return null
  return props.options.find(opt => getOptionValue(opt) === props.modelValue) || null
})

const filteredOptions = computed(() => {
  if (!props.searchable || !searchQuery.value) {
    return props.options
  }
  
  const query = searchQuery.value.toLowerCase()
  return props.options.filter(option => {
    const label = getOptionLabel(option).toLowerCase()
    return label.includes(query)
  })
})

// Helper functions
function getOptionLabel(option) {
  if (typeof props.optionLabel === 'function') {
    return props.optionLabel(option)
  }
  return option?.[props.optionLabel] ?? option
}

function getOptionValue(option) {
  if (typeof props.optionValue === 'function') {
    return props.optionValue(option)
  }
  return option?.[props.optionValue] ?? option
}

function isSelected(value) {
  if (props.multiple) {
    return selectedValues.value.includes(value)
  }
  return props.modelValue === value
}

// Dropdown control
function toggleDropdown() {
  if (props.disabled) return
  
  if (isOpen.value) {
    closeDropdown()
  } else {
    openDropdown()
  }
}

function openDropdown() {
  if (props.disabled) return
  isOpen.value = true
  searchQuery.value = ''
  highlightedIndex.value = -1
  
  nextTick(() => {
    if (props.searchable && searchInputRef.value) {
      searchInputRef.value.focus()
    }
  })
}

function closeDropdown() {
  isOpen.value = false
  searchQuery.value = ''
  highlightedIndex.value = -1
}

// Selection
function selectOption(option) {
  if (props.disabled) return
  
  const value = getOptionValue(option)
  
  if (props.multiple) {
    toggleOption(option)
  } else {
    const oldValue = props.modelValue
    emit('update:modelValue', value)
    if (oldValue !== value) {
      emit('change', value)
    }
    closeDropdown()
  }
}

function toggleOption(option) {
  if (props.disabled) return
  
  const value = getOptionValue(option)
  const currentValues = [...selectedValues.value]
  const oldValues = [...currentValues]
  
  if (isSelected(value)) {
    const index = currentValues.indexOf(value)
    currentValues.splice(index, 1)
  } else {
    currentValues.push(value)
  }
  
  emit('update:modelValue', currentValues)
  if (JSON.stringify(oldValues) !== JSON.stringify(currentValues)) {
    emit('change', currentValues)
  }
}

function removeSelection(value) {
  if (props.disabled) return
  
  const currentValues = [...selectedValues.value]
  const index = currentValues.indexOf(value)
  if (index > -1) {
    currentValues.splice(index, 1)
    emit('update:modelValue', currentValues)
    emit('change', currentValues)
  }
}

// Keyboard navigation
function handleArrowDown() {
  if (!isOpen.value) {
    openDropdown()
    return
  }
  
  if (highlightedIndex.value < filteredOptions.value.length - 1) {
    highlightedIndex.value++
    scrollToHighlighted()
  }
}

function handleArrowUp() {
  if (!isOpen.value) return
  
  if (highlightedIndex.value > 0) {
    highlightedIndex.value--
    scrollToHighlighted()
  }
}

function scrollToHighlighted() {
  nextTick(() => {
    const element = optionRefs.value[highlightedIndex.value]
    if (element && dropdownMenuRef.value) {
      element.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
    }
  })
}

// Click outside handler
function handleClickOutside(event) {
  if (
    dropdownRef.value &&
    !dropdownRef.value.contains(event.target) &&
    dropdownMenuRef.value &&
    !dropdownMenuRef.value.contains(event.target)
  ) {
    closeDropdown()
  }
}

// Watch for Enter key on highlighted option
watch(highlightedIndex, (newIndex) => {
  if (newIndex >= 0 && newIndex < filteredOptions.value.length) {
    // Auto-select on Enter when highlighted
    const handleEnter = (e) => {
      if (e.key === 'Enter' && isOpen.value) {
        e.preventDefault()
        selectOption(filteredOptions.value[newIndex])
      }
    }
    document.addEventListener('keydown', handleEnter)
    return () => document.removeEventListener('keydown', handleEnter)
  }
})

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
@import '@/assets/styles/components/DropdownSelect.css';
</style>
