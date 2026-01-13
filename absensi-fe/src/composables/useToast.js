// src/composables/useToast.js
import { ref } from 'vue'

// Shared state untuk semua component
const toasts = ref([])
let toastId = 0

export function useToast() {
  const toast = ({ title, description, variant = 'default', duration = 4000 }) => {
    const id = toastId++
    
    toasts.value.push({
      id,
      title,
      description,
      variant,
      duration
    })

    // Auto remove after duration
    if (duration > 0) {
      setTimeout(() => {
        dismiss(id)
      }, duration)
    }

    return id
  }

  const dismiss = (id) => {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }

  return {
    toast,
    toasts,
    dismiss
  }
}