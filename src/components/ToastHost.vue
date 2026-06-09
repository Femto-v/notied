<script setup>
import { useToastStore } from '@/stores/toast'
const toast = useToastStore()

const styles = {
  success: 'border-pine-200 bg-pine-50 text-pine-800',
  error: 'border-red-200 bg-red-50 text-red-700',
  info: 'border-hairline bg-white text-ink',
}
</script>

<template>
  <div class="fixed bottom-5 right-5 z-[100] flex flex-col gap-2 w-[min(92vw,360px)]">
    <TransitionGroup name="toast">
      <div
        v-for="t in toast.toasts"
        :key="t.id"
        class="flex items-start gap-3 rounded-lg border px-4 py-3 shadow-lift text-sm"
        :class="styles[t.type]"
        role="status"
      >
        <span class="mt-0.5 shrink-0">
          <svg v-if="t.type === 'success'" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4l3.1 3.1 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
          <svg v-else-if="t.type === 'error'" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM9 6a1 1 0 1 1 2 0v4a1 1 0 1 1-2 0V6Zm1 7a1.2 1.2 0 1 0 0 2.4A1.2 1.2 0 0 0 10 13Z" clip-rule="evenodd"/></svg>
          <svg v-else class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm1-11a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm-1 2a1 1 0 0 1 1 1v4a1 1 0 1 1-2 0v-4a1 1 0 0 1 1-1Z" clip-rule="evenodd"/></svg>
        </span>
        <p class="flex-1 leading-snug">{{ t.message }}</p>
        <button @click="toast.dismiss(t.id)" class="shrink-0 opacity-50 hover:opacity-100" aria-label="Dismiss">
          <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path d="M6 6l8 8M14 6l-8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all .28s cubic-bezier(.21,1.02,.73,1); }
.toast-enter-from { opacity: 0; transform: translateX(16px); }
.toast-leave-to { opacity: 0; transform: translateX(16px); }
.toast-move { transition: transform .28s; }
</style>
