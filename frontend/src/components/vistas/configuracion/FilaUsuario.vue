<template>
  <div class="fila-usuario" :class="{ 'inactivo': !usuario.activo }">
    <div class="fila-info">
      <div class="usuario-avatar">
        <span class="avatar-inicial">{{ iniciales }}</span>
      </div>
      
      <div class="usuario-datos">
        <h3 class="usuario-nombre">{{ usuario.name }}</h3>
        <p class="usuario-email">{{ usuario.email }}</p>
        <div class="usuario-meta">
          <span class="usuario-rol">{{ usuario.rol }}</span>
          <span class="usuario-separador">•</span>
          <span class="usuario-fecha">Creado: {{ fechaFormateada }}</span>
        </div>
      </div>
    </div>
    
    <div class="fila-estado">
      <span class="estado-badge" :class="{ 'activo': usuario.activo, 'inactivo': !usuario.activo }">
        {{ usuario.activo ? 'Activo' : 'Inactivo' }}
      </span>
    </div>
    
    <div class="fila-acciones">
      <button
        @click="$emit('ver', usuario.id)"
        class="accion-btn ver"
        aria-label="Ver detalles"
        title="Ver detalles"
      >
        <EyeIcon class="accion-icon" />
      </button>
      <button
        @click="$emit('eliminar', usuario.id)"
        class="accion-btn eliminar"
        aria-label="Eliminar"
        title="Eliminar"
      >
        <TrashIcon class="accion-icon" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { EyeIcon, TrashIcon } from '@/components/globales/iconos'

const props = defineProps({
  usuario: {
    type: Object,
    required: true,
  },
})

defineEmits(['ver', 'eliminar'])

const iniciales = computed(() => {
  const nombres = props.usuario.name.split(' ')
  if (nombres.length >= 2) {
    return (nombres[0][0] + nombres[1][0]).toUpperCase()
  }
  return props.usuario.name.substring(0, 2).toUpperCase()
})

const fechaFormateada = computed(() => {
  const fecha = new Date(props.usuario.creado_el)
  return fecha.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
})
</script>

<style scoped>
.fila-usuario {
  background: var(--color-surface-bg);
  border: 1px solid var(--color-border);
  border-radius: 16px;
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: all 0.2s;
  margin-bottom: 0.75rem;
}

.fila-usuario:hover {
  border-color: var(--color-primary);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.fila-usuario.inactivo {
  opacity: 0.7;
}

.fila-info {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 1rem;
  min-width: 0;
}

.usuario-avatar {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-hover) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.avatar-inicial {
  color: white;
  font-weight: 600;
  font-size: 1rem;
  letter-spacing: 0.5px;
  display: block;
  line-height: 1;
}

.usuario-datos {
  flex: 1;
  min-width: 0;
}

.usuario-nombre {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-heading);
  margin: 0 0 0.25rem 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.usuario-email {
  font-size: 0.875rem;
  color: var(--color-text-muted);
  margin: 0 0 0.5rem 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.usuario-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.usuario-rol {
  font-size: 0.8rem;
  padding: 0.25rem 0.75rem;
  background: rgba(59, 130, 246, 0.1);
  color: var(--color-primary);
  border-radius: 8px;
  font-weight: 500;
}

.usuario-separador {
  color: var(--color-text-muted);
  font-size: 0.8rem;
}

.usuario-fecha {
  font-size: 0.8rem;
  color: var(--color-text-muted);
}

.fila-estado {
  display: flex;
  align-items: center;
  flex-shrink: 0;
}

.estado-badge {
  padding: 0.4rem 0.875rem;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.estado-badge.activo {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.estado-badge.inactivo {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.fila-acciones {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}

.accion-btn {
  width: 44px;
  height: 44px;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  background: var(--color-surface-bg);
  color: var(--color-text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  flex-shrink: 0;
}

.accion-btn:hover {
  border-color: var(--color-primary);
  background: rgba(59, 130, 246, 0.1);
  color: var(--color-primary);
  transform: scale(1.05);
}

.accion-btn.eliminar:hover {
  border-color: #ef4444;
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.accion-icon {
  width: 18px;
  height: 18px;
}

/* Responsive */
@media (max-width: 768px) {
  .fila-usuario {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  
  .fila-info {
    width: 100%;
  }
  
  .fila-estado {
    align-self: flex-start;
  }
  
  .fila-acciones {
    width: 100%;
    justify-content: flex-end;
  }
  
  .usuario-meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
  
  .usuario-separador {
    display: none;
  }
}

@media (max-width: 640px) {
  .fila-usuario {
    padding: 1rem;
  }
  
  .accion-btn {
    width: 40px;
    height: 40px;
  }
  
  .accion-icon {
    width: 16px;
    height: 16px;
  }
}

/* Dark mode */
html.dark .fila-usuario:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

html.dark .usuario-rol {
  background: rgba(59, 130, 246, 0.15);
}
</style>
