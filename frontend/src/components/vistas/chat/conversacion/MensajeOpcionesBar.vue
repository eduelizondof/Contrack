<script setup>
import { ref } from 'vue'
import XIcon from '@/components/globales/iconos/XIcon.vue'
import ReplyIcon from '@/components/globales/iconos/ReplyIcon.vue'
import EditIcon from '@/components/globales/iconos/EditIcon.vue'
import TrashIcon from '@/components/globales/iconos/TrashIcon.vue'
import CopyIcon from '@/components/globales/iconos/CopyIcon.vue'
import NotificacionToast from '@/components/ui/NotificacionToast.vue'

const props = defineProps({
  mensaje: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['responder', 'editar', 'eliminar', 'cancelar'])

const mostrarNotificacion = ref(false)

const copiarTexto = async () => {
  if (props.mensaje.contenido) {
    await navigator.clipboard.writeText(props.mensaje.contenido)
    mostrarNotificacion.value = true
  }
}
</script>

<template>
  <div class="opciones-bar">
    <div class="opciones-info">
      <div v-if="mensaje.es_propio && mensaje.vistos && mensaje.vistos.length > 0" class="vistos-info">
        <span class="vistos-label">Visto por:</span>
        <span class="vistos-nombres">
          {{ mensaje.vistos.map(v => v.name).join(', ') }}
        </span>
      </div>
      <div v-else-if="mensaje.es_propio" class="vistos-info">
        <span class="vistos-label">Aún no visto</span>
      </div>
      <div v-else class="vistos-info">
        <span class="opciones-preview">
          {{ mensaje.contenido?.substring(0, 30) }}{{ mensaje.contenido?.length > 30 ? '...' : '' }}
        </span>
      </div>
    </div>

    <div class="opciones-acciones">
      <!-- Responder -->
      <button 
        class="btn-opcion"
        title="Responder"
        @click="emit('responder', mensaje)"
      >
        <ReplyIcon class="w-5 h-5" />
      </button>

      <!-- Copiar -->
      <button 
        v-if="mensaje.contenido"
        class="btn-opcion"
        title="Copiar"
        @click="copiarTexto"
      >
        <CopyIcon class="w-5 h-5" />
      </button>

      <!-- Editar (solo propios) -->
      <button 
        v-if="mensaje.es_propio && !mensaje.eliminado"
        class="btn-opcion"
        title="Editar"
        @click="emit('editar', mensaje)"
      >
        <EditIcon class="w-5 h-5" />
      </button>

      <!-- Eliminar (solo propios) -->
      <button 
        v-if="mensaje.es_propio && !mensaje.eliminado"
        class="btn-opcion btn-danger"
        title="Eliminar"
        @click="emit('eliminar', mensaje)"
      >
        <TrashIcon class="w-5 h-5" />
      </button>

      <!-- Cerrar -->
      <button 
        class="btn-opcion"
        title="Cancelar"
        @click="emit('cancelar')"
      >
        <XIcon class="w-5 h-5" />
      </button>
    </div>
  </div>

  <!-- Notificación de copiado -->
  <NotificacionToast
    v-model:visible="mostrarNotificacion"
    mensaje="Mensaje copiado"
  />
</template>

<style scoped>
.opciones-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 16px;
  background: var(--color-primary);
  color: var(--color-primary-foreground);
}

.opciones-info {
  flex: 1;
  min-width: 0;
}

.opciones-preview {
  font-size: 0.875rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  opacity: 0.9;
}

.vistos-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.vistos-label {
  font-size: 0.75rem;
  opacity: 0.8;
  font-weight: 500;
}

.vistos-nombres {
  font-size: 0.875rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  opacity: 0.9;
}

.opciones-acciones {
  display: flex;
  align-items: center;
  gap: 4px;
}

.btn-opcion {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.1);
  color: var(--color-primary-foreground);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-opcion:hover {
  background: rgba(255, 255, 255, 0.2);
}

.btn-danger:hover {
  background: #ef4444;
}
</style>
