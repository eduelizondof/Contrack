<template>
  <Teleport to="body">
    <Transition name="alerta">
      <div
        v-if="estado.visible"
        class="alerta-backdrop"
        :class="claseBackdrop"
        @click.self="handleCerrar"
      >
        <div
          class="alerta-box-wrapper"
          :class="[claseWrapper, { 'alerta-con-timer': estado.duracion && estado.duracion > 0 }]"
          :style="estiloBox"
        >
          <!-- SVG para el borde animado -->
          <svg
            v-if="estado.duracion && estado.duracion > 0"
            class="alerta-border-svg"
            :class="`alerta-${estado.tipo}`"
            :style="estiloBox"
            viewBox="0 0 500 500"
            preserveAspectRatio="none"
          >
            <rect
              class="alerta-border-path"
              :class="`alerta-${estado.tipo}`"
              x="2.5"
              y="2.5"
              width="495"
              height="495"
              rx="21"
              ry="21"
              fill="none"
              pathLength="1000"
            />
          </svg>
          <div
            class="alerta-box"
            :class="claseBox"
          >
          <!-- Icono -->
          <div
            v-if="estado.mostrarIcono && estado.tipo !== 'modal' && iconoComponente"
            class="alerta-icono-container"
            :class="`alerta-${estado.tipo}`"
          >
            <component :is="iconoComponente" class="alerta-icono" />
          </div>

          <!-- Título -->
          <h2 v-if="estado.titulo" class="alerta-titulo">
            {{ estado.titulo }}
          </h2>

          <!-- Mensaje -->
          <p v-if="estado.mensaje" class="alerta-mensaje">
            {{ estado.mensaje }}
          </p>

          <!-- Botones de acción -->
          <div v-if="estado.mostrarAcciones" class="alerta-acciones">
            <button
              class="alerta-btn alerta-btn-cancelar"
              @click="handleCerrar"
              :disabled="procesando"
            >
              {{ estado.textoCancelar }}
            </button>
            <button
              class="alerta-btn alerta-btn-confirmar"
              :class="{ 'alerta-btn-danger': estado.peligro }"
              @click="handleConfirmar"
              :disabled="procesando"
            >
              {{ estado.textoConfirmar }}
            </button>
          </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useAlerta } from '@/composables/useAlerta'
import CheckCircleIcon from '@/components/globales/iconos/CheckCircleIcon.vue'
import AlertCircleIcon from '@/components/globales/iconos/AlertCircleIcon.vue'
import AlertTriangleIcon from '@/components/globales/iconos/AlertTriangleIcon.vue'
import InfoCircleIcon from '@/components/globales/iconos/InfoCircleIcon.vue'

const { estado, cerrar, aceptarConfirmacion } = useAlerta()
const procesando = ref(false)

// Clase del backdrop según tipo
const claseBackdrop = computed(() => {
  if (!estado.value.tipo || estado.value.tipo === 'modal') {
    return 'alerta-backdrop-modal'
  }
  return `alerta-backdrop-${estado.value.tipo}`
})

// Clase del wrapper según tipo y si tiene timer
const claseWrapper = computed(() => {
  const clases = []
  
  if (estado.value.tipo && estado.value.tipo !== 'modal') {
    clases.push(`alerta-${estado.value.tipo}`)
  }
  
  return clases.join(' ')
})

// Clase del box según tipo
const claseBox = computed(() => {
  const clases = []
  
  if (estado.value.tipo && estado.value.tipo !== 'modal') {
    clases.push(`alerta-${estado.value.tipo}`)
  }
  
  return clases.join(' ')
})

// Estilo del box para la duración del timer
const estiloBox = computed(() => {
  if (estado.value.duracion && estado.value.duracion > 0) {
    return {
      '--timer-duration': `${estado.value.duracion}ms`,
    }
  }
  return {}
})

// Icono según tipo
const iconoComponente = computed(() => {
  const iconos = {
    success: CheckCircleIcon,
    error: AlertCircleIcon,
    warning: AlertTriangleIcon,
    info: InfoCircleIcon,
  }
  return iconos[estado.value.tipo] || null
})

// Manejar cierre
function handleCerrar() {
  if (!procesando.value) {
    cerrar()
  }
}

// Manejar confirmación
async function handleConfirmar() {
  if (procesando.value) return
  
  procesando.value = true
  try {
    aceptarConfirmacion()
  } finally {
    // El procesando se resetea cuando se cierra la alerta
    setTimeout(() => {
      procesando.value = false
    }, 300)
  }
}
</script>

<style>
@import '@/assets/styles/components/AlertaGlobal.css';
</style>
