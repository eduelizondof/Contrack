<template>
  <Transition name="loader-fade">
    <div v-if="visible" class="page-loader">
      <div class="loader-container">
        <div class="logo-container">
          <svg
            class="conectiva-logo"
            viewBox="0 0 708.81 612"
            xmlns="http://www.w3.org/2000/svg"
            shape-rendering="geometricPrecision"
          >
            <defs>
              <!-- Filtro de glow con color primario -->
              <filter id="metallic-glow" x="-50%" y="-50%" width="200%" height="200%">
                <feGaussianBlur in="SourceAlpha" stdDeviation="3" result="blur" />
                <feOffset in="blur" dx="0" dy="0" result="offsetBlur" />
                <feFlood flood-color="#3b82f6" flood-opacity="0.4" />
                <feComposite in2="offsetBlur" operator="in" />
                <feMerge>
                  <feMergeNode />
                  <feMergeNode in="SourceGraphic" />
                </feMerge>
              </filter>
              
              <!-- Gradiente para el barrido diagonal único -->
              <linearGradient id="shine-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="rgba(255, 255, 255, 0)" />
                <stop offset="40%" stop-color="rgba(255, 255, 255, 0)" />
                <stop offset="48%" stop-color="rgba(255, 255, 255, 0.4)" />
                <stop offset="50%" stop-color="rgba(255, 255, 255, 0.9)" />
                <stop offset="52%" stop-color="rgba(255, 255, 255, 0.4)" />
                <stop offset="60%" stop-color="rgba(255, 255, 255, 0)" />
                <stop offset="100%" stop-color="rgba(255, 255, 255, 0)" />
              </linearGradient>
              
              <!-- Filtro de blur para suavizar bordes del barrido -->
              <filter id="shine-blur" x="-50%" y="-50%" width="200%" height="200%">
                <feGaussianBlur in="SourceGraphic" stdDeviation="12" />
              </filter>
              
              <!-- Máscara para limitar el barrido al logo -->
              <mask id="logo-mask">
                <rect width="100%" height="100%" fill="white" />
              </mask>
            </defs>
            
            <g transform="translate(3.535021 47.084995)">
              <g class="logo-group" filter="url(#metallic-glow)">
                <!-- Paths del logo -->
                <path
                  class="logo-path"
                  d="M229.47,443.73c-48.72-.09-56-38.88-56-62v-260.51c0-.62-.03-1.21-.15-1.8c17.33-5.23,29.93-21.3,29.93-40.3c0-23.25-18.85-42.13-42.1-42.13s-42.13,18.88-42.13,42.13c0,19.01,12.6,35.07,29.93,40.3-.12.59-.15,1.18-.15,1.8v260.51c0,53.46,30.98,86.64,80.82,86.64h.19l221.88-.27c6.81-.19,12.17-5.85,12.01-12.66-.09-3.25-1.42-6.19-3.56-8.33-2.32-2.35-5.57-3.78-9.1-3.68l-221.57.3ZM161.15,96.46c-9.56,0-17.37-7.77-17.37-17.33s7.8-17.37,17.37-17.37s17.33,7.8,17.33,17.37-7.77,17.33-17.33,17.33Z"
                  fill="#ffffff"
                />
                
                <path
                  class="logo-path"
                  d="M552.97,398.44v-250.73c0-53.46-30.98-86.67-80.82-86.67l-225.16.3c-6.78.19-12.17,5.85-11.98,12.66.15,6.81,5.88,12.23,12.63,12.01l224.66-.3c48.72.09,56,38.88,56,62v250.73c-17.24,5.29-29.78,21.33-29.78,40.3c0,23.25,18.85,42.1,42.13,42.1s42.1-18.85,42.1-42.1c0-18.97-12.54-35.01-29.78-40.3Zm-12.32,57.64c-9.6,0-17.37-7.77-17.37-17.33s7.77-17.37,17.37-17.37s17.33,7.8,17.33,17.37-7.77,17.33-17.33,17.33Z"
                  fill="#ffffff"
                />
                
                <path
                  class="logo-path"
                  d="M362.27,402.53c21.33-1.89,41.23-8.64,58.66-19.16c3.71,1.15,7.65,1.73,11.73,1.73c22.44,0,40.64-18.23,40.64-40.67c0-4.64-.77-9.13-2.23-13.28c10.71-19.62,16.81-42.13,16.81-66.06c0-26.93-7.71-52.03-21.05-73.27c4.09-6.35,6.47-13.93,6.47-22.04c0-22.44-18.2-40.64-40.64-40.64-10.18,0-19.47,3.75-26.59,9.91-12.38-5.57-25.75-9.32-39.78-10.96-5.39-.65-10.86-.99-16.41-.99-6.25,0-12.38.4-18.42,1.24-67.51,8.98-119.58,66.8-119.58,136.75s50.21,125.7,115.95,136.23c7.18,1.18,14.55,1.76,22.04,1.76c4.18,0,8.3-.19,12.38-.56l.02.01Zm70.39-42c-5.17,0-9.78-2.45-12.72-6.25-2.14-2.69-3.37-6.13-3.37-9.84c0-8.88,7.21-16.1,16.1-16.1c3.47,0,6.69,1.11,9.32,3c4.12,2.91,6.78,7.71,6.78,13.09c0,8.88-7.24,16.1-16.1,16.1h-.01Zm-1.27-206.78c.4-.03.84-.06,1.27-.06c8.51,0,15.54,6.72,16.03,15.11.06.31.06.65.06.99c0,8.88-7.24,16.1-16.1,16.1-.59,0-1.15-.03-1.7-.09-6.78-.71-12.32-5.63-13.9-12.1-.34-1.24-.5-2.54-.5-3.9c0-8.42,6.53-15.38,14.83-16.03l.01-.02ZM342.3,151.9c2.51-.19,5.05-.28,7.58-.28c15.29,0,29.9,3.03,43.24,8.57-.74,3.06-1.15,6.28-1.15,9.6c0,22.47,18.2,40.67,40.67,40.67c5.32,0,10.37-1.02,15.01-2.88c8.33,14.08,13.68,30.09,15.23,47.2h-157.03c2.26-40.33,15.82-76.52,36.43-102.89l.02.01Zm-33.03,7.24c-15.51,27.15-25.26,60.02-27.08,95.65h-45.32c3.96-43.8,32.9-80.48,72.4-95.65ZM237.31,279.55h44.91c1.76,33.43,10.56,64.42,24.52,90.48-36.96-15.26-64.17-49.46-69.43-90.48Zm68.59,0h156.57c-1.39,10.65-4.21,20.83-8.33,30.33-6.22-3.87-13.59-6.13-21.48-6.13-22.47,0-40.67,18.2-40.67,40.67c0,8.48,2.6,16.37,7.06,22.91-14.89,7.18-31.54,11.21-49.15,11.21-3.53,0-7.03-.15-10.49-.5-18.88-25.88-31.23-60.33-33.49-98.5l-.02.01Z"
                  fill="#ffffff"
                />
              </g>
              
              <!-- Barrido diagonal único - rectángulo largo en dirección diagonal -->
              <rect
                class="shine-overlay"
                x="-200"
                y="-200"
                width="1200"
                height="200"
                fill="url(#shine-gradient)"
                filter="url(#shine-blur)"
                mask="url(#logo-mask)"
                opacity="0.7"
              />
            </g>
          </svg>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  delay: {
    type: Number,
    default: 500
  }
})

const visible = ref(false)
let timer = null

onMounted(() => {
  timer = setTimeout(() => {
    visible.value = true
  }, props.delay)
})

onUnmounted(() => {
  if (timer) {
    clearTimeout(timer)
  }
})

function hide() {
  visible.value = false
}

defineExpose({ hide })
</script>

<style scoped>
.page-loader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: var(--color-background);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.loader-container {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}

.logo-container {
  width: 800px;
  height: 720px;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: logoBounce 4s ease-in-out infinite;
}

.conectiva-logo {
  width: 100%;
  height: 100%;
}

.logo-path {
  fill: var(--color-primary);
  transition: fill 0.3s ease;
}

/* Dark mode: logo blanco */
:global(.dark) .logo-path {
  fill: #ffffff;
}

.logo-group {
  filter: url(#metallic-glow);
}

/* Barrido diagonal único - animación suave */
.shine-overlay {
  mix-blend-mode: screen;
  pointer-events: none;
  transform-origin: center;
  animation: shineSweep 4s ease-in-out infinite;
}

@keyframes shineSweep {
  0% {
    transform: translate(-200%, -200%) rotate(45deg);
    opacity: 0;
  }
  30% {
    opacity: 0.8;
  }
  70% {
    opacity: 0.8;
  }
  100% {
    transform: translate(200%, 200%) rotate(45deg);
    opacity: 0;
  }
}

/* Animación de bounce lenta y suave */
@keyframes logoBounce {
  0%, 100% {
    transform: scale(1) translateY(0);
  }
  50% {
    transform: scale(1.05) translateY(-10px);
  }
}

/* Transiciones - más lentas y suaves */
.loader-fade-enter-active {
  transition: opacity 0.8s ease-in;
}

.loader-fade-leave-active {
  transition: opacity 1s ease-out;
}

.loader-fade-enter-from,
.loader-fade-leave-to {
  opacity: 0;
}

/* Detener animaciones cuando se está ocultando */
.loader-fade-leave-active .logo-container,
.loader-fade-leave-active .shine-overlay {
  animation: none !important;
}


/* Mobile */
@media (max-width: 768px) {
  .logo-container {
    width: 400px;
    height: 360px;
  }
}

@media (max-width: 480px) {
  .logo-container {
    width: 320px;
    height: 288px;
  }
}
</style>