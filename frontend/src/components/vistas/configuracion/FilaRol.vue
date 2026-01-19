<template>
  <div class="fila-rol" @click="$emit('ver', rol.id)">
    <div class="rol-info">
      <h3 class="rol-nombre">{{ rol.nombre }}</h3>
      <div class="rol-meta">
        <span class="meta-item">
          <UsersIcon class="meta-icon" />
          {{ rol.usuarios_count || 0 }} usuarios
        </span>
        <span class="meta-item">
          <ShieldIcon class="meta-icon" />
          {{ rol.permisos?.length || 0 }} permisos
        </span>
      </div>
    </div>
    
    <div class="rol-acciones" @click.stop>
      <button 
        @click="mostrarPermisos = !mostrarPermisos" 
        class="btn-ver-permisos"
        :class="{ 'activo': mostrarPermisos }"
        title="Ver permisos"
      >
        <EyeIcon class="icon" />
      </button>
      <button @click="$emit('ver', rol.id)" class="btn-editar" title="Editar rol">
        <EditIcon class="icon" />
      </button>
      <button @click="handleEliminar" class="btn-eliminar" title="Eliminar rol">
        <TrashIcon class="icon" />
      </button>
    </div>
    
    <!-- Panel de permisos expandible -->
    <div v-if="mostrarPermisos" class="permisos-panel" @click.stop>
      <div v-if="!rol.permisos || rol.permisos.length === 0" class="sin-permisos">
        No hay permisos asignados
      </div>
      <div v-else class="permisos-lista">
        <span 
          v-for="permiso in permisosAgrupados" 
          :key="permiso" 
          class="permiso-badge"
        >
          {{ permiso }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { UsersIcon, ShieldIcon, EyeIcon, EditIcon, TrashIcon } from '@/components/globales/iconos'

const props = defineProps({
  rol: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['ver', 'eliminar'])

const mostrarPermisos = ref(false)

const permisosAgrupados = computed(() => {
  if (!props.rol.permisos) return []
  // Mostrar solo los primeros 20 permisos y el resto como "+X más"
  const permisos = props.rol.permisos
  if (permisos.length <= 20) return permisos
  return [...permisos.slice(0, 20), `+${permisos.length - 20} más`]
})

function handleEliminar() {
  emit('eliminar', props.rol.id)
}
</script>

<style scoped>
.fila-rol {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.25rem;
  background: var(--color-surface-bg);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.fila-rol:hover {
  border-color: var(--color-primary);
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
}

.rol-info {
  flex: 1;
  min-width: 200px;
}

.rol-nombre {
  font-size: 1.05rem;
  font-weight: 600;
  color: var(--color-heading);
  margin: 0 0 0.5rem 0;
}

.rol-meta {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.825rem;
  color: var(--color-text-muted);
}

.meta-icon {
  width: 14px;
  height: 14px;
}

.rol-acciones {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.btn-ver-permisos,
.btn-editar,
.btn-eliminar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  background: var(--color-surface-bg);
  color: var(--color-text-muted);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-ver-permisos:hover,
.btn-editar:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
  background: rgba(59, 130, 246, 0.05);
}

.btn-ver-permisos.activo {
  border-color: var(--color-primary);
  color: var(--color-primary);
  background: rgba(59, 130, 246, 0.1);
}

.btn-eliminar:hover {
  border-color: #ef4444;
  color: #ef4444;
  background: rgba(239, 68, 68, 0.05);
}

.btn-ver-permisos .icon,
.btn-editar .icon,
.btn-eliminar .icon {
  width: 16px;
  height: 16px;
}

.permisos-panel {
  width: 100%;
  padding-top: 1rem;
  margin-top: 0.5rem;
  border-top: 1px solid var(--color-border);
}

.sin-permisos {
  color: var(--color-text-muted);
  font-size: 0.875rem;
  font-style: italic;
}

.permisos-lista {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.permiso-badge {
  padding: 0.35rem 0.75rem;
  background: var(--color-background-mute);
  border-radius: 6px;
  font-size: 0.75rem;
  color: var(--color-text);
  font-family: monospace;
}

@media (max-width: 640px) {
  .fila-rol {
    padding: 1rem;
  }
  
  .rol-acciones {
    width: 100%;
    justify-content: flex-end;
  }
  
  .rol-nombre {
    font-size: 0.95rem;
  }
}
</style>
