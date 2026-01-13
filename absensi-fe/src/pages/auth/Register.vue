<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const name = ref('')
const email = ref('')
const password = ref('')
const password_confirmation = ref('')
const loading = ref(false)
const error = ref('')

const register = async () => {
  loading.value = true
  error.value = ''

  try {
    await authStore.register(
      name.value,
      email.value,
      password.value,
      password_confirmation.value
    )
    router.push('/dashboard')
  } catch (e) {
    console.error('Register error:', e)
    error.value = e.response?.data?.message || 'Registrasi gagal'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="w-full max-w-md bg-white rounded-lg shadow p-8">
      <h2 class="text-2xl font-bold text-center mb-6">Register Absensi</h2>

      <form @submit.prevent="register">
        <input 
          v-model="name" 
          type="text" 
          placeholder="Nama Lengkap" 
          required
          class="w-full mb-4 px-4 py-2 border rounded focus:ring focus:ring-indigo-200" 
        />
        
        <input 
          v-model="email" 
          type="email" 
          placeholder="Email" 
          required
          class="w-full mb-4 px-4 py-2 border rounded focus:ring focus:ring-indigo-200" 
        />
        
        <input 
          v-model="password" 
          type="password" 
          placeholder="Password" 
          required
          class="w-full mb-4 px-4 py-2 border rounded focus:ring focus:ring-indigo-200" 
        />
        
        <input 
          v-model="password_confirmation" 
          type="password" 
          placeholder="Konfirmasi Password" 
          required
          class="w-full mb-4 px-4 py-2 border rounded focus:ring focus:ring-indigo-200" 
        />

        <button 
          type="submit"
          :disabled="loading" 
          class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg font-semibold disabled:opacity-50"
        >
          {{ loading ? 'Loading...' : 'Daftar' }}
        </button>
      </form>

      <p v-if="error" class="text-red-500 text-sm text-center mt-2">{{ error }}</p>
      
      <p class="text-center mt-4 text-sm text-gray-600">
        Sudah punya akun? 
        <router-link to="/login" class="text-indigo-600 hover:underline">
          Login
        </router-link>
      </p>
    </div>
  </div>
</template>