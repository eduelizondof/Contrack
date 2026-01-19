<template>
  <div class="menu-item-wrapper">
    <component
      :is="item.route ? 'router-link' : 'button'"
      :to="item.route || undefined"
      :class="['menu-item', { active: isActive }]"
      @click="handleClick"
    >
      <div class="menu-icon-wrapper">
        <component :is="iconComponent" class="menu-icon" />
        <!-- Badge de notificaciones para chat -->
        <span 
          v-if="chatBadge > 0" 
          class="menu-badge"
        >
          {{ chatBadge > 99 ? '99+' : chatBadge }}
        </span>
      </div>
      <span v-if="!collapsed" class="menu-label">{{ item.label }}</span>
      <ChevronDownIcon
        v-if="!collapsed && hasSubmenu"
        :class="['chevron', { rotated: submenuOpen }]"
      />
    </component>

    <Transition name="submenu">
      <div v-if="!collapsed && hasSubmenu && submenuOpen" class="submenu">
        <router-link
          v-for="subItem in item.submenu"
          :key="subItem.id"
          :to="subItem.route"
          class="submenu-item"
          @click="handleSubmenuClick(subItem)"
        >
          {{ subItem.label }}
        </router-link>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, h } from 'vue'
import { useRoute } from 'vue-router'
import { useLayoutStore } from '@/stores/layout'
import { useChatStore } from '@/stores/chat'
import * as icons from './iconos/index.js'
import ChevronDownIcon from './iconos/ChevronDownIcon.vue'

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  collapsed: {
    type: Boolean,
    default: false,
  },
})

const route = useRoute()
const layoutStore = useLayoutStore()
const chatStore = useChatStore()
const submenuOpen = ref(false)

// Badge para chat
const chatBadge = computed(() => {
  if (props.item.id === 'chat') {
    return chatStore.totalMensajesNoLeidos
  }
  return 0
})

const hasSubmenu = computed(() => {
  return props.item.submenu && props.item.submenu.length > 0
})

const isActive = computed(() => {
  if (!props.item.route) return false
  return route.path === props.item.route || 
         (hasSubmenu.value && props.item.submenu.some(sub => sub.route === route.path))
})

const iconComponent = computed(() => {
  const iconName = props.item.icon
  const IconComponent = icons[iconName]
  return IconComponent || icons.HomeIcon
})

const emit = defineEmits(['navigate'])

const handleClick = (e) => {
  // Si el item no tiene ruta (como el chat), manejar acción especial
  if (!props.item.route) {
    e.preventDefault()
    if (props.item.id === 'chat') {
      layoutStore.abrirChatDrawer()
    }
    emit('navigate')
    return
  }

  if (hasSubmenu.value && !props.collapsed) {
    e.preventDefault()
    submenuOpen.value = !submenuOpen.value
  } else {
    // Si no tiene submenú, emitir evento para cerrar menú flotante
    emit('navigate')
  }
}

const handleSubmenuClick = (subItem) => {
  // Verificar si el subitem tiene submenú anidado
  const hasSubSubmenu = subItem.submenu && subItem.submenu.length > 0
  
  // Solo cerrar el menú flotante si el subitem NO tiene sub-submenú
  if (!hasSubSubmenu) {
    emit('navigate')
  }
  // Si tiene sub-submenú, aquí se podría agregar lógica para desplegarlo
  // pero actualmente no hay sub-submenús en la estructura
}
</script>

<style scoped>
.menu-item-wrapper {
  position: relative;
}

.menu-icon-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.submenu {
  margin-left: 2.5rem;
  margin-top: 0.25rem;
  overflow: hidden;
}

/* Badge de notificaciones */
.menu-badge {
  position: absolute;
  top: -0.25rem;
  right: -0.25rem;
  min-width: 1.125rem;
  height: 1.125rem;
  padding: 0 0.25rem;
  background: var(--color-primary, #3b82f6);
  color: white;
  border-radius: 0.5625rem;
  font-size: 0.625rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  border: 2px solid var(--color-surface-bg, #ffffff);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  z-index: 10;
}

html.dark .menu-badge {
  border-color: var(--color-surface-bg, #111111);
}

/* Transiciones */
.submenu-enter-active,
.submenu-leave-active {
  transition: all 0.3s ease;
  max-height: 500px;
}

.submenu-enter-from,
.submenu-leave-to {
  opacity: 0;
  max-height: 0;
  transform: translateY(-10px);
}
</style>
