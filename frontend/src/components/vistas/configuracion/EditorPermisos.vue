<template>
  <div class="editor-permisos">
    <div v-if="cargando || cargandoLocal" class="cargando">
      <p>Cargando permisos...</p>
    </div>
    
    <div v-else class="categorias-lista">
      <div 
        v-for="categoria in categorias" 
        :key="categoria.modulo"
        class="categoria-item"
      >
        <div class="categoria-header" @click="toggleCategoria(categoria.modulo)">
          <div class="categoria-info">
            <ChevronDownIcon 
              class="categoria-icono" 
              :class="{ 'rotado': categoriasAbiertas.includes(categoria.modulo) }"
            />
            <h4 class="categoria-titulo">{{ categoria.titulo }}</h4>
            <span class="categoria-contador">
              {{ contarPermisosCategoria(categoria) }}/{{ categoria.permisos.length }}
            </span>
          </div>
          <div class="categoria-acciones" @click.stop>
            <button 
              @click="seleccionarTodosCategoria(categoria)"
              class="btn-seleccionar-todo"
              title="Seleccionar todos"
            >
              <CheckIcon class="icon" />
              <span class="btn-text">Todos</span>
            </button>
            <button 
              @click="deseleccionarTodosCategoria(categoria)"
              class="btn-deseleccionar-todo"
              title="Deseleccionar todos"
            >
              <XIcon class="icon" />
              <span class="btn-text">Ninguno</span>
            </button>
          </div>
        </div>
        
        <div 
          v-show="categoriasAbiertas.includes(categoria.modulo)" 
          class="categoria-permisos"
        >
          <label 
            v-for="permiso in categoria.permisos" 
            :key="permiso.nombre"
            class="permiso-item"
            :class="{ 'activo': permisosLocales.includes(permiso.nombre) }"
          >
            <input
              type="checkbox"
              :checked="permisosLocales.includes(permiso.nombre)"
              @change="togglePermiso(permiso.nombre)"
              class="permiso-checkbox"
            />
            <span class="permiso-switch">
              <span class="switch-slider"></span>
            </span>
            <span class="permiso-info">
              <span class="permiso-accion">{{ permiso.accion }}</span>
              <span class="permiso-recurso">{{ permiso.recurso }}</span>
            </span>
          </label>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { ChevronDownIcon, XIcon } from '@/components/globales/iconos'
import rolesService from '@/services/roles'

// Icono de check inline
const CheckIcon = {
  template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`
}

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  cargando: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue'])

const categorias = ref([])
const cargandoLocal = ref(false)
const categoriasAbiertas = ref([])
const permisosLocales = ref([])

// Flag para evitar loop infinito
let isUpdatingFromProp = false

// Inicializar con modelValue
onMounted(async () => {
  permisosLocales.value = [...props.modelValue]
  await cargarCategorias()
})

// Sincronizar solo cuando modelValue cambia externamente
watch(() => props.modelValue, (nuevo) => {
  if (!isUpdatingFromProp) {
    permisosLocales.value = [...nuevo]
  }
}, { deep: true })

async function cargarCategorias() {
  cargandoLocal.value = true
  try {
    categorias.value = await rolesService.obtenerPermisosCategorias()
    // Abrir primera categoría por defecto
    if (categorias.value.length > 0) {
      categoriasAbiertas.value = [categorias.value[0].modulo]
    }
  } catch (error) {
    console.error('Error al cargar categorías:', error)
  } finally {
    cargandoLocal.value = false
  }
}

function toggleCategoria(modulo) {
  const index = categoriasAbiertas.value.indexOf(modulo)
  if (index === -1) {
    categoriasAbiertas.value.push(modulo)
  } else {
    categoriasAbiertas.value.splice(index, 1)
  }
}

function contarPermisosCategoria(categoria) {
  return categoria.permisos.filter(p => permisosLocales.value.includes(p.nombre)).length
}

function togglePermiso(nombre) {
  const index = permisosLocales.value.indexOf(nombre)
  if (index === -1) {
    permisosLocales.value.push(nombre)
  } else {
    permisosLocales.value.splice(index, 1)
  }
  emitirCambios()
}

function seleccionarTodosCategoria(categoria) {
  const nuevosPermisos = categoria.permisos.map(p => p.nombre)
  const permisosSet = new Set(permisosLocales.value)
  nuevosPermisos.forEach(p => permisosSet.add(p))
  permisosLocales.value = Array.from(permisosSet)
  emitirCambios()
}

function deseleccionarTodosCategoria(categoria) {
  const permisosRemover = categoria.permisos.map(p => p.nombre)
  permisosLocales.value = permisosLocales.value.filter(p => !permisosRemover.includes(p))
  emitirCambios()
}

function emitirCambios() {
  isUpdatingFromProp = true
  emit('update:modelValue', [...permisosLocales.value])
  // Reset flag after next tick
  setTimeout(() => {
    isUpdatingFromProp = false
  }, 0)
}
</script>

<style scoped>
.editor-permisos {
  width: 100%;
}

.cargando {
  padding: 2rem;
  text-align: center;
  color: var(--color-text-muted);
}

.categorias-lista {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.categoria-item {
  border: 1px solid var(--color-border);
  border-radius: 10px;
  overflow: hidden;
}

.categoria-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.875rem 1rem;
  background: var(--color-background-mute);
  cursor: pointer;
  transition: background 0.2s;
}

.categoria-header:hover {
  background: var(--color-background-soft);
}

.categoria-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.categoria-icono {
  width: 18px;
  height: 18px;
  color: var(--color-text-muted);
  transition: transform 0.2s;
}

.categoria-icono.rotado {
  transform: rotate(180deg);
}

.categoria-titulo {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--color-heading);
  margin: 0;
  text-transform: capitalize;
}

.categoria-contador {
  padding: 0.2rem 0.5rem;
  background: var(--color-primary);
  color: #ffffff;
  border-radius: 10px;
  font-size: 0.7rem;
  font-weight: 600;
}

.categoria-acciones {
  display: flex;
  gap: 0.5rem;
}

.btn-seleccionar-todo,
.btn-deseleccionar-todo {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  padding: 0.4rem 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  background: var(--color-surface-bg);
  color: var(--color-text);
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-seleccionar-todo:hover {
  border-color: #22c55e;
  background: rgba(34, 197, 94, 0.1);
  color: #22c55e;
}

.btn-deseleccionar-todo:hover {
  border-color: #ef4444;
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.btn-seleccionar-todo .icon,
.btn-deseleccionar-todo .icon {
  width: 14px;
  height: 14px;
}

.categoria-permisos {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 0.5rem;
  padding: 1rem;
  background: var(--color-surface-bg);
}

.permiso-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.permiso-item:hover {
  border-color: var(--color-primary);
  background: rgba(59, 130, 246, 0.03);
}

.permiso-item.activo {
  border-color: var(--color-primary);
  background: rgba(59, 130, 246, 0.08);
}

.permiso-checkbox {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.permiso-switch {
  position: relative;
  width: 36px;
  height: 20px;
  background: #d1d5db;
  border-radius: 10px;
  transition: background 0.2s;
  flex-shrink: 0;
}

.permiso-checkbox:checked + .permiso-switch {
  background: #3b82f6;
}

.switch-slider {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 16px;
  height: 16px;
  background: #ffffff;
  border-radius: 50%;
  transition: transform 0.2s;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.permiso-checkbox:checked + .permiso-switch .switch-slider {
  transform: translateX(16px);
}

.permiso-info {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  min-width: 0;
}

.permiso-accion {
  font-size: 0.825rem;
  font-weight: 500;
  color: var(--color-text);
  text-transform: capitalize;
}

.permiso-recurso {
  font-size: 0.7rem;
  color: var(--color-text-muted);
}

/* Dark mode overrides */
html.dark .permiso-switch {
  background: #4b5563;
}

html.dark .permiso-checkbox:checked + .permiso-switch {
  background: #3b82f6;
}

@media (max-width: 768px) {
  .btn-text {
    display: none;
  }
  
  .btn-seleccionar-todo,
  .btn-deseleccionar-todo {
    padding: 0.4rem;
  }
}

@media (max-width: 640px) {
  .categoria-permisos {
    grid-template-columns: 1fr;
  }
  
  .categoria-header {
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  
  .categoria-acciones {
    margin-left: auto;
  }
}
</style>

