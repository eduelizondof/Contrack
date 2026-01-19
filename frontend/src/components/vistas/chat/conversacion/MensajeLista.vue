<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { useChatStore } from '@/stores/chat'
import MensajeItem from './MensajeItem.vue'
import { format, isToday, isYesterday, isSameDay } from 'date-fns'
import { es } from 'date-fns/locale'

const props = defineProps({
  mensajeSeleccionado: {
    type: Object,
    default: null
  },
  mensajeResaltado: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['seleccionar'])

const chatStore = useChatStore()
const listaRef = ref(null)
const cargandoMas = ref(false)

// Verificar si hay mensajes temporales
const tieneMensajesTemporales = computed(() => {
  return chatStore.mensajes.some(m => m.es_temporal)
})

// Agrupar mensajes por fecha
const mensajesAgrupados = computed(() => {
  const grupos = []
  let grupoActual = null

  chatStore.mensajes.forEach((mensaje) => {
    const fecha = new Date(mensaje.creado_el)
    
    if (!grupoActual || !isSameDay(new Date(grupoActual.fecha), fecha)) {
      grupoActual = {
        fecha: mensaje.creado_el,
        label: formatearFechaGrupo(fecha),
        mensajes: []
      }
      grupos.push(grupoActual)
    }
    
    grupoActual.mensajes.push(mensaje)
  })

  return grupos
})

function formatearFechaGrupo(fecha) {
  if (isToday(fecha)) return 'Hoy'
  if (isYesterday(fecha)) return 'Ayer'
  return format(fecha, "d 'de' MMMM, yyyy", { locale: es })
}

// Scroll al final cuando hay nuevos mensajes
watch(
  () => chatStore.mensajes.length,
  async (newLen, oldLen) => {
    if (newLen > oldLen) {
      await nextTick()
      scrollToBottom()
    }
  }
)

// Cuando se reemplazan mensajes temporales, mantener scroll al final
watch(
  () => tieneMensajesTemporales.value,
  async (tieneTemporales, teniaTemporales) => {
    // Si antes tenía temporales y ahora no, significa que se cargaron los reales
    if (teniaTemporales && !tieneTemporales) {
      await nextTick()
      scrollToBottom()
    }
  }
)

// Scroll al final al montar
onMounted(async () => {
  await nextTick()
  scrollToBottom()
})

function scrollToBottom() {
  if (listaRef.value) {
    listaRef.value.scrollTop = listaRef.value.scrollHeight
  }
}

// Cargar más mensajes al hacer scroll arriba
async function handleScroll() {
  if (!listaRef.value || cargandoMas.value) return

  if (listaRef.value.scrollTop < 100 && chatStore.tieneMasMensajes) {
    cargandoMas.value = true
    const scrollHeightAntes = listaRef.value.scrollHeight
    
    await chatStore.cargarMasMensajes()
    
    await nextTick()
    // Mantener posición de scroll
    listaRef.value.scrollTop = listaRef.value.scrollHeight - scrollHeightAntes
    cargandoMas.value = false
  }
}

function handleSeleccionar(mensaje) {
  emit('seleccionar', mensaje)
}
</script>

<template>
  <div 
    ref="listaRef"
    class="mensaje-lista"
    @scroll="handleScroll"
  >
    <!-- Indicador de carga (solo si no hay mensajes temporales) -->
    <div v-if="(cargandoMas || chatStore.cargandoMensajes) && chatStore.mensajes.length === 0" class="lista-loading">
      <span class="spinner"></span>
      <span>Cargando mensajes...</span>
    </div>
    
    <!-- Indicador de carga en background (cuando ya hay mensajes) -->
    <div v-if="chatStore.cargandoMensajes && chatStore.mensajes.length > 0 && !tieneMensajesTemporales" class="lista-loading-background">
      <span class="spinner-small"></span>
      <span>Cargando más mensajes...</span>
    </div>

    <!-- Grupos de mensajes -->
    <div 
      v-for="grupo in mensajesAgrupados"
      :key="grupo.fecha"
      class="mensaje-grupo"
    >
      <!-- Separador de fecha -->
      <div class="fecha-separador">
        <span>{{ grupo.label }}</span>
      </div>

      <!-- Mensajes del grupo -->
      <MensajeItem
        v-for="mensaje in grupo.mensajes"
        :key="mensaje.id"
        :mensaje="mensaje"
        :seleccionado="mensajeSeleccionado?.id === mensaje.id"
        :resaltado="mensajeResaltado === mensaje.id"
        @click="handleSeleccionar(mensaje)"
      />
    </div>

    <!-- Estado vacío -->
    <div v-if="chatStore.mensajes.length === 0 && !chatStore.cargandoMensajes && !tieneMensajesTemporales" class="lista-vacia">
      <div class="vacia-content">
        <div class="vacia-icon-bg">
          <i class="fa-solid fa-paper-plane text-4xl text-primary/60"></i>
        </div>
        <p>Aún no hay mensajes</p>
        <span>¡Manda un saludo para iniciar la conversación! 👋</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.mensaje-lista {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

/* Loading */
.lista-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px;
  color: var(--color-muted-foreground);
  font-size: 0.875rem;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.spinner-small {
  width: 16px;
  height: 16px;
  border: 2px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.lista-loading-background {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 8px;
  color: var(--color-muted-foreground);
  font-size: 0.75rem;
  position: sticky;
  top: 0;
  background: var(--color-background);
  z-index: 10;
  border-bottom: 1px solid var(--color-border);
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Grupos */
.mensaje-grupo {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

/* Separador de fecha */
.fecha-separador {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px 0;
}

.fecha-separador span {
  padding: 4px 16px;
  background: var(--color-background);
  border-radius: 9999px;
  font-size: 0.75rem;
  color: var(--color-muted-foreground);
  font-weight: 500;
}

/* Estado vacío */
.lista-vacia {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.vacia-content {
  text-align: center;
  color: var(--color-muted-foreground);
}

.vacia-icon-bg {
  width: 80px;
  height: 80px;
  margin: 0 auto 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-background);
  border-radius: 20px;
  border: 1px solid var(--color-border);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.vacia-content p {
  font-weight: 600;
  color: var(--color-foreground);
  margin: 0 0 4px 0;
  font-size: 1.125rem;
}

.vacia-content span {
  font-size: 0.875rem;
  opacity: 0.8;
}

/* Scrollbar */
.mensaje-lista::-webkit-scrollbar {
  width: 6px;
}

.mensaje-lista::-webkit-scrollbar-track {
  background: transparent;
}

.mensaje-lista::-webkit-scrollbar-thumb {
  background: var(--color-border);
  border-radius: 3px;
}

.mensaje-lista::-webkit-scrollbar-thumb:hover {
  background: var(--color-muted-foreground);
}
</style>
