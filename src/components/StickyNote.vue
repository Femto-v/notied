<script setup>
import { ref, computed, nextTick, watch } from 'vue'
import { marked } from 'marked'
import { colorHex, NOTE_COLORS } from '@/composables/useNoteColors'

const props = defineProps({
  note: { type: Object, required: true },
  dragging: { type: Boolean, default: false },
})
const emit = defineEmits([
  'start-drag', 'start-resize', 'change-color', 'save-content', 'delete', 'focus',
])

const editing = ref(false)
const draft = ref(props.note.content)
const textarea = ref(null)
const showColors = ref(false)

// Render markdown + checklist support. marked handles task-list syntax.
marked.setOptions({ breaks: true, gfm: true })
const rendered = computed(() => marked.parse(props.note.content || '*Empty note*'))

// A stable, content-derived tilt so each note feels hand-pinned (±2.5°).
const tilt = computed(() => {
  const seed = (props.note.id * 928371) % 100
  return ((seed / 100) * 5 - 2.5).toFixed(2)
})

async function startEdit() {
  draft.value = props.note.content
  editing.value = true
  await nextTick()
  textarea.value?.focus()
}
function saveEdit() {
  editing.value = false
  if (draft.value !== props.note.content) {
    emit('save-content', { ...props.note, content: draft.value })
  }
}
function cancelEdit() {
  editing.value = false
  draft.value = props.note.content
}

watch(() => props.note.content, (v) => { if (!editing.value) draft.value = v })

function pickColor(key) {
  showColors.value = false
  emit('change-color', { ...props.note, color: key })
}
</script>

<template>
  <div
    class="group absolute rounded-note shadow-note select-none transition-shadow"
    :class="[dragging ? 'shadow-noteLift z-20 cursor-grabbing' : 'z-10', editing ? 'z-30' : '']"
    :style="{
      left: note.pos_x + 'px',
      top: note.pos_y + 'px',
      width: note.width + 'px',
      height: note.height + 'px',
      background: colorHex(note.color),
      transform: dragging || editing ? 'rotate(0deg) scale(1.01)' : `rotate(${tilt}deg)`,
    }"
    @pointerdown="emit('focus', note)"
  >
    <!-- pin -->
    <span class="absolute -top-2 left-1/2 -translate-x-1/2 w-3 h-3 rounded-full bg-ink/25 shadow-sm" />

    <!-- drag handle (top strip) -->
    <div
      class="h-7 flex items-center justify-between px-2 cursor-grab active:cursor-grabbing"
      @pointerdown.stop="emit('start-drag', $event, note)"
    >
      <span class="font-mono text-[10px] text-ink/30 uppercase tracking-wider">note</span>
      <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity" @pointerdown.stop>
        <!-- color -->
        <div class="relative">
          <button class="p-1 rounded hover:bg-ink/5" @click="showColors = !showColors" aria-label="Change colour">
            <span class="block w-3 h-3 rounded-full ring-1 ring-ink/20" :style="{ background: colorHex(note.color) }" />
          </button>
          <div v-if="showColors" class="absolute right-0 mt-1 flex gap-1 p-1.5 rounded-lg bg-white shadow-lift border border-hairline z-40">
            <button
              v-for="c in NOTE_COLORS" :key="c.key"
              class="w-5 h-5 rounded-full ring-1 ring-ink/10 hover:scale-110 transition-transform"
              :style="{ background: c.hex }" :aria-label="c.key" @click="pickColor(c.key)"
            />
          </div>
        </div>
        <!-- edit -->
        <button class="p-1 rounded hover:bg-ink/5" @click="startEdit" aria-label="Edit note">
          <svg class="w-3.5 h-3.5 text-ink/50" viewBox="0 0 16 16" fill="none"><path d="M11 3l2 2-7 7-2.5.5.5-2.5 7-7Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/></svg>
        </button>
        <!-- delete -->
        <button class="p-1 rounded hover:bg-red-100" @click="emit('delete', note)" aria-label="Delete note">
          <svg class="w-3.5 h-3.5 text-ink/40 hover:text-red-500" viewBox="0 0 16 16" fill="none"><path d="M4 5h8M6.5 5V3.5h3V5M5.5 5l.5 7h4l.5-7" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
      </div>
    </div>

    <!-- body -->
    <div class="px-3 pb-3 h-[calc(100%-1.75rem)] overflow-auto no-scrollbar">
      <template v-if="editing">
        <textarea
          ref="textarea" v-model="draft"
          class="w-full h-full resize-none bg-transparent border-0 p-0 text-sm text-ink/90
                 focus:ring-0 font-sans leading-snug placeholder:text-ink/30"
          placeholder="Type markdown… # heading, - [ ] task"
          @keydown.esc="cancelEdit" @keydown.meta.enter="saveEdit" @keydown.ctrl.enter="saveEdit"
          @blur="saveEdit" @pointerdown.stop
        />
      </template>
      <div
        v-else
        class="note-md text-sm text-ink/90 leading-snug cursor-text"
        @dblclick="startEdit" v-html="rendered"
      />
    </div>

    <!-- resize handle -->
    <div
      class="absolute bottom-0 right-0 w-4 h-4 cursor-se-resize opacity-0 group-hover:opacity-100 transition-opacity"
      @pointerdown.stop="emit('start-resize', $event, note)"
    >
      <svg class="w-full h-full text-ink/30" viewBox="0 0 16 16" fill="none"><path d="M14 6L6 14M14 11l-3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
    </div>
  </div>
</template>

<style scoped>
.note-md :deep(h1), .note-md :deep(h2), .note-md :deep(h3) {
  font-family: 'Bricolage Grotesque', system-ui, sans-serif;
  font-weight: 700; font-size: .95rem; margin: 0 0 .35rem;
}
.note-md :deep(p) { margin: 0 0 .4rem; }
.note-md :deep(ul) { margin: 0; padding-left: 1.1rem; list-style: disc; }
.note-md :deep(ul li.task-list-item) { list-style: none; margin-left: -1.1rem; }
.note-md :deep(input[type="checkbox"]) { margin-right: .4rem; accent-color: #166349; }
.note-md :deep(code) { background: rgba(31,36,33,.08); padding: 0 .25rem; border-radius: 3px; font-size: .8rem; }
.note-md :deep(a) { color: #114D3A; text-decoration: underline; }
.note-md :deep(strong) { font-weight: 700; }
</style>
