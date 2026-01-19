<script setup>
import { ref, computed, Teleport } from 'vue'
import { useChatStore } from '@/stores/chat'
import { useAuthStore } from '@/stores/auth'
import ArrowLeftIcon from '@/components/globales/iconos/ArrowLeftIcon.vue'
import UserPlusIcon from '@/components/globales/iconos/UserPlusIcon.vue'
import ArchiveIcon from '@/components/globales/iconos/ArchiveIcon.vue'
import LogoutIcon from '@/components/globales/iconos/LogoutIcon.vue'
import TrashIcon from '@/components/globales/iconos/TrashIcon.vue'
import XIcon from '@/components/globales/iconos/XIcon.vue'
import AlertaFlotante from '@/components/ui/alerta-flotante/AlertaFlotante.vue'
import { useSweetAlert } from '@/composables/useSweetAlert'
import MiembroItem from './MiembroItem.vue'
import AgregarMiembrosForm from './AgregarMiembrosForm.vue'

const props = defineProps({
  isMobile: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['cerrar'])

const chatStore = useChatStore()
const authStore = useAuthStore()
const { success, error: showError } = useSweetAlert()

const mostrandoAgregarMiembros = ref(false)
const editandoNombre = ref(false)
const nombreEditado = ref('')

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

const esCreador = computed(() => {
  return chatStore.conversacionActiva?.creado_por === authStore.user?.id
})

const esAdmin = computed(() => {
  if (!chatStore.conversacionActiva || !authStore.user) return false
  const usuario = chatStore.conversacionActiva.usuarios?.find(u => u.id === authStore.user.id)
  return usuario?.pivot?.es_admin || esCreador.value
})

const puedeEditarNombre = computed(() => {
  return esAdmin.value && chatStore.conversacionActiva?.es_grupo
})

const iniciarEdicionNombre = () => {
  if (!puedeEditarNombre.value) return
  nombreEditado.value = chatStore.conversacionActiva?.nombre || ''
  editandoNombre.value = true
}

const cancelarEdicionNombre = () => {
  editandoNombre.value = false
  nombreEditado.value = ''
}

const guardarNombre = async () => {
  // TODO: Implementar actualización de nombre cuando el backend lo soporte
  editandoNombre.value = false
  success('Nombre actualizado')
}

const archivar = async () => {
  await chatStore.archivarConversacion(chatStore.conversacionActiva.id)
  success('Conversación archivada')
  emit('cerrar')
}

const desarchivar = async () => {
  await chatStore.desarchivarConversacion(chatStore.conversacionActiva.id)
  success('Conversación desarchivada')
}

const abrirConfirmacionSalir = () => {
  const totalMiembros = chatStore.conversacionActiva?.usuarios?.length || 0
  
  if (totalMiembros === 1) {
    // Último miembro - preguntar si eliminar grupo
    alerta.value = {
      isOpen: true,
      type: 'error',
      title: '¿Eliminar el grupo?',
      message: 'Eres el único miembro. ¿Deseas eliminar este grupo? Esta acción no se puede deshacer.',
      confirmText: 'Eliminar grupo',
      action: confirmarEliminar,
      loading: false
    }
  } else {
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
}

const confirmarSalir = async () => {
  alerta.value.loading = true
  try {
    await chatStore.salirConversacion(chatStore.conversacionActiva.id)
    success('Has salido del grupo')
    emit('cerrar')
  } catch (err) {
    showError('Error al salir de la conversación')
  } finally {
    alerta.value.isOpen = false
    alerta.value.loading = false
  }
}

const confirmarEliminar = async () => {
  alerta.value.loading = true
  try {
    await chatStore.eliminarConversacion(chatStore.conversacionActiva.id)
    success('Grupo eliminado')
    emit('cerrar')
  } catch (err) {
    showError('Error al eliminar el grupo')
  } finally {
    alerta.value.isOpen = false
    alerta.value.loading = false
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

const handleMiembroAgregado = () => {
  mostrandoAgregarMiembros.value = false
  // El store ya actualiza la conversación automáticamente
}

const handleMiembroRemovido = async (userId) => {
  try {
    await chatStore.removerMiembro(chatStore.conversacionActiva.id, userId)
    success('Miembro removido')
  } catch (err) {
    showError('Error al remover miembro')
  }
}

const handleAdminCambiado = async (userId, esAdmin) => {
  try {
    await chatStore.cambiarAdmin(chatStore.conversacionActiva.id, userId, esAdmin)
    success(esAdmin ? 'Usuario promovido a administrador' : 'Usuario removido como administrador')
  } catch (err) {
    showError('Error al cambiar rol')
  }
}
</script>

<template>
  <div class="conversacion-perfil">
    <!-- Header -->
    <header class="perfil-header">
      <button
        class="btn-volver"
        @click="emit('cerrar')"
      >
        <ArrowLeftIcon class="w-5 h-5" />
      </button>
      <h2 class="perfil-titulo">Información del grupo</h2>
      <div style="width: 40px;"></div> <!-- Spacer para centrar -->
    </header>

    <!-- Contenido -->
    <div class="perfil-content">
      <!-- Avatar y nombre -->
      <div class="perfil-avatar-section">
        <div class="perfil-avatar">
          <i class="fa-solid fa-users text-4xl"></i>
        </div>
        <div class="perfil-nombre-section">
          <div v-if="!editandoNombre" class="perfil-nombre-display">
            <h3>{{ chatStore.conversacionActiva?.nombre || 'Sin nombre' }}</h3>
            <button
              v-if="puedeEditarNombre"
              class="btn-editar-nombre"
              @click="iniciarEdicionNombre"
            >
              <i class="fa-solid fa-pencil"></i>
              Editar nombre
            </button>
          </div>
          <div v-else class="perfil-nombre-edit">
            <input
              v-model="nombreEditado"
              type="text"
              class="input-nombre"
              placeholder="Nombre del grupo"
              @keyup.enter="guardarNombre"
              @keyup.esc="cancelarEdicionNombre"
            />
            <div class="nombre-edit-actions">
              <button class="btn-guardar" @click="guardarNombre">Guardar</button>
              <button class="btn-cancelar" @click="cancelarEdicionNombre">Cancelar</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Lista de miembros -->
      <div class="perfil-seccion">
        <div class="seccion-header">
          <h4>Miembros ({{ chatStore.conversacionActiva?.usuarios?.length || 0 }})</h4>
          <button
            v-if="esAdmin && chatStore.conversacionActiva?.es_grupo"
            class="btn-agregar-miembro"
            @click="mostrandoAgregarMiembros = true"
          >
            <UserPlusIcon class="w-4 h-4" />
            Agregar
          </button>
        </div>
        <div class="miembros-lista">
          <MiembroItem
            v-for="usuario in chatStore.conversacionActiva?.usuarios"
            :key="usuario.id"
            :usuario="usuario"
            :es-creador="usuario.id === chatStore.conversacionActiva?.creado_por"
            :puede-gestionar="esAdmin && usuario.id !== authStore.user?.id"
            :no-puede-remover-creador="usuario.id === chatStore.conversacionActiva?.creado_por"
            :solo-dos-miembros="(chatStore.conversacionActiva?.usuarios?.length || 0) === 2"
            @remover="handleMiembroRemovido"
            @cambiar-admin="handleAdminCambiado"
          />
        </div>
      </div>

      <!-- Acciones -->
      <div class="perfil-seccion">
        <h4>Acciones</h4>
        <div class="acciones-lista">
          <button
            v-if="!chatStore.conversacionActiva?.archivada"
            class="accion-item"
            :disabled="archivando"
            @click="archivar"
          >
            <ArchiveIcon class="w-5 h-5" />
            <span v-if="!archivando">Archivar conversación</span>
            <span v-else>Archivando...</span>
          </button>
          <button
            v-else
            class="accion-item"
            :disabled="archivando"
            @click="desarchivar"
          >
            <ArchiveIcon class="w-5 h-5" />
            <span v-if="!archivando">Desarchivar conversación</span>
            <span v-else>Desarchivando...</span>
          </button>
          
          <button
            v-if="chatStore.conversacionActiva?.es_grupo"
            class="accion-item accion-item-danger"
            @click="abrirConfirmacionSalir"
          >
            <LogoutIcon class="w-5 h-5" />
            <span>Salir del grupo</span>
          </button>

          <button
            v-if="esCreador"
            class="accion-item accion-item-danger"
            @click="abrirConfirmacionEliminar"
          >
            <TrashIcon class="w-5 h-5" />
            <span>Eliminar grupo</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal agregar miembros -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="mostrandoAgregarMiembros"
          class="modal-overlay"
          @click.self="mostrandoAgregarMiembros = false"
        >
          <div class="modal-content-agregar">
            <div class="modal-header">
              <h3>Agregar miembros</h3>
              <button
                class="btn-icon"
                @click="mostrandoAgregarMiembros = false"
              >
                <XIcon class="w-5 h-5" />
              </button>
            </div>
            <div class="modal-body-agregar">
              <AgregarMiembrosForm
                :conversacion-id="chatStore.conversacionActiva?.id"
                @agregado="handleMiembroAgregado"
                @cancelar="mostrandoAgregarMiembros = false"
              />
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

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
  </div>
</template>

<style scoped>
.conversacion-perfil {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--color-background);
}

.perfil-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid var(--color-border);
  background: var(--color-background);
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
}

.btn-volver:hover {
  background: var(--color-muted);
}

.perfil-titulo {
  flex: 1;
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0;
  text-align: center;
}

.perfil-content {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}

.perfil-avatar-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  margin-bottom: 32px;
  padding-bottom: 24px;
  border-bottom: 1px solid var(--color-border);
}

.perfil-avatar {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: var(--color-primary);
  color: var(--color-primary-foreground);
  display: flex;
  align-items: center;
  justify-content: center;
}

.perfil-nombre-section {
  width: 100%;
  text-align: center;
}

.perfil-nombre-display h3 {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 12px 0;
  color: var(--color-foreground);
}

.btn-editar-nombre {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 8px;
  border: 1px solid var(--color-border);
  background: var(--color-muted);
  color: var(--color-foreground);
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-editar-nombre:hover {
  background: var(--color-accent);
}

.perfil-nombre-edit {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.input-nombre {
  width: 100%;
  padding: 12px;
  border-radius: 12px;
  border: 1px solid var(--color-border);
  background: var(--color-muted);
  color: var(--color-foreground);
  font-size: 1.125rem;
  font-weight: 600;
  text-align: center;
}

.input-nombre:focus {
  outline: none;
  border-color: var(--color-primary);
  background: var(--color-background);
}

.nombre-edit-actions {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.btn-guardar,
.btn-cancelar {
  padding: 8px 16px;
  border-radius: 8px;
  border: none;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-guardar {
  background: var(--color-primary);
  color: var(--color-primary-foreground);
}

.btn-guardar:hover {
  opacity: 0.9;
}

.btn-cancelar {
  background: var(--color-muted);
  color: var(--color-foreground);
}

.btn-cancelar:hover {
  background: var(--color-accent);
}

.perfil-seccion {
  margin-bottom: 32px;
}

.seccion-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.seccion-header h4 {
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
  color: var(--color-foreground);
}

.btn-agregar-miembro {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 8px;
  border: 1px solid var(--color-border);
  background: var(--color-primary);
  color: var(--color-primary-foreground);
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-agregar-miembro:hover {
  opacity: 0.9;
}

.miembros-lista {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.acciones-lista {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.accion-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 12px;
  border: 1px solid var(--color-border);
  background: var(--color-muted);
  color: var(--color-foreground);
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
  width: 100%;
}

.accion-item:hover:not(:disabled) {
  background: var(--color-accent);
}

.accion-item:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.accion-item-danger {
  color: #ef4444;
  border-color: #fee2e2;
}

.accion-item-danger:hover {
  background: #fee2e2;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  z-index: 50;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.modal-content-agregar {
  width: 100%;
  max-width: 500px;
  max-height: 80vh;
  background: var(--color-background);
  border-radius: 16px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid var(--color-border);
}

.modal-header h3 {
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0;
}

.modal-body-agregar {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
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
