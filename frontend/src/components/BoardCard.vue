<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

const props = defineProps({
  board: { type: Object, required: true },
  accent: { type: String, default: 'green' },
})
defineEmits(['delete'])

const accents = {
  green: 'bg-sticky-green/40',
  yellow: 'bg-sticky-yellow/40',
  blue: 'bg-sticky-blue/40',
  pink: 'bg-sticky-pink/40',
  purple: 'bg-sticky-purple/40',
}

const updated = computed(() => {
  const diff = Date.now() - props.board.updated_at
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'just now'
  if (mins < 60) return `${mins} min ago`
  const hrs = Math.floor(mins / 60)
  if (hrs < 24) return `${hrs}h ago`
  return `${Math.floor(hrs / 24)}d ago`
})
</script>

<template>
  <RouterLink
    :to="{ name: 'board', params: { id: board.id } }"
    class="group relative card overflow-hidden hover:shadow-noteLift hover:-translate-y-0.5 transition-all duration-200"
  >
    <div class="h-1.5" :class="accents[accent]" />
    <div class="p-5">
      <div class="flex items-start justify-between gap-2">
        <h3 class="font-display text-lg font-semibold leading-tight group-hover:text-pine-600 transition-colors">
          {{ board.title }}
        </h3>
        <button
          @click.prevent="$emit('delete', board)"
          class="shrink-0 -mt-1 -mr-1 p-1.5 rounded-md text-graphite/40 hover:text-red-500 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-all"
          aria-label="Delete board"
        >
          <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none"><path d="M5 6h10M8 6V4.5h4V6M6.5 6l.5 9h6l.5-9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
      </div>
      <p v-if="board.description" class="mt-1 text-sm text-graphite line-clamp-2">{{ board.description }}</p>
      <div class="mt-4 flex items-center gap-3 font-mono text-[11px] text-graphite">
        <span class="inline-flex items-center gap-1">
          <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none"><rect x="2.5" y="2.5" width="11" height="11" rx="1.5" stroke="currentColor" stroke-width="1.3"/></svg>
          {{ board.note_count }} {{ board.note_count === 1 ? 'note' : 'notes' }}
        </span>
        <span>· updated {{ updated }}</span>
      </div>
    </div>
  </RouterLink>
</template>
