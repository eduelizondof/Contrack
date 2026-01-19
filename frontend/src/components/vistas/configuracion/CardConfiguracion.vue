<template>
  <div 
    class="card-configuracion"
    :class="{ 'disabled': disabled }"
    @click="handleClick"
  >
    <div class="card-icon">
      <component :is="icon" class="icon" />
    </div>
    <div class="card-content">
      <h3 class="card-title">{{ titulo }}</h3>
      <p class="card-description">{{ descripcion }}</p>
    </div>
    <div class="card-arrow">
      <ChevronRightIcon class="arrow-icon" />
    </div>
  </div>
</template>

<script setup>
import { ChevronRightIcon } from '@/components/globales/iconos'

const props = defineProps({
  titulo: {
    type: String,
    required: true,
  },
  descripcion: {
    type: String,
    default: '',
  },
  icon: {
    type: [Object, String],
    required: true,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['click'])

function handleClick() {
  if (!props.disabled) {
    emit('click')
  }
}
</script>

<style scoped>
@import '@/assets/styles/components/CardConfiguracion.css';
</style>
