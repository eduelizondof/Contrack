<script setup>
import { computed } from 'vue'
import { formatDistanceToNow } from 'date-fns'
import { es } from 'date-fns/locale'
import { useAuthStore } from '@/stores/auth'
import UsersIcon from '@/components/globales/iconos/UsersIcon.vue'
import ArchiveIcon from '@/components/globales/iconos/ArchiveIcon.vue'
import ChevronRightIcon from '@/components/globales/iconos/ChevronRightIcon.vue'

const props = defineProps({
  conversacion: {
    type: Object,
    required: true
  },
  activa: {
    type: Boolean,
    default: false
  },
  archivada: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['click'])
const authStore = useAuthStore()

// Obtener el otro usuario en chats 1-1
const otroUsuario = computed(() => {
  if (props.conversacion.es_grupo || !props.conversacion.usuarios) return null
  return props.conversacion.usuarios.find(u => u.id !== authStore.usuario?.id)
})

// Formatear tiempo relativo
const tiempoRelativo = computed(() => {
  if (!props.conversacion.ultimo_mensaje?.creado_el) return ''
  
  try {
    const fecha = new Date(props.conversacion.ultimo_mensaje.creado_el)
    return formatDistanceToNow(fecha, { addSuffix: true, locale: es })
  } catch {
    return ''
  }
})

// Iniciales para avatar
const iniciales = computed(() => {
  // Si es chat 1-1, usar iniciales del otro usuario
  if (otroUsuario.value) {
    const nombre = otroUsuario.value.name || ''
    return nombre.split(' ').slice(0, 2).map(p => p.charAt(0)).join('').toUpperCase() || '?'
  }
  
  // Si es grupo con nombre, usar el nombre del grupo
  const nombre = props.conversacion.nombre || ''
  if (nombre) {
    return nombre.split(' ').slice(0, 2).map(p => p.charAt(0)).join('').toUpperCase()
  }
  
  // Fallback: usar el primer usuario
  const usuarios = props.conversacion.usuarios || []
  if (usuarios.length > 0) {
    return usuarios[0].name.charAt(0).toUpperCase()
  }
  
  return '?'
})

// Color de avatar basado en ID del otro usuario o conversación
const avatarColor = computed(() => {
  const colors = [
    '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
    '#ec4899', '#f43f5e', '#ef4444', '#f97316',
    '#f59e0b', '#eab308', '#84cc16', '#22c55e',
    '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9',
  ]
  // Usar ID del otro usuario en chats 1-1, o ID de conversación en grupos
  const id = otroUsuario.value?.id || props.conversacion.id
  return colors[id % colors.length]
})
</script>

<template>
  <button
    class="conversacion-item"
    :class="{ activa, archivada }"
    @click="emit('click')"
  >
    <!-- Avatar -->
    <div 
      class="avatar"
      :style="{ backgroundColor: avatarColor }"
    >
      <UsersIcon v-if="conversacion.es_grupo" class="w-5 h-5" />
      <span v-else>{{ iniciales }}</span>
    </div>

    <!-- Info -->
    <div class="info">
      <div class="info-header">
        <span class="nombre">{{ conversacion.nombre }}</span>
        <span class="tiempo">{{ tiempoRelativo }}</span>
      </div>
      <div class="ultimo-mensaje">
        <template v-if="conversacion.ultimo_mensaje">
          <!-- En grupos, mostrar nombre del usuario que envió el mensaje -->
          <span class="usuario" v-if="conversacion.es_grupo">
            {{ conversacion.ultimo_mensaje.usuario?.name || conversacion.ultimo_mensaje.usuario }}:
          </span>
          <span class="contenido">
            {{ conversacion.ultimo_mensaje.contenido }}
          </span>
        </template>
        <span v-else class="sin-mensajes">No hay mensajes</span>
      </div>
    </div>

    <!-- Badge mensajes nuevos (solo si el último mensaje es de otro usuario y no leído) -->
    <div v-if="conversacion.tiene_mensajes_nuevos" class="badge-mensajes-nuevos"></div>

    <!-- Indicador archivado -->
    <ArchiveIcon v-if="archivada" class="icono-archivada w-4 h-4" />

    <!-- Icono de click (solo móvil) -->
    <ChevronRightIcon class="icono-click w-5 h-5" />
  </button>
</template>

<style scoped>
.conversacion-item {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 12px;
  border: none;
  border-radius: 12px;
  background: transparent;
  cursor: pointer;
  text-align: left;
  transition: all 0.2s ease;
}

.conversacion-item:hover {
  background: var(--color-muted);
}

.conversacion-item.activa {
  background: var(--color-accent);
}

.conversacion-item.archivada {
  opacity: 0.7;
}

/* Avatar */
.avatar {
  width: 48px;
  height: 48px;
  min-width: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 1rem;
}

/* Info */
.info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.nombre {
  font-weight: 500;
  color: var(--color-foreground);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tiempo {
  font-size: 0.75rem;
  color: var(--color-muted-foreground);
  white-space: nowrap;
  flex-shrink: 0;
}

.ultimo-mensaje {
  display: flex;
  gap: 4px;
  font-size: 0.875rem;
  color: var(--color-muted-foreground);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.ultimo-mensaje .usuario {
  font-weight: 500;
  flex-shrink: 0;
}

.ultimo-mensaje .contenido {
  overflow: hidden;
  text-overflow: ellipsis;
}

.sin-mensajes {
  font-style: italic;
}

/* Badge mensajes nuevos */
.badge-mensajes-nuevos {
  width: 10px;
  height: 10px;
  min-width: 10px;
  border-radius: 50%;
  background: #ef4444;
  flex-shrink: 0;
  box-shadow: 0 0 0 2px var(--color-background);
}

/* Icono archivada */
.icono-archivada {
  color: var(--color-muted-foreground);
  flex-shrink: 0;
}

/* Icono de click (solo móvil) */
.icono-click {
  color: var(--color-muted-foreground);
  flex-shrink: 0;
  opacity: 0.6;
  transition: all 0.2s ease;
  display: none; /* Oculto por defecto en desktop */
}

@media (max-width: 1023px) {
  .icono-click {
    display: block; /* Mostrar solo en móvil */
  }

  .conversacion-item:hover .icono-click,
  .conversacion-item:active .icono-click {
    opacity: 1;
    transform: translateX(4px);
  }

  .conversacion-item.activa .icono-click {
    color: var(--color-primary);
    opacity: 1;
  }
}
</style>
