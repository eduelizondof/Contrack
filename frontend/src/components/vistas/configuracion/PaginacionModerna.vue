<template>
  <div class="paginacion-moderna" v-if="totalPaginas > 1">
    <button
      @click="irAPagina(paginaActual - 1)"
      :disabled="paginaActual === 1"
      class="paginacion-btn"
      aria-label="Página anterior"
    >
      <ChevronLeftIcon class="btn-icon" />
    </button>
    
    <div class="paginacion-numeros">
      <button
        v-for="pagina in paginasVisibles"
        :key="pagina"
        @click="irAPagina(pagina)"
        class="paginacion-numero"
        :class="{
          'active': pagina === paginaActual,
          'ellipsis': pagina === '...'
        }"
        :disabled="pagina === '...'"
      >
        {{ pagina }}
      </button>
    </div>
    
    <button
      @click="irAPagina(paginaActual + 1)"
      :disabled="paginaActual === totalPaginas"
      class="paginacion-btn"
      aria-label="Página siguiente"
    >
      <ChevronRightIcon class="btn-icon" />
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { ChevronLeftIcon, ChevronRightIcon } from '@/components/globales/iconos'

const props = defineProps({
  paginaActual: {
    type: Number,
    required: true,
  },
  totalPaginas: {
    type: Number,
    required: true,
  },
})

const emit = defineEmits(['cambiar-pagina'])

const paginasVisibles = computed(() => {
  const total = props.totalPaginas
  const actual = props.paginaActual
  const paginas = []
  
  if (total <= 7) {
    // Mostrar todas las páginas si son 7 o menos
    for (let i = 1; i <= total; i++) {
      paginas.push(i)
    }
  } else {
    // Lógica para mostrar páginas con elipsis
    if (actual <= 3) {
      // Al inicio
      for (let i = 1; i <= 4; i++) {
        paginas.push(i)
      }
      paginas.push('...')
      paginas.push(total)
    } else if (actual >= total - 2) {
      // Al final
      paginas.push(1)
      paginas.push('...')
      for (let i = total - 3; i <= total; i++) {
        paginas.push(i)
      }
    } else {
      // En el medio
      paginas.push(1)
      paginas.push('...')
      for (let i = actual - 1; i <= actual + 1; i++) {
        paginas.push(i)
      }
      paginas.push('...')
      paginas.push(total)
    }
  }
  
  return paginas
})

function irAPagina(pagina) {
  if (pagina >= 1 && pagina <= props.totalPaginas && pagina !== props.paginaActual) {
    emit('cambiar-pagina', pagina)
  }
}
</script>

<style scoped>
@import '@/assets/styles/components/PaginacionModerna.css';
</style>
