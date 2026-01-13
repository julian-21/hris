// stores/auth.js
import { defineStore } from 'pinia'
import api from '../services/api'
import { ref, computed } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token'))
  const roles = ref(JSON.parse(localStorage.getItem('roles') || '[]'))
  const permissions = ref(JSON.parse(localStorage.getItem('permissions') || '[]'))

  // Computed property (bukan function)
  const isAuthenticated = computed(() => !!token.value)
  
  // Helper functions
  const hasRole = (role) => {
    return roles.value.includes(role)
  }
  
  const hasPermission = (permission) => {
    return permissions.value.includes(permission)
  }
  
  const hasAnyRole = (rolesList) => {
    return rolesList.some(role => roles.value.includes(role))
  }
  
  const hasAnyPermission = (permissionsList) => {
    return permissionsList.some(perm => permissions.value.includes(perm))
  }
  
  const canApprove = computed(() => {
    return hasAnyRole(['Admin', 'HR', 'Direktur', 'Digital Lead']) || 
           (user.value?.bawahan && user.value.bawahan.length > 0)
  })

  const login = async (email, password) => {
    const response = await api.post('/login', { email, password })
    
    token.value = response.data.token
    user.value = response.data.user
    roles.value = response.data.roles
    permissions.value = response.data.permissions
    
    localStorage.setItem('token', response.data.token)
    localStorage.setItem('roles', JSON.stringify(response.data.roles))
    localStorage.setItem('permissions', JSON.stringify(response.data.permissions))
    
    return response.data
  }

  const register = async (data) => {
    const response = await api.post('/register', data)
    
    token.value = response.data.token
    user.value = response.data.user
    roles.value = response.data.roles
    permissions.value = response.data.permissions
    
    localStorage.setItem('token', response.data.token)
    localStorage.setItem('roles', JSON.stringify(response.data.roles))
    localStorage.setItem('permissions', JSON.stringify(response.data.permissions))
    
    return response.data
  }

  const logout = async () => {
    try {
      await api.post('/logout')
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      user.value = null
      token.value = null
      roles.value = []
      permissions.value = []
      
      localStorage.removeItem('token')
      localStorage.removeItem('roles')
      localStorage.removeItem('permissions')
    }
  }

  const fetchUser = async () => {
    if (!token.value) return null
    
    try {
      const response = await api.get('/me')
      user.value = response.data.user
      roles.value = response.data.roles
      permissions.value = response.data.permissions
      
      localStorage.setItem('roles', JSON.stringify(response.data.roles))
      localStorage.setItem('permissions', JSON.stringify(response.data.permissions))
      
      return response.data
    } catch (error) {
      logout()
      throw error
    }
  }

  return { 
    user, 
    token, 
    roles,
    permissions,
    isAuthenticated, // computed, bukan function
    hasRole,
    hasPermission,
    hasAnyRole,
    hasAnyPermission,
    canApprove, // computed
    login, 
    register, 
    logout, 
    fetchUser
  }
})