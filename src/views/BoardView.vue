<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { boardsApi, notesApi } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import { useDraggable } from '@/composables/useDraggable'
import { NOTE_COLORS } from '@/composables/useNoteColors'
import AppNav from '@/components/AppNav.vue'
import StickyNote from '@/components/StickyNote.vue'
import AppSpinner from '@/components/AppSpinner.vue'
import AppModal from '@/components/AppModal.vue'

const props = defineProps({ id: { type: [String, Number], required: true } })
const router = useRouter()
const toast = useToastStore()

const board = ref(null)
const notes = ref([])
const loading = ref(true)
const error = ref(false)
const canvasEl = ref(null)
const activeId = ref(null)
const nextColor = ref('yellow')

async function load() {
  loading.value = true
  error.value = false
  try {
    const data = await boardsApi.get(props.id)
    board.value = { id: data.id, title: data.title, description: data.description }
    notes.value = data.notes || []
  } catch (err) {
    if (err.response?.status === 404) toast.error('That board no longer exists.')
    error.value = true
  } finally {
    loading.value = false
  }
}
onMounted(load)

// --- Persistence helpers (PUT on drag/resize/color/content end) ---
async function persistNote(note) {
  try {
    await notesApi.update(note.id, {
      content: note.content, pos_x: Math.round(note.pos_x), pos_y: Math.round(note.pos_y),
      width: Math.round(note.width), height: Math.round(note.height), color: note.color,
    })
  } catch {
    toast.error('Change not saved — check your connection.')
  }
}

const { dragging, startDrag, startResize } = useDraggable({
  onDragEnd: persistNote,
  onResizeEnd: persistNote,
})

function onStartDrag(event, note) {
  activeId.value = note.id
  startDrag(event, note, canvasEl.value)
}

function changeColor(updated) {
  const n = notes.value.find((x) => x.id === updated.id)
  if (n) { n.color = updated.color; persistNote(n) }
}
function saveContent(updated) {
  const n = notes.value.find((x) => x.id === updated.id)
  if (n) { n.content = updated.content; persistNote(n) }
}

// --- Add note ---
const adding = ref(false)
async function addNote() {
  adding.value = true
  // stagger new notes so they don't stack exactly
  const offset = (notes.value.length % 5) * 26
  try {
    const note = await notesApi.create(props.id, {
      content: '', pos_x: 60 + offset, pos_y: 60 + offset,
      width: 220, height: 180, color: nextColor.value,
    })
    notes.value.push(note)
    // scroll new note into view
    setTimeout(() => canvasEl.value?.scrollTo({ left: note.pos_x - 40, top: note.pos_y - 40, behavior: 'smooth' }), 50)
  } catch {
    toast.error('Could not add a note.')
  } finally {
    adding.value = false
  }
}

// --- Delete note ---
const noteToDelete = ref(null)
async function confirmDeleteNote() {
  const id = noteToDelete.value.id
  noteToDelete.value = null
  const idx = notes.value.findIndex((n) => n.id === id)
  const removed = notes.value[idx]
  notes.value.splice(idx, 1) // optimistic
  try {
    await notesApi.remove(id)
  } catch {
    notes.value.splice(idx, 0, removed) // rollback
    toast.error('Could not delete the note.')
  }
}
</script>

<template>
  <div class="h-screen flex flex-col bg-paper">
    <AppNav />

    <!-- Board sub-header -->
    <div class="border-b border-hairline bg-white">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 h-12 flex items-center gap-3">
        <RouterLink :to="{ name: 'dashboard' }" class="btn-ghost p-1.5 rounded-md -ml-1.5" aria-label="Back to boards">
          <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none"><path d="M12 5l-5 5 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </RouterLink>
        <div class="min-w-0">
          <h1 class="font-display text-base font-semibold truncate">{{ board?.title || 'Loading…' }}</h1>
        </div>
        <span v-if="!loading" class="font-mono text-[11px] text-graphite">· {{ notes.length }} {{ notes.length === 1 ? 'note' : 'notes' }}</span>
        <div class="ml-auto flex items-center gap-1">
          <RouterLink :to="{ name: 'board-settings', params: { id } }" class="btn-ghost p-2 rounded-md" aria-label="Board settings">
            <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="2.5" stroke="currentColor" stroke-width="1.5"/><path d="M10 2v2M10 16v2M18 10h-2M4 10H2M15.5 4.5l-1.4 1.4M5.9 14.1l-1.4 1.4M15.5 15.5l-1.4-1.4M5.9 5.9L4.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- Canvas -->
    <div class="relative flex-1 overflow-hidden">
      <!-- loading -->
      <div v-if="loading" class="absolute inset-0 grid place-items-center">
        <AppSpinner :size="28">Loading board…</AppSpinner>
      </div>

      <!-- error -->
      <div v-else-if="error" class="absolute inset-0 grid place-items-center">
        <div class="text-center">
          <p class="text-graphite mb-4">This board couldn't be opened.</p>
          <RouterLink :to="{ name: 'dashboard' }" class="btn-outline">Back to boards</RouterLink>
        </div>
      </div>

      <template v-else>
        <!-- scrollable note surface -->
        <div ref="canvasEl" class="absolute inset-0 overflow-auto paper-grain">
          <div class="relative" style="width: 2400px; height: 1600px;">
            <!-- empty hint -->
            <div v-if="!notes.length" class="absolute inset-0 grid place-items-center pointer-events-none">
              <div class="text-center">
                <div class="mx-auto w-12 h-12 rounded-note bg-sticky-yellow shadow-note grid place-items-center rotate-[-6deg] mb-3">
                  <svg class="w-5 h-5 text-ink/40" viewBox="0 0 20 20" fill="none"><path d="M10 5v10M5 10h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </div>
                <p class="text-sm text-graphite">This board is empty. Add your first note.</p>
              </div>
            </div>

            <StickyNote
              v-for="note in notes" :key="note.id"
              :note="note"
              :dragging="dragging && activeId === note.id"
              @start-drag="onStartDrag"
              @start-resize="startResize"
              @change-color="changeColor"
              @save-content="saveContent"
              @delete="noteToDelete = $event"
              @focus="activeId = note.id"
            />
          </div>
        </div>

        <!-- Floating toolbar -->
        <div class="absolute left-1/2 -translate-x-1/2 bottom-5 flex items-center gap-2 rounded-full bg-white shadow-noteLift border border-hairline px-2 py-1.5">
          <button class="btn-primary rounded-full !px-4 !py-2" :disabled="adding" @click="addNote">
            <AppSpinner v-if="adding" :size="16" />
            <svg v-else class="w-4 h-4" viewBox="0 0 20 20" fill="none"><path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            Add note
          </button>
          <span class="w-px h-6 bg-hairline mx-0.5" />
          <div class="flex items-center gap-1 pr-1">
            <span class="font-mono text-[10px] text-graphite uppercase tracking-wider mr-0.5">color</span>
            <button
              v-for="c in NOTE_COLORS" :key="c.key"
              class="w-6 h-6 rounded-full ring-1 ring-ink/10 transition-transform hover:scale-110"
              :class="nextColor === c.key ? 'ring-2 ring-pine-500 scale-110' : ''"
              :style="{ background: c.hex }" :aria-label="`New notes in ${c.key}`"
              @click="nextColor = c.key"
            />
          </div>
        </div>
      </template>
    </div>

    <!-- delete note confirm -->
    <AppModal :open="!!noteToDelete" title="Delete note?" @close="noteToDelete = null">
      <p class="text-sm text-graphite">This note will be removed from the board.</p>
      <div class="flex justify-end gap-2 mt-5">
        <button class="btn-ghost" @click="noteToDelete = null">Cancel</button>
        <button class="btn-danger" @click="confirmDeleteNote">Delete note</button>
      </div>
    </AppModal>
  </div>
</template>
