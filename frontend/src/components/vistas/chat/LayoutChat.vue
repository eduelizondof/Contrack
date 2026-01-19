<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useChatStore } from '@/stores/chat'
import { useAuthStore } from '@/stores/auth'
import ConversacionBuscador from './sidebar/ConversacionBuscador.vue'
import ConversacionLista from './sidebar/ConversacionLista.vue'
import ConversacionHeader from './conversacion/ConversacionHeader.vue'
import ConversacionPerfil from './conversacion/ConversacionPerfil.vue'
import BusquedaMensajes from './conversacion/BusquedaMensajes.vue'
import MensajeLista from './conversacion/MensajeLista.vue'
import MensajeInput from './input/MensajeInput.vue'
import MensajeOpcionesBar from './conversacion/MensajeOpcionesBar.vue'
import ConversacionNueva from './sidebar/ConversacionNueva.vue'
import PlusIcon from '@/components/globales/iconos/PlusIcon.vue'
import MessageCircleIcon from '@/components/globales/iconos/MessageCircleIcon.vue'
import AlertaFlotante from '@/components/ui/alerta-flotante/AlertaFlotante.vue'
import { useSweetAlert } from '@/composables/useSweetAlert'

const chatStore = useChatStore()
const authStore = useAuthStore()
const { success, error: showError } = useSweetAlert()

const mensajeSeleccionado = ref(null)
const mensajeResaltado = ref(null) // Mensaje resaltado desde búsqueda
const respondiendoA = ref(null)
const editando = ref(null)
const mostrandoNueva = ref(false)
const mostrandoPerfil = ref(false)
const mostrandoBusqueda = ref(false)

// Estado de alertas
const alerta = ref({
  isOpen: false,
  type: 'error',
  title: '',
  message: '',
  confirmText: 'Eliminar',
  loading: false,
  targetMensaje: null
})

// Responsive
const isMobile = ref(window.innerWidth < 1024)

const handleResize = () => {
  const wasMobile = isMobile.value
  isMobile.value = window.innerWidth < 1024
  
  // Si cambió a móvil y no hay conversación activa, mostrar sidebar
  if (isMobile.value && !wasMobile && !chatStore.conversacionActiva && !mostrandoNueva.value) {
    chatStore.sidebarAbierto = true
  }
}

onMounted(async () => {
  window.addEventListener('resize', handleResize)
  // En móvil, mostrar sidebar por defecto si no hay conversación activa
  if (isMobile.value && !chatStore.conversacionActiva) {
    chatStore.sidebarAbierto = true
  }
  await chatStore.cargarConversaciones(false, true) // Carga inicial con loading visible
  chatStore.iniciarPollingCompleto() // Polling completo cuando está en chat
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  chatStore.detenerPollingCompleto()
  chatStore.limpiar()
})

// Handlers
const handleSeleccionarMensaje = async (mensaje) => {
  if (mensajeSeleccionado.value?.id === mensaje.id) {
    // Deseleccionar
    mensajeSeleccionado.value = null
    mensajeResaltado.value = null
  } else {
    // Seleccionar y resaltar
    mensajeSeleccionado.value = mensaje
    mensajeResaltado.value = mensaje.id
    
    // Scroll al mensaje seleccionado
    await nextTick()
    setTimeout(() => {
      const elemento = document.querySelector(`[data-mensaje-id="${mensaje.id}"]`)
      if (elemento) {
        elemento.scrollIntoView({ 
          behavior: 'smooth', 
          block: 'center' 
        })
      }
    }, 100)
  }
}

const handleResponder = (mensaje) => {
  respondiendoA.value = mensaje
  mensajeSeleccionado.value = null
  mensajeResaltado.value = null
}

const handleEditar = (mensaje) => {
  editando.value = mensaje
  mensajeSeleccionado.value = null
  mensajeResaltado.value = null
}

const handleEliminar = (mensaje) => {
  alerta.value = {
    isOpen: true,
    type: 'error',
    title: '¿Eliminar mensaje?',
    message: '¿Estás seguro de eliminar este mensaje? No podrás recuperarlo.',
    confirmText: 'Eliminar mensaje',
    loading: false,
    targetMensaje: mensaje
  }
}

const confirmarEliminarMensaje = async () => {
  if (!alerta.value.targetMensaje) return
  
  alerta.value.loading = true
  try {
    await chatStore.eliminarMensaje(alerta.value.targetMensaje.id)
    success('Mensaje eliminado')
    mensajeSeleccionado.value = null
    mensajeResaltado.value = null
  } catch (err) {
    showError('Error al eliminar el mensaje')
  } finally {
    alerta.value.isOpen = false
    alerta.value.loading = false
    alerta.value.targetMensaje = null
  }
}

const handleCancelarOpciones = () => {
  mensajeSeleccionado.value = null
  mensajeResaltado.value = null
  respondiendoA.value = null
  editando.value = null
}

const toggleNueva = () => {
  if (mostrandoNueva.value) {
    // Si ya está abierto, cerrarlo
    mostrandoNueva.value = false
    // En móvil, volver a mostrar sidebar si no hay conversación activa
    if (isMobile.value && !chatStore.conversacionActiva) {
      chatStore.sidebarAbierto = true
    }
  } else {
    // Abrir formulario
    mostrandoNueva.value = true
    chatStore.conversacionActiva = null
    // En móvil, ocultar sidebar cuando se abre el formulario
    if (isMobile.value) {
      chatStore.sidebarAbierto = false
    }
  }
}

const handleCancelarNueva = () => {
  mostrandoNueva.value = false
  // En móvil, volver a mostrar sidebar si no hay conversación activa
  if (isMobile.value && !chatStore.conversacionActiva) {
    chatStore.sidebarAbierto = true
  }
}

const handleVerPerfil = () => {
  mostrandoPerfil.value = true
  // En móvil, ocultar sidebar
  if (isMobile.value) {
    chatStore.sidebarAbierto = false
  }
}

const handleCerrarPerfil = () => {
  mostrandoPerfil.value = false
}

const handleAbrirBusqueda = () => {
  // Solo abrir búsqueda si hay conversación activa
  if (!chatStore.conversacionActiva) return
  
  mostrandoBusqueda.value = true
  // En móvil, ocultar sidebar
  if (isMobile.value) {
    chatStore.sidebarAbierto = false
  }
}

const handleCerrarBusqueda = () => {
  mostrandoBusqueda.value = false
  // En móvil, si hay conversación activa, asegurar que se muestre
  if (isMobile.value) {
    if (chatStore.conversacionActiva) {
      chatStore.sidebarAbierto = false
    } else {
      // Si no hay conversación activa, mostrar sidebar
      chatStore.sidebarAbierto = true
    }
  }
}

const handleIrAMensaje = async (mensajeId) => {
  // Cerrar búsqueda
  mostrandoBusqueda.value = false
  
  // Esperar a que se cierre la búsqueda y se muestre la lista
  await nextTick()
  
  // Buscar el mensaje en la lista actual
  let mensaje = chatStore.mensajes.find(m => m.id === mensajeId)
  
  // Si no está cargado, cargar todos los mensajes hasta este
  if (!mensaje) {
    try {
      mensaje = await chatStore.cargarMensajesHasta(mensajeId)
      // Esperar a que Vue actualice el DOM
      await nextTick()
    } catch (error) {
      console.error('Error al cargar mensajes hasta:', error)
      showError('Error al cargar el mensaje')
      return
    }
  }
  
  if (mensaje) {
    // Resaltar el mensaje
    mensajeResaltado.value = mensajeId
    
    // Función para hacer scroll al mensaje
    const scrollToMensaje = () => {
      const elemento = document.querySelector(`[data-mensaje-id="${mensajeId}"]`)
      if (elemento) {
        elemento.scrollIntoView({ 
          behavior: 'smooth', 
          block: 'center' 
        })
        return true
      }
      return false
    }
    
    // Intentar hacer scroll inmediatamente
    if (!scrollToMensaje()) {
      // Si no se encuentra, intentar varias veces con delays
      let intentos = 0
      const maxIntentos = 20
      const intervalo = setInterval(() => {
        intentos++
        if (scrollToMensaje() || intentos >= maxIntentos) {
          clearInterval(intervalo)
        }
      }, 100)
    }
    
    // Quitar resaltado después de 3 segundos (solo si no está seleccionado)
    setTimeout(() => {
      if (mensajeSeleccionado.value?.id !== mensajeId) {
        mensajeResaltado.value = null
      }
    }, 3000)
  }
}

const handleCreada = (conv) => {
  mostrandoNueva.value = false
  // En móvil, ocultar sidebar y mostrar conversación
  if (isMobile.value) {
    chatStore.sidebarAbierto = false
  }
  chatStore.seleccionarConversacion(conv.id)
}

// Mostrar vista de conversación en mobile
const mostrarConversacion = computed(() => {
  if (!isMobile.value) return true
  // En móvil, mostrar conversación si:
  // - Hay una activa Y (no estamos en perfil O estamos buscando)
  // - O estamos creando una nueva
  const tieneConversacion = chatStore.conversacionActiva !== null
  const enBusqueda = mostrandoBusqueda.value && tieneConversacion
  return (tieneConversacion && !mostrandoPerfil.value) || mostrandoNueva.value || enBusqueda
})

// Mostrar perfil
const mostrarPerfil = computed(() => {
  return mostrandoPerfil.value && chatStore.conversacionActiva?.es_grupo
})

// Mostrar contenido principal (conversación, nueva, perfil o búsqueda)
const mostrarContenido = computed(() => {
  if (!isMobile.value) return true
  // En móvil, mostrar contenido si:
  // - Hay una conversación activa Y (no estamos en perfil O estamos buscando) - conversación normal
  // - O estamos creando una nueva
  // - O estamos mostrando el perfil
  const tieneConversacion = chatStore.conversacionActiva !== null
  const enBusqueda = mostrandoBusqueda.value && tieneConversacion
  return (tieneConversacion && !mostrandoPerfil.value) || mostrandoNueva.value || enBusqueda || mostrarPerfil.value
})

// Mostrar sidebar
const mostrarSidebar = computed(() => {
  if (!isMobile.value) return true
  // En móvil, mostrar sidebar si:
  // - No hay conversación activa Y no estamos creando una nueva Y no estamos en perfil/búsqueda
  // - O está explícitamente abierto
  const debeMostrar = !chatStore.conversacionActiva && !mostrandoNueva.value && !mostrandoPerfil.value && !mostrandoBusqueda.value
  return debeMostrar || chatStore.sidebarAbierto
})

// Mantener resaltado sincronizado con mensaje seleccionado
watch(() => mensajeSeleccionado.value, (nuevoMensaje) => {
  if (nuevoMensaje) {
    // Si hay un mensaje seleccionado, mantener el resaltado
    mensajeResaltado.value = nuevoMensaje.id
  } else {
    // Si no hay mensaje seleccionado, limpiar el resaltado solo si no viene de búsqueda
    // (el resaltado de búsqueda se maneja en handleIrAMensaje)
    if (!mostrandoBusqueda.value) {
      mensajeResaltado.value = null
    }
  }
})

// Resetear estado al seleccionar de la lista
watch(() => chatStore.conversacionActiva, (val, oldVal) => {
  // Si cambió de conversación, limpiar mensaje seleccionado
  if (oldVal && val && oldVal.id !== val.id) {
    mensajeSeleccionado.value = null
  }
  
  if (val) {
    mostrandoNueva.value = false
    mostrandoPerfil.value = false
    mostrandoBusqueda.value = false
    mensajeResaltado.value = null // Limpiar resaltado al cambiar de conversación
    mensajeSeleccionado.value = null // Limpiar mensaje seleccionado al cambiar de conversación
    // En móvil, ocultar sidebar cuando se selecciona una conversación
    if (isMobile.value) {
      chatStore.sidebarAbierto = false
    }
  } else {
    // Si no hay conversación activa y estamos en móvil, mostrar sidebar
    if (isMobile.value && !mostrandoNueva.value) {
      chatStore.sidebarAbierto = true
    }
  }
})
</script>

<template>
  <div class="chat-layout">
    <!-- Sidebar -->
    <aside 
      v-if="mostrarSidebar"
      class="chat-sidebar"
      :class="{ 'sidebar-mobile': isMobile }"
    >
      <div class="sidebar-header">
        <div class="flex items-center justify-between w-full">
          <h2 class="sidebar-title">Mensajes</h2>
          <button 
            class="btn-plus" 
            title="Nueva Conversación"
            @click="toggleNueva"
            :class="{ active: mostrandoNueva }"
          >
            <PlusIcon class="w-5 h-5" />
          </button>
        </div>
      </div>
      
      <ConversacionBuscador />
      
      <div class="sidebar-content">
        <ConversacionLista />
      </div>
    </aside>

    <!-- Contenido principal -->
    <main 
      v-if="mostrarContenido"
      class="chat-main"
      :class="{ 'with-pattern': chatStore.conversacionActiva }"
    >
      <!-- Formulario Nueva Conversación -->
      <template v-if="mostrandoNueva">
        <ConversacionNueva 
          :is-mobile="isMobile"
          @cancelar="handleCancelarNueva"
          @creada="handleCreada"
        />
      </template>

      <template v-else-if="mostrarPerfil">
        <!-- Vista de perfil del grupo -->
        <ConversacionPerfil
          :is-mobile="isMobile"
          @cerrar="handleCerrarPerfil"
        />
      </template>

      <template v-else-if="mostrandoBusqueda && chatStore.conversacionActiva">
        <!-- Búsqueda de mensajes -->
        <BusquedaMensajes
          :is-mobile="isMobile"
          @cerrar="handleCerrarBusqueda"
          @ir-a-mensaje="handleIrAMensaje"
        />
      </template>

      <template v-else-if="chatStore.conversacionActiva">
        <!-- Barra de opciones de mensaje -->
        <MensajeOpcionesBar
          v-if="mensajeSeleccionado"
          :mensaje="mensajeSeleccionado"
          @responder="handleResponder"
          @editar="handleEditar"
          @eliminar="handleEliminar"
          @cancelar="handleCancelarOpciones"
        />

        <!-- Header de conversación -->
        <ConversacionHeader 
          v-else
          :is-mobile="isMobile"
          @ver-perfil="handleVerPerfil"
          @abrir-busqueda="handleAbrirBusqueda"
        />

        <!-- Lista de mensajes -->
        <MensajeLista
          :mensaje-seleccionado="mensajeSeleccionado"
          :mensaje-resaltado="mensajeResaltado"
          @seleccionar="handleSeleccionarMensaje"
        />

        <!-- Input de mensaje -->
        <MensajeInput
          :respondiendo-a="respondiendoA"
          :editando="editando"
          @cancelar-respuesta="respondiendoA = null"
          @cancelar-editar="editando = null"
        />
      </template>

      <!-- Estado vacío -->
      <div v-else class="chat-empty">
        <div class="empty-content">
          <div class="empty-icon-wrapper">
             <i class="fa-solid fa-comments text-6xl text-primary/40"></i>
          </div>
          <h3>Centro de Comunicaciones</h3>
          <p>Selecciona una conversación existente o crea una nueva para comenzar a colaborar con tu equipo.</p>
          <button class="btn-primary-ghost mt-6" @click="toggleNueva">
            <PlusIcon class="w-4 h-4 mr-2" />
            Iniciar nueva conversación
          </button>
        </div>
      </div>
    </main>

    <!-- Alerta de Confirmación de Mensaje -->
    <AlertaFlotante
      v-model:is-open="alerta.isOpen"
      :type="alerta.type"
      :title="alerta.title"
      :message="alerta.message"
      :confirm-text="alerta.confirmText"
      :loading="alerta.loading"
      @confirm="confirmarEliminarMensaje"
    />
  </div>
</template>

<style scoped>
.chat-layout {
  display: flex;
  height: 100%;
  background: var(--color-background);
  position: relative;
  overflow: hidden;
}

/* Sidebar */
.chat-sidebar {
  width: 320px;
  min-width: 320px;
  border-right: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  background: var(--color-background);
  z-index: 10;
}

.sidebar-mobile {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  z-index: 40;
  width: 100%;
  max-width: 100%;
}

.sidebar-header {
  padding: 16px 20px;
  border-bottom: 1px solid var(--color-border);
  background: linear-gradient(135deg, var(--color-muted) 0%, var(--color-background) 100%);
}

.sidebar-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-foreground);
  margin: 0;
  letter-spacing: -0.01em;
}

.btn-plus {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-primary);
  color: var(--color-primary-foreground);
  border: none;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-plus:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(var(--color-primary-rgb), 0.3);
}

.btn-plus.active {
  background: var(--color-accent);
  color: var(--color-foreground);
  transform: rotate(45deg);
}

.sidebar-content {
  flex: 1;
  overflow-y: auto;
}

/* Main content */
.chat-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  background: var(--color-muted);
  position: relative;
}

.chat-main.with-pattern {
  background-color: var(--color-muted);
  background-image: radial-gradient(var(--color-border) 0.5px, transparent 0.5px);
  background-size: 20px 20px;
}

/* Empty state */
.chat-empty {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  z-index: 1;
}

.empty-content {
  text-align: center;
  max-width: 400px;
  padding: 40px;
  background: var(--color-background);
  border-radius: 24px;
  border: 1px solid var(--color-border);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
}

.empty-icon-wrapper {
  margin-bottom: 24px;
}

.empty-content h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-foreground);
  margin: 0 0 12px 0;
}

.empty-content p {
  color: var(--color-muted-foreground);
  margin: 0;
  line-height: 1.6;
}

.btn-primary-ghost {
  display: inline-flex;
  align-items: center;
  padding: 10px 20px;
  background: var(--color-primary-alpha, rgba(var(--color-primary-rgb), 0.1));
  color: var(--color-primary);
  border: none;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary-ghost:hover {
  background: var(--color-primary);
  color: var(--color-primary-foreground);
}

/* Scrollbar */
.sidebar-content::-webkit-scrollbar {
  width: 6px;
}

.sidebar-content::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar-content::-webkit-scrollbar-thumb {
  background: var(--color-border);
  border-radius: 3px;
}

.sidebar-content::-webkit-scrollbar-thumb:hover {
  background: var(--color-muted-foreground);
}

/* Responsive */
@media (max-width: 1023px) {
  .chat-sidebar {
    width: 100%;
    max-width: 100%;
  }
}
</style>
