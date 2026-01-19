<script setup>
import { ref } from 'vue'
import { useChatStore } from '@/stores/chat'
import SearchIcon from '@/components/globales/iconos/SearchIcon.vue'
import XIcon from '@/components/globales/iconos/XIcon.vue'
import { useSweetAlert } from '@/composables/useSweetAlert'

const props = defineProps({
  conversacionId: {
    type: [Number, String],
    required: true
  }
})

const emit = defineEmits(['agregado', 'cancelar'])

const chatStore = useChatStore()
const { success, error: showError } = useSweetAlert()

const busqueda = ref('')
const resultadosBusqueda = ref([])
const buscando = ref(false)

const buscarUsuarios = async () => {
  if (busqueda.value.length < 2) {
    resultadosBusqueda.value = []
    return
  }
  buscando.value = true
  try {
    const usuarios = await chatStore.buscarUsuarios(busqueda.value)
    // Filtrar usuarios que ya son miembros
    const miembrosIds = chatStore.conversacionActiva?.usuarios?.map(u => u.id) || []
    resultadosBusqueda.value = usuarios.filter(u => !miembrosIds.includes(u.id))
  } catch (error) {
    showError('Error al buscar usuarios')
  } finally {
    buscando.value = false
  }
}

const agregarMiembro = async (usuario) => {
  try {
    await chatStore.agregarMiembro(props.conversacionId, usuario.id)
    success(`${usuario.name} agregado al grupo`)
    busqueda.value = ''
    resultadosBusqueda.value = []
    emit('agregado')
  } catch (error) {
    showError('Error al agregar miembro')
  }
}
</script>

<template>
  <div class="agregar-miembros-form">
    <div class="form-group">
      <label>Buscar Usuarios</label>
      <div class="input-wrapper">
        <SearchIcon class="input-icon" />
        <input
          v-model="busqueda"
          type="text"
          placeholder="Escribe nombre o correo..."
          class="app-input"
          @input="buscarUsuarios"
        />
      </div>
    </div>

    <div class="resultados-lista">
      <div v-if="buscando" class="loading">
        <div class="spinner-small"></div>
        <span>Buscando...</span>
      </div>

      <button
        v-for="usuario in resultadosBusqueda"
        :key="usuario.id"
        class="usuario-item"
        @click="agregarMiembro(usuario)"
      >
        <div class="usuario-avatar">
          {{ usuario.name.charAt(0).toUpperCase() }}
        </div>
        <div class="usuario-info">
          <span class="usuario-nombre">{{ usuario.name }}</span>
          <span class="usuario-email">{{ usuario.email }}</span>
        </div>
        <div class="usuario-accion">
          <i class="fa-solid fa-plus"></i>
        </div>
      </button>

      <div
        v-if="resultadosBusqueda.length === 0 && busqueda.length >= 2 && !buscando"
        class="no-resultados"
      >
        No se encontraron usuarios
      </div>

      <div
        v-if="busqueda.length < 2"
        class="hint"
      >
        Escribe al menos 2 caracteres para buscar
      </div>
    </div>
  </div>
</template>

<style scoped>
.agregar-miembros-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-muted-foreground);
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 12px;
  width: 18px;
  height: 18px;
  color: var(--color-muted-foreground);
}

.app-input {
  width: 100%;
  padding: 12px 12px 12px 40px;
  border-radius: 14px;
  border: 1px solid var(--color-border);
  background: var(--color-muted);
  color: var(--color-foreground);
  transition: all 0.2s;
}

.app-input:focus {
  outline: none;
  border-color: var(--color-primary);
  background: var(--color-background);
  box-shadow: 0 0 0 4px var(--color-primary-alpha, rgba(var(--color-primary-rgb), 0.1));
}

.resultados-lista {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 400px;
  overflow-y: auto;
}

.loading {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px;
  justify-content: center;
  color: var(--color-muted-foreground);
}

.spinner-small {
  width: 18px;
  height: 18px;
  border: 2px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.usuario-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 14px;
  border: 1px solid transparent;
  background: var(--color-muted);
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
  width: 100%;
}

.usuario-item:hover {
  background: var(--color-accent);
  border-color: var(--color-primary);
}

.usuario-avatar {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  background: var(--color-primary);
  color: var(--color-primary-foreground);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.125rem;
  flex-shrink: 0;
}

.usuario-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.usuario-nombre {
  font-weight: 600;
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

.usuario-accion {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--color-primary);
  color: var(--color-primary-foreground);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.no-resultados,
.hint {
  padding: 20px;
  text-align: center;
  color: var(--color-muted-foreground);
  font-size: 0.875rem;
}
</style>
