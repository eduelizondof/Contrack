<template>
  <PlantillaApp>
    <div class="vista-usuarios">
      <header class="usuarios-header">
        <div class="header-content">
          <div>
            <h1 class="page-title">Usuarios</h1>
            <p class="page-subtitle">Gestiona usuarios del sistema</p>
          </div>
        </div>
        <button @click="mostrarFormulario = true" class="btn-nuevo">
          <PlusIcon class="icon" />
          <span>Nuevo Usuario</span>
        </button>
      </header>
      
      <BuscadorUsuarios
        v-model="busqueda"
        :rol-filtro="rolFiltro"
        :solo-activos="soloActivos"
        @buscar="handleBuscar"
        @filtro-rol="handleFiltroRol"
        @toggle-activos="handleToggleActivos"
      />
      
      <div v-if="cargando" class="cargando-container">
        <p>Cargando usuarios...</p>
      </div>
      
      <div v-else-if="usuarios.length === 0" class="sin-resultados">
        <p>No se encontraron usuarios</p>
      </div>
      
      <div v-else class="usuarios-lista">
        <FilaUsuario
          v-for="usuario in usuarios"
          :key="usuario.id"
          :usuario="usuario"
          @ver="handleVer"
          @eliminar="handleEliminar"
        />
      </div>
      
      <PaginacionModerna
        v-if="meta.total > 0"
        :pagina-actual="paginaActual"
        :total-paginas="meta.ultima_pagina"
        @cambiar-pagina="handleCambiarPagina"
      />
      
      <!-- Modal para crear/editar usuario -->
      <Modal
        v-model="mostrarFormulario"
        :titulo="usuarioEditando ? 'Editar Usuario' : 'Nuevo Usuario'"
        tamanio="md"
        @cerrar="cerrarFormulario"
      >
        <FormularioUsuario
          ref="formularioRef"
          :usuario="usuarioEditando"
          :guardando="guardando"
          :errores="errores"
          :error-general="errorGeneral"
          @submit="handleSubmitFormulario"
        />
        <template #footer>
          <button type="button" @click="cerrarFormulario" class="btn-cancelar">
            Cancelar
          </button>
          <button type="button" @click="handleSubmitFormularioDesdeModal" class="btn-guardar" :disabled="guardando">
            <span v-if="guardando" class="btn-loading">
              <span class="spinner"></span>
              Guardando...
            </span>
            <span v-else>Guardar</span>
          </button>
        </template>
      </Modal>
      
      <!-- Modal de confirmación de eliminación -->
      <Modal
        v-model="mostrarConfirmacion"
        titulo="¿Eliminar usuario?"
        tamanio="sm"
      >
        <p>Esta acción no se puede deshacer. ¿Estás seguro de eliminar a <strong>{{ usuarioAEliminar?.name }}</strong>?</p>
        <template #footer>
          <button @click="cerrarConfirmacion" class="btn-cancelar">Cancelar</button>
          <button @click="confirmarEliminacion" class="btn-eliminar" :disabled="eliminando">
            <span v-if="eliminando" class="btn-loading">
              <span class="spinner"></span>
              Eliminando...
            </span>
            <span v-else>Eliminar</span>
          </button>
        </template>
      </Modal>
    </div>
  </PlantillaApp>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import PlantillaApp from '@/layouts/PlantillaApp.vue'
import BuscadorUsuarios from '@/components/vistas/configuracion/BuscadorUsuarios.vue'
import FilaUsuario from '@/components/vistas/configuracion/FilaUsuario.vue'
import PaginacionModerna from '@/components/vistas/configuracion/PaginacionModerna.vue'
import FormularioUsuario from '@/components/vistas/configuracion/FormularioUsuario.vue'
import Modal from '@/components/globales/modales/Modal.vue'
import { PlusIcon } from '@/components/globales/iconos'
import usuariosService from '@/services/usuarios'
import { useAlerta } from '@/composables/useAlerta'

const alerta = useAlerta()

const router = useRouter()

const usuarios = ref([])
const cargando = ref(false)
const paginaActual = ref(1)
const porPagina = 10
const meta = ref({
  total: 0,
  ultima_pagina: 1,
})

const busqueda = ref('')
const rolFiltro = ref(null)
const soloActivos = ref(false)

const mostrarFormulario = ref(false)
const formularioRef = ref(null)
const usuarioEditando = ref(null)
const guardando = ref(false)
const errores = ref({})
const errorGeneral = ref('')

const mostrarConfirmacion = ref(false)
const usuarioAEliminar = ref(null)
const eliminando = ref(false)

onMounted(() => {
  cargarUsuarios()
})

async function cargarUsuarios() {
  cargando.value = true
  try {
    // Validar mínimo 3 caracteres para búsqueda (si hay texto)
    const buscarTexto = busqueda.value && busqueda.value.trim().length >= 3 
      ? busqueda.value.trim() 
      : (busqueda.value && busqueda.value.trim().length > 0 ? null : busqueda.value)
    
    // Si hay texto pero menos de 3 caracteres, no buscar
    if (busqueda.value && busqueda.value.trim().length > 0 && busqueda.value.trim().length < 3) {
      usuarios.value = []
      meta.value = {
        total: 0,
        ultima_pagina: 1,
      }
      cargando.value = false
      return
    }

    const respuesta = await usuariosService.obtenerUsuarios({
      pagina: paginaActual.value,
      por_pagina: porPagina,
      buscar: buscarTexto || undefined,
      rol: rolFiltro.value,
      solo_activos: soloActivos.value,
    })
    
    usuarios.value = respuesta.datos
    meta.value = respuesta.meta
  } catch (error) {
    console.error('Error al cargar usuarios:', error)
    alerta.error({
      titulo: 'Error',
      mensaje: 'No se pudo cargar la lista de usuarios.',
      duracion: 1000,
    })
  } finally {
    cargando.value = false
  }
}

function handleBuscar() {
  paginaActual.value = 1
  cargarUsuarios()
}

function handleFiltroRol(rol) {
  rolFiltro.value = rol
  paginaActual.value = 1
  cargarUsuarios()
}

function handleToggleActivos(activo) {
  soloActivos.value = activo
  paginaActual.value = 1
  cargarUsuarios()
}

function handleCambiarPagina(pagina) {
  paginaActual.value = pagina
  cargarUsuarios()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function handleVer(id) {
  router.push({ name: 'detalle-usuario', params: { id } })
}

function handleEliminar(id) {
  const usuario = usuarios.value.find(u => u.id === id)
  usuarioAEliminar.value = usuario
  mostrarConfirmacion.value = true
}

async function handleSubmitFormulario(datos) {
  guardando.value = true
  errores.value = {}
  errorGeneral.value = ''
  
  try {
    if (usuarioEditando.value) {
      await usuariosService.actualizarUsuario(usuarioEditando.value.id, datos)
      alerta.success({
        titulo: 'Guardado',
        mensaje: 'Usuario actualizado correctamente',
        duracion: 1000,
      })
      cerrarFormulario()
      cargarUsuarios()
    } else {
      const nuevoUsuario = await usuariosService.crearUsuario(datos)
      alerta.success({
        titulo: 'Guardado',
        mensaje: 'Usuario creado correctamente',
        duracion: 1000,
      })
      cerrarFormulario()
      // Redirigir al detalle del nuevo usuario para configurar rol, horario, etc.
      router.push({ name: 'detalle-usuario', params: { id: nuevoUsuario.id } })
    }
  } catch (error) {
    console.error('Error al guardar usuario:', error)
    
    let mensaje = 'Ocurrió un error al guardar el usuario.'
    let titulo = 'Error'
    
    // Manejar diferentes tipos de errores según el código de estado
    if (error.response) {
      const status = error.response.status
      
      switch (status) {
        case 403:
          // No autorizado - Permisos insuficientes
          titulo = 'Acceso Denegado'
          mensaje = 'No tienes permisos para realizar esta acción. Contacta al administrador del sistema.'
          break
          
        case 401:
          // No autenticado - Sesión expirada
          titulo = 'Sesión Expirada'
          mensaje = 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.'
          break
          
        case 422:
          // Errores de validación
          errores.value = error.response.data.errors || {}
          const mensajesValidacion = Object.values(error.response.data.errors || {}).flat()
          mensaje = mensajesValidacion.length > 0 
            ? mensajesValidacion.join(', ') 
            : 'Por favor, verifica los datos ingresados.'
          break
          
        case 404:
          titulo = 'No Encontrado'
          mensaje = error.response.data?.mensaje || 'El recurso solicitado no fue encontrado.'
          break
          
        case 500:
          titulo = 'Error del Servidor'
          mensaje = 'Ocurrió un error en el servidor. Por favor, intenta nuevamente más tarde.'
          break
          
        default:
          // Intentar obtener mensaje del servidor
          if (error.response.data?.mensaje) {
            mensaje = error.response.data.mensaje
          } else if (error.response.data?.message) {
            mensaje = error.response.data.message
          }
      }
    } else if (error.request) {
      // Error de red - Sin respuesta del servidor
      titulo = 'Error de Conexión'
      mensaje = 'No se pudo conectar con el servidor. Verifica tu conexión a internet e intenta nuevamente.'
    } else if (error.message) {
      // Error de configuración o otro error
      mensaje = error.message
    }
    
    alerta.error({
      titulo,
      mensaje,
      duracion: 4000,
    })
  } finally {
    guardando.value = false
  }
}

function handleSubmitFormularioDesdeModal() {
  if (formularioRef.value) {
    const datos = formularioRef.value.getFormData()
    handleSubmitFormulario(datos)
  }
}

async function confirmarEliminacion() {
  eliminando.value = true
  try {
    await usuariosService.eliminarUsuario(usuarioAEliminar.value.id)
    alerta.success({
      titulo: 'Eliminado',
      mensaje: 'Usuario eliminado correctamente',
      duracion: 1000,
    })
    cerrarConfirmacion()
    cargarUsuarios()
  } catch (error) {
    console.error('Error al eliminar usuario:', error)
    
    let mensaje = 'Ocurrió un error al eliminar el usuario.'
    let titulo = 'Error'
    
    if (error.response) {
      const status = error.response.status
      
      switch (status) {
        case 403:
          titulo = 'Acceso Denegado'
          mensaje = error.response.data?.mensaje || 'No tienes permisos para eliminar usuarios.'
          break
          
        case 401:
          titulo = 'Sesión Expirada'
          mensaje = 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.'
          break
          
        case 404:
          titulo = 'No Encontrado'
          mensaje = 'El usuario no fue encontrado.'
          break
          
        case 500:
          titulo = 'Error del Servidor'
          mensaje = 'Ocurrió un error en el servidor. Por favor, intenta nuevamente más tarde.'
          break
          
        default:
          if (error.response.data?.mensaje) {
            mensaje = error.response.data.mensaje
          } else if (error.response.data?.message) {
            mensaje = error.response.data.message
          }
      }
    } else if (error.request) {
      titulo = 'Error de Conexión'
      mensaje = 'No se pudo conectar con el servidor. Verifica tu conexión a internet e intenta nuevamente.'
    } else if (error.message) {
      mensaje = error.message
    }
    
    alerta.error({
      titulo,
      mensaje,
      duracion: 4000,
    })
  } finally {
    eliminando.value = false
  }
}

function cerrarFormulario() {
  mostrarFormulario.value = false
  usuarioEditando.value = null
  errores.value = {}
  errorGeneral.value = ''
}

function cerrarConfirmacion() {
  mostrarConfirmacion.value = false
  usuarioAEliminar.value = null
}

</script>

<style scoped>
@import '@/assets/styles/views/usuarios/VistaUsuarios.css';
</style>
