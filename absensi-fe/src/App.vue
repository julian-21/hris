<!-- src/App.vue -->
<template>
  <div id="app">
    <!-- Splash Screen -->
    <SplashScreen />
    
    <!-- Main App (hanya muncul setelah splash selesai) -->
    <RouterView v-if="appStore.splashComplete" v-slot="{ Component }">
      <transition name="page-fade" mode="out-in">
        <component :is="Component" />
      </transition>
    </RouterView>
    
    <!-- Toast Container -->
    <ToastContainer />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAppStore } from '@/stores/app'
import SplashScreen from '@/components/SplashScreen.vue'
import ToastContainer from '@/components/ToastContainer.vue'

const appStore = useAppStore()

onMounted(() => {
  // Inisialisasi aplikasi saat component mounted
  appStore.initializeApp()
})
</script>

<style>
/* Page Transition Animation */
.page-fade-enter-active,
.page-fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.page-fade-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.page-fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>