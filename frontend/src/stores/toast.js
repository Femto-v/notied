import { defineStore } from 'pinia'
import { ref } from 'vue'

let _id = 0

export const useToastStore = defineStore('toast', () => {
  const toasts = ref([])

  function push(message, type = 'info', timeout = 3200) {
    const id = ++_id
    toasts.value.push({ id, message, type })
    if (timeout) setTimeout(() => dismiss(id), timeout)
    return id
  }
  function dismiss(id) {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }
  const success = (m) => push(m, 'success')
  const error = (m) => push(m, 'error', 4500)
  const info = (m) => push(m, 'info')

  return { toasts, push, dismiss, success, error, info }
})
