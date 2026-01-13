// File: services/api.js
import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }
})

// Request interceptor - attach token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    
    console.log('📤 API Request:', {
      method: config.method?.toUpperCase(),
      url: config.url,
      hasToken: !!token,
      token: token ? token.substring(0, 20) + '...' : null
    })
    
    return config
  },
  (error) => {
    console.error('❌ Request error:', error)
    return Promise.reject(error)
  }
)

// Response interceptor - handle errors
api.interceptors.response.use(
  (response) => {
    console.log('✅ API Response:', {
      method: response.config.method?.toUpperCase(),
      url: response.config.url,
      status: response.status
    })
    return response
  },
  (error) => {
    console.error('❌ API Error:', {
      method: error.config?.method?.toUpperCase(),
      url: error.config?.url,
      status: error.response?.status,
      message: error.response?.data?.message || error.message
    })
    
    // Handle 401 Unauthorized
    if (error.response?.status === 401) {
      console.log('🔐 Unauthorized - clearing token')
      localStorage.removeItem('token')
      localStorage.removeItem('roles')
      localStorage.removeItem('permissions')
      
      // Redirect to login if not already there
      if (!window.location.pathname.includes('/login')) {
        window.location.href = '/login'
      }
    }
    
    return Promise.reject(error)
  }
)

export default api