// composables/useAttendanceReport.js
import { ref } from 'vue'
import api from '@/services/api'

export function useAttendanceReport() {
  const loading = ref(false)
  const error = ref(null)

  async function getReport(params) {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.get('/attendance-reports', { params })
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat rekap'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function getSummary(params) {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.get('/attendance-reports/summary', { params })
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat summary'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function getUsers() {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.get('/attendance-reports/users')
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Gagal memuat users'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    getReport,
    getSummary,
    getUsers
  }
}