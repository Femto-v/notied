<script setup>
import { watch, onBeforeUnmount } from 'vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
})
const emit = defineEmits(['close'])

function onKey(e) {
  if (e.key === 'Escape') emit('close')
}
watch(
  () => props.open,
  (open) => {
    if (open) window.addEventListener('keydown', onKey)
    else window.removeEventListener('keydown', onKey)
  }
)
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))
</script>

<template>
  <Transition name="modal">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
    >
      <div class="absolute inset-0 bg-ink/30 backdrop-blur-sm" @click="emit('close')" />
      <div class="relative w-full max-w-md card animate-pop-in">
        <header v-if="title" class="flex items-center justify-between px-5 py-4 border-b border-hairline">
          <h2 class="font-display text-lg font-semibold">{{ title }}</h2>
          <button @click="emit('close')" class="btn-ghost p-1.5 -mr-1.5 rounded-md" aria-label="Close">
            <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none"><path d="M6 6l8 8M14 6l-8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          </button>
        </header>
        <div class="p-5">
          <slot />
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity .2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
