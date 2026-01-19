<template>
  <PlantillaLogin>
    <main class="login-main">
      <!-- Login Form Card -->
      <div class="login-form-card">
        <!-- Logo and Header dentro del card -->
        <div class="login-header">
          <div class="login-logo-container">
            <img 
              src="/icono-bco.png" 
              alt="CNERP Logo" 
              class="login-logo "
            />
            
          </div>
          <h1 class="login-title">
            Inicia Sesión
          </h1>
          <p class="login-subtitle">
            Ingresa tu información de acceso
          </p>
        </div>

        <!-- Login Form -->
        <form @submit.prevent="startLogin(handleLogin)" class="login-form">
          <div v-if="error" class="login-error-message">
            {{ error }}
          </div>

          <div class="login-form-group">
            <TextInput
              id="email"
              v-model="email"
              type="email"
              placeholder="Correo electrónico"
              :icon="EmailIcon"
              :disabled="cargando"
              required
            />
          </div>

          <div class="login-form-group">
            <PasswordInput
              id="password"
              v-model="password"
              placeholder="Contraseña"
              :icon="LockIcon"
              :disabled="cargando"
              required
            />
          </div>

          <div class="login-checkbox-group">
            <Checkbox
              id="remember"
              v-model="remember"
              :disabled="cargando"
            >
              Recordarme
            </Checkbox>
          </div>

          <button
            type="submit"
            class="login-submit-btn"
            :disabled="cargando"
            aria-label="Iniciar sesión"
          >
            <span v-if="cargando" class="login-loading-content">
              <svg class="login-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="login-spinner-circle" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="login-spinner-path" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Iniciando sesión...
            </span>
            <span v-else>INGRESAR</span>
          </button>
        </form>

        <!-- Theme Toggle al centro - FUERA del form para evitar submit -->
        <div class="login-theme-toggle-container">
          <AlternadorTema />
        </div>
      </div>
    </main>
  </PlantillaLogin>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import PlantillaLogin from '@/layouts/PlantillaLogin.vue'
import TextInput from '@/components/globales/inputs/TextInput.vue'
import PasswordInput from '@/components/globales/inputs/PasswordInput.vue'
import Checkbox from '@/components/globales/inputs/Checkbox.vue'
import EmailIcon from '@/components/globales/iconos/EmailIcon.vue'
import LockIcon from '@/components/globales/iconos/LockIcon.vue'
import AlternadorTema from '@/components/globales/AlternadorTema.vue'
import { useLogin } from '@/composables/useLogin'
import '@/assets/styles/views/autenticacion/VistaLogin.css'

const router = useRouter()
const authStore = useAuthStore()

const { startLogin } = useLogin()

const email = ref('')
const password = ref('')
const remember = ref(false)
const error = ref('')
const cargando = ref(false)

const tooltipMensaje = 'Para recuperar tu contraseña, contacta al administrador del sistema'

onMounted(() => {
  // Si ya está autenticado, redirigir al inicio
  if (authStore.estaAutenticado) {
    router.push('/inicio')
  }
})

const handleLogin = async () => {
  error.value = ''
  cargando.value = true

  try {
    const resultado = await authStore.login({
      email: email.value,
      password: password.value,
      remember: remember.value,
    })

    if (resultado.exito) {
      router.push('/inicio')
    } else {
      error.value = resultado.mensaje
    }
  } catch (err) {
    error.value = 'Error al iniciar sesión. Por favor, intenta nuevamente.'
  } finally {
    cargando.value = false
  }
}
</script>
