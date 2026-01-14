<!-- src/components/NotificationPanel.vue -->
<template>
  <div class="relative" v-click-outside="closePanel">
    <button 
      @click="togglePanel"
      class="relative rounded-md p-2 hover:bg-accent transition-colors"
    >
      <Bell class="w-4 h-4" />
      <span v-if="hasUnread" class="absolute top-1.5 right-1.5 flex h-2 w-2">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-destructive opacity-75"></span>
        <span class="relative inline-flex rounded-full h-2 w-2 bg-destructive"></span>
      </span>
    </button>

    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div 
        v-if="isOpen"
        class="absolute right-0 top-full mt-2 w-96 rounded-lg border border-border bg-popover shadow-lg z-50 max-h-[600px] flex flex-col"
      >
        <div class="flex items-center justify-between p-4 border-b border-border shrink-0">
          <div class="flex items-center gap-2">
            <h3 class="font-semibold text-sm">Notifikasi</h3>
            <span 
              v-if="unreadCount > 0"
              class="px-2 py-0.5 text-xs font-medium rounded-full bg-destructive text-destructive-foreground"
            >
              {{ unreadCount }}
            </span>
          </div>
          <button
            v-if="unreadCount > 0"
            @click="handleMarkAllAsRead"
            class="text-xs text-primary hover:underline"
          >
            Tandai semua dibaca
          </button>
        </div>

        <div class="flex-1 overflow-y-auto">
          <div v-if="loading && notifications.length === 0" class="p-8 text-center">
            <div class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-solid border-current border-r-transparent"></div>
            <p class="mt-2 text-sm text-muted-foreground">Memuat...</p>
          </div>

          <div v-else-if="notifications.length === 0" class="p-8 text-center">
            <Bell class="w-12 h-12 mx-auto text-muted-foreground/50 mb-3" />
            <p class="text-sm text-muted-foreground">Tidak ada notifikasi</p>
          </div>

          <div v-else class="divide-y divide-border">
            <div
              v-for="notification in notifications"
              :key="notification.id"
              @click="handleNotificationClick(notification)"
              class="p-4 hover:bg-accent/50 cursor-pointer transition-colors relative group"
              :class="{ 'bg-accent/20': !notification.is_read }"
            >
              <div class="flex items-start gap-3">
                <div 
                  class="flex-shrink-0 w-8 h-8 rounded-md flex items-center justify-center"
                  :class="getIconClass(notification.type)"
                >
                  <component :is="getIcon(notification.type)" class="w-4 h-4" />
                </div>

                <div class="flex-1 min-w-0 pr-6">
                  <p class="text-sm font-medium mb-1" :class="{ 'text-foreground': !notification.is_read }">
                    {{ notification.title }}
                  </p>
                  <p class="text-xs text-muted-foreground line-clamp-2">
                    {{ notification.message }}
                  </p>
                  <p class="text-xs text-muted-foreground mt-1">
                    {{ formatTime(notification.created_at) }}
                  </p>
                </div>

                <div class="absolute top-2 right-2 flex items-center gap-1">
                  <button
                    @click.stop="handleDelete(notification.id)"
                    class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-destructive/10 text-muted-foreground hover:text-destructive transition-all"
                  >
                    <X class="w-3.5 h-3.5" />
                  </button>
                  <div 
                    v-if="!notification.is_read"
                    class="w-2 h-2 rounded-full bg-primary"
                  ></div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="hasMore && notifications.length > 0" class="p-4 text-center border-t border-border">
            <button
              @click="loadMore"
              :disabled="loading"
              class="text-sm text-primary hover:underline disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ loading ? 'Memuat...' : 'Muat lebih banyak' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotifications } from '@/composables/useNotifications'
import { Bell, X, Calendar, Clock, ClipboardList, FileText, AlertCircle, Megaphone } from 'lucide-vue-next'

const router = useRouter()
const isOpen = ref(false)
const { notifications, unreadCount, loading, hasMore, hasUnread, fetchNotifications, fetchUnreadCount, markAsRead, markAllAsRead, deleteNotification } = useNotifications()

let pollInterval = null

const togglePanel = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value && notifications.value.length === 0) fetchNotifications()
}

const closePanel = () => { isOpen.value = false }

const handleNotificationClick = async (notification) => {
  if (!notification.is_read) await markAsRead(notification.id)
  if (notification.data?.link) {
    router.push(notification.data.link)
    closePanel()
  }
}

const handleMarkAllAsRead = async () => { await markAllAsRead() }
const handleDelete = async (id) => { await deleteNotification(id) }
const loadMore = () => { fetchNotifications(true) }

const getIcon = (type) => {
  const icons = { leave: Calendar, overtime: Clock, attendance: ClipboardList, announcement: Megaphone, default: AlertCircle }
  return icons[type] || icons.default
}

const getIconClass = (type) => {
  const classes = { leave: 'bg-blue-500/10 text-blue-500', overtime: 'bg-purple-500/10 text-purple-500', attendance: 'bg-green-500/10 text-green-500', announcement: 'bg-orange-500/10 text-orange-500', default: 'bg-gray-500/10 text-gray-500' }
  return classes[type] || classes.default
}

const formatTime = (date) => {
  const now = new Date()
  const notifDate = new Date(date)
  const diff = Math.floor((now - notifDate) / 1000)
  if (diff < 60) return 'Baru saja'
  if (diff < 3600) return `${Math.floor(diff / 60)} menit lalu`
  if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`
  if (diff < 604800) return `${Math.floor(diff / 86400)} hari lalu`
  return notifDate.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => { if (!(el === event.target || el.contains(event.target))) binding.value() }
    document.addEventListener('click', el.clickOutsideEvent)
  },
  unmounted(el) { document.removeEventListener('click', el.clickOutsideEvent) }
}

onMounted(() => {
  fetchUnreadCount()
  pollInterval = setInterval(fetchUnreadCount, 30000)
})

onUnmounted(() => { if (pollInterval) clearInterval(pollInterval) })
</script>