<template>
  <div class="login-layout">
    <!-- Animated background shapes -->
    <div class="shape-blob shape-1"></div>
    <div class="shape-blob shape-2"></div>
    <div class="shape-blob shape-3"></div>
    <div class="shape-blob shape-4"></div>
    <div class="shape-blob shape-5"></div>
    
    <!-- Background pattern overlay -->
    <div class="pattern-overlay"></div>
    
    <!-- Main content container -->
    <div class="login-container">
      <slot />
    </div>

    <!-- Login overlay -->
    <SuperposicionIniciarSesion />
  </div>
</template>

<script setup>
import SuperposicionIniciarSesion from '@/components/globales/SuperposicionIniciarSesion.vue'
// Layout para login con shapes animados y efecto glass
</script>

<style scoped>
.login-layout {
  min-height: 100vh;
  min-height: 100dvh;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #F3F4F6;
  padding: 1rem;
  position: relative;
  overflow: hidden;
  transition: background-color 0.3s;
}

/* Animated morphing shapes */
.shape-blob {
  position: absolute;
  filter: blur(60px);
  z-index: 0;
  opacity: 0.6;
  pointer-events: none;
  will-change: transform, border-radius;
}

.shape-1 {
  top: -10%;
  left: -10%;
  width: 350px;
  height: 350px;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.7) 0%, rgba(6, 182, 212, 0.7) 100%);
  border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%;
  animation: morph 12s ease-in-out infinite;
}

.shape-2 {
  bottom: 10%;
  right: -10%;
  width: 300px;
  height: 300px;
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.7) 0%, rgba(59, 130, 246, 0.7) 100%);
  border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
  animation: morph 10s ease-in-out infinite reverse;
}

.shape-3 {
  top: 50%;
  left: 50%;
  width: 250px;
  height: 250px;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.6) 0%, rgba(139, 92, 246, 0.6) 100%);
  border-radius: 50% 40% 60% 30% / 50% 40% 60% 50%;
  animation: morph-center 14s ease-in-out infinite;
}

.shape-4 {
  top: 20%;
  right: 20%;
  width: 220px;
  height: 220px;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.6) 0%, rgba(99, 102, 241, 0.6) 100%);
  border-radius: 30% 70% 50% 50% / 40% 60% 40% 60%;
  animation: morph 11s ease-in-out infinite reverse;
}

.shape-5 {
  bottom: 30%;
  left: 15%;
  width: 280px;
  height: 280px;
  background: linear-gradient(135deg, rgba(6, 182, 212, 0.6) 0%, rgba(59, 130, 246, 0.6) 100%);
  border-radius: 70% 30% 50% 50% / 60% 40% 60% 40%;
  animation: morph 13s ease-in-out infinite;
}

@keyframes morph {
  0% {
    border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%;
    transform: translate(0, 0) scale(1);
  }
  33% {
    border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
    transform: translate(10px, -10px) scale(1.05);
  }
  66% {
    border-radius: 50% 50% 40% 60% / 50% 60% 40% 50%;
    transform: translate(-5px, 5px) scale(0.95);
  }
  100% {
    border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%;
    transform: translate(0, 0) scale(1);
  }
}

@keyframes morph-center {
  0% {
    border-radius: 50% 40% 60% 30% / 50% 40% 60% 50%;
    transform: translate(-50%, -50%) translate(0, 0) scale(1);
  }
  33% {
    border-radius: 40% 60% 50% 50% / 60% 50% 40% 50%;
    transform: translate(-50%, -50%) translate(15px, -15px) scale(1.05);
  }
  66% {
    border-radius: 60% 50% 40% 60% / 50% 60% 50% 40%;
    transform: translate(-50%, -50%) translate(-10px, 10px) scale(0.95);
  }
  100% {
    border-radius: 50% 40% 60% 30% / 50% 40% 60% 50%;
    transform: translate(-50%, -50%) translate(0, 0) scale(1);
  }
}

/* Pattern overlay */
.pattern-overlay {
  position: absolute;
  inset: 0;
  opacity: 0.03;
  background-image: url('data:image/svg+xml,<svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g fill="%23000000" fill-opacity="1"><path d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/></g></g></svg>');
  pointer-events: none;
}

.login-container {
  width: 100%;
  max-width: 450px;
  position: relative;
  z-index: 10;
}

/* Responsive: más grande en desktop */
@media (min-width: 768px) {
  .login-container {
    max-width: 70%;
  }
}

@media (min-width: 1024px) {
  .login-container {
    max-width: 80%;
  }
}

@media (min-width: 1280px) {
  .login-container {
    max-width: 70%;
  }
}

/* Dark mode */
.dark .login-layout {
  background: #0B0C15;
}

.dark .shape-blob {
  opacity: 0.5;
}

.dark .pattern-overlay {
  opacity: 0.05;
}
</style>
