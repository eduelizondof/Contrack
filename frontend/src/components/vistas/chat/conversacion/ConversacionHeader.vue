<script setup>
import { ref, computed } from 'vue'
import { useChatStore } from '@/stores/chat'
import ArrowLeftIcon from '@/components/globales/iconos/ArrowLeftIcon.vue'
import SearchIcon from '@/components/globales/iconos/SearchIcon.vue'
import MoreVerticalIcon from '@/components/globales/iconos/MoreVerticalIcon.vue'
import ArchiveIcon from '@/components/globales/iconos/ArchiveIcon.vue'
import LogoutIcon from '@/components/globales/iconos/LogoutIcon.vue'
import UsersIcon from '@/components/globales/iconos/UsersIcon.vue'
import TrashIcon from '@/components/globales/iconos/TrashIcon.vue'
import XIcon from '@/components/globales/iconos/XIcon.vue'
import { useAuthStore } from '@/stores/auth'
import AlertaFlotante from '@/components/ui/alerta-flotante/AlertaFlotante.vue'
import { useSweetAlert } from '@/composables/useSweetAlert'

defineProps({
  isMobile: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['ver-perfil', 'abrir-busqueda'])

const chatStore = useChatStore()
const authStore = useAuthStore()
const { success, error: showError } = useSweetAlert()

const menuAbierto = ref(false)
const archivando = ref(false)

// Obtener el otro usuario en chats 1-1
const otroUsuario = computed(() => {
  if (!chatStore.conversacionActiva?.usuarios || chatStore.conversacionActiva?.es_grupo) {
    return null
  }
  return chatStore.conversacionActiva.usuarios.find(u => u.id !== authStore.usuario?.id)
})

// Subtítulo dinámico
const subtitulo = computed(() => {
  if (chatStore.conversacionActiva?.es_grupo) {
    return `${chatStore.conversacionActiva.usuarios?.length || 0} miembros`
  }
  // En chat 1-1, mostrar email del otro usuario si está disponible
  if (otroUsuario.value?.email) {
    return otroUsuario.value.email
  }
  return 'Chat directo'
})

// Estado de alertas
const alerta = ref({
  isOpen: false,
  type: 'warning',
  title: '',
  message: '',
  action: null,
  confirmText: 'Confirmar',
  loading: false
})

const toggleMenu = () => {
  menuAbierto.value = !menuAbierto.value
}

const cerrarMenu = () => {
  menuAbierto.value = false
}

const volver = () => {
  chatStore.toggleSidebar()
}

const archivar = async () => {
  if (archivando.value) return // Prevenir múltiples peticiones
  
  archivando.value = true
  try {
    await chatStore.archivarConversacion(chatStore.conversacionActiva.id)
    cerrarMenu()
    success('Conversación archivada')
  } catch (err) {
    showError('Error al archivar la conversación')
  } finally {
    archivando.value = false
  }
}

const desarchivar = async () => {
  if (archivando.value) return // Prevenir múltiples peticiones
  
  archivando.value = true
  try {
    await chatStore.desarchivarConversacion(chatStore.conversacionActiva.id)
    cerrarMenu()
    success('Conversación desarchivada')
  } catch (err) {
    showError('Error al desarchivar la conversación')
  } finally {
    archivando.value = false
  }
}

const abrirConfirmacionEliminar = () => {
  alerta.value = {
    isOpen: true,
    type: 'error',
    title: '¿Eliminar conversación?',
    message: '¿Estás seguro de que quieres eliminar esta conversación para todos? Esta acción no se puede deshacer.',
    confirmText: 'Eliminar para todos',
    action: confirmarEliminar,
    loading: false
  }
}

const confirmarEliminar = async () => {
  alerta.value.loading = true
  try {
    await chatStore.eliminarConversacion(chatStore.conversacionActiva.id)
    success('Conversación eliminada')
    cerrarMenu()
  } catch (err) {
    showError('Error al eliminar conversación')
  } finally {
    alerta.value.isOpen = false
    alerta.value.loading = false
  }
}

const abrirConfirmacionSalir = () => {
  alerta.value = {
    isOpen: true,
    type: 'warning',
    title: '¿Salir del grupo?',
    message: '¿Estás seguro de que quieres salir de esta conversación?',
    confirmText: 'Salir del grupo',
    action: confirmarSalir,
    loading: false
  }
}

const confirmarSalir = async () => {
  alerta.value.loading = true
  try {
    await chatStore.salirConversacion(chatStore.conversacionActiva.id)
    success('Has salido del grupo')
    cerrarMenu()
  } catch (err) {
    showError('Error al salir de la conversación')
  } finally {
    alerta.value.isOpen = false
    alerta.value.loading = false
  }
}

const verMiembros = () => {
  emit('ver-perfil')
  cerrarMenu()
}

const esCreador = computed(() => {
  return chatStore.conversacionActiva?.creado_por === authStore.user?.id
})
</script>

<template>
  <header class="conversacion-header">
    <!-- Botón volver (mobile) -->
    <button
      v-if="isMobile"
      class="btn-icon"
      @click="volver"
    >
      <ArrowLeftIcon class="w-5 h-5" />
    </button>

    <!-- Info de la conversación -->
    <div
      class="header-info"
      :class="{ 'clickeable': chatStore.conversacionActiva?.es_grupo }"
      @click="chatStore.conversacionActiva?.es_grupo && emit('ver-perfil')"
    >
      <h2 class="header-titulo">
        {{ chatStore.conversacionActiva?.nombre }}
      </h2>
      <span class="header-subtitulo">
        {{ subtitulo }}
      </span>
    </div>

    <!-- Acciones -->
    <div class="header-acciones">
      <button 
        v-if="chatStore.conversacionActiva"
        class="btn-icon" 
        title="Buscar mensajes" 
        @click="emit('abrir-busqueda')"
      >
        <SearchIcon class="w-5 h-5" />
      </button>

      <div class="menu-container">
        <button 
          class="btn-icon"
          @click="toggleMenu"
        >
          <MoreVerticalIcon class="w-5 h-5" />
        </button>

        <!-- Dropdown menu -->
        <Transition name="fade">
          <div v-if="menuAbierto" class="menu-dropdown">
            <button 
              v-if="chatStore.conversacionActiva?.es_grupo"
              class="menu-item"
              @click="verMiembros"
            >
              <UsersIcon class="w-4 h-4" />
              <span>Ver miembros</span>
            </button>
            
            <button 
              v-if="!chatStore.conversacionActiva?.archivada"
              class="menu-item"
              @click="archivar"
            >
              <ArchiveIcon class="w-4 h-4" />
              <span>Archivar</span>
            </button>
            
            <button 
              v-else
              class="menu-item"
              @click="desarchivar"
            >
              <ArchiveIcon class="w-4 h-4" />
              <span>Desarchivar</span>
            </button>
            
            <button 
              v-if="chatStore.conversacionActiva?.es_grupo"
              class="menu-item menu-item-danger"
              @click="abrirConfirmacionSalir"
            >
              <LogoutIcon class="w-4 h-4" />
              <span>Salir del grupo</span>
            </button>

            <button 
              v-if="esCreador"
              class="menu-item menu-item-danger"
              @click="abrirConfirmacionEliminar"
            >
              <TrashIcon class="w-4 h-4" />
              <span>Eliminar chat</span>
            </button>
          </div>
        </Transition>

        <!-- Overlay para cerrar menú -->
        <div 
          v-if="menuAbierto" 
          class="menu-overlay"
          @click="cerrarMenu"
        ></div>
      </div>
    </div>

    <!-- Alerta de Confirmación -->
    <AlertaFlotante
      v-model:is-open="alerta.isOpen"
      :type="alerta.type"
      :title="alerta.title"
      :message="alerta.message"
      :confirm-text="alerta.confirmText"
      :loading="alerta.loading"
      @confirm="alerta.action"
    />
  </header>
</template>

<style scoped>
.conversacion-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid var(--color-border);
  background: var(--color-background);
}

.btn-icon {
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
}

.btn-icon:hover {
  background: var(--color-muted);
}

.header-info {
  flex: 1;
  min-width: 0;
}

.header-info.clickeable {
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 8px;
  transition: background 0.2s;
}

.header-info.clickeable:hover {
  background: var(--color-muted);
}

.header-titulo {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-foreground);
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.header-subtitulo {
  font-size: 0.75rem;
  color: var(--color-muted-foreground);
}

.header-acciones {
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Menu */
.menu-container {
  position: relative;
}

.menu-overlay {
  position: fixed;
  inset: 0;
  z-index: 40;
}

.menu-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 8px;
  min-width: 180px;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
  z-index: 50;
  overflow: hidden;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 12px 16px;
  border: none;
  background: transparent;
  color: var(--color-foreground);
  font-size: 0.875rem;
  cursor: pointer;
  transition: background 0.2s ease;
}

.menu-item:hover:not(:disabled) {
  background: var(--color-muted);
}

.menu-item:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.menu-item-danger {
  color: #ef4444;
}

.menu-item-danger:hover {
  background: #fee2e2;
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
