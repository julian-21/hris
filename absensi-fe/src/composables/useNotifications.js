// src/composables/useNotifications.js
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function useNotifications() {
  const authStore = useAuthStore()
  const notifications = ref([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const page = ref(1)
  const hasMore = ref(true)

  const fetchNotifications = async (loadMore = false) => {
    if (loading.value) return
    
    loading.value = true
    try {
      const currentPage = loadMore ? page.value + 1 : 1
      const response = await fetch(
        `https://mbg.erpdis.com/api/notifications?page=${currentPage}`,
        {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Accept': 'application/json'
          }
        }
      )

      if (!response.ok) throw new Error('Failed to fetch')

      const data = await response.json()
      
      if (data.success) {
        if (loadMore) {
          notifications.value = [...notifications.value, ...data.notifications.data]
          page.value = currentPage
        } else {
          notifications.value = data.notifications.data
          page.value = 1
        }
        
        unreadCount.value = data.unread_count
        hasMore.value = data.notifications.current_page < data.notifications.last_page
      }
    } catch (error) {
      console.error('Error fetching notifications:', error)
    } finally {
      loading.value = false
    }
  }

  const fetchUnreadCount = async () => {
    try {
      const response = await fetch('https://mbg.erpdis.com/api/notifications/unread-count', {
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
          'Accept': 'application/json'
        }
      })

      if (!response.ok) throw new Error('Failed to fetch')

      const data = await response.json()
      if (data.success) {
        unreadCount.value = data.count
      }
    } catch (error) {
      console.error('Error fetching unread count:', error)
    }
  }

  const markAsRead = async (id) => {
    try {
      const response = await fetch(`https://mbg.erpdis.com/api/notifications/${id}/read`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      })

      if (!response.ok) throw new Error('Failed to mark as read')

      const data = await response.json()
      if (data.success) {
        const notification = notifications.value.find(n => n.id === id)
        if (notification) {
          notification.is_read = true
          notification.read_at = new Date()
        }
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
    } catch (error) {
      console.error('Error marking notification as read:', error)
    }
  }

  const markAllAsRead = async () => {
    try {
      const response = await fetch('https://mbg.erpdis.com/api/notifications/mark-all-read', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      })

      if (!response.ok) throw new Error('Failed to mark all as read')

      const data = await response.json()
      if (data.success) {
        notifications.value.forEach(n => {
          n.is_read = true
          n.read_at = new Date()
        })
        unreadCount.value = 0
      }
    } catch (error) {
      console.error('Error marking all as read:', error)
    }
  }

  const deleteNotification = async (id) => {
    try {
      const response = await fetch(`https://mbg.erpdis.com/api/notifications/${id}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
          'Accept': 'application/json'
        }
      })

      if (!response.ok) throw new Error('Failed to delete')

      const data = await response.json()
      if (data.success) {
        const index = notifications.value.findIndex(n => n.id === id)
        if (index !== -1) {
          const wasUnread = !notifications.value[index].is_read
          notifications.value.splice(index, 1)
          if (wasUnread) {
            unreadCount.value = Math.max(0, unreadCount.value - 1)
          }
        }
      }
    } catch (error) {
      console.error('Error deleting notification:', error)
    }
  }

  const hasUnread = computed(() => unreadCount.value > 0)

  return {
    notifications,
    unreadCount,
    loading,
    hasMore,
    hasUnread,
    fetchNotifications,
    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
    deleteNotification
  }
}