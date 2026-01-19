<script setup>
import { ref, watch } from 'vue'
import { useChatStore } from '@/stores/chat'
import SearchIcon from '@/components/globales/iconos/SearchIcon.vue'
import XIcon from '@/components/globales/iconos/XIcon.vue'
import ArrowLeftIcon from '@/components/globales/iconos/ArrowLeftIcon.vue'
import ArrowRightIcon from '@/components/globales/iconos/ArrowRightIcon.vue'

const resaltarTexto = (texto, busqueda) => {
  if (!texto || !busqueda) return texto
  const regex = new RegExp(`(${busqueda.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi')
  return texto.replace(regex, '<mark>$1</mark>')
}

const formatearFecha = (fecha) => {
  if (!fecha) return ''
  const date = new Date(fecha)
  const ahora = new Date()
  const diff = ahora - date
  const minutos = Math.floor(diff / 60000)
  const horas = Math.floor(minutos / 60)
  const dias = Math.floor(horas / 24)

  if (minutos < 1) return 'Ahora'
  if (minutos < 60) return `Hace ${minutos} min`
  if (horas < 24) return `Hace ${horas} h`
  if (dias < 7) return `Hace ${dias} d`
  
  return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
}

const props = defineProps({
  isMobile: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['cerrar', 'ir-a-mensaje'])

const chatStore = useChatStore()

const busqueda = ref('')
const resultados = ref([])
const buscando = ref(false)
const indiceSeleccionado = ref(-1)

const buscar = async () => {
  if (busqueda.value.length < 2) {
    resultados.value = []
    indiceSeleccionado.value = -1
    return
  }

  // Validar que hay conversación activa
  if (!chatStore.conversacionActiva?.id) {
    console.error('No hay conversación activa para buscar')
    resultados.value = []
    return
  }

  buscando.value = true
  try {
    const mensajes = await chatStore.buscarMensajes(chatStore.conversacionActiva.id, busqueda.value)
    resultados.value = mensajes
    indiceSeleccionado.value = -1
  } catch (error) {
    console.error('Error al buscar mensajes:', error)
    resultados.value = []
  } finally {
    buscando.value = false
  }
}

const irAMensaje = (mensaje) => {
  emit('ir-a-mensaje', mensaje.id)
  emit('cerrar')
}

const navegarResultados = (direccion) => {
  if (resultados.value.length === 0) return
  
  if (direccion === 'arriba') {
    indiceSeleccionado.value = indiceSeleccionado.value > 0 
      ? indiceSeleccionado.value - 1 
      : resultados.value.length - 1
  } else {
    indiceSeleccionado.value = indiceSeleccionado.value < resultados.value.length - 1
      ? indiceSeleccionado.value + 1
      : 0
  }
  
  // Scroll al resultado seleccionado
  const elemento = document.querySelector(`.resultado-item.selected`)
  if (elemento) {
    elemento.scrollIntoView({ block: 'nearest' })
  }
}

const seleccionarResultado = () => {
  if (indiceSeleccionado.value >= 0 && resultados.value[indiceSeleccionado.value]) {
    irAMensaje(resultados.value[indiceSeleccionado.value])
  }
}

// Buscar cuando cambia el término
watch(busqueda, () => {
  buscar()
})

// Manejar teclas
const handleKeydown = (e) => {
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    navegarResultados('abajo')
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    navegarResultados('arriba')
  } else if (e.key === 'Enter' && indiceSeleccionado.value >= 0) {
    e.preventDefault()
    seleccionarResultado()
  } else if (e.key === 'Escape') {
    emit('cerrar')
  }
}
</script>

<template>
  <div class="busqueda-mensajes" @keydown="handleKeydown">
    <!-- Validación: Si no hay conversación activa, mostrar mensaje -->
    <div v-if="!chatStore.conversacionActiva" class="sin-conversacion">
      <div class="sin-conversacion-content">
        <i class="fa-solid fa-exclamation-circle text-4xl text-muted-foreground mb-4"></i>
        <p>No hay conversación activa</p>
        <span class="hint">Selecciona una conversación para buscar mensajes</span>
        <button class="btn-cerrar-busqueda" @click="emit('cerrar')">
          Cerrar
        </button>
      </div>
    </div>

    <template v-else>
      <!-- Header -->
      <header class="busqueda-header">
      <button
        v-if="isMobile"
        class="btn-volver"
        @click="emit('cerrar')"
      >
        <ArrowLeftIcon class="w-5 h-5" />
      </button>
      <div class="busqueda-input-wrapper">
        <SearchIcon class="input-icon" />
        <input
          v-model="busqueda"
          type="text"
          class="busqueda-input"
          placeholder="Buscar mensajes..."
          autofocus
        />
        <button
          v-if="busqueda.length > 0"
          class="btn-limpiar"
          @click="busqueda = ''"
        >
          <XIcon class="w-4 h-4" />
        </button>
      </div>
      <button
        v-if="!isMobile"
        class="btn-cerrar"
        @click="emit('cerrar')"
      >
        <XIcon class="w-5 h-5" />
      </button>
    </header>

    <!-- Resultados -->
    <div class="busqueda-resultados">
      <div v-if="buscando" class="loading">
        <div class="spinner"></div>
        <span>Buscando...</span>
      </div>

      <div v-else-if="busqueda.length >= 2 && resultados.length === 0" class="no-resultados">
        <i class="fa-solid fa-search text-4xl text-muted-foreground mb-4"></i>
        <p>No se encontraron mensajes</p>
        <span class="hint">Intenta con otros términos de búsqueda</span>
      </div>

      <div v-else-if="busqueda.length < 2" class="hint">
        Escribe al menos 2 caracteres para buscar
      </div>

      <div v-else class="resultados-lista">
        <div class="resultados-header">
          <span>{{ resultados.length }} resultado{{ resultados.length !== 1 ? 's' : '' }}</span>
          <div class="navegacion-resultados">
            <button
              class="btn-nav"
              :disabled="resultados.length === 0"
              @click="navegarResultados('arriba')"
              title="Anterior (↑)"
            >
              <ArrowLeftIcon class="w-4 h-4" />
            </button>
            <span class="indice-info">
              {{ indiceSeleccionado >= 0 ? `${indiceSeleccionado + 1} / ${resultados.length}` : '-' }}
            </span>
            <button
              class="btn-nav"
              :disabled="resultados.length === 0"
              @click="navegarResultados('abajo')"
              title="Siguiente (↓)"
            >
              <ArrowRightIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
        <button
          v-for="(mensaje, index) in resultados"
          :key="mensaje.id"
          class="resultado-item"
          :class="{ selected: indiceSeleccionado === index }"
          @click="irAMensaje(mensaje)"
        >
          <div class="resultado-contenido">
            <div class="resultado-usuario">{{ mensaje.usuario }}</div>
            <div class="resultado-texto" v-html="resaltarTexto(mensaje.contenido || '', busqueda)"></div>
            <div class="resultado-fecha">{{ formatearFecha(mensaje.creado_el) }}</div>
          </div>
        </button>
      </div>
    </div>
    </template>
  </div>
</template>


<style scoped>
.busqueda-mensajes {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--color-background);
}

/* Sin conversación activa */
.sin-conversacion {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 24px;
}

.sin-conversacion-content {
  text-align: center;
  max-width: 400px;
}

.sin-conversacion-content p {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-foreground);
  margin: 16px 0 8px 0;
}

.sin-conversacion-content .hint {
  display: block;
  font-size: 0.875rem;
  color: var(--color-muted-foreground);
  margin-bottom: 24px;
}

.btn-cerrar-busqueda {
  padding: 10px 20px;
  border-radius: 12px;
  border: 1px solid var(--color-border);
  background: var(--color-primary);
  color: var(--color-primary-foreground);
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cerrar-busqueda:hover {
  opacity: 0.9;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(var(--color-primary-rgb), 0.3);
}

.busqueda-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid var(--color-border);
  background: var(--color-background);
}

.btn-volver,
.btn-cerrar {
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

.btn-volver:hover,
.btn-cerrar:hover {
  background: var(--color-muted);
}

.busqueda-input-wrapper {
  flex: 1;
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
  z-index: 1;
}

.busqueda-input {
  width: 100%;
  padding: 10px 40px 10px 40px;
  border-radius: 12px;
  border: 1px solid var(--color-border);
  background: var(--color-muted);
  color: var(--color-foreground);
  font-size: 0.875rem;
  transition: all 0.2s;
}

.busqueda-input:focus {
  outline: none;
  border-color: var(--color-primary);
  background: var(--color-background);
  box-shadow: 0 0 0 4px var(--color-primary-alpha, rgba(var(--color-primary-rgb), 0.1));
}

.btn-limpiar {
  position: absolute;
  right: 8px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: none;
  background: var(--color-muted-foreground);
  color: var(--color-background);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-limpiar:hover {
  background: var(--color-foreground);
}

.busqueda-resultados {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 40px;
  color: var(--color-muted-foreground);
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.no-resultados {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
  color: var(--color-muted-foreground);
}

.no-resultados p {
  font-size: 1rem;
  font-weight: 600;
  margin: 16px 0 8px 0;
}

.hint {
  padding: 40px 20px;
  text-align: center;
  color: var(--color-muted-foreground);
  font-size: 0.875rem;
}

.resultados-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--color-border);
  font-size: 0.875rem;
  color: var(--color-muted-foreground);
}

.navegacion-resultados {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-nav {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1px solid var(--color-border);
  background: var(--color-muted);
  color: var(--color-foreground);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-nav:hover:not(:disabled) {
  background: var(--color-accent);
  border-color: var(--color-primary);
}

.btn-nav:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.indice-info {
  font-size: 0.75rem;
  min-width: 50px;
  text-align: center;
}

.resultados-lista {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.resultado-item {
  padding: 12px;
  border-radius: 12px;
  border: 1px solid var(--color-border);
  background: var(--color-muted);
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
}

.resultado-item:hover {
  background: var(--color-accent);
  border-color: var(--color-primary);
}

.resultado-item.selected {
  background: var(--color-primary-alpha, rgba(var(--color-primary-rgb), 0.1));
  border-color: var(--color-primary);
}

.resultado-contenido {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.resultado-usuario {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-primary);
}

.resultado-texto {
  font-size: 0.875rem;
  color: var(--color-foreground);
  line-height: 1.4;
}

.resultado-texto :deep(mark) {
  background: var(--color-primary);
  color: var(--color-primary-foreground);
  padding: 2px 4px;
  border-radius: 4px;
  font-weight: 600;
}

.resultado-fecha {
  font-size: 0.75rem;
  color: var(--color-muted-foreground);
}
</style>
