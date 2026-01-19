<template>
  <div class="dropzone-container">
    <div
      ref="dropzoneElement"
      class="dropzone"
      :class="{ 'dz-started': archivos.length > 0, 'dz-disabled': deshabilitado }"
    >
      <div class="dz-message">
        <div class="dz-icon">
          <svg
            width="48"
            height="48"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <polyline points="17 8 12 3 7 8" />
            <line x1="12" y1="3" x2="12" y2="15" />
          </svg>
        </div>
        <p class="dz-text">{{ textoMensaje }}</p>
        <p v-if="ayuda" class="dz-help">{{ ayuda }}</p>
      </div>
    </div>

    <div v-if="archivos.length > 0" class="archivos-lista">
      <div
        v-for="(archivo, index) in archivos"
        :key="index"
        class="archivo-item"
      >
        <div class="archivo-info">
          <div class="archivo-icono">
            <svg
              v-if="esImagen(archivo)"
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
              <circle cx="8.5" cy="8.5" r="1.5" />
              <polyline points="21 15 16 10 5 21" />
            </svg>
            <svg
              v-else
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
              <polyline points="14 2 14 8 20 8" />
              <line x1="16" y1="13" x2="8" y2="13" />
              <line x1="16" y1="17" x2="8" y2="17" />
              <polyline points="10 9 9 9 8 9" />
            </svg>
          </div>
          <div class="archivo-details">
            <p class="archivo-nombre">{{ archivo.name }}</p>
            <p class="archivo-tamaño">{{ formatearTamaño(archivo.size) }}</p>
          </div>
        </div>
        <button
          v-if="!deshabilitado"
          @click="eliminarArchivo(index)"
          type="button"
          class="btn-eliminar-archivo"
          :aria-label="`Eliminar ${archivo.name}`"
        >
          <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <line x1="18" y1="6" x2="6" y2="18" />
            <line x1="6" y1="6" x2="18" y2="18" />
          </svg>
        </button>
      </div>
    </div>

    <div v-if="error" class="error-mensaje">
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import Dropzone from 'dropzone'
import 'dropzone/dist/dropzone.css'

const props = defineProps({
  modelValue: {
    type: [File, Array],
    default: null,
  },
  aceptar: {
    type: String,
    default: '*/*',
  },
  maxArchivos: {
    type: Number,
    default: 1,
  },
  maxTamaño: {
    type: Number,
    default: 10485760, // 10MB por defecto
  },
  textoMensaje: {
    type: String,
    default: 'Arrastra archivos aquí o haz clic para seleccionar',
  },
  ayuda: {
    type: String,
    default: '',
  },
  deshabilitado: {
    type: Boolean,
    default: false,
  },
  multiple: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'error'])

const dropzoneElement = ref(null)
const dropzone = ref(null)
const archivos = ref([])
const error = ref('')

onMounted(() => {
  if (!dropzoneElement.value) return

  Dropzone.autoDiscover = false

  dropzone.value = new Dropzone(dropzoneElement.value, {
    url: '#', // No necesitamos URL porque manejamos archivos localmente
    autoProcessQueue: false,
    addRemoveLinks: false,
    // Si no es múltiple, no limitar con maxFiles (lo manejamos manualmente)
    // Si es múltiple, usar el límite especificado
    maxFiles: props.multiple ? props.maxArchivos : null,
    maxFilesize: props.maxTamaño / 1048576, // Convertir a MB
    acceptedFiles: props.aceptar,
    multiple: props.multiple,
    disabled: props.deshabilitado,
    createImageThumbnails: true,
    thumbnailWidth: 120,
    thumbnailHeight: 120,
    clickable: !props.deshabilitado,
  })

  // Manejar archivos añadidos
  dropzone.value.on('addedfile', (file) => {
    error.value = ''
    
    if (props.multiple) {
      // Verificar límite manualmente para múltiples archivos
      // dropzone.value.files ya incluye el archivo recién agregado
      if (dropzone.value.files.length > props.maxArchivos) {
        error.value = `Solo se permiten ${props.maxArchivos} archivo(s)`
        dropzone.value.removeFile(file)
        return
      }
      archivos.value.push(file)
      emit('update:modelValue', [...archivos.value])
    } else {
      // Eliminar todos los archivos anteriores excepto el nuevo
      // El archivo nuevo ya está en dropzone.value.files cuando se dispara addedfile
      const todosLosArchivos = [...dropzone.value.files]
      todosLosArchivos.forEach((f) => {
        if (f !== file) {
          dropzone.value.removeFile(f)
        }
      })
      archivos.value = [file]
      emit('update:modelValue', file)
    }
  })

  // Manejar errores
  dropzone.value.on('error', (file, errorMessage) => {
    error.value = errorMessage
    emit('error', errorMessage)
    
    if (file) {
      dropzone.value.removeFile(file)
    }
  })

  // Manejar archivos removidos
  dropzone.value.on('removedfile', (file) => {
    archivos.value = archivos.value.filter((f) => f !== file)
    
    if (props.multiple) {
      emit('update:modelValue', archivos.value.length > 0 ? [...archivos.value] : null)
    } else {
      emit('update:modelValue', null)
    }
  })

  // Cargar archivos iniciales si existen
  if (props.modelValue) {
    if (Array.isArray(props.modelValue)) {
      props.modelValue.forEach((file) => {
        if (file instanceof File) {
          dropzone.value.addFile(file)
        }
      })
    } else if (props.modelValue instanceof File) {
      dropzone.value.addFile(props.modelValue)
    }
  }
})

onUnmounted(() => {
  if (dropzone.value) {
    dropzone.value.destroy()
  }
})

watch(
  () => props.deshabilitado,
  (nuevoValor) => {
    if (dropzone.value) {
      if (nuevoValor) {
        dropzone.value.disable()
      } else {
        dropzone.value.enable()
      }
    }
  }
)

watch(
  () => props.modelValue,
  (nuevoValor) => {
    if (!dropzone.value) return

    // Limpiar archivos actuales del dropzone
    const archivosActuales = dropzone.value.files
    archivosActuales.forEach((file) => {
      dropzone.value.removeFile(file)
    })
    archivos.value = []

    // Agregar nuevos archivos
    if (nuevoValor) {
      if (Array.isArray(nuevoValor)) {
        nuevoValor.forEach((file) => {
          if (file instanceof File) {
            dropzone.value.addFile(file)
          }
        })
      } else if (nuevoValor instanceof File) {
        dropzone.value.addFile(nuevoValor)
      }
    }
  }
)

function eliminarArchivo(index) {
  if (!dropzone.value) return
  
  const archivo = archivos.value[index]
  dropzone.value.removeFile(archivo)
}

function esImagen(archivo) {
  return archivo.type && archivo.type.startsWith('image/')
}

function formatearTamaño(bytes) {
  if (bytes === 0) return '0 Bytes'
  
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}
</script>

<style scoped>
@import '@/assets/styles/components/DropzoneArchivo.css';
</style>
