<template>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 1024 1024"
    :class="['collapse-icon-svg', { 'is-collapsed': collapsed }]"
    fill="currentColor"
  >
    <!-- Icono combinado: hamburguesa con flecha -->
    <!-- Líneas horizontales (hamburguesa) -->
    <path d="M914.368 673.664h-519.68c-25.152 0-45.568-22.016-45.568-48.896 0-26.88 20.416-48.896 45.568-48.896h519.68c25.216 0 45.632 22.016 45.632 48.896 0 26.88-20.48 48.896-45.632 48.896z m0-228.096h-519.68c-25.152 0-45.568-21.952-45.568-48.896 0-26.88 20.416-48.896 45.568-48.896h519.68c25.216 0 45.632 22.016 45.632 48.896 0 26.88-20.48 48.896-45.632 48.896z m-3.264-219.904H115.328c-26.88 0-50.56-20.352-51.328-47.168A48.896 48.896 0 0 1 112.896 128h795.776c26.88 0 50.56 20.416 51.328 47.168a48.896 48.896 0 0 1-48.896 50.56z" />
    <!-- Flecha apuntando a la izquierda -->
    <path d="M64 510.784l227.328 162.112V348.672L64 510.784c0 0.768 0 0.768 0 0z" />
    <!-- Línea inferior -->
    <path d="M115.328 896h795.776c26.88 0 50.56 20.48 51.328 47.232a48.896 48.896 0 0 1-48.896 50.496H115.328c-26.88 0-50.56-20.416-51.328-47.232a48.896 48.896 0 0 1 48.896-50.496z" />
  </svg>
</template>

<script setup>
defineProps({
  collapsed: {
    type: Boolean,
    default: false
  }
})
</script>

<style scoped>
.collapse-icon-svg {
  width: 1.75rem;
  height: 1.75rem;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Estado expandido: icono normal (flecha apunta a la izquierda) */
.collapse-icon-svg:not(.is-collapsed) {
  transform: scaleX(1);
}

/* Estado colapsado: espejo horizontal (flecha apunta a la derecha) */
.collapse-icon-svg.is-collapsed {
  transform: scaleX(-1);
}
</style>
