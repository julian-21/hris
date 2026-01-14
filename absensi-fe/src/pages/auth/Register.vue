<template>
  <div class="min-h-screen flex items-center justify-center bg-zinc-50 py-12 px-4 sm:px-6 lg:px-8">
    <!-- Register Form -->
    <div class="w-full max-w-md space-y-8">
      <!-- Header -->
      <div class="text-center">
        <h2 class="text-3xl font-bold tracking-tight text-zinc-900">
          Create your account
        </h2>
        <p class="mt-2 text-sm text-zinc-600">
          Register to start using the system
        </p>
      </div>
      
      <!-- Form Card -->
      <div class="bg-white py-8 px-6 shadow rounded-lg border border-zinc-200">
        <form class="space-y-6" @submit.prevent="register">
          <!-- Error Alert -->
          <Transition
            enter-active-class="transition-all duration-200"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
          >
            <div v-if="error" class="rounded-md bg-red-50 p-4 border border-red-200">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3 flex-1">
                  <p class="text-sm font-medium text-red-800">
                    {{ error }}
                  </p>
                </div>
                <button @click="error = ''" type="button" class="ml-3 inline-flex text-red-400 hover:text-red-500">
                  <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                  </svg>
                </button>
              </div>
            </div>
          </Transition>

          <!-- Name -->
          <div>
            <label for="name" class="block text-sm font-medium text-zinc-700 mb-1.5">
              Full Name
            </label>
            <input
              id="name"
              v-model="name"
              type="text"
              required
              autocomplete="name"
              class="block w-full px-3 py-2 border border-zinc-300 rounded-md shadow-sm placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900 transition-colors"
              placeholder="John Doe"
              :disabled="loading"
            />
          </div>

          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-medium text-zinc-700 mb-1.5">
              Email address
            </label>
            <input
              id="email"
              v-model="email"
              type="email"
              required
              autocomplete="email"
              class="block w-full px-3 py-2 border border-zinc-300 rounded-md shadow-sm placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900 transition-colors"
              placeholder="name@company.com"
              :disabled="loading"
            />
          </div>

          <!-- Password -->
          <div>
            <label for="password" class="block text-sm font-medium text-zinc-700 mb-1.5">
              Password
            </label>
            <input
              id="password"
              v-model="password"
              type="password"
              required
              autocomplete="new-password"
              class="block w-full px-3 py-2 border border-zinc-300 rounded-md shadow-sm placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900 transition-colors"
              placeholder="Enter your password"
              :disabled="loading"
            />
          </div>

          <!-- Password Confirmation -->
          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-zinc-700 mb-1.5">
              Confirm Password
            </label>
            <input
              id="password_confirmation"
              v-model="password_confirmation"
              type="password"
              required
              autocomplete="new-password"
              class="block w-full px-3 py-2 border border-zinc-300 rounded-md shadow-sm placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900 transition-colors"
              placeholder="Confirm your password"
              :disabled="loading"
            />
          </div>

          <!-- Submit Button -->
          <div>
            <button
              type="submit"
              :disabled="loading"
              class="w-full flex justify-center items-center gap-2 py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-900 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <svg v-if="!loading" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
              </svg>
              <svg v-else class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ loading ? 'Creating account...' : 'Create account' }}</span>
            </button>
          </div>

          <!-- Divider -->
          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-zinc-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-2 bg-white text-zinc-500">Already have an account?</span>
            </div>
          </div>

          <!-- Sign in link -->
          <div class="text-center">
            <router-link to="/login" class="font-medium text-zinc-900 hover:text-zinc-700">
              Sign in instead
            </router-link>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

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