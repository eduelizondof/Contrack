<script setup>
import { ref } from 'vue'

const emit = defineEmits(['seleccionar', 'cerrar'])

// Categorías de emojis
const categorias = ref([
  {
    nombre: 'Caritas',
    emojis: ['😀', '😃', '😄', '😁', '😅', '😂', '🤣', '😊', '😇', '🙂', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😜', '🤪', '😝', '🤑', '🤗', '🤭', '🤫', '🤔', '🤐', '🤨', '😐', '😑', '😶', '😏', '😒', '🙄', '😬', '🤥', '😌', '😔', '😪', '🤤', '😴', '😷', '🤒', '🤕', '🤢', '🤮', '🤧', '🥵', '🥶', '🥴', '😵', '🤯', '🤠', '🥳', '😎', '🤓', '🧐']
  },
  {
    nombre: 'Gestos',
    emojis: ['👍', '👎', '👊', '✊', '🤛', '🤜', '🤞', '✌️', '🤟', '🤘', '👌', '🤏', '👈', '👉', '👆', '👇', '☝️', '✋', '🤚', '🖐️', '🖖', '👋', '🤙', '💪', '🙏', '👏', '🤝', '🙌', '👐', '💅', '🤳', '💋', '👄', '👅', '👂', '👃', '👀', '👁️', '🧠', '💀', '👻', '💩', '🤡', '👹', '👺', '👽', '🤖', '🎃', '😺', '😸', '😹', '😻', '😼', '😽', '🙀', '😿', '😾']
  },
  {
    nombre: 'Corazones',
    emojis: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '♥️', '💌', '💐', '🌹', '🥀', '🌸', '🌷', '🌺', '🌻', '🌼', '💮']
  },
  {
    nombre: 'Objetos',
    emojis: ['📱', '💻', '🖥️', '🖨️', '⌨️', '🖱️', '🖲️', '💽', '💾', '💿', '📀', '📼', '📷', '📸', '📹', '🎥', '📽️', '🎞️', '📞', '☎️', '📟', '📠', '📺', '📻', '🎙️', '🎚️', '🎛️', '⏱️', '⏲️', '⏰', '🕰️', '📡', '🔋', '🔌', '💡', '🔦', '🕯️', '🧯', '🛢️', '💸', '💵', '💴', '💶', '💷', '💰', '💳', '💎', '⚖️', '🔧', '🔨', '⚒️', '🛠️', '⛏️', '🔩', '⚙️', '🔗', '📎', '🖇️', '📐']
  },
  {
    nombre: 'Símbolos',
    emojis: ['✅', '❌', '❓', '❗', '‼️', '⁉️', '💯', '🔴', '🟠', '🟡', '🟢', '🔵', '🟣', '⚫', '⚪', '🟤', '🔶', '🔷', '🔸', '🔹', '🔺', '🔻', '💠', '🔘', '🔳', '🔲', '🏁', '🚩', '🎌', '🏴', '⭐', '🌟', '✨', '⚡', '🔥', '💥', '☀️', '🌙', '⭕', '✔️', '☑️', '✖️', '➕', '➖', '➗', '➡️', '⬅️', '⬆️', '⬇️', '↗️', '↘️', '↙️', '↖️', '↕️', '↔️', '🔄', '🔃', '🔙', '🔚']
  }
])

const categoriaActiva = ref(0)

const seleccionarEmoji = (emoji) => {
  emit('seleccionar', emoji)
}
</script>

<template>
  <div class="emoji-picker">
    <!-- Tabs de categorías -->
    <div class="picker-tabs">
      <button
        v-for="(cat, index) in categorias"
        :key="cat.nombre"
        class="tab-btn"
        :class="{ activa: categoriaActiva === index }"
        @click="categoriaActiva = index"
      >
        {{ cat.emojis[0] }}
      </button>
    </div>

    <!-- Grid de emojis -->
    <div class="picker-content">
      <div class="categoria-nombre">{{ categorias[categoriaActiva].nombre }}</div>
      <div class="emoji-grid">
        <button
          v-for="emoji in categorias[categoriaActiva].emojis"
          :key="emoji"
          class="emoji-btn"
          @click="seleccionarEmoji(emoji)"
        >
          {{ emoji }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.emoji-picker {
  position: absolute;
  bottom: 100%;
  right: 0;
  margin-bottom: 8px;
  width: 320px;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.2);
  z-index: 50;
  overflow: hidden;
}

/* Tabs */
.picker-tabs {
  display: flex;
  border-bottom: 1px solid var(--color-border);
  padding: 8px;
  gap: 4px;
}

.tab-btn {
  flex: 1;
  padding: 8px;
  border: none;
  background: transparent;
  border-radius: 8px;
  font-size: 1.25rem;
  cursor: pointer;
  transition: background 0.2s ease;
}

.tab-btn:hover {
  background: var(--color-muted);
}

.tab-btn.activa {
  background: var(--color-accent);
}

/* Content */
.picker-content {
  padding: 12px;
  max-height: 250px;
  overflow-y: auto;
}

.categoria-nombre {
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--color-muted-foreground);
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.emoji-grid {
  display: grid;
  grid-template-columns: repeat(8, 1fr);
  gap: 4px;
}

.emoji-btn {
  width: 100%;
  aspect-ratio: 1;
  border: none;
  background: transparent;
  border-radius: 8px;
  font-size: 1.5rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

.emoji-btn:hover {
  background: var(--color-muted);
  transform: scale(1.15);
}

/* Scrollbar */
.picker-content::-webkit-scrollbar {
  width: 6px;
}

.picker-content::-webkit-scrollbar-track {
  background: transparent;
}

.picker-content::-webkit-scrollbar-thumb {
  background: var(--color-border);
  border-radius: 3px;
}

/* Mobile */
@media (max-width: 400px) {
  .emoji-picker {
    width: calc(100vw - 32px);
    left: 0;
    right: 0;
    margin-left: auto;
    margin-right: auto;
  }

  .emoji-grid {
    grid-template-columns: repeat(7, 1fr);
  }
}
</style>
