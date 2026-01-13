<template>
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-50 space-y-2 max-w-md">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="getToastClass(toast.variant)"
          class="rounded-lg shadow-lg p-4 flex items-start gap-3 backdrop-blur-sm border"
        >
          <!-- Icon -->
          <div class="flex-shrink-0 pt-0.5">
            <CheckCircle2 v-if="toast.variant === 'default'" class="h-5 w-5 text-green-600" />
            <XCircle v-else-if="toast.variant === 'destructive'" class="h-5 w-5 text-red-600" />
            <AlertCircle v-else-if="toast.variant === 'warning'" class="h-5 w-5 text-yellow-600" />
            <Info v-else class="h-5 w-5 text-blue-600" />
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <h3 v-if="toast.title" class="font-semibold text-sm mb-1">
              {{ toast.title }}
            </h3>
            <p v-if="toast.description" class="text-sm opacity-90">
              {{ toast.description }}
            </p>
          </div>

          <!-- Close button -->
          <button
            @click="dismiss(toast.id)"
            class="flex-shrink-0 rounded-sm opacity-70 hover:opacity-100 transition-opacity"
          >
            <X class="h-4 w-4" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { CheckCircle2, XCircle, AlertCircle, Info, X } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

const { toasts, dismiss } = useToast()

function getToastClass(variant) {
  const classes = {
    default: 'bg-white border-green-200 text-gray-900',
    destructive: 'bg-white border-red-200 text-gray-900',
    warning: 'bg-white border-yellow-200 text-gray-900',
    info: 'bg-white border-blue-200 text-gray-900'
  }
  return classes[variant] || classes.default
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.toast-move {
  transition: transform 0.3s ease;
}
</style>