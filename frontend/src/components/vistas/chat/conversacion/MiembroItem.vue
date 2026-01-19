<script setup>
import { ref } from 'vue'
import MoreVerticalIcon from '@/components/globales/iconos/MoreVerticalIcon.vue'
import XIcon from '@/components/globales/iconos/XIcon.vue'
import CheckIcon from '@/components/globales/iconos/CheckIcon.vue'

const props = defineProps({
  usuario: {
    type: Object,
    required: true
  },
  esCreador: {
    type: Boolean,
    default: false
  },
  puedeGestionar: {
    type: Boolean,
    default: false
  },
  noPuedeRemoverCreador: {
    type: Boolean,
    default: false
  },
  soloDosMiembros: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['remover', 'cambiar-admin'])

const menuAbierto = ref(false)

const toggleMenu = () => {
  menuAbierto.value = !menuAbierto.value
}

const cerrarMenu = () => {
  menuAbierto.value = false
}

const esAdmin = props.usuario.pivot?.es_admin || props.esCreador

const handleRemover = () => {
  emit('remover', props.usuario.id)
  cerrarMenu()
}

const handleCambiarAdmin = () => {
  emit('cambiar-admin', props.usuario.id, !esAdmin)
  cerrarMenu()
}
</script>

<template>
  <div class="miembro-item">
    <div class="miembro-avatar">
      {{ usuario.name.charAt(0).toUpperCase() }}
    </div>
    <div class="miembro-info">
      <div class="miembro-nombre-row">
        <span class="miembro-nombre">{{ usuario.name }}</span>
        <div class="miembro-badges">
          <span v-if="esCreador" class="badge badge-creador">Creador</span>
          <span v-else-if="esAdmin" class="badge badge-admin">Admin</span>
        </div>
      </div>
      <span class="miembro-email">{{ usuario.email }}</span>
    </div>
    <div v-if="puedeGestionar" class="miembro-acciones">
      <div class="menu-container">
        <button class="btn-menu" @click="toggleMenu">
          <MoreVerticalIcon class="w-5 h-5" />
        </button>
        <Transition name="fade">
          <div v-if="menuAbierto" class="menu-dropdown">
            <button
              class="menu-item"
              @click="handleCambiarAdmin"
            >
              <CheckIcon v-if="!esAdmin" class="w-4 h-4" />
              <XIcon v-else class="w-4 h-4" />
              <span>{{ esAdmin ? 'Remover admin' : 'Hacer admin' }}</span>
            </button>
            <button
              v-if="!noPuedeRemoverCreador && !soloDosMiembros"
              class="menu-item menu-item-danger"
              @click="handleRemover"
            >
              <XIcon class="w-4 h-4" />
              <span>Expulsar</span>
            </button>
            <div
              v-if="soloDosMiembros && !noPuedeRemoverCreador"
              class="menu-item menu-item-disabled"
              title="No se puede expulsar cuando solo hay 2 miembros. Usa 'Salir del grupo' en su lugar."
            >
              <XIcon class="w-4 h-4" />
              <span>Expulsar (no disponible)</span>
            </div>
          </div>
        </Transition>
        <div
          v-if="menuAbierto"
          class="menu-overlay"
          @click="cerrarMenu"
        ></div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.miembro-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 12px;
  background: var(--color-muted);
  transition: background 0.2s;
}

.miembro-item:hover {
  background: var(--color-accent);
}

.miembro-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: var(--color-primary);
  color: var(--color-primary-foreground);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.125rem;
  flex-shrink: 0;
}

.miembro-info {
  flex: 1;
  min-width: 0;
}

.miembro-nombre-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.miembro-nombre {
  font-weight: 600;
  color: var(--color-foreground);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.miembro-badges {
  display: flex;
  gap: 4px;
}

.badge {
  padding: 2px 8px;
  border-radius: 9999px;
  font-size: 0.625rem;
  font-weight: 600;
  text-transform: uppercase;
}

.badge-creador {
  background: var(--color-primary);
  color: var(--color-primary-foreground);
}

.badge-admin {
  background: var(--color-muted-foreground);
  color: var(--color-background);
}

.miembro-email {
  font-size: 0.75rem;
  color: var(--color-muted-foreground);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.miembro-acciones {
  flex-shrink: 0;
}

.menu-container {
  position: relative;
}

.btn-menu {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: none;
  background: transparent;
  color: var(--color-foreground);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-menu:hover {
  background: var(--color-background);
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
  text-align: left;
}

.menu-item:hover {
  background: var(--color-muted);
}

.menu-item-danger {
  color: #ef4444;
}

.menu-item-danger:hover {
  background: #fee2e2;
}

.menu-item-disabled {
  opacity: 0.5;
  cursor: not-allowed;
  color: var(--color-muted-foreground);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
