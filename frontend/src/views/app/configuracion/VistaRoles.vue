<template>
  <PlantillaApp>
    <div class="vista-roles">
      <header class="roles-header">
        <div class="header-content">
          <div>
            <h1 class="page-title">Roles y Permisos</h1>
            <p class="page-subtitle">Gestiona los roles del sistema</p>
          </div>
        </div>
        <button @click="abrirModalCrear" class="btn-nuevo">
          <PlusIcon class="icon" />
          <span>Nuevo Rol</span>
        </button>
      </header>
      
      <div v-if="cargando" class="cargando-container">
        <p>Cargando roles...</p>
      </div>
      
      <div v-else-if="roles.length === 0" class="sin-resultados">
        <p>No hay roles registrados</p>
      </div>
      
      <div v-else class="roles-lista">
        <FilaRol
          v-for="rol in roles"
          :key="rol.id"
          :rol="rol"
          @ver="handleVer"
          @eliminar="handleEliminar"
        />
      </div>
      
      <!-- Modal para crear rol -->
      <Modal
        v-model="mostrarModalCrear"
        titulo="Nuevo Rol"
        tamanio="md"
        @cerrar="cerrarModalCrear"
      >
        <template #default>
          <form @submit.prevent="handleCrearRol" id="form-crear-rol">
            <div class="form-group">
              <label for="nombre-rol">Nombre del rol</label>
              <input
                id="nombre-rol"
                v-model="nuevoRol.nombre"
                type="text"
                placeholder="Ej: Supervisor"
                class="form-input"
                :class="{ 'error': errores.nombre }"
                required
              />
              <p v-if="errores.nombre" class="error-mensaje">{{ errores.nombre[0] }}</p>
            </div>
            <div v-if="errorGeneral" class="error-general">
              {{ errorGeneral }}
            </div>
          </form>
        </template>
        <template #footer>
          <button type="button" @click="cerrarModalCrear" class="btn-cancelar">
            Cancelar
          </button>
          <button type="submit" form="form-crear-rol" class="btn-guardar" :disabled="guardando">
            {{ guardando ? 'Creando...' : 'Crear y Configurar' }}
          </button>
        </template>
      </Modal>
      
      <!-- Modal de error al eliminar -->
      <ModalConfirmacion
        v-if="mostrarErrorEliminar"
        titulo="No se puede eliminar el rol"
        :peligro="false"
        texto-confirmar="Entendido"
        @confirmar="cerrarErrorEliminar"
        @cerrar="cerrarErrorEliminar"
      >
        <p>{{ mensajeErrorEliminar }}</p>
      </ModalConfirmacion>
    </div>
  </PlantillaApp>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import PlantillaApp from '@/layouts/PlantillaApp.vue'
import FilaRol from '@/components/vistas/configuracion/FilaRol.vue'
import Modal from '@/components/globales/modales/Modal.vue'
import ModalConfirmacion from '@/components/globales/modales/ModalConfirmacion.vue'
import { PlusIcon } from '@/components/globales/iconos'
import rolesService from '@/services/roles'

const router = useRouter()

const roles = ref([])
const cargando = ref(false)

const mostrarModalCrear = ref(false)
const nuevoRol = ref({ nombre: '' })
const guardando = ref(false)
const errores = ref({})
const errorGeneral = ref('')

const mostrarErrorEliminar = ref(false)
const mensajeErrorEliminar = ref('')

onMounted(() => {
  cargarRoles()
})

async function cargarRoles() {
  cargando.value = true
  try {
    roles.value = await rolesService.obtenerRoles()
  } catch (error) {
    console.error('Error al cargar roles:', error)
  } finally {
    cargando.value = false
  }
}

function abrirModalCrear() {
  nuevoRol.value = { nombre: '' }
  errores.value = {}
  errorGeneral.value = ''
  mostrarModalCrear.value = true
}

function cerrarModalCrear() {
  mostrarModalCrear.value = false
}

async function handleCrearRol() {
  guardando.value = true
  errores.value = {}
  errorGeneral.value = ''
  
  try {
    const rolCreado = await rolesService.crearRol(nuevoRol.value)
    cerrarModalCrear()
    router.push({ name: 'detalle-rol', params: { id: rolCreado.id } })
  } catch (error) {
    if (error.response?.status === 422) {
      errores.value = error.response.data.errors || {}
    } else {
      errorGeneral.value = error.response?.data?.mensaje || 'Error al crear el rol'
    }
  } finally {
    guardando.value = false
  }
}

function handleVer(id) {
  router.push({ name: 'detalle-rol', params: { id } })
}

async function handleEliminar(id) {
  try {
    await rolesService.eliminarRol(id)
    await cargarRoles()
  } catch (error) {
    if (error.response?.status === 422) {
      mensajeErrorEliminar.value = error.response.data.mensaje
      mostrarErrorEliminar.value = true
    } else {
      console.error('Error al eliminar rol:', error)
    }
  }
}

function cerrarErrorEliminar() {
  mostrarErrorEliminar.value = false
}
</script>

<style scoped>
@import '@/assets/styles/views/configuracion/VistaRoles.css';
</style>
