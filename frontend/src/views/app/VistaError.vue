<template>
  <PlantillaApp>
    <div class="error-container">
      <div class="error-content animate-fade-in">
        <!-- Ilustración animada -->
        <div class="error-illustration">
          <div class="frame">
            <div class="scene1">
              <div class="boy">
                <div class="boy__head">
                  <div class="boy__hair"></div>
                  <div class="boy__eyes"></div>
                  <div class="boy__mouth"></div>
                  <div class="boy__cheeks"></div>
                </div>
                <div class="noodle"></div>
                <div class="boy__leftArm">
                  <div class="chopsticks"></div>
                </div>
              </div>
              <div class="plate"></div>
              <div class="rightArm"></div>
            </div>
            <div class="scene2" v-if="tipo === '404'">5 minutes later</div>
          </div>
        </div>

        <!-- Mensaje principal -->
        <div class="error-message">
          <h1 class="error-title">
            <span v-if="tipo === '404'">404</span>
            <span v-else>{{ titulo }}</span>
          </h1>
          <p class="error-description">
            {{ mensaje }}
          </p>
        </div>

        <!-- Botones de acción -->
        <div class="error-actions">
          <button 
            v-if="puedeRegresar" 
            @click="regresar" 
            class="btn-secondary"
          >
            <ArrowLeftIcon width="18" height="18" />
            Regresar
          </button>
          <button @click="irAlInicio" class="btn-primary">
            Ir al Inicio
            <ArrowRightIcon width="18" height="18" />
          </button>
        </div>
      </div>
    </div>
  </PlantillaApp>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import PlantillaApp from '@/layouts/PlantillaApp.vue'
import { ArrowLeftIcon, ArrowRightIcon } from '@/components/globales/iconos'

const props = defineProps({
  tipo: {
    type: String,
    default: '404', // '404' | 'construccion'
    validator: (value) => ['404', 'construccion'].includes(value)
  },
  titulo: {
    type: String,
    default: 'Página en Construcción'
  },
  mensaje: {
    type: String,
    default: 'Esta página aún no está disponible. Estamos trabajando en ella.'
  }
})

const router = useRouter()

const puedeRegresar = computed(() => {
  return window.history.length > 1
})

function regresar() {
  router.go(-1)
}

function irAlInicio() {
  router.push({ name: 'inicio' })
}
</script>

<style scoped>
@import '@/assets/styles/views/app/VistaError.css';
</style>