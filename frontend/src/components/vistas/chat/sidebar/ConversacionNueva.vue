<script setup>
import { ref, onMounted } from 'vue'
import { useChatStore } from '@/stores/chat'
import XIcon from '@/components/globales/iconos/XIcon.vue'
import ArrowLeftIcon from '@/components/globales/iconos/ArrowLeftIcon.vue'
import UserPlusIcon from '@/components/globales/iconos/UserPlusIcon.vue'
import SearchIcon from '@/components/globales/iconos/SearchIcon.vue'
import UsersIcon from '@/components/globales/iconos/UsersIcon.vue'
import ChevronRightIcon from '@/components/globales/iconos/ChevronRightIcon.vue'

const props = defineProps({
  isMobile: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['cancelar', 'creada'])

const chatStore = useChatStore()

const nombre = ref('')
const busqueda = ref('')
const usuariosSeleccionados = ref([])
const resultadosBusqueda = ref([])
const buscando = ref(false)
const creando = ref(false)

const buscarUsuarios = async () => {
  if (busqueda.value.length < 2) {
    resultadosBusqueda.value = []
    return
  }
  buscando.value = true
  resultadosBusqueda.value = await chatStore.buscarUsuarios(busqueda.value)
  buscando.value = false
}

const toggleUsuario = (usuario) => {
  const index = usuariosSeleccionados.value.findIndex(u => u.id === usuario.id)
  if (index === -1) {
    usuariosSeleccionados.value.push(usuario)
  } else {
    usuariosSeleccionados.value.splice(index, 1)
  }
}

const crear = async () => {
  if (usuariosSeleccionados.value.length === 0 || creando.value || chatStore.creandoConversacion) return

  creando.value = true
  try {
    const usuariosIds = usuariosSeleccionados.value.map(u => u.id)
    const conv = await chatStore.crearConversacion(nombre.value, usuariosIds)
    emit('creada', conv)
  } catch (error) {
    console.error('Error al crear conversación:', error)
  } finally {
    creando.value = false
  }
}

const removerSeleccionado = (id) => {
  usuariosSeleccionados.value = usuariosSeleccionados.value.filter(u => u.id !== id)
}
</script>

<template>
  <div class="nueva-conversacion">
    <div class="nueva-header">
      <button
        v-if="isMobile"
        class="btn-volver"
        @click="emit('cancelar')"
      >
        <ArrowLeftIcon class="w-5 h-5" />
      </button>
      <div class="header-info">
        <UserPlusIcon v-if="!isMobile" class="w-6 h-6 text-primary" />
        <h2>Nueva Conversación</h2>
      </div>
      <button v-if="!isMobile" class="btn-close" @click="emit('cancelar')">
        <XIcon class="w-5 h-5" />
      </button>
    </div>

    <div class="nueva-body">
      <!-- Nombre de grupo (opcional) -->
      <div class="form-group" v-if="usuariosSeleccionados.length >= 1">
        <label>
          Nombre del Grupo 
          <span class="label-hint">(Opcional - Si no lo pones, se usará el nombre de los usuarios)</span>
        </label>
        <div class="input-wrapper">
          <i class="fa-solid fa-users input-icon"></i>
          <input 
            v-model="nombre" 
            type="text" 
            placeholder="Ej: Tareas, Pendientes, Equipo de Ventas..." 
            class="app-input"
          />
        </div>
      </div>

      <!-- Usuarios Seleccionados -->
      <div v-if="usuariosSeleccionados.length > 0" class="usuarios-seleccionados">
        <label>Seleccionados ({{ usuariosSeleccionados.length }})</label>
        <div class="chips-container">
          <div 
            v-for="u in usuariosSeleccionados" 
            :key="u.id" 
            class="usuario-chip"
          >
            <span>{{ u.name }}</span>
            <button @click="removerSeleccionado(u.id)"><XIcon class="w-3 h-3" /></button>
          </div>
        </div>
      </div>

      <!-- Buscador -->
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

      <!-- Resultados -->
      <div class="resultados-lista">
        <div v-if="buscando" class="asistente-loading">
          <div class="spinner-small"></div>
          <span>Buscando...</span>
        </div>

        <button 
          v-for="usuario in resultadosBusqueda" 
          :key="usuario.id"
          class="usuario-item"
          :class="{ seleccionado: usuariosSeleccionados.some(u => u.id === usuario.id) }"
          @click="toggleUsuario(usuario)"
        >
          <div class="usuario-avatar">
            {{ usuario.name.charAt(0).toUpperCase() }}
          </div>
          <div class="usuario-info">
            <span class="usuario-nombre">{{ usuario.name }}</span>
            <span class="usuario-email">{{ usuario.email }}</span>
          </div>
          <div class="usuario-check">
            <div class="check-box"></div>
          </div>
        </button>

        <div v-if="resultadosBusqueda.length === 0 && busqueda.length >= 2 && !buscando" class="no-resultados">
          No se encontraron usuarios
        </div>
      </div>
    </div>

    <div class="nueva-footer">
      <button 
        class="btn-crear" 
        :disabled="usuariosSeleccionados.length === 0 || creando || chatStore.creandoConversacion"
        @click="crear"
      >
        <span v-if="creando || chatStore.creandoConversacion" class="spinner-small"></span>
        <template v-else>
          <span>{{ usuariosSeleccionados.length > 1 ? 'Crear Grupo' : 'Iniciar Chat' }}</span>
          <ChevronRightIcon class="w-5 h-5 ml-2" />
        </template>
      </button>
    </div>
  </div>
</template>

<style scoped>
.nueva-conversacion {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--color-background);
}

.nueva-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--color-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  background: linear-gradient(135deg, var(--color-muted) 0%, var(--color-background) 100%);
}

.btn-volver {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: none;
  background: transparent;
  color: var(--color-foreground);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.2s ease;
  flex-shrink: 0;
}

.btn-volver:hover {
  background: var(--color-muted);
}

.header-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.header-info h2 {
  font-size: 1.25rem;
  font-weight: 700;
  margin: 0;
  color: var(--color-foreground);
}

.btn-close {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-muted);
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-close:hover {
  background: var(--color-accent);
  color: var(--color-foreground);
}

.nueva-body {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
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
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.label-hint {
  font-size: 0.75rem;
  font-weight: 400;
  color: var(--color-muted-foreground);
  opacity: 0.7;
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

.usuarios-seleccionados {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.usuarios-seleccionados label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-muted-foreground);
}

.chips-container {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.usuario-chip {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  background: var(--color-primary);
  color: var(--color-primary-foreground);
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 500;
}

.usuario-chip button {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.usuario-chip button:hover {
  background: rgba(255, 255, 255, 0.4);
}

.resultados-lista {
  display: flex;
  flex-direction: column;
  gap: 8px;
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
}

.usuario-item.seleccionado {
  background: var(--color-background);
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
}

.usuario-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.usuario-nombre {
  font-weight: 600;
  color: var(--color-foreground);
}

.usuario-email {
  font-size: 0.75rem;
  color: var(--color-muted-foreground);
}

.check-box {
  width: 20px;
  height: 20px;
  border: 2px solid var(--color-border);
  border-radius: 6px;
  transition: all 0.2s;
}

.seleccionado .check-box {
  background: var(--color-primary);
  border-color: var(--color-primary);
}

.no-resultados {
  padding: 20px;
  text-align: center;
  color: var(--color-muted-foreground);
  font-size: 0.875rem;
}

.nueva-footer {
  padding: 20px 24px;
  border-top: 1px solid var(--color-border);
}

.btn-crear {
  width: 100%;
  padding: 14px;
  border-radius: 16px;
  background: var(--color-primary);
  color: var(--color-primary-foreground);
  font-weight: 700;
  font-size: 1rem;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-crear:hover:not(:disabled) {
  opacity: 0.9;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(var(--color-primary-rgb), 0.3);
}

.btn-crear:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.spinner-small {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
