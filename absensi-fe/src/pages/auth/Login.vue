<template>
  <div class="min-h-screen flex items-center justify-center bg-zinc-50 py-12 px-4 sm:px-6 lg:px-8">
    <!-- Door Animation Overlay -->
    <Transition
      enter-active-class="transition-all duration-500 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
    >
      <div v-if="showDoorAnimation" class="fixed inset-0 z-50 flex items-center justify-center">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-zinc-950"></div>
        
        <!-- Door Container -->
        <div class="relative z-10 w-full h-full flex items-center justify-center perspective-1000">
          <!-- Left Door -->
          <div 
            class="absolute inset-y-0 left-0 w-1/2 shadow-2xl transition-all duration-[1500ms] ease-in-out origin-left"
            :class="doorOpened ? '-translate-x-full rotate-y-90' : ''"
          >
            <div class="absolute inset-0 bg-gradient-to-br from-zinc-800 via-zinc-900 to-zinc-950">
              <!-- Rivets pattern -->
              <div class="absolute top-8 left-8 w-3 h-3 bg-zinc-700 rounded-full shadow-inner"></div>
              <div class="absolute top-8 right-8 w-3 h-3 bg-zinc-700 rounded-full shadow-inner"></div>
              <div class="absolute bottom-8 left-8 w-3 h-3 bg-zinc-700 rounded-full shadow-inner"></div>
              <div class="absolute bottom-8 right-8 w-3 h-3 bg-zinc-700 rounded-full shadow-inner"></div>
              
              <!-- Steel plates -->
              <div class="absolute inset-x-8 top-16 bottom-16 grid grid-rows-3 gap-4">
                <!-- Top plate -->
                <div class="border-2 border-zinc-700/60 bg-zinc-800/30 relative">
                  <div class="absolute inset-1 border border-zinc-600/40"></div>
                  <!-- Plate rivets -->
                  <div class="absolute top-2 left-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                  <div class="absolute top-2 right-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                  <div class="absolute bottom-2 left-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                  <div class="absolute bottom-2 right-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                </div>
                
                <!-- Middle plate -->
                <div class="border-2 border-zinc-700/60 bg-zinc-800/30 relative">
                  <div class="absolute inset-1 border border-zinc-600/40"></div>
                  <div class="absolute top-2 left-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                  <div class="absolute top-2 right-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                  <div class="absolute bottom-2 left-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                  <div class="absolute bottom-2 right-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                </div>
                
                <!-- Bottom plate -->
                <div class="border-2 border-zinc-700/60 bg-zinc-800/30 relative">
                  <div class="absolute inset-1 border border-zinc-600/40"></div>
                  <div class="absolute top-2 left-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                  <div class="absolute top-2 right-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                  <div class="absolute bottom-2 left-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                  <div class="absolute bottom-2 right-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                </div>
              </div>
              
              <!-- Door handle -->
              <div class="absolute top-1/2 right-12 -translate-y-1/2">
                <div class="w-6 h-20 bg-gradient-to-b from-zinc-600 via-zinc-700 to-zinc-800 rounded border border-zinc-600 shadow-xl"></div>
              </div>
              
              <!-- Metal texture -->
              <div class="absolute inset-0 bg-gradient-to-br from-white/[0.02] via-transparent to-transparent"></div>
              
              <!-- Edge highlight -->
              <div class="absolute inset-y-0 right-0 w-px bg-gradient-to-b from-transparent via-zinc-600/50 to-transparent"></div>
            </div>
          </div>
          
          <!-- Right Door -->
          <div 
            class="absolute inset-y-0 right-0 w-1/2 shadow-2xl transition-all duration-[1500ms] ease-in-out origin-right"
            :class="doorOpened ? 'translate-x-full -rotate-y-90' : ''"
          >
            <div class="absolute inset-0 bg-gradient-to-bl from-zinc-800 via-zinc-900 to-zinc-950">
              <!-- Rivets pattern -->
              <div class="absolute top-8 left-8 w-3 h-3 bg-zinc-700 rounded-full shadow-inner"></div>
              <div class="absolute top-8 right-8 w-3 h-3 bg-zinc-700 rounded-full shadow-inner"></div>
              <div class="absolute bottom-8 left-8 w-3 h-3 bg-zinc-700 rounded-full shadow-inner"></div>
              <div class="absolute bottom-8 right-8 w-3 h-3 bg-zinc-700 rounded-full shadow-inner"></div>
              
              <!-- Steel plates -->
              <div class="absolute inset-x-8 top-16 bottom-16 grid grid-rows-3 gap-4">
                <!-- Top plate -->
                <div class="border-2 border-zinc-700/60 bg-zinc-800/30 relative">
                  <div class="absolute inset-1 border border-zinc-600/40"></div>
                  <div class="absolute top-2 left-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                  <div class="absolute top-2 right-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                  <div class="absolute bottom-2 left-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                  <div class="absolute bottom-2 right-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                </div>
                
                <!-- Middle plate -->
                <div class="border-2 border-zinc-700/60 bg-zinc-800/30 relative">
                  <div class="absolute inset-1 border border-zinc-600/40"></div>
                  <div class="absolute top-2 left-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                  <div class="absolute top-2 right-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                  <div class="absolute bottom-2 left-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                  <div class="absolute bottom-2 right-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                </div>
                
                <!-- Bottom plate -->
                <div class="border-2 border-zinc-700/60 bg-zinc-800/30 relative">
                  <div class="absolute inset-1 border border-zinc-600/40"></div>
                  <div class="absolute top-2 left-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                  <div class="absolute top-2 right-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                  <div class="absolute bottom-2 left-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                  <div class="absolute bottom-2 right-2 w-2 h-2 bg-zinc-700 rounded-full"></div>
                </div>
              </div>
              
              <!-- Door handle -->
              <div class="absolute top-1/2 left-12 -translate-y-1/2">
                <div class="w-6 h-20 bg-gradient-to-b from-zinc-600 via-zinc-700 to-zinc-800 rounded border border-zinc-600 shadow-xl"></div>
              </div>
              
              <!-- Metal texture -->
              <div class="absolute inset-0 bg-gradient-to-bl from-white/[0.02] via-transparent to-transparent"></div>
              
              <!-- Edge highlight -->
              <div class="absolute inset-y-0 left-0 w-px bg-gradient-to-b from-transparent via-zinc-600/50 to-transparent"></div>
            </div>
          </div>
          
          <!-- Welcome Message -->
          <Transition
            enter-active-class="transition-all duration-700 delay-500"
            enter-from-class="opacity-0 scale-75"
            enter-to-class="opacity-100 scale-100"
          >
            <div v-if="doorOpened" class="relative z-20 text-center">
              <div class="mb-8">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-emerald-500 rounded-full shadow-2xl animate-bounce">
                  <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                  </svg>
                </div>
              </div>
              <h2 class="text-5xl font-bold text-white mb-4">Selamat Datang!</h2>
              <p class="text-2xl text-emerald-400 mb-8 font-semibold">{{ authStore.user?.name }}</p>
              <div class="flex items-center justify-center gap-3">
                <div class="flex gap-1.5">
                  <div class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce" style="animation-delay: 0s;"></div>
                  <div class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce" style="animation-delay: 0.15s;"></div>
                  <div class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce" style="animation-delay: 0.3s;"></div>
                </div>
                <p class="text-sm text-zinc-300 font-medium">Mengarahkan ke dashboard...</p>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>

    <!-- Login Form -->
    <div class="w-full max-w-md space-y-8">
      <!-- Header -->
      <div class="text-center">
        <h2 class="text-3xl font-bold tracking-tight text-zinc-900">
          Sign in to your account
        </h2>
        <p class="mt-2 text-sm text-zinc-600">
          Enter your credentials to continue
        </p>
      </div>
      
      <!-- Form Card -->
      <div class="bg-white py-8 px-6 shadow rounded-lg border border-zinc-200">
        <form class="space-y-6" @submit.prevent="handleLogin">
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

          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-medium text-zinc-700 mb-1.5">
              Email address
            </label>
            <input
              id="email"
              v-model="form.email"
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
              v-model="form.password"
              type="password"
              required
              autocomplete="current-password"
              class="block w-full px-3 py-2 border border-zinc-300 rounded-md shadow-sm placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900 transition-colors"
              placeholder="Enter your password"
              :disabled="loading"
            />
          </div>

          <!-- Remember & Forgot -->
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <input
                id="remember-me"
                v-model="form.remember"
                type="checkbox"
                class="h-4 w-4 text-zinc-900 focus:ring-zinc-900 border-zinc-300 rounded"
              />
              <label for="remember-me" class="ml-2 block text-sm text-zinc-700">
                Remember me
              </label>
            </div>

            <div class="text-sm">
              <router-link to="/forgot-password" class="font-medium text-zinc-900 hover:text-zinc-700">
                Forgot password?
              </router-link>
            </div>
          </div>

          <!-- Submit Button -->
          <div>
            <button
              type="submit"
              :disabled="loading"
              class="w-full flex justify-center items-center gap-2 py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-zinc-900 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-900 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <svg v-if="!loading" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
              </svg>
              <svg v-else class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ loading ? 'Signing in...' : 'Sign in' }}</span>
            </button>
          </div>

          <!-- Divider -->
          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-zinc-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-2 bg-white text-zinc-500">Don't have an account?</span>
            </div>
          </div>

          <!-- Sign up link -->
          <div class="text-center">
            <router-link to="/register" class="font-medium text-zinc-900 hover:text-zinc-700">
              Create an account
            </router-link>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  email: '',
  password: '',
  remember: false
})

const loading = ref(false)
const error = ref('')
const showDoorAnimation = ref(false)
const doorOpened = ref(false)

const handleLogin = async () => {
  loading.value = true
  error.value = ''
  
  try {
    await authStore.login(form.email, form.password)
    
    // Show door animation
    showDoorAnimation.value = true
    
    // Wait a bit then open the doors
    setTimeout(() => {
      doorOpened.value = true
    }, 1000)
    
    // Redirect after animation completes
    setTimeout(() => {
      const redirectPath = router.currentRoute.value.query.redirect || '/dashboard'
      router.push(redirectPath)
    }, 3000)
    
  } catch (err) {
    error.value = err.response?.data?.message || 'Login failed. Please check your credentials.'
    console.error('Login error:', err)
    loading.value = false
  }
}
</script>

<style scoped>
.perspective-1000 {
  perspective: 1000px;
}

.rotate-y-90 {
  transform: rotateY(-90deg);
}

.-rotate-y-90 {
  transform: rotateY(90deg);
}

/* Smooth animations */
* {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>