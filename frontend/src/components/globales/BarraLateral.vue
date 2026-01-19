<template>
  <aside :class="['sidebar', { collapsed: collapsed }]">
    <div class="sidebar-content">
      <div v-if="isDesktop" class="sidebar-header">
        <button
          @click="toggleCollapse"
          class="sidebar-collapse-header-button"
          :aria-label="collapsed ? 'Expandir menú' : 'Colapsar menú'"
        >
          <CollapseIcon :collapsed="collapsed" class="sidebar-collapse-icon" />
        </button>
      </div>
      <nav class="sidebar-nav">
        <ElementoMenu
          v-for="item in menuItems"
          :key="item.id"
          :item="item"
          :collapsed="collapsed"
        />
      </nav>

      <div class="sidebar-footer">
        <button
          @click="toggleTheme"
          :class="['theme-button', { collapsed: collapsed }]"
          :aria-label="isDark ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'"
        >
          <AlternadorTema :class="{ 'collapsed': collapsed }" :standalone="false" />
          <span v-if="!collapsed" class="theme-label">Tema</span>
        </button>
        <button
          @click="irAConfiguracion"
          :class="['config-button', { collapsed: collapsed }]"
          aria-label="Configuración"
        >
          <component :is="SettingsIcon" class="config-icon" />
          <span v-if="!collapsed" class="config-label">Configuración</span>
        </button>
        <button
          @click="startLogout"
          :class="['logout-button', { collapsed: collapsed, 'confirming': confirmando }]"
          :aria-label="confirmando ? 'Confirmar cierre de sesión' : 'Cerrar sesión'"
        >
          <component :is="LogoutIcon" class="logout-icon" />
          <span v-if="!collapsed" class="logout-label">
            {{ confirmando ? '¿Estás seguro?' : 'Cerrar Sesión' }}
          </span>
        </button>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useLayoutStore } from '@/stores/layout'
import { useThemeStore } from '@/stores/theme'
import { usePlatform } from '@/composables/usePlatform'
import { menuItems } from '@/config/menuItems'
import ElementoMenu from './ElementoMenu.vue'
import CollapseIcon from './iconos/CollapseIcon.vue'
import LogoutIcon from './iconos/LogoutIcon.vue'
import SettingsIcon from './iconos/SettingsIcon.vue'
import AlternadorTema from './AlternadorTema.vue'
import { useLogout } from '@/composables/useLogout'

const router = useRouter()
const authStore = useAuthStore()
const layoutStore = useLayoutStore()
const themeStore = useThemeStore()
const { isDesktop } = usePlatform()

const collapsed = computed(() => layoutStore.sidebarCollapsed)
const isDark = computed(() => themeStore.currentTheme === 'dark')

const { confirmando, startLogout } = useLogout()

const toggleCollapse = () => {
  layoutStore.toggleSidebar()
}

const toggleTheme = () => {
  themeStore.toggleTheme()
}

const irAConfiguracion = () => {
  router.push('/configuracion')
}

// El cierre de sesión se maneja en useLogout
</script>

<style scoped>
@import '@/assets/styles/components/BarraLateral.css';
</style>
