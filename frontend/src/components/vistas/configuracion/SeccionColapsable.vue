<template>
  <div class="seccion-colapsable" :class="{ 'abierta': estaAbierta }">
    <button
      @click="toggle"
      class="seccion-header"
      :aria-expanded="estaAbierta"
    >
      <div class="seccion-titulo-wrapper">
        <h3 class="seccion-titulo">{{ titulo }}</h3>
        <p v-if="subtitulo" class="seccion-subtitulo">{{ subtitulo }}</p>
      </div>
      <ChevronDownIcon 
        class="seccion-icono" 
        :class="{ 'rotado': estaAbierta }"
      />
    </button>
    
    <div class="seccion-contenido" v-show="estaAbierta">
      <div class="seccion-body">
        <slot />
      </div>
      <div v-if="mostrarGuardar" class="seccion-acciones">
        <button
          @click="$emit('guardar')"
          :disabled="guardando"
          class="btn-guardar-seccion"
        >
          {{ guardando ? 'Guardando...' : 'Guardar' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { ChevronDownIcon } from '@/components/globales/iconos'

const props = defineProps({
  titulo: {
    type: String,
    required: true,
  },
  subtitulo: {
    type: String,
    default: '',
  },
  abierta: {
    type: Boolean,
    default: false,
  },
  mostrarGuardar: {
    type: Boolean,
    default: false,
  },
  guardando: {
    type: Boolean,
    default: false,
  },
})

const estaAbierta = ref(props.abierta)

function toggle() {
  estaAbierta.value = !estaAbierta.value
}

defineEmits(['guardar'])

defineExpose({
  abrir: () => { estaAbierta.value = true },
  cerrar: () => { estaAbierta.value = false },
  toggle,
  estaAbierta: () => estaAbierta.value,
})
</script>

<style scoped>
@import '@/assets/styles/components/SeccionColapsable.css';
</style>
