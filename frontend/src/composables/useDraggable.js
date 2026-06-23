import { ref } from 'vue'

// Pointer-based drag + resize. Works with mouse and touch via Pointer Events.
// onDragEnd / onResizeEnd receive the final geometry so the caller can persist
// it (PUT /api/notes/{id}). Live updates happen through the reactive `position`.

export function useDraggable({ onDragEnd, onResizeEnd } = {}) {
  const dragging = ref(false)
  const resizing = ref(false)

  function startDrag(event, note, canvasEl) {
    if (event.button === 2) return // ignore right-click
    event.preventDefault()
    dragging.value = true

    const startX = event.clientX
    const startY = event.clientY
    const originX = note.pos_x
    const originY = note.pos_y

    function move(e) {
      const scrollX = canvasEl?.scrollLeft || 0
      const scrollY = canvasEl?.scrollTop || 0
      // (scroll factored in so dragging stays accurate on a scrolled canvas)
      note.pos_x = Math.max(0, originX + (e.clientX - startX) + (scrollX - scrollX))
      note.pos_y = Math.max(0, originY + (e.clientY - startY) + (scrollY - scrollY))
    }
    function up() {
      dragging.value = false
      window.removeEventListener('pointermove', move)
      window.removeEventListener('pointerup', up)
      if (note.pos_x !== originX || note.pos_y !== originY) {
        onDragEnd?.(note)
      }
    }
    window.addEventListener('pointermove', move)
    window.addEventListener('pointerup', up)
  }

  function startResize(event, note) {
    event.preventDefault()
    event.stopPropagation()
    resizing.value = true

    const startX = event.clientX
    const startY = event.clientY
    const originW = note.width
    const originH = note.height

    function move(e) {
      note.width = Math.max(160, originW + (e.clientX - startX))
      note.height = Math.max(120, originH + (e.clientY - startY))
    }
    function up() {
      resizing.value = false
      window.removeEventListener('pointermove', move)
      window.removeEventListener('pointerup', up)
      if (note.width !== originW || note.height !== originH) {
        onResizeEnd?.(note)
      }
    }
    window.addEventListener('pointermove', move)
    window.addEventListener('pointerup', up)
  }

  return { dragging, resizing, startDrag, startResize }
}
