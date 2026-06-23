<script setup>
defineProps({
  size: { type: String, default: 'md' }, // sm | md | lg
  mono: { type: Boolean, default: false }, // single-color (for dark nav)
})
const sizes = {
  sm: 'text-lg',
  md: 'text-2xl',
  lg: 'text-4xl',
}
</script>

<template>
  <span
    class="inline-flex items-center gap-1.5 font-display font-extrabold tracking-tight select-none"
    :class="sizes[size]"
  >
    <!-- the pin: signature mark -->
    <svg
      :class="size === 'sm' ? 'w-4 h-4' : size === 'lg' ? 'w-8 h-8' : 'w-6 h-6'"
      viewBox="0 0 24 24" fill="none" aria-hidden="true"
    >
      <path
        d="M12 2.5c-3.6 0-6 2.4-6 5.6 0 4 4.2 8.3 5.5 9.6.3.3.7.3 1 0C13.8 16.4 18 12.1 18 8.1 18 4.9 15.6 2.5 12 2.5Z"
        :fill="mono ? 'currentColor' : '#1E7D5A'"
      />
      <circle cx="12" cy="8" r="2.4" :fill="mono ? '#0C3A2C' : '#FBFAF6'" />
    </svg>
    <span :class="mono ? 'text-current' : 'text-ink'">
      No<span :class="mono ? 'text-pine-200' : 'text-pine-500'">tied</span>
    </span>
  </span>
</template>
