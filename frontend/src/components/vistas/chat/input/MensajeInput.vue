<script setup>
import { ref, watch, nextTick } from 'vue'
import { useChatStore } from '@/stores/chat'
import SendIcon from '@/components/globales/iconos/SendIcon.vue'
import PaperclipIcon from '@/components/globales/iconos/PaperclipIcon.vue'
import SmileIcon from '@/components/globales/iconos/SmileIcon.vue'
import XIcon from '@/components/globales/iconos/XIcon.vue'
import ReplyIcon from '@/components/globales/iconos/ReplyIcon.vue'
import ImageIcon from '@/components/globales/iconos/ImageIcon.vue'
import FileTextIcon from '@/components/globales/iconos/FileTextIcon.vue'
import EmojiPicker from './EmojiPicker.vue'

const props = defineProps({
  respondiendoA: {
    type: Object,
    default: null
  },
  editando: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['cancelar-respuesta', 'cancelar-editar'])

const chatStore = useChatStore()

const mensaje = ref('')
const textareaRef = ref(null)
const inputFileRef = ref(null)
const mostrarEmojis = ref(false)
const mostrarAdjuntos = ref(false)
const archivoSeleccionado = ref(null)

// Pre-llenar contenido al editar
watch(() => props.editando, (val) => {
  if (val) {
    mensaje.value = val.contenido || ''
    nextTick(() => {
      textareaRef.value?.focus()
    })
  } else {
    mensaje.value = ''
  }
}, { immediate: true })

// Ajustar altura del textarea
watch(mensaje, () => {
  nextTick(() => {
    if (textareaRef.value) {
      textareaRef.value.style.height = 'auto'
      textareaRef.value.style.height = Math.min(textareaRef.value.scrollHeight, 150) + 'px'
    }
  })
})

const enviar = async () => {
  if (chatStore.enviandoMensaje || chatStore.editandoMensaje) return

  const contenido = mensaje.value.trim()
  
  if (!contenido && !archivoSeleccionado.value) return

  try {
    if (props.editando) {
      // Editar mensaje
      await chatStore.editarMensaje(props.editando.id, contenido)
      emit('cancelar-editar')
    } else if (archivoSeleccionado.value) {
      // Enviar archivo
      await chatStore.enviarArchivo(
        archivoSeleccionado.value,
        contenido,
        props.respondiendoA?.id
      )
      archivoSeleccionado.value = null
      emit('cancelar-respuesta')
    } else {
      // Enviar mensaje de texto
      await chatStore.enviarMensaje(
        contenido,
        'texto',
        props.respondiendoA?.id
      )
      emit('cancelar-respuesta')
    }

    mensaje.value = ''
    mostrarEmojis.value = false
  } catch (error) {
    console.error('Error al enviar:', error)
  }
}

const handleKeydown = (e) => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    enviar()
  }
}

const agregarEmoji = (emoji) => {
  mensaje.value += emoji
  textareaRef.value?.focus()
}

const abrirSelectorArchivo = (tipo = 'todos') => {
  mostrarAdjuntos.value = false
  if (inputFileRef.value) {
    if (tipo === 'imagen') {
      inputFileRef.value.accept = 'image/*'
    } else {
      inputFileRef.value.accept = '.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip'
    }
    inputFileRef.value.click()
  }
}

const handleArchivoSeleccionado = (e) => {
  const archivo = e.target.files?.[0]
  if (archivo) {
    // Validar tamaño (10MB)
    if (archivo.size > 10 * 1024 * 1024) {
      alert('El archivo no puede superar los 10MB')
      return
    }
    archivoSeleccionado.value = archivo
  }
  e.target.value = ''
}

const cancelarArchivo = () => {
  archivoSeleccionado.value = null
}
</script>

<template>
  <div class="mensaje-input-container">
    <!-- Preview de respuesta -->
    <div v-if="respondiendoA" class="input-respuesta">
      <ReplyIcon class="w-4 h-4" />
      <div class="respuesta-info">
        <span class="respuesta-autor">{{ respondiendoA.usuario.name }}</span>
        <span class="respuesta-contenido">{{ respondiendoA.contenido?.substring(0, 50) }}</span>
      </div>
      <button class="btn-cancelar" @click="emit('cancelar-respuesta')">
        <XIcon class="w-4 h-4" />
      </button>
    </div>

    <!-- Preview de edición -->
    <div v-if="editando" class="input-editando">
      <span class="editando-texto">
        <span>Editando mensaje</span>
      </span>
      <button 
        class="btn-cancelar" 
        :disabled="chatStore.editandoMensaje"
        @click="emit('cancelar-editar')"
      >
        <XIcon class="w-4 h-4" />
      </button>
    </div>

    <!-- Preview de archivo -->
    <div v-if="archivoSeleccionado" class="input-archivo">
      <div class="archivo-preview">
        <ImageIcon v-if="archivoSeleccionado.type.startsWith('image/')" class="w-5 h-5 archivo-icono" />
        <FileTextIcon v-else class="w-5 h-5 archivo-icono" />
        <div class="archivo-info">
          <span class="archivo-nombre">{{ archivoSeleccionado.name }}</span>
          <span class="archivo-hint">Haz clic en enviar para adjuntar el archivo</span>
        </div>
        <span class="archivo-peso">
          {{ (archivoSeleccionado.size / 1024).toFixed(1) }} KB
        </span>
      </div>
      <button class="btn-cancelar" @click="cancelarArchivo">
        <XIcon class="w-4 h-4" />
      </button>
    </div>

    <!-- Input principal -->
    <div class="input-wrapper">
      <!-- Botón adjuntos -->
      <div class="adjuntos-container">
        <button 
          class="btn-icon"
          @click="mostrarAdjuntos = !mostrarAdjuntos"
        >
          <PaperclipIcon class="w-5 h-5" />
        </button>

        <!-- Dropdown adjuntos -->
        <Transition name="fade">
          <div v-if="mostrarAdjuntos" class="adjuntos-dropdown">
            <button 
              class="dropdown-item"
              @click="abrirSelectorArchivo('imagen')"
            >
              <ImageIcon class="w-5 h-5" />
              <span>Imagen</span>
            </button>
            <button 
              class="dropdown-item"
              @click="abrirSelectorArchivo('documento')"
            >
              <FileTextIcon class="w-5 h-5" />
              <span>Documento</span>
            </button>
          </div>
        </Transition>
      </div>

      <!-- Textarea -->
      <textarea
        ref="textareaRef"
        v-model="mensaje"
        :placeholder="editando ? 'Editar mensaje...' : 'Escribe un mensaje...'"
        class="input-textarea"
        rows="1"
        @keydown="handleKeydown"
      ></textarea>

      <!-- Botón emojis -->
      <div class="emoji-container">
        <button 
          class="btn-icon"
          @click="mostrarEmojis = !mostrarEmojis"
        >
          <SmileIcon class="w-5 h-5" />
        </button>

        <!-- Picker de emojis -->
        <EmojiPicker 
          v-if="mostrarEmojis"
          @seleccionar="agregarEmoji"
          @cerrar="mostrarEmojis = false"
        />
      </div>

      <!-- Botón enviar -->
      <button 
        class="btn-enviar"
        :disabled="(!mensaje.trim() && !archivoSeleccionado) || chatStore.enviandoMensaje || chatStore.editandoMensaje"
        :class="{ 'editando': chatStore.editandoMensaje }"
        @click="enviar"
      >
        <span v-if="chatStore.enviandoMensaje || chatStore.editandoMensaje" class="spinner"></span>
        <SendIcon v-else class="w-5 h-5" />
      </button>
    </div>

    <!-- Input file oculto -->
    <input
      ref="inputFileRef"
      type="file"
      class="hidden"
      @change="handleArchivoSeleccionado"
    />

    <!-- Overlay para cerrar dropdowns -->
    <div 
      v-if="mostrarAdjuntos || mostrarEmojis"
      class="input-overlay"
      @click="mostrarAdjuntos = false; mostrarEmojis = false"
    ></div>
  </div>
</template>

<style scoped>
.mensaje-input-container {
  border-top: 1px solid var(--color-border);
  background: var(--color-background);
  position: relative;
}

/* Previews */
.input-respuesta,
.input-editando,
.input-archivo {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: var(--color-background);
  border-bottom: 1px solid var(--color-border);
  font-size: 0.875rem;
}

.respuesta-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.respuesta-autor {
  font-weight: 500;
  color: var(--color-primary);
}

.respuesta-contenido {
  color: var(--color-muted-foreground);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.input-editando {
  color: var(--color-primary);
  font-weight: 500;
}

.input-editando span {
  flex: 1;
}

.editando-texto {
  display: flex;
  align-items: center;
  gap: 8px;
}

.archivo-preview {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.archivo-icono {
  color: var(--color-primary);
  flex-shrink: 0;
}

.archivo-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.archivo-nombre {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-weight: 600;
  color: var(--color-foreground);
  font-size: 0.875rem;
}

.archivo-hint {
  font-size: 0.75rem;
  color: var(--color-muted-foreground);
  font-weight: 400;
}

.archivo-peso {
  color: var(--color-muted-foreground);
  flex-shrink: 0;
  font-size: 0.8125rem;
  font-weight: 500;
}

.btn-cancelar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: none;
  background: var(--color-muted-foreground);
  color: var(--color-background);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  transition: background 0.2s ease;
}

.btn-cancelar:hover {
  background: var(--color-foreground);
}

/* Input wrapper */
.input-wrapper {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  padding: 12px 16px;
}

.btn-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: none;
  background: transparent;
  color: var(--color-muted-foreground);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.btn-icon:hover {
  background: var(--color-muted);
  color: var(--color-foreground);
}

/* Textarea */
.input-textarea {
  flex: 1;
  min-height: 40px;
  max-height: 150px;
  padding: 10px 16px;
  border: 1px solid var(--color-border);
  border-radius: 20px;
  background: var(--color-muted);
  color: var(--color-foreground);
  font-size: 0.9375rem;
  font-family: inherit;
  resize: none;
  overflow-y: auto;
  transition: all 0.2s ease;
}

.input-textarea:focus {
  outline: none;
  border-color: var(--color-primary);
  background: var(--color-background);
}

.input-textarea::placeholder {
  color: var(--color-muted-foreground);
}

/* Botón enviar */
.btn-enviar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: none;
  background: var(--color-primary);
  color: var(--color-primary-foreground);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.btn-enviar:hover:not(:disabled) {
  transform: scale(1.05);
}

.btn-enviar:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}


/* Spinner */
.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Dropdowns */
.adjuntos-container,
.emoji-container {
  position: relative;
}

.adjuntos-dropdown {
  position: absolute;
  bottom: 100%;
  left: 0;
  margin-bottom: 8px;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
  z-index: 50;
  overflow: hidden;
}

.dropdown-item {
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
  white-space: nowrap;
}

.dropdown-item:hover {
  background: var(--color-muted);
}

/* Overlay */
.input-overlay {
  position: fixed;
  inset: 0;
  z-index: 40;
}

/* Hidden */
.hidden {
  display: none;
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
