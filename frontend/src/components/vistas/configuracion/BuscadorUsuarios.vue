<template>
  <div class="buscador-usuarios">
    <div class="buscador-container">
      <div class="input-wrapper">
        <SearchIcon class="search-icon" />
        <input
          v-model="busquedaLocal"
          type="text"
          placeholder="Buscar por nombre o email..."
          class="search-input"
          @input="handleBuscar"
        />
        <button
          v-if="busquedaLocal"
          @click="limpiarBusqueda"
          class="clear-button"
          aria-label="Limpiar búsqueda"
        >
          <XIcon class="clear-icon" />
        </button>
      </div>
    </div>
    
    <div class="filtros-container">
      <select
        v-model="rolFiltroLocal"
        @change="handleFiltroRol"
        class="filtro-select"
      >
        <option value="">Todos los roles</option>
        <option
          v-for="rol in roles"
          :key="rol.id"
          :value="rol.id"
        >
          {{ rol.nombre }}
        </option>
      </select>
      
      <button
        @click="toggleActivos"
        class="filtro-toggle"
        :class="{ 'active': soloActivos }"
      >
        <span class="toggle-label">Solo activos</span>
        <div class="toggle-switch" :class="{ 'on': soloActivos }">
          <span class="toggle-slider"></span>
        </div>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { SearchIcon, XIcon } from '@/components/globales/iconos'
import usuariosService from '@/services/usuarios'

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  rolFiltro: {
    type: [String, Number],
    default: null,
  },
  soloActivos: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'buscar', 'filtro-rol', 'toggle-activos'])

const busquedaLocal = ref(props.modelValue)
const rolFiltroLocal = ref(props.rolFiltro)
const roles = ref([])
let debounceTimer = null

// Cargar roles
async function cargarRoles() {
  try {
    const rolesData = await usuariosService.obtenerRoles()
    roles.value = rolesData
  } catch (error) {
    console.error('Error al cargar roles:', error)
  }
}

function handleBuscar() {
  // Limpiar timer anterior si existe
  if (debounceTimer) {
    clearTimeout(debounceTimer)
  }

  // Si la búsqueda está vacía, buscar inmediatamente para limpiar filtros
  if (!busquedaLocal.value || busquedaLocal.value.trim() === '') {
    emit('update:modelValue', busquedaLocal.value)
    emit('buscar', busquedaLocal.value)
    return
  }

  // Validar mínimo 3 caracteres
  if (busquedaLocal.value.trim().length < 3) {
    // No buscar, solo actualizar el valor del modelo
    emit('update:modelValue', busquedaLocal.value)
    return
  }

  // Debounce de 500ms para búsquedas con 3 o más caracteres
  debounceTimer = setTimeout(() => {
    emit('update:modelValue', busquedaLocal.value)
    emit('buscar', busquedaLocal.value)
  }, 500)
}

function limpiarBusqueda() {
  busquedaLocal.value = ''
  // Limpiar timer si existe
  if (debounceTimer) {
    clearTimeout(debounceTimer)
  }
  emit('update:modelValue', '')
  emit('buscar', '')
}

function handleFiltroRol() {
  emit('filtro-rol', rolFiltroLocal.value)
}

function toggleActivos() {
  emit('toggle-activos', !props.soloActivos)
}

watch(() => props.modelValue, (newValue) => {
  busquedaLocal.value = newValue
})

watch(() => props.rolFiltro, (newValue) => {
  rolFiltroLocal.value = newValue
})

cargarRoles()
</script>

<style scoped>
.buscador-usuarios {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.buscador-container {
  width: 100%;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 1rem;
  width: 20px;
  height: 20px;
  color: var(--color-text-muted);
  pointer-events: none;
  z-index: 1;
}

.search-input {
  width: 100%;
  padding: 0.875rem 1rem 0.875rem 3rem;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  background: var(--color-surface-bg);
  color: var(--color-text);
  font-size: 0.95rem;
  transition: all 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-input::placeholder {
  color: var(--color-text-muted);
}

.clear-button {
  position: absolute;
  right: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  color: var(--color-text-muted);
  cursor: pointer;
  border-radius: 8px;
  transition: all 0.2s;
}

.clear-button:hover {
  background: var(--color-background-mute);
  color: var(--color-text);
}

.clear-icon {
  width: 18px;
  height: 18px;
}

.filtros-container {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  align-items: center;
}

.filtro-select {
  padding: 0.75rem 1rem;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  background: var(--color-surface-bg);
  color: var(--color-text);
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
  min-width: 160px;
}

.filtro-select:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filtro-toggle {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  background: var(--color-surface-bg);
  cursor: pointer;
  transition: all 0.2s;
}

.filtro-toggle:hover {
  border-color: var(--color-primary);
}

.filtro-toggle.active {
  border-color: var(--color-primary);
  background: rgba(59, 130, 246, 0.1);
}

.toggle-label {
  font-size: 0.9rem;
  color: var(--color-text);
  font-weight: 500;
}

.toggle-switch {
  width: 44px;
  height: 24px;
  border-radius: 12px;
  background: var(--color-border);
  position: relative;
  transition: background 0.2s;
}

.toggle-switch.on {
  background: var(--color-primary);
}

.toggle-slider {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: white;
  transition: transform 0.2s;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.toggle-switch.on .toggle-slider {
  transform: translateX(20px);
}

/* Dark mode */
html.dark .search-input {
  background: var(--color-surface-bg);
}

html.dark .filtro-select {
  background: var(--color-surface-bg);
}

html.dark .filtro-toggle {
  background: var(--color-surface-bg);
}

@media (max-width: 640px) {
  .filtros-container {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filtro-select {
    width: 100%;
  }
  
  .filtro-toggle {
    width: 100%;
    justify-content: space-between;
  }
}
</style>
