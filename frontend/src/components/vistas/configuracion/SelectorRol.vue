<template>
  <div class="selector-rol">
    <div v-if="cargando" class="cargando">
      <p>Cargando roles...</p>
    </div>
    
    <div v-else class="roles-grid">
      <label
        v-for="rol in roles"
        :key="rol.id"
        class="rol-option"
        :class="{ 'seleccionado': rolSeleccionado === rol.id }"
      >
        <input
          type="radio"
          :value="rol.id"
          v-model="rolSeleccionado"
          @change="handleCambio"
          class="rol-radio"
        />
        <div class="rol-content">
          <h4 class="rol-nombre">{{ rol.nombre }}</h4>
          <p class="rol-descripcion">{{ rol.descripcion }}</p>
        </div>
        <div class="rol-check">
          <div class="check-circle" :class="{ 'activo': rolSeleccionado === rol.id }"></div>
        </div>
      </label>
    </div>
    
    <div v-if="error" class="error-message">
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import usuariosService from '@/services/usuarios'

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null,
  },
})

const emit = defineEmits(['update:modelValue', 'cambio'])

const roles = ref([])
const cargando = ref(true)
const error = ref('')
const rolSeleccionado = ref(props.modelValue)

async function cargarRoles() {
  cargando.value = true
  error.value = ''
  
  try {
    const rolesData = await usuariosService.obtenerRoles()
    roles.value = rolesData
  } catch (err) {
    error.value = 'Error al cargar los roles'
    console.error('Error al cargar roles:', err)
  } finally {
    cargando.value = false
  }
}

function handleCambio() {
  emit('update:modelValue', rolSeleccionado.value)
  emit('cambio', rolSeleccionado.value)
}

onMounted(() => {
  cargarRoles()
})
</script>

<style scoped>
.selector-rol {
  width: 100%;
}

.cargando {
  padding: 2rem;
  text-align: center;
  color: var(--color-text-muted);
}

.error-message {
  padding: 1rem;
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  border-radius: 12px;
  font-size: 0.875rem;
  margin-top: 1rem;
}

.roles-grid {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.rol-option {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.25rem;
  border: 2px solid var(--color-border);
  border-radius: 12px;
  background: var(--color-surface-bg);
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}

.rol-option:hover {
  border-color: var(--color-primary);
  background: rgba(59, 130, 246, 0.05);
}

.rol-option.seleccionado {
  border-color: var(--color-primary);
  background: rgba(59, 130, 246, 0.1);
}

.rol-radio {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.rol-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.rol-nombre {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-heading);
  margin: 0;
}

.rol-descripcion {
  font-size: 0.875rem;
  color: var(--color-text-muted);
  margin: 0;
  line-height: 1.4;
}

.rol-check {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.check-circle {
  width: 24px;
  height: 24px;
  border: 2px solid var(--color-border);
  border-radius: 50%;
  position: relative;
  transition: all 0.2s;
}

.check-circle.activo {
  border-color: var(--color-primary);
  background: var(--color-primary);
}

.check-circle.activo::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 8px;
  height: 8px;
  background: white;
  border-radius: 50%;
}

/* Dark mode */
html.dark .rol-option:hover {
  background: rgba(59, 130, 246, 0.1);
}

html.dark .rol-option.seleccionado {
  background: rgba(59, 130, 246, 0.15);
}

@media (max-width: 640px) {
  .rol-option {
    padding: 0.875rem 1rem;
  }
  
  .rol-nombre {
    font-size: 0.95rem;
  }
  
  .rol-descripcion {
    font-size: 0.8rem;
  }
}
</style>
