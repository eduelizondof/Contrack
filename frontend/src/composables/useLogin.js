import { onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { loginMessages } from '@/config/menuItems'

// Duraciones de las animaciones CSS (en ms)
const FADE_OVERLAY_DURATION = 500 // fade-overlay transition
const SLIDE_UP_DURATION = 400 // slide-up transition
const MESSAGE_DISPLAY_TIME = 1000 // tiempo entre mensajes
// Tiempo mínimo: entrada (0.5s) + al menos 1 mensaje visible (1s) + salida (0.5s)
const MIN_DISPLAY_TIME = FADE_OVERLAY_DURATION + MESSAGE_DISPLAY_TIME + FADE_OVERLAY_DURATION

export function useLogin() {
    const authStore = useAuthStore()

    const startLogin = async (loginCallback) => {
        const startTime = Date.now()
        
        // Activate overlay
        authStore.isLoggingIn = true

        // Cycle through messages
        let msgIndex = 0
        authStore.loginMessage = loginMessages[msgIndex]

        const messageInterval = setInterval(() => {
            msgIndex++
            if (msgIndex < loginMessages.length) {
                authStore.loginMessage = loginMessages[msgIndex]
            } else {
                clearInterval(messageInterval)
            }
        }, MESSAGE_DISPLAY_TIME)

        try {
            // Execute the login callback
            if (loginCallback && typeof loginCallback === 'function') {
                await loginCallback()
            }

            // Calcular tiempo transcurrido
            const elapsedTime = Date.now() - startTime
            // Asegurar tiempo mínimo para que las animaciones se vean completamente
            const remainingTime = Math.max(0, MIN_DISPLAY_TIME - elapsedTime)
            
            if (remainingTime > 0) {
                await new Promise(resolve => setTimeout(resolve, remainingTime))
            }
        } catch (error) {
            console.error('Error during login:', error)
        } finally {
            // Reset state
            authStore.isLoggingIn = false
            authStore.loginMessage = ''
            clearInterval(messageInterval)
        }
    }

    return {
        startLogin
    }
}
