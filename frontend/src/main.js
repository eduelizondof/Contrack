import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'
import { useThemeStore } from './stores/theme'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Inicializar tema antes de montar
const themeStore = useThemeStore()
themeStore.initializeTheme()

// Montar la app - el router guard manejará la inicialización de auth cuando sea necesario
app.mount('#app')
