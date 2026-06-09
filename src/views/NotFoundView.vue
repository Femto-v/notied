<script setup>
import { RouterLink } from 'vue-router'
import BrandMark from '@/components/BrandMark.vue'
</script>

<template>
  <div class="min-h-screen grid place-items-center cork-grain p-6">
    <div class="text-center">
      <div class="mx-auto w-44 px-5 py-7 rounded-note bg-sticky-yellow shadow-noteLift rotate-[-4deg] mb-8 relative">
        <span class="absolute -top-2 left-1/2 -translate-x-1/2 w-3 h-3 rounded-full bg-ink/25" />
        <p class="font-display text-5xl font-extrabold text-ink/80">404</p>
        <p class="font-mono text-[11px] text-ink/50 mt-1">page not found</p>
      </div>
      <h1 class="font-display text-2xl font-extrabold">This note fell off the board</h1>
      <p class="text-graphite text-sm mt-2 mb-6">The page you're after doesn't exist or has been moved.</p>
      <RouterLink :to="{ name: 'dashboard' }" class="btn-primary">Back to your boards</RouterLink>
      <div class="mt-10 opacity-50"><BrandMark size="sm" /></div>
    </div>
  </div>
</template>
