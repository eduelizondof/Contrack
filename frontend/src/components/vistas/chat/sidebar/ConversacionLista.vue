<script setup>
import { ref, computed } from 'vue'
import { useChatStore } from '@/stores/chat'
import ConversacionItem from './ConversacionItem.vue'
import ArchiveIcon from '@/components/globales/iconos/ArchiveIcon.vue'
import MessageCircleIcon from '@/components/globales/iconos/MessageCircleIcon.vue'
import ChevronDownIcon from '@/components/globales/iconos/ChevronDownIcon.vue'

const chatStore = useChatStore()
const archivadasExpandido = ref(false)

const conversaciones = computed(() => chatStore.conversacionesActivas)
const archivadas = computed(() => chatStore.conversacionesArchivadas)

// Controlar carga de archivadas en polling
const toggleArchivadas = () => {
  archivadasExpandido.value = !archivadasExpandido.value
  // Activar/desactivar carga de archivadas en polling
  chatStore.toggleCargarArchivadas(archivadasExpandido.value)
}
</script>

<template>
  <div class="lista-container">
    <!-- Conversaciones activas -->
    <div v-if="conversaciones.length > 0" class="lista-seccion">
      <ConversacionItem
        v-for="conv in conversaciones"
        :key="conv.id"
        :conversacion="conv"
        :activa="chatStore.conversacionActiva?.id === conv.id"
        @click="chatStore.seleccionarConversacion(conv.id)"
      />
    </div>

    <!-- Archivadas -->
    <div v-if="archivadas.length > 0" class="lista-seccion archivadas">
      <button 
        class="seccion-header collapsible-btn"
        :class="{ expanded: archivadasExpandido }"
        @click="toggleArchivadas"
      >
        <div class="flex items-center gap-2">
          <ArchiveIcon class="w-4 h-4" />
          <span>Archivadas ({{ archivadas.length }})</span>
        </div>
        <ChevronDownIcon class="w-4 h-4 transition-transform icon-chevron" :class="{ 'rotate-180': archivadasExpandido }" />
      </button>
      
      <Transition name="expand">
        <div v-if="archivadasExpandido" class="archivadas-lista">
          <ConversacionItem
            v-for="conv in archivadas"
            :key="conv.id"
            :conversacion="conv"
            :activa="chatStore.conversacionActiva?.id === conv.id"
            :archivada="true"
            @click="chatStore.seleccionarConversacion(conv.id)"
          />
        </div>
      </Transition>
    </div>

    <!-- Estado vacío -->
    <div v-if="conversaciones.length === 0 && archivadas.length === 0" class="lista-vacia">
      <MessageCircleIcon class="w-12 h-12 text-muted-foreground" />
      <p>No tienes conversaciones</p>
      <span class="text-sm text-muted-foreground">
        Busca un usuario arriba para iniciar una
      </span>
    </div>

    <!-- Loading solo en carga inicial, no en polling -->
    <div v-if="chatStore.cargando && conversaciones.length === 0" class="lista-loading">
      <div class="loading-content">
        <span class="spinner"></span>
        <span class="loading-text">Buscando conversaciones...</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.lista-container {
  flex: 1;
  overflow-y: auto;
}

.lista-seccion {
  padding: 8px;
}

.lista-seccion.archivadas {
  border-top: 1px solid var(--color-border);
  margin-top: 8px;
  padding-top: 16px;
}

.seccion-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  gap: 8px;
  padding: 8px 12px;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-muted-foreground);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  background: transparent;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.collapsible-btn:hover {
  background: var(--color-muted);
  border-radius: 8px;
  color: var(--color-foreground);
}

.icon-chevron {
  opacity: 0.7;
}

.expand-enter-active,
.expand-leave-active {
  transition: all 0.3s ease-out;
  max-height: 500px;
  overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
  max-height: 0;
  opacity: 0;
  transform: translateY(-10px);
}

/* Estado vacío */
.lista-vacia {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 24px;
  text-align: center;
  color: var(--color-muted-foreground);
}

.lista-vacia p {
  margin: 16px 0 4px 0;
  font-weight: 500;
  color: var(--color-foreground);
}

/* Loading */
.lista-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px 24px;
}

.loading-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding: 24px 32px;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  min-width: 200px;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--color-muted);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.loading-text {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-foreground);
  text-align: center;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
