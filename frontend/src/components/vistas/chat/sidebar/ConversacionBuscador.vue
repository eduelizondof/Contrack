<script setup>
import { ref, watch } from 'vue'
import { useChatStore } from '@/stores/chat'
import SearchIcon from '@/components/globales/iconos/SearchIcon.vue'
import XIcon from '@/components/globales/iconos/XIcon.vue'

const chatStore = useChatStore()

const busqueda = ref('')
const usuarios = ref([])
const buscandoUsuarios = ref(false)

// Buscar usuarios para crear conversación
const buscarUsuarios = async () => {
  if (busqueda.value.length < 2) {
    usuarios.value = []
    return
  }

  buscandoUsuarios.value = true
  usuarios.value = await chatStore.buscarUsuarios(busqueda.value)
  buscandoUsuarios.value = false
}

// Debounce de búsqueda
let debounceTimer = null
watch(busqueda, (val) => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    if (val.length >= 2) {
      buscarUsuarios()
    } else {
      usuarios.value = []
    }
  }, 300)
})

// Crear conversación directa con usuario
const iniciarConversacion = async (usuario) => {
  try {
    const conv = await chatStore.crearConversacion(null, [usuario.id])
    // Seleccionar inmediatamente (no esperar)
    chatStore.seleccionarConversacion(conv.id)
    busqueda.value = ''
    usuarios.value = []
  } catch (error) {
    console.error('Error al crear conversación:', error)
  }
}

const limpiarBusqueda = () => {
  busqueda.value = ''
  usuarios.value = []
}
</script>

<template>
  <div class="buscador-container">
    <div class="buscador-input-wrapper">
      <SearchIcon class="buscador-icon" />
      <input
        v-model="busqueda"
        type="text"
        placeholder="Buscar usuario o conversación..."
        class="buscador-input"
      />
      <button
        v-if="busqueda"
        class="btn-limpiar"
        @click="limpiarBusqueda"
      >
        <XIcon class="w-4 h-4" />
      </button>
    </div>

    <!-- Resultados de búsqueda de usuarios -->
    <div v-if="usuarios.length > 0" class="resultados-usuarios">
      <div class="resultados-header">
        <span>Iniciar conversación con:</span>
      </div>
      <button
        v-for="usuario in usuarios"
        :key="usuario.id"
        class="usuario-item"
        @click="iniciarConversacion(usuario)"
      >
        <div class="usuario-avatar">
          {{ usuario.name.charAt(0).toUpperCase() }}
        </div>
        <div class="usuario-info">
          <span class="usuario-nombre">{{ usuario.name }}</span>
          <span class="usuario-email">{{ usuario.email }}</span>
        </div>
      </button>
    </div>

    <!-- Indicador de carga -->
    <div v-if="buscandoUsuarios" class="buscando">
      <div class="buscando-content">
        <span class="spinner"></span>
        <span class="buscando-text">Buscando usuarios...</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.buscador-container {
  padding: 12px 16px;
  border-bottom: 1px solid var(--color-border);
  position: relative;
}

.buscador-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.buscador-icon {
  position: absolute;
  left: 12px;
  width: 18px;
  height: 18px;
  color: var(--color-muted-foreground);
  pointer-events: none;
}

.buscador-input {
  width: 100%;
  padding: 10px 36px;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  background: var(--color-muted);
  color: var(--color-foreground);
  font-size: 0.875rem;
  transition: all 0.2s ease;
}

.buscador-input:focus {
  outline: none;
  border-color: var(--color-primary);
  background: var(--color-background);
}

.buscador-input::placeholder {
  color: var(--color-muted-foreground);
}

.btn-limpiar {
  position: absolute;
  right: 8px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: none;
  background: var(--color-muted-foreground);
  color: var(--color-background);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-limpiar:hover {
  background: var(--color-foreground);
}

/* Resultados */
.resultados-usuarios {
  position: absolute;
  top: 100%;
  left: 16px;
  right: 16px;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
  z-index: 50;
  max-height: 300px;
  overflow-y: auto;
}

.resultados-header {
  padding: 8px 12px;
  font-size: 0.75rem;
  color: var(--color-muted-foreground);
  border-bottom: 1px solid var(--color-border);
}

.usuario-item {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 12px;
  border: none;
  background: transparent;
  cursor: pointer;
  text-align: left;
  transition: background 0.2s ease;
}

.usuario-item:hover {
  background: var(--color-muted);
}

.usuario-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--color-primary);
  color: var(--color-primary-foreground);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1rem;
}

.usuario-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.usuario-nombre {
  font-weight: 500;
  color: var(--color-foreground);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.usuario-email {
  font-size: 0.75rem;
  color: var(--color-muted-foreground);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Loading */
.buscando {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px 12px;
}

.buscando-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 12px 20px;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  width: 100%;
}

.spinner {
  width: 24px;
  height: 24px;
  border: 3px solid var(--color-muted);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  flex-shrink: 0;
}

.buscando-text {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-foreground);
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
