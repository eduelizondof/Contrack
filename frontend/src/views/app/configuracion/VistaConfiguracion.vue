<template>
  <PlantillaApp>
    <div class="vista-configuracion">
      <header class="config-header">
        <div class="header-content">
          <h1 class="page-title">Configuración</h1>
          <p class="page-subtitle">Gestiona la configuración del sistema</p>
        </div>
      </header>
      
      <div class="config-grid">
        <CardConfiguracion
          titulo="Usuarios"
          descripcion="Gestiona usuarios del sistema"
          :icon="UsersIcon"
          @click="handleUsuarios"
        />
        
        <CardConfiguracion
          titulo="Roles y Permisos"
          descripcion="Define roles y asigna permisos"
          :icon="ShieldIcon"
          @click="handleRoles"
        />
      </div>
    </div>
  </PlantillaApp>
</template>

<script setup>
import { useRouter } from 'vue-router'
import PlantillaApp from '@/layouts/PlantillaApp.vue'
import CardConfiguracion from '@/components/vistas/configuracion/CardConfiguracion.vue'
import { UsersIcon, ShieldIcon } from '@/components/globales/iconos'

const router = useRouter()

function handleUsuarios() {
  router.push({ name: 'usuarios' })
}

function handleRoles() {
  router.push({ name: 'roles' })
}
</script>

<style scoped>
@import '@/assets/styles/views/configuracion/VistaConfiguracion.css';
</style>
