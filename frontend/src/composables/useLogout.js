import { ref, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useLayoutStore } from '@/stores/layout'
import { logoutMessages } from '@/config/menuItems'

// Duraciones de las animaciones CSS (en ms)
const FADE_OVERLAY_DURATION = 500 // fade-overlay transition
const SLIDE_UP_DURATION = 400 // slide-up transition
const MESSAGE_DISPLAY_TIME = 1000 // tiempo entre mensajes
// Tiempo mínimo: entrada (0.5s) + tiempo para ver varios mensajes + salida (0.5s)
// Mostrar al menos 3 mensajes completos para mejor UX
const MIN_DISPLAY_TIME = FADE_OVERLAY_DURATION + (MESSAGE_DISPLAY_TIME * 3) + FADE_OVERLAY_DURATION

export function useLogout() {
    const router = useRouter()
    const authStore = useAuthStore()
    const layoutStore = useLayoutStore()

    const confirmando = ref(false)
    const timer = ref(null)

    const startLogout = async () => {
        if (!confirmando.value) {
            confirmando.value = true

            // Auto-reset after 5 seconds
            if (timer.value) clearTimeout(timer.value)
            timer.value = setTimeout(() => {
                confirmando.value = false
            }, 5000)

            return
        }

        // Second click: Confirm logout
        if (timer.value) clearTimeout(timer.value)
        const startTime = Date.now()
        authStore.isLoggingOut = true

        // Cycle through messages
        let msgIndex = 0
        authStore.logoutMessage = logoutMessages[msgIndex]

        const messageInterval = setInterval(() => {
            msgIndex++
            if (msgIndex < logoutMessages.length) {
                authStore.logoutMessage = logoutMessages[msgIndex]
            } else {
                clearInterval(messageInterval)
            }
        }, MESSAGE_DISPLAY_TIME)

        try {
            // Ejecutar logout
            if (layoutStore.menuFlotanteAbierto) {
                layoutStore.cerrarMenuFlotante()
            }

            await authStore.logout()

            // Calcular tiempo transcurrido
            const elapsedTime = Date.now() - startTime
            // Asegurar tiempo mínimo para que las animaciones se vean completamente
            const remainingTime = Math.max(0, MIN_DISPLAY_TIME - elapsedTime)
            
            if (remainingTime > 0) {
                await new Promise(resolve => setTimeout(resolve, remainingTime))
            }

            // Reset state for next session
            authStore.isLoggingOut = false
            authStore.logoutMessage = ''

            router.push('/login')
        } catch (error) {
            console.error('Error during logout:', error)
            authStore.isLoggingOut = false
            confirmando.value = false
        } finally {
            clearInterval(messageInterval)
        }
    }

    onUnmounted(() => {
        if (timer.value) clearTimeout(timer.value)
    })

    return {
        confirmando,
        startLogout
    }
}
