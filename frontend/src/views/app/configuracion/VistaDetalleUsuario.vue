<template>
  <PlantillaApp>
    <div class="vista-detalle-usuario">
      <header class="detalle-header">
        <div class="header-content">
          <div>
            <h1 class="page-title">{{ usuario?.name || 'Cargando...' }}</h1>
            <p class="page-subtitle">{{ usuario?.email || '' }}</p>
          </div>
        </div>
      </header>
      
      <div v-if="cargando" class="cargando-container">
        <p>Cargando información del usuario...</p>
      </div>
      
      <div v-else-if="usuario" class="detalle-contenido">
        <SeccionColapsable
          titulo="Información Básica"
          subtitulo="Datos personales y de contacto"
          :abierta="true"
          :mostrar-guardar="true"
          :guardando="guardandoInfo"
          @guardar="handleGuardarInfo"
        >
          <FormularioUsuario
            ref="formularioInfoRef"
            :usuario="usuario"
            :guardando="guardandoInfo"
            :errores="erroresInfo"
            :error-general="errorInfoGeneral"
            @submit="handleActualizarInfo"
          />
        </SeccionColapsable>
        
        <SeccionColapsable
          titulo="Restablecer Contraseña"
          subtitulo="Cambia la contraseña del usuario"
        >
          <FormularioRestablecerPassword
            ref="formularioPasswordRef"
            :guardando="guardandoPassword"
            :errores="erroresPassword"
            :error-general="errorPasswordGeneral"
            @submit="handleRestablecerPassword"
          />
        </SeccionColapsable>
        
        <SeccionColapsable
          titulo="Rol y Permisos"
          subtitulo="Asigna el rol del usuario"
          :mostrar-guardar="true"
          :guardando="guardandoRol"
          @guardar="handleGuardarRol"
        >
          <SelectorRol
            v-model="rolSeleccionado"
            @cambio="() => {}"
          />
        </SeccionColapsable>
        
        <SeccionColapsable
          titulo="Zona de Peligro"
          subtitulo="Acciones irreversibles"
          :abierta="false"
        >
          <ZonaPeligro
            ref="zonaPeligroRef"
            mensaje="Al eliminar este usuario, se eliminarán permanentemente todos sus datos y no se podrá recuperar."
            texto-boton="Eliminar Usuario"
            :titulo-confirmacion="`¿Eliminar a ${usuario?.name}?`"
            mensaje-confirmacion="Esta acción no se puede deshacer."
            @eliminar="confirmarEliminacion"
          />
        </SeccionColapsable>
        
        <div class="acciones-finales">
          <button @click="cancelar" class="btn-cancelar-final">
            Cancelar
          </button>
        </div>
      </div>
    </div>
  </PlantillaApp>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PlantillaApp from '@/layouts/PlantillaApp.vue'
import SeccionColapsable from '@/components/vistas/configuracion/SeccionColapsable.vue'
import FormularioUsuario from '@/components/vistas/configuracion/FormularioUsuario.vue'
import FormularioRestablecerPassword from '@/components/vistas/configuracion/FormularioRestablecerPassword.vue'
import SelectorRol from '@/components/vistas/configuracion/SelectorRol.vue'
import ZonaPeligro from '@/components/globales/ZonaPeligro.vue'
import usuariosService from '@/services/usuarios'
import { useAlerta } from '@/composables/useAlerta'

const alerta = useAlerta()

const route = useRoute()
const router = useRouter()

const usuario = ref(null)
const cargando = ref(false)

const guardandoInfo = ref(false)
const erroresInfo = ref({})
const errorInfoGeneral = ref('')

const guardandoPassword = ref(false)
const erroresPassword = ref({})
const errorPasswordGeneral = ref('')
const formularioPasswordRef = ref(null)
const formularioInfoRef = ref(null)

const rolSeleccionado = ref(null)
const guardandoRol = ref(false)

const zonaPeligroRef = ref(null)

onMounted(() => {
  cargarUsuario()
})

async function cargarUsuario() {
  cargando.value = true
  try {
    const datos = await usuariosService.obtenerUsuario(route.params.id)
    usuario.value = datos
    rolSeleccionado.value = datos.rol_id
  } catch (error) {
    console.error('Error al cargar usuario:', error)
    alerta.error({
      titulo: 'Error',
      mensaje: 'No se pudo cargar la información del usuario.',
      duracion: 1000,
    })
    router.push({ name: 'usuarios' })
  } finally {
    cargando.value = false
  }
}

async function handleGuardarInfo() {
  if (!formularioInfoRef.value) return
  
  // Obtener datos del formulario usando el método expuesto
  const formData = formularioInfoRef.value.getFormData()
  
  await handleActualizarInfo(formData)
}

async function handleActualizarInfo(datos) {
  guardandoInfo.value = true
  erroresInfo.value = {}
  errorInfoGeneral.value = ''
  
  try {
    await usuariosService.actualizarUsuario(usuario.value.id, datos)
    alerta.success({
      titulo: 'Guardado',
      mensaje: 'Información actualizada correctamente',
      duracion: 1000,
    })
    await cargarUsuario()
  } catch (error) {
    console.error('Error al actualizar información:', error)
    
    let mensaje = 'Ocurrió un error al actualizar la información.'
    if (error.response?.status === 422) {
      erroresInfo.value = error.response.data.errors || {}
      mensaje = Object.values(error.response.data.errors || {}).flat().join(', ') || mensaje
    } else if (error.response?.data?.mensaje) {
      mensaje = error.response.data.mensaje
    } else if (error.message) {
      mensaje = error.message
    }
    
    alerta.error({
      titulo: 'Error',
      mensaje,
      duracion: 1000,
    })
  } finally {
    guardandoInfo.value = false
  }
}

async function handleRestablecerPassword(datos) {
  guardandoPassword.value = true
  erroresPassword.value = {}
  errorPasswordGeneral.value = ''
  
  try {
    await usuariosService.restablecerPassword(usuario.value.id, datos)
    
    alerta.success({
      titulo: 'Contraseña actualizada',
      mensaje: 'La contraseña ha sido restablecida correctamente',
      duracion: 1000,
    })
    
    if (formularioPasswordRef.value) {
      formularioPasswordRef.value.reset()
    }
  } catch (error) {
    console.error('Error al restablecer contraseña:', error)
    
    let mensaje = 'Ocurrió un error al restablecer la contraseña.'
    if (error.response?.status === 422) {
      erroresPassword.value = error.response.data.errors || {}
      mensaje = Object.values(error.response.data.errors || {}).flat().join(', ') || mensaje
    } else if (error.response?.data?.mensaje) {
      mensaje = error.response.data.mensaje
    } else if (error.message) {
      mensaje = error.message
    }
    
    alerta.error({
      titulo: 'Error',
      mensaje,
      duracion: 1000,
    })
  } finally {
    guardandoPassword.value = false
  }
}

async function handleGuardarRol() {
  await handleCambioRol(rolSeleccionado.value)
}

async function handleCambioRol(rolId) {
  guardandoRol.value = true
  try {
    await usuariosService.actualizarRolUsuario(usuario.value.id, rolId)
    alerta.success({
      titulo: 'Guardado',
      mensaje: 'Rol actualizado correctamente',
      duracion: 1000,
    })
    await cargarUsuario()
  } catch (error) {
    console.error('Error al actualizar rol:', error)
    alerta.error({
      titulo: 'Error',
      mensaje: error.response?.data?.mensaje || 'No se pudo actualizar el rol del usuario',
      duracion: 1000,
    })
  } finally {
    guardandoRol.value = false
  }
}

async function confirmarEliminacion() {
  try {
    await usuariosService.eliminarUsuario(usuario.value.id)
    alerta.success({
      titulo: 'Eliminado',
      mensaje: 'Usuario eliminado correctamente',
      duracion: 1000,
    })
    router.push({ name: 'usuarios' })
  } catch (error) {
    console.error('Error al eliminar usuario:', error)
    alerta.error({
      titulo: 'Error',
      mensaje: error.response?.data?.mensaje || 'No se pudo eliminar el usuario',
      duracion: 1000,
    })
    if (zonaPeligroRef.value) {
      zonaPeligroRef.value.finalizarEliminacion()
    }
  }
}

function cancelar() {
  router.push({ name: 'usuarios' })
}
</script>

<style scoped>
@import '@/assets/styles/views/usuarios/VistaDetalleUsuario.css';
</style>
