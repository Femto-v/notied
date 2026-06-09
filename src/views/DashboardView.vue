<script setup>
import { ref, onMounted } from 'vue'
import { boardsApi, activityApi } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import { useForm, required, maxLen } from '@/composables/useForm'
import AppNav from '@/components/AppNav.vue'
import BoardCard from '@/components/BoardCard.vue'
import AppModal from '@/components/AppModal.vue'
import AppSpinner from '@/components/AppSpinner.vue'

const toast = useToastStore()

const boards = ref([])
const loading = ref(true)
const error = ref(false)

const accentCycle = ['green', 'yellow', 'blue', 'pink', 'purple']

const activity = ref([])

async function loadBoards() {
  loading.value = true
  error.value = false
  try {
    boards.value = await boardsApi.list()
  } catch {
    error.value = true
  } finally {
    loading.value = false
  }
}

async function loadActivity() {
  try {
    activity.value = await activityApi.list()
  } catch {
    activity.value = []
  }
}

onMounted(() => {
  loadBoards()
  loadActivity()
})

// Map an activity record to display text + icon.
function describe(a) {
  switch (a.type) {
    case 'board_created': return { verb: 'created board', target: a.board_title, icon: 'note' }
    case 'board_updated': return { verb: 'updated board', target: a.board_title, icon: 'edit' }
    case 'board_deleted': return { verb: 'deleted board', target: a.board_title, icon: 'trash' }
    case 'note_created': return { verb: 'added a note to', target: a.board_title, icon: 'note' }
    case 'note_updated': return { verb: 'edited a note in', target: a.board_title, icon: 'edit' }
    case 'note_deleted': return { verb: 'removed a note from', target: a.board_title, icon: 'trash' }
    default: return { verb: 'updated', target: a.board_title, icon: 'edit' }
  }
}

function timeAgo(ts) {
  const s = Math.floor((Date.now() - ts) / 1000)
  if (s < 60) return 'just now'
  const m = Math.floor(s / 60)
  if (m < 60) return `${m} min ago`
  const h = Math.floor(m / 60)
  if (h < 24) return `${h}h ago`
  const d = Math.floor(h / 24)
  return d === 1 ? 'yesterday' : `${d}d ago`
}

// --- Create board modal ---
const showCreate = ref(false)
const creating = ref(false)
const createForm = useForm(
  { title: '', description: '' },
  { title: [required('Give your board a name.'), maxLen(120)] }
)

function openCreate() {
  createForm.reset()
  showCreate.value = true
}

async function submitCreate() {
  if (!createForm.validate()) return
  creating.value = true
  try {
    const board = await boardsApi.create({ ...createForm.fields })
    boards.value.unshift(board)
    showCreate.value = false
    toast.success(`“${board.title}” created.`)
    loadActivity()
  } catch {
    toast.error('Could not create the board.')
  } finally {
    creating.value = false
  }
}

// --- Delete confirm ---
const toDelete = ref(null)
const deleting = ref(false)
async function confirmDelete() {
  if (!toDelete.value) return
  deleting.value = true
  try {
    await boardsApi.remove(toDelete.value.id)
    boards.value = boards.value.filter((b) => b.id !== toDelete.value.id)
    toast.success('Board deleted.')
    toDelete.value = null
    loadActivity()
  } catch {
    toast.error('Could not delete the board.')
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-paper">
    <AppNav />

    <main class="mx-auto max-w-7xl px-4 sm:px-6 py-8">
      <div class="flex items-end justify-between gap-4 mb-6">
        <div>
          <h1 class="font-display text-2xl sm:text-3xl font-extrabold">My boards</h1>
          <p class="text-sm text-graphite mt-1">Every board is a fresh wall of sticky notes.</p>
        </div>
        <button class="btn-primary" @click="openCreate">
          <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none"><path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          New board
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="i in 3" :key="i" class="card p-5 animate-pulse">
          <div class="h-1.5 -mx-5 -mt-5 mb-4 bg-cork rounded-t-xl" />
          <div class="h-5 w-2/3 bg-cork rounded mb-3" />
          <div class="h-3 w-full bg-cork/60 rounded mb-2" />
          <div class="h-3 w-1/3 bg-cork/60 rounded" />
        </div>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="card p-8 text-center">
        <p class="text-graphite mb-4">We couldn't load your boards.</p>
        <button class="btn-outline" @click="loadBoards">Try again</button>
      </div>

      <!-- Empty -->
      <div v-else-if="!boards.length" class="card p-12 text-center cork-grain">
        <div class="mx-auto w-14 h-14 rounded-note bg-sticky-yellow shadow-note grid place-items-center rotate-[-6deg] mb-4">
          <svg class="w-6 h-6 text-ink/50" viewBox="0 0 20 20" fill="none"><path d="M10 5v10M5 10h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </div>
        <h3 class="font-display text-lg font-semibold">No boards yet</h3>
        <p class="text-sm text-graphite mt-1 mb-5">Create your first board to start pinning notes.</p>
        <button class="btn-primary" @click="openCreate">Create a board</button>
      </div>

      <!-- Gallery -->
      <div v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <BoardCard
          v-for="(b, i) in boards" :key="b.id"
          :board="b" :accent="accentCycle[i % accentCycle.length]"
          @delete="toDelete = $event"
        />
        <button
          @click="openCreate"
          class="card border-dashed grid place-items-center min-h-[140px] text-graphite hover:border-pine-300 hover:text-pine-600 transition-colors"
        >
          <span class="flex items-center gap-2 text-sm font-medium">
            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none"><path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            New board
          </span>
        </button>
      </div>

      <!-- Recent activity -->
      <section v-if="activity.length" class="mt-10">
        <h2 class="font-display text-lg font-semibold mb-3">Recent activity</h2>
        <div class="card divide-y divide-hairline">
          <div v-for="a in activity" :key="a.id" class="flex items-center gap-3 px-5 py-3">
            <span class="grid place-items-center w-7 h-7 rounded-full bg-pine-50 text-pine-600 shrink-0">
              <svg v-if="describe(a).icon === 'note'" class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none"><rect x="2.5" y="2.5" width="11" height="11" rx="1.5" stroke="currentColor" stroke-width="1.3"/></svg>
              <svg v-else-if="describe(a).icon === 'edit'" class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none"><path d="M11 3l2 2-7 7-2.5.5.5-2.5 7-7Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/></svg>
              <svg v-else class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none"><path d="M3 4.5h10M6.5 4.5V3h3v1.5M5 4.5l.5 8h5l.5-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <p class="text-sm text-ink flex-1">
              <span class="font-semibold">{{ a.user_name }}</span> {{ describe(a).verb }}
              <span class="italic text-graphite">{{ describe(a).target }}</span>
            </p>
            <span class="font-mono text-[11px] text-graphite shrink-0">{{ timeAgo(a.created_at) }}</span>
          </div>
        </div>
      </section>
    </main>

    <!-- Create modal -->
    <AppModal :open="showCreate" title="New board" @close="showCreate = false">
      <form @submit.prevent="submitCreate" novalidate class="space-y-4">
        <div>
          <label for="b-title" class="label">Board name</label>
          <input
            id="b-title" v-model="createForm.fields.title" type="text"
            class="field" :class="{ 'field-error': createForm.errors.title }"
            placeholder="e.g. Study Notes" @blur="createForm.touch('title')" autofocus
          />
          <p v-if="createForm.errors.title" class="mt-1 text-[12px] text-red-600">{{ createForm.errors.title }}</p>
        </div>
        <div>
          <label for="b-desc" class="label">Description <span class="normal-case text-graphite/60">(optional)</span></label>
          <textarea
            id="b-desc" v-model="createForm.fields.description" rows="2"
            class="field resize-none" placeholder="What's this board for?"
          />
        </div>
        <div class="flex justify-end gap-2 pt-1">
          <button type="button" class="btn-ghost" @click="showCreate = false">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="creating">
            <AppSpinner v-if="creating" :size="16" />
            {{ creating ? 'Creating…' : 'Create board' }}
          </button>
        </div>
      </form>
    </AppModal>

    <!-- Delete confirm -->
    <AppModal :open="!!toDelete" title="Delete board?" @close="toDelete = null">
      <p class="text-sm text-graphite">
        “<span class="font-semibold text-ink">{{ toDelete?.title }}</span>” and all its notes will be
        permanently removed. This can't be undone.
      </p>
      <div class="flex justify-end gap-2 mt-5">
        <button class="btn-ghost" @click="toDelete = null">Keep it</button>
        <button class="btn-danger" :disabled="deleting" @click="confirmDelete">
          <AppSpinner v-if="deleting" :size="16" />
          {{ deleting ? 'Deleting…' : 'Delete board' }}
        </button>
      </div>
    </AppModal>
  </div>
</template>
