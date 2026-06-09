<script setup>
defineProps({
  size: { type: Number, default: 20 },
  label: { type: String, default: 'Loading' },
})
</script>

<template>
  <span class="inline-flex items-center gap-2 text-graphite" role="status">
    <svg
      :width="size" :height="size" viewBox="0 0 24 24"
      class="animate-spin" aria-hidden="true"
    >
      <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-opacity=".2" stroke-width="3" />
      <path d="M21 12a9 9 0 0 0-9-9" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
    </svg>
    <span v-if="$slots.default" class="text-sm"><slot /></span>
    <span class="sr-only">{{ label }}</span>
  </span>
</template>
