<script setup>
import { onMounted, onBeforeUnmount } from 'vue'
import { RouterView } from 'vue-router'
import { usePageLoader } from '@/composables/usePageLoader'
import CargadorPagina from '@/components/globales/CargadorPagina.vue'
import SuperposicionCerrarSesion from '@/components/globales/SuperposicionCerrarSesion.vue'
import AlertaGlobal from '@/components/globales/AlertaGlobal.vue'

const { showLoader, loaderRef, initializeLoader, cleanupLoader } = usePageLoader()

onMounted(() => {
  initializeLoader()
})

onBeforeUnmount(() => {
  cleanupLoader()
})
</script>

<template>
  <CargadorPagina v-if="showLoader" ref="loaderRef" :delay="300" />
  <RouterView />
  <SuperposicionCerrarSesion />
  <AlertaGlobal />
</template>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  width: 100%;
  min-height: 100vh;
}
</style>
