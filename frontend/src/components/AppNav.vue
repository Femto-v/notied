<script setup>
import { ref, computed, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { searchApi } from '@/services/api'
import BrandMark from './BrandMark.vue'
import AppSpinner from './AppSpinner.vue'

const props = defineProps({
  showSearch: { type: Boolean, default: true },
})

const router = useRouter()
const auth = useAuthStore()

const initials = computed(() => {
  const n = auth.user?.name || auth.user?.email || '?'
  return n.trim().split(/\s+/).slice(0, 2).map((p) => p[0]?.toUpperCase()).join('')
})

// --- Search (debounced async) ---
const term = ref('')
const results = ref([])
const searching = ref(false)
const showResults = ref(false)
let debounce

function onSearchInput() {
  clearTimeout(debounce)
  if (!term.value.trim()) {
    results.value = []
    showResults.value = false
    return
  }
  searching.value = true
  showResults.value = true
  debounce = setTimeout(async () => {
    try {
      results.value = await searchApi.query(term.value.trim())
    } finally {
      searching.value = false
    }
  }, 300)
}

function goToResult(r) {
  showResults.value = false
  term.value = ''
  router.push({ name: 'board', params: { id: r.board_id } })
}

function preview(content) {
  return content.replace(/[#*`\->[\]]/g, '').replace(/\s+/g, ' ').trim().slice(0, 64)
}

// --- Avatar menu ---
const menuOpen = ref(false)
function closeMenu(e) {
  if (!e.target.closest('[data-avatar-menu]')) menuOpen.value = false
}
window.addEventListener('click', closeMenu)
onBeforeUnmount(() => window.removeEventListener('click', closeMenu))

function logout() {
  auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <header class="sticky top-0 z-30 bg-pine-800 text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 h-14 flex items-center gap-4">
      <RouterLink :to="{ name: 'dashboard' }" class="shrink-0">
        <BrandMark size="md" mono />
      </RouterLink>

      <!-- Search -->
      <div v-if="showSearch" class="relative flex-1 max-w-md ml-auto">
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/50" viewBox="0 0 20 20" fill="none">
            <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.8" />
            <path d="M14 14l3 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
          </svg>
          <input
            v-model="term"
            @input="onSearchInput"
            @focus="term && (showResults = true)"
            type="search"
            placeholder="Search notes…"
            class="w-full rounded-lg bg-pine-900/60 border border-white/10 pl-9 pr-3 py-2 text-sm
                   text-white placeholder:text-white/40 focus:bg-pine-900 focus:border-pine-400
                   focus:ring-0 transition-colors"
            aria-label="Search notes"
          />
        </div>

        <!-- Results dropdown -->
        <div
          v-if="showResults"
          class="absolute mt-2 w-full rounded-xl border border-hairline bg-white text-ink shadow-lift overflow-hidden animate-fade-up"
        >
          <div v-if="searching" class="px-4 py-6 flex justify-center">
            <AppSpinner>Searching…</AppSpinner>
          </div>
          <ul v-else-if="results.length" class="max-h-80 overflow-auto py-1">
            <li v-for="r in results" :key="r.id">
              <button
                @click="goToResult(r)"
                class="w-full text-left px-4 py-2.5 hover:bg-cork transition-colors"
              >
                <p class="text-sm font-medium truncate">{{ preview(r.content) || 'Empty note' }}</p>
                <p class="font-mono text-[11px] text-graphite mt-0.5">in {{ r.board_title }}</p>
              </button>
            </li>
          </ul>
          <div v-else class="px-4 py-6 text-center text-sm text-graphite">
            No notes match “{{ term }}”.
          </div>
        </div>
      </div>

      <!-- Avatar menu -->
      <div class="relative shrink-0" data-avatar-menu :class="{ 'ml-auto': !showSearch }">
        <button
          @click="menuOpen = !menuOpen"
          class="flex items-center gap-2 rounded-full hover:bg-white/10 pl-1 pr-2 py-1 transition-colors"
          aria-haspopup="true" :aria-expanded="menuOpen"
        >
          <span class="grid place-items-center w-8 h-8 rounded-full bg-pine-400 text-pine-900 font-semibold text-xs">
            {{ initials }}
          </span>
          <svg class="w-4 h-4 text-white/60" viewBox="0 0 20 20" fill="currentColor"><path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>

        <Transition name="menu">
          <div
            v-if="menuOpen"
            class="absolute right-0 mt-2 w-52 rounded-xl border border-hairline bg-white text-ink shadow-lift overflow-hidden"
          >
            <div class="px-4 py-3 border-b border-hairline">
              <p class="text-sm font-medium truncate">{{ auth.user?.name }}</p>
              <p class="font-mono text-[11px] text-graphite truncate">{{ auth.user?.email }}</p>
            </div>
            <RouterLink :to="{ name: 'profile' }" @click="menuOpen = false" class="flex items-center gap-2.5 px-4 py-2.5 text-sm hover:bg-cork transition-colors">
              <svg class="w-4 h-4 text-graphite" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="6.5" r="3" stroke="currentColor" stroke-width="1.6"/><path d="M4 16c0-3 2.7-4.5 6-4.5S16 13 16 16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
              Profile
            </RouterLink>
            <button @click="logout" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
              <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none"><path d="M8 4H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3M13 7l3 3-3 3M16 10H8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
              Log out
            </button>
          </div>
        </Transition>
      </div>
    </div>
  </header>
</template>

<style scoped>
.menu-enter-active, .menu-leave-active { transition: all .16s ease; }
.menu-enter-from, .menu-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
