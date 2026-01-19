<script setup>
import { computed } from 'vue'
import { format } from 'date-fns'
import { useAuthStore } from '@/stores/auth'
import CheckIcon from '@/components/globales/iconos/CheckIcon.vue'
import CheckCheckIcon from '@/components/globales/iconos/CheckCheckIcon.vue'
import EditIcon from '@/components/globales/iconos/EditIcon.vue'
import ImageIcon from '@/components/globales/iconos/ImageIcon.vue'
import FileTextIcon from '@/components/globales/iconos/FileTextIcon.vue'
import LinkIcon from '@/components/globales/iconos/LinkIcon.vue'
import ReplyIcon from '@/components/globales/iconos/ReplyIcon.vue'

const authStore = useAuthStore()

const props = defineProps({
  mensaje: {
    type: Object,
    required: true
  },
  seleccionado: {
    type: Boolean,
    default: false
  },
  resaltado: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['click'])

// Formatear hora
const hora = computed(() => {
  try {
    return format(new Date(props.mensaje.creado_el), 'HH:mm')
  } catch {
    return ''
  }
})

// Obtener información del usuario (propio o de otros)
const usuarioInfo = computed(() => {
  if (props.mensaje.es_propio) {
    // Para mensajes propios, usar información del usuario autenticado
    return {
      id: authStore.usuario?.id || 0,
      name: authStore.usuario?.name || 'Tú'
    }
  } else {
    // Para mensajes de otros, usar información del mensaje
    return {
      id: props.mensaje.usuario?.id || 0,
      name: props.mensaje.usuario?.name || 'Usuario'
    }
  }
})

// Color de avatar basado en user_id
const avatarColor = computed(() => {
  const colors = [
    '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
    '#ec4899', '#f43f5e', '#ef4444', '#f97316',
    '#f59e0b', '#eab308', '#84cc16', '#22c55e',
  ]
  const userId = usuarioInfo.value.id || 0
  return colors[userId % colors.length]
})

// Iniciales del usuario
const iniciales = computed(() => {
  const name = usuarioInfo.value.name || '?'
  if (typeof name === 'string' && name.length > 0) {
    return name.charAt(0).toUpperCase()
  }
  return '?'
})

// Detectar si el contenido es un link
const esLink = computed(() => {
  if (props.mensaje.tipo === 'link') return true
  if (props.mensaje.tipo !== 'texto') return false
  
  const urlRegex = /(https?:\/\/[^\s]+)/g
  return urlRegex.test(props.mensaje.contenido || '')
})

// Formatear contenido con links clickeables
const contenidoFormateado = computed(() => {
  if (!props.mensaje.contenido) return ''
  
  const urlRegex = /(https?:\/\/[^\s]+)/g
  return props.mensaje.contenido.replace(urlRegex, (url) => {
    return `<a href="${url}" target="_blank" rel="noopener noreferrer" class="mensaje-link">${url}</a>`
  })
})

const handleClick = () => {
  if (!props.mensaje.eliminado) {
    emit('click')
  }
}
</script>

<template>
  <div 
    class="mensaje-item"
    :class="{ 
      propio: mensaje.es_propio, 
      seleccionado,
      resaltado,
      eliminado: mensaje.eliminado 
    }"
    :data-mensaje-id="mensaje.id"
    @click="handleClick"
  >
    <!-- Avatar (siempre visible) -->
    <div 
      class="mensaje-avatar"
      :style="{ backgroundColor: avatarColor }"
    >
      {{ iniciales }}
    </div>

    <div class="mensaje-contenido-wrapper">
      <!-- Nombre del remitente (siempre visible) -->
      <span class="mensaje-autor">
        {{ usuarioInfo.name }}
      </span>

      <!-- Respuesta a -->
      <div v-if="mensaje.responde_a" class="mensaje-respuesta">
        <ReplyIcon class="w-3 h-3" />
        <span class="respuesta-autor">{{ mensaje.responde_a.usuario }}</span>
        <span class="respuesta-contenido">{{ mensaje.responde_a.contenido }}</span>
      </div>

      <!-- Contenido del mensaje -->
      <div class="mensaje-burbuja" :class="{ propio: mensaje.es_propio }">
        <!-- Mensaje eliminado -->
        <template v-if="mensaje.eliminado">
          <span class="mensaje-eliminado">
            🚫 Mensaje eliminado
          </span>
        </template>

        <!-- Adjuntos (imágenes) -->
        <template v-else-if="mensaje.adjuntos?.length > 0">
          <div 
            v-for="adjunto in mensaje.adjuntos"
            :key="adjunto.id"
            class="mensaje-adjunto"
          >
            <template v-if="adjunto.es_imagen">
              <img 
                :src="adjunto.url" 
                :alt="adjunto.nombre_original"
                class="adjunto-imagen"
                loading="lazy"
              />
            </template>
            <template v-else>
              <a 
                :href="adjunto.url" 
                target="_blank"
                class="adjunto-archivo"
              >
                <FileTextIcon class="w-8 h-8" />
                <div class="archivo-info">
                  <span class="archivo-nombre">{{ adjunto.nombre_original }}</span>
                  <span class="archivo-peso">{{ adjunto.peso }}</span>
                </div>
              </a>
            </template>
          </div>
          <p v-if="mensaje.contenido" class="mensaje-texto" v-html="contenidoFormateado"></p>
        </template>

        <!-- Texto normal -->
        <template v-else>
          <p class="mensaje-texto" v-html="contenidoFormateado"></p>
        </template>

        <!-- Footer: hora, editado, visto -->
        <div class="mensaje-footer">
          <span v-if="mensaje.editado" class="mensaje-editado">
            <EditIcon class="w-3 h-3" />
            editado
          </span>
          <span class="mensaje-hora">{{ hora }}</span>
          <template v-if="mensaje.es_propio && !mensaje.eliminado">
            <CheckCheckIcon 
              v-if="mensaje.visto_por_todos" 
              class="w-4 h-4 mensaje-visto" 
            />
            <CheckIcon 
              v-else-if="mensaje.vistos_count > 0" 
              class="w-4 h-4" 
            />
            <CheckIcon 
              v-else 
              class="w-4 h-4" 
            />
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.mensaje-item {
  display: flex;
  gap: 8px;
  max-width: 80%;
  cursor: pointer;
  transition: all 0.15s ease;
  border-radius: 12px;
  padding: 4px;
}

.mensaje-item:hover {
  background: var(--color-background);
}

.mensaje-item.seleccionado {
  background: var(--color-accent);
}

.mensaje-item.resaltado {
  background: var(--color-primary-alpha, rgba(var(--color-primary-rgb), 0.15));
  animation: resaltarMensaje 0.6s ease-out;
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
  box-shadow: 0 0 0 4px var(--color-primary-alpha, rgba(var(--color-primary-rgb), 0.1));
}

.mensaje-item.resaltado:hover {
  background: var(--color-primary-alpha, rgba(var(--color-primary-rgb), 0.2));
}

@keyframes resaltarMensaje {
  0% {
    transform: scale(1);
    box-shadow: 0 0 0 0 var(--color-primary-alpha, rgba(var(--color-primary-rgb), 0.3));
    outline-offset: 0px;
  }
  50% {
    transform: scale(1.01);
    box-shadow: 0 0 0 8px var(--color-primary-alpha, rgba(var(--color-primary-rgb), 0.1));
    outline-offset: 4px;
  }
  100% {
    transform: scale(1);
    box-shadow: 0 0 0 4px var(--color-primary-alpha, rgba(var(--color-primary-rgb), 0.1));
    outline-offset: 2px;
  }
}

.mensaje-item.propio {
  align-self: flex-end;
  flex-direction: row-reverse;
}

.mensaje-item.eliminado {
  cursor: default;
  opacity: 0.7;
}

/* Avatar */
.mensaje-avatar {
  width: 36px;
  height: 36px;
  min-width: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 0.875rem;
}

/* Contenido */
.mensaje-contenido-wrapper {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.mensaje-autor {
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--color-primary);
  padding-left: 12px;
}

.mensaje-item.propio .mensaje-autor {
  padding-left: 0;
  padding-right: 12px;
  text-align: right;
}

/* Respuesta */
.mensaje-respuesta {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px 12px;
  background: var(--color-muted);
  border-left: 3px solid var(--color-primary);
  border-radius: 0 8px 8px 0;
  font-size: 0.75rem;
  color: var(--color-muted-foreground);
  margin-bottom: 2px;
}

.respuesta-autor {
  font-weight: 500;
  color: var(--color-primary);
}

.respuesta-contenido {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 200px;
}

/* Burbuja */
.mensaje-burbuja {
  background: var(--color-background);
  border-radius: 16px;
  padding: 10px 14px;
  border: 1px solid var(--color-border);
  color: var(--color-foreground);
}

.mensaje-burbuja.propio {
  background: var(--color-primary);
  color: var(--color-primary-foreground);
  border-color: var(--color-primary);
}

/* Texto */
.mensaje-texto {
  margin: 0;
  white-space: pre-wrap;
  word-break: break-word;
  font-size: 0.9375rem;
  line-height: 1.4;
  color: inherit;
}

.mensaje-burbuja.propio .mensaje-texto :deep(.mensaje-link) {
  color: var(--color-primary-foreground);
  text-decoration: underline;
}

.mensaje-burbuja:not(.propio) .mensaje-texto :deep(.mensaje-link) {
  color: var(--color-primary);
  text-decoration: underline;
}

/* Mensaje eliminado */
.mensaje-eliminado {
  font-style: italic;
  color: var(--color-muted-foreground);
  font-size: 0.875rem;
}

/* Adjuntos */
.mensaje-adjunto {
  margin-bottom: 8px;
}

.adjunto-imagen {
  max-width: 100%;
  max-height: 300px;
  border-radius: 12px;
  object-fit: cover;
}

.adjunto-archivo {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: var(--color-muted);
  border-radius: 12px;
  text-decoration: none;
  color: var(--color-foreground);
  transition: background 0.2s ease;
}

.adjunto-archivo:hover {
  background: var(--color-accent);
}

.mensaje-burbuja.propio .adjunto-archivo {
  background: rgba(255, 255, 255, 0.2);
  color: var(--color-primary-foreground);
}

.archivo-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.archivo-nombre {
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.archivo-peso {
  font-size: 0.75rem;
  opacity: 0.7;
}

/* Footer */
.mensaje-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 4px;
  margin-top: 4px;
}

.mensaje-hora {
  font-size: 0.625rem;
  opacity: 0.7;
  color: inherit;
}

.mensaje-editado {
  display: flex;
  align-items: center;
  gap: 2px;
  font-size: 0.625rem;
  opacity: 0.7;
  color: inherit;
}

.mensaje-visto {
  color: #22c55e;
}

.mensaje-burbuja.propio .mensaje-visto {
  color: var(--color-primary-foreground);
}

/* Responsive */
@media (max-width: 640px) {
  .mensaje-item {
    max-width: 90%;
  }
}
</style>
