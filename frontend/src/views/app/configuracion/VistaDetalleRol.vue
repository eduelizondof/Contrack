<template>
  <PlantillaApp>
    <div class="vista-detalle-rol">
      <header class="detalle-header">
        <div class="header-content">
          <div>
            <h1 class="page-title">{{ rol?.nombre || 'Cargando...' }}</h1>
            <p class="page-subtitle">{{ rol?.usuarios_count || 0 }} usuarios con este rol</p>
          </div>
        </div>
      </header>
      
      <div v-if="cargando" class="cargando-container">
        <p>Cargando información del rol...</p>
      </div>
      
      <div v-else-if="rol" class="detalle-contenido">
        <SeccionColapsable
          titulo="Información del Rol"
          subtitulo="Nombre y configuración básica"
          :abierta="true"
          :mostrar-guardar="true"
          :guardando="guardandoNombre"
          @guardar="handleGuardarNombre"
        >
          <div class="form-group">
            <label for="nombre-rol">Nombre del rol</label>
            <input
              id="nombre-rol"
              v-model="nombreEdicion"
              type="text"
              class="form-input"
              :class="{ 'error': erroresNombre.nombre }"
            />
            <p v-if="erroresNombre.nombre" class="error-mensaje">{{ erroresNombre.nombre[0] }}</p>
          </div>
        </SeccionColapsable>
        
        <SeccionColapsable
          titulo="Permisos"
          subtitulo="Configura qué puede hacer este rol"
          :abierta="true"
          :mostrar-guardar="true"
          :guardando="guardandoPermisos"
          @guardar="handleGuardarPermisos"
        >
          <EditorPermisos
            v-model="permisosSeleccionados"
            :cargando="cargandoCategorias"
          />
        </SeccionColapsable>
        
        <SeccionColapsable
          titulo="Zona de Peligro"
          subtitulo="Acciones irreversibles"
          :abierta="false"
        >
          <ZonaPeligro
            ref="zonaPeligroRef"
            mensaje="Al eliminar este rol, los usuarios que lo tengan asignado perderán sus permisos asociados."
            texto-boton="Eliminar Rol"
            :titulo-confirmacion="`¿Eliminar rol ${rol?.nombre}?`"
            :mensaje-confirmacion="`Esta acción no se puede deshacer.`"
            :deshabilitado="rol?.usuarios_count > 0"
            @eliminar="confirmarEliminacion"
          >
            <template #mensaje>
              <template v-if="rol?.usuarios_count > 0">
                <strong>No se puede eliminar este rol.</strong> Actualmente hay {{ rol.usuarios_count }} usuarios con este rol asignado.
              </template>
              <template v-else>
                Al eliminar este rol, se eliminará permanentemente y no se podrá recuperar.
              </template>
            </template>
          </ZonaPeligro>
        </SeccionColapsable>
        
        <div class="acciones-finales">
          <button @click="cancelar" class="btn-cancelar-final">
            Volver a Roles
          </button>
        </div>
      </div>
    </div>
  </PlantillaApp>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PlantillaApp from '@/layouts/PlantillaApp.vue'
import SeccionColapsable from '@/components/vistas/configuracion/SeccionColapsable.vue'
import EditorPermisos from '@/components/vistas/configuracion/EditorPermisos.vue'
import ZonaPeligro from '@/components/globales/ZonaPeligro.vue'
import rolesService from '@/services/roles'
import { useAlerta } from '@/composables/useAlerta'

const alerta = useAlerta()

const route = useRoute()
const router = useRouter()

const rol = ref(null)
const cargando = ref(false)

const nombreEdicion = ref('')
const guardandoNombre = ref(false)
const erroresNombre = ref({})

const permisosSeleccionados = ref([])
const guardandoPermisos = ref(false)
const cargandoCategorias = ref(false)

const zonaPeligroRef = ref(null)

onMounted(() => {
  cargarRol()
})

watch(() => rol.value, (nuevoRol) => {
  if (nuevoRol) {
    nombreEdicion.value = nuevoRol.nombre
    permisosSeleccionados.value = nuevoRol.permisos || []
  }
})

async function cargarRol() {
  cargando.value = true
  try {
    const datos = await rolesService.obtenerRol(route.params.id)
    rol.value = datos
    nombreEdicion.value = datos.nombre
    permisosSeleccionados.value = datos.permisos || []
  } catch (error) {
    console.error('Error al cargar rol:', error)
    alerta.error({
      titulo: 'Error',
      mensaje: 'No se pudo cargar la información del rol.',
      duracion: 1000,
    })
    router.push({ name: 'roles' })
  } finally {
    cargando.value = false
  }
}

async function handleGuardarNombre() {
  guardandoNombre.value = true
  erroresNombre.value = {}
  
  try {
    await rolesService.actualizarRol(rol.value.id, { nombre: nombreEdicion.value })
    alerta.success({
      titulo: 'Guardado',
      mensaje: 'Nombre del rol actualizado correctamente',
      duracion: 1000,
    })
    await cargarRol()
  } catch (error) {
    console.error('Error al actualizar nombre:', error)
    
    if (error.response?.status === 422) {
      erroresNombre.value = error.response.data.errors || {}
      const mensaje = Object.values(error.response.data.errors || {}).flat().join(', ')
      if (mensaje) {
        alerta.error({
          titulo: 'Error de validación',
          mensaje,
          duracion: 1000,
        })
      }
    } else {
      alerta.error({
        titulo: 'Error',
        mensaje: error.response?.data?.mensaje || 'No se pudo actualizar el nombre del rol',
        duracion: 1000,
      })
    }
  } finally {
    guardandoNombre.value = false
  }
}

async function handleGuardarPermisos() {
  guardandoPermisos.value = true
  
  try {
    await rolesService.actualizarPermisosRol(rol.value.id, permisosSeleccionados.value)
    alerta.success({
      titulo: 'Guardado',
      mensaje: 'Permisos actualizados correctamente',
      duracion: 1000,
    })
    await cargarRol()
  } catch (error) {
    console.error('Error al actualizar permisos:', error)
    alerta.error({
      titulo: 'Error',
      mensaje: error.response?.data?.mensaje || 'No se pudieron actualizar los permisos',
      duracion: 1000,
    })
  } finally {
    guardandoPermisos.value = false
  }
}

async function confirmarEliminacion() {
  try {
    await rolesService.eliminarRol(rol.value.id)
    alerta.success({
      titulo: 'Eliminado',
      mensaje: 'Rol eliminado correctamente',
      duracion: 1000,
    })
    router.push({ name: 'roles' })
  } catch (error) {
    console.error('Error al eliminar rol:', error)
    alerta.error({
      titulo: 'Error',
      mensaje: error.response?.data?.mensaje || 'No se pudo eliminar el rol',
      duracion: 1000,
    })
    if (zonaPeligroRef.value) {
      zonaPeligroRef.value.finalizarEliminacion()
    }
  }
}

function cancelar() {
  router.push({ name: 'roles' })
}
</script>

<style scoped>
@import '@/assets/styles/views/configuracion/VistaDetalleRol.css';
</style>
