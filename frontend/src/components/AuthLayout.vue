<script setup>
import BrandMark from '@/components/BrandMark.vue'

// Decorative sticky notes for the brand panel — purely visual.
const decoNotes = [
  { text: 'Ship M1 by Week 4', color: '#FFE89C', rot: -5, top: '12%', left: '8%' },
  { text: 'JWT auth ✓', color: '#C9EDC1', rot: 4, top: '34%', left: '52%' },
  { text: 'drag. drop. done.', color: '#BFE0FB', rot: -3, top: '58%', left: '14%' },
  { text: 'ide nya disini', color: '#FFC7D3', rot: 6, top: '72%', left: '54%' },
]
</script>

<template>
  <div class="min-h-screen grid lg:grid-cols-2">
    <!-- Brand panel -->
    <div class="relative hidden lg:flex flex-col justify-between overflow-hidden cork-grain p-12">
      <BrandMark size="md" />

      <!-- pinned decorative notes -->
      <div class="absolute inset-0 pointer-events-none">
        <div
          v-for="(n, i) in decoNotes"
          :key="i"
          class="absolute w-40 px-4 py-5 rounded-note shadow-note font-mono text-[13px] text-ink/80 leading-snug"
          :style="{
            background: n.color,
            transform: `rotate(${n.rot}deg)`,
            top: n.top, left: n.left,
            animationDelay: `${i * 90}ms`,
          }"
          :class="'animate-pop-in'"
        >
          <span class="absolute -top-1.5 left-1/2 -translate-x-1/2 w-2.5 h-2.5 rounded-full bg-ink/30" />
          {{ n.text }}
        </div>
      </div>

      <div class="relative max-w-sm">
        <h1 class="font-display text-3xl font-extrabold leading-tight">
          Your ideas, unstuck.
        </h1>
        <p class="mt-3 text-graphite text-sm leading-relaxed">
          A browser-based sticky-note board. Drag, colour, tag, and search every
          thought — no installs, nothing lost.
        </p>
      </div>
    </div>

    <!-- Form panel -->
    <div class="flex items-center justify-center p-6 sm:p-10 bg-paper">
      <div class="w-full max-w-sm animate-fade-up">
        <div class="lg:hidden mb-8"><BrandMark size="md" /></div>
        <slot />
      </div>
    </div>
  </div>
</template>
