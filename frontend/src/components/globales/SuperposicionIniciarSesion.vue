<template>
  <Teleport to="body">
    <Transition name="fade-overlay">
      <div v-if="authStore.isLoggingIn" class="login-overlay">
        <div class="login-overlay-content">
          <div class="loader-container">
            <div class="pulse-loader"></div>
            <div class="spinner-loader"></div>
          </div>
          <div class="message-container">
            <Transition name="slide-up" mode="out-in">
              <p :key="authStore.loginMessage" class="login-message">
                {{ authStore.loginMessage }}
              </p>
            </Transition>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
</script>

<style scoped>
.login-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(0, 0, 0, 0.8);
  backdrop-filter: blur(12px);
  display: flex;
  align-items: center;
  justify-content: center;
}

.login-overlay-content {
  text-align: center;
  color: white;
}

.loader-container {
  position: relative;
  width: 100px;
  height: 100px;
  margin: 0 auto 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.spinner-loader {
  width: 60px;
  height: 60px;
  border: 4px solid rgba(255, 255, 255, 0.1);
  border-left-color: var(--color-primary, #6366f1);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.pulse-loader {
  position: absolute;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: var(--color-primary, #6366f1);
  opacity: 0.15;
  animation: pulse 2s ease-out infinite;
}

.login-message {
  font-size: 1.25rem;
  font-weight: 500;
  letter-spacing: 0.025em;
  font-family: 'Outfit', sans-serif;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes pulse {
  0% { transform: scale(0.5); opacity: 0.5; }
  100% { transform: scale(1.5); opacity: 0; }
}

/* Transitions */
.fade-overlay-enter-active,
.fade-overlay-leave-active {
  transition: opacity 0.5s ease;
}

.fade-overlay-enter-from,
.fade-overlay-leave-to {
  opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-up-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.slide-up-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
