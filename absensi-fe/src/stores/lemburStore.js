import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { lemburApi } from '@/services/lemburApi'

export const useLemburStore = defineStore('lembur', () => {
  // State
  const lemburs = ref([])
  const currentLembur = ref(null)
  const statistics = ref(null)
  const loading = ref(false)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
  })

  // Default filters state (opsional, karena sekarang kita kirim via params)
  const filters = ref({
    status: 'all',
    final_status: 'all',
    month: '',
    year: '',
    search: ''
  })

  // Computed
  const hasLemburs = computed(() => lemburs.value.length > 0)

  // Actions

  // UPDATE PENTING DISINI: Menambahkan parameter `params`
  async function fetchLemburs(page = 1, extraParams = {}) {
    loading.value = true
    try {
      // Gabungkan pagination, filter default store, dan filter dari component (extraParams)
      const queryParams = {
        page,
        per_page: pagination.value.per_page,
        ...filters.value,  // Filter bawaan store
        ...extraParams     // Filter spesifik dari component (misal: user_id)
      }

      const response = await lemburApi.getAll(queryParams)
      
      if (response.data.success) {
        // Update state global (sebagai cache/default)
        lemburs.value = response.data.data.data
        
        // Update pagination info
        pagination.value = {
          current_page: response.data.data.current_page,
          last_page: response.data.data.last_page,
          per_page: response.data.data.per_page,
          total: response.data.data.total
        }
      }

      // PENTING: Return data response agar Component bisa menggunakannya secara independen
      return response.data
    } catch (error) {
      console.error('Error fetching lemburs:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function fetchLemburById(id) {
    loading.value = true
    try {
      const response = await lemburApi.getById(id)
      if (response.data.success) {
        currentLembur.value = response.data.data
      }
      return response.data
    } catch (error) {
      console.error('Error fetching lembur:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function createLembur(data) {
    loading.value = true
    try {
      const response = await lemburApi.create(data)
      // Kita tidak perlu auto-fetch disini jika component akan melakukan refresh manual
      return response.data
    } catch (error) {
      console.error('Error creating lembur:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function updateLembur(id, data) {
    loading.value = true
    try {
      const response = await lemburApi.update(id, data)
      return response.data
    } catch (error) {
      console.error('Error updating lembur:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function deleteLembur(id) {
    loading.value = true
    try {
      const response = await lemburApi.delete(id)
      return response.data
    } catch (error) {
      console.error('Error deleting lembur:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function approveLembur(id) {
    loading.value = true
    try {
      const response = await lemburApi.approve(id)
      return response.data
    } catch (error) {
      console.error('Error approving lembur:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function rejectLembur(id) {
    loading.value = true
    try {
      const response = await lemburApi.reject(id)
      return response.data
    } catch (error) {
      console.error('Error rejecting lembur:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function finalApproveLembur(id) {
    loading.value = true
    try {
      const response = await lemburApi.finalApprove(id)
      return response.data
    } catch (error) {
      console.error('Error final approving lembur:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function finalRejectLembur(id) {
    loading.value = true
    try {
      const response = await lemburApi.finalReject(id)
      return response.data
    } catch (error) {
      console.error('Error final rejecting lembur:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function fetchStatistics() {
    // Kita gunakan filters yang ada di store saat ini
    try {
      const params = { ...filters.value }
      const response = await lemburApi.getStatistics(params)
      
      if (response.data.success) {
        statistics.value = response.data.data
      }
      return response.data
    } catch (error) {
      console.error('Error fetching statistics:', error)
      throw error
    }
  }

  function setFilters(newFilters) {
    filters.value = { ...filters.value, ...newFilters }
  }

  function resetFilters() {
    filters.value = {
      status: 'all',
      final_status: 'all',
      month: '',
      year: '',
      search: ''
    }
  }

  return {
    lemburs,
    currentLembur,
    statistics,
    loading,
    pagination,
    filters,
    hasLemburs,
    fetchLemburs,
    fetchLemburById,
    createLembur,
    updateLembur,
    deleteLembur,
    approveLembur,
    rejectLembur,
    finalApproveLembur,
    finalRejectLembur,
    fetchStatistics,
    setFilters,
    resetFilters
  }
})