// src/composables/useSearch.js
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

export function useSearch() {
  const authStore = useAuthStore()
  const router = useRouter()
  const searchQuery = ref('')
  const searchResults = ref(null)
  const loading = ref(false)
  const showResults = ref(false)

  const search = async (query = null) => {
    const q = query || searchQuery.value
    
    if (!q || q.trim().length < 2) {
      searchResults.value = null
      showResults.value = false
      return
    }

    loading.value = true
    try {
      const response = await fetch(
        `https://mbg.erpdis.com/api/search?q=${encodeURIComponent(q.trim())}`,
        {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Accept': 'application/json'
          }
        }
      )

      if (!response.ok) throw new Error('Search failed')

      const data = await response.json()
      
      if (data.success) {
        searchResults.value = data.results
        showResults.value = true
      }
    } catch (error) {
      console.error('Error searching:', error)
      searchResults.value = null
    } finally {
      loading.value = false
    }
  }

  const clearSearch = () => {
    searchQuery.value = ''
    searchResults.value = null
    showResults.value = false
  }

  const goToResult = (item) => {
    if (item.link) {
      router.push(item.link)
      clearSearch()
    }
  }

  const hasResults = computed(() => {
    if (!searchResults.value) return false
    return Object.values(searchResults.value).some(arr => arr && arr.length > 0)
  })

  const totalResults = computed(() => {
    if (!searchResults.value) return 0
    return Object.values(searchResults.value).reduce((sum, arr) => sum + (arr?.length || 0), 0)
  })

  return {
    searchQuery,
    searchResults,
    loading,
    showResults,
    hasResults,
    totalResults,
    search,
    clearSearch,
    goToResult
  }
}