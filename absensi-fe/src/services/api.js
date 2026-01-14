import axios from 'axios'

const isDev = import.meta.env.MODE === 'development'

const api = axios.create({
  baseURL: 'https://mbg.erpdis.com/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }
})

// Request interceptor
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')

    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }

    if (isDev) {
      console.log('📤 API Request:', {
        method: config.method?.toUpperCase(),
        url: config.url,
        hasToken: !!token,
        token: token ? token.substring(0, 20) + '...' : null
      })
    }

    return config
  },
  (error) => {
    if (isDev) {
      console.error('❌ Request error:', error)
    }
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response) => {
    if (isDev) {
      console.log('✅ API Response:', {
        method: response.config.method?.toUpperCase(),
        url: response.config.url,
        status: response.status
      })
    }

    return response
  },
  (error) => {
    if (isDev) {
      console.error('❌ API Error:', {
        method: error.config?.method?.toUpperCase(),
        url: error.config?.url,
        status: error.response?.status,
        message: error.response?.data?.message || error.message
      })
    }

    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('roles')
      localStorage.removeItem('permissions')

      if (!window.location.pathname.includes('/login')) {
        window.location.href = '/login'
      }
    }

    return Promise.reject(error)
  }
)

export default api
