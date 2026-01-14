<!-- src/components/SplashScreen.vue -->
<template>
  <transition name="splash-fade">
    <div v-if="!appStore.splashComplete" class="splash-screen">
      <div class="splash-content">
        <!-- Logo Animation -->
        <div class="logo-container">
          <div class="logo-circle">
            <svg viewBox="0 0 100 100" class="logo-svg">
              <circle cx="50" cy="50" r="45" class="logo-ring" />
              <text x="50" y="60" text-anchor="middle" class="logo-text">HRIS</text>
            </svg>
          </div>
        </div>
        
        <!-- Company Name -->
        <h1 class="company-name">MBG HRIS</h1>
        
        <!-- Loading Bar -->
        <div class="loading-container">
          <div class="loading-bar">
            <div 
              class="loading-progress" 
              :style="{ width: appStore.loadingProgress + '%' }"
            ></div>
          </div>
          <p class="loading-text">{{ loadingText }}</p>
        </div>
        
        <!-- Animated Dots -->
        <div class="loading-dots">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
      
      <!-- Background Animation -->
      <div class="bg-animation">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
        <div class="circle circle-3"></div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { computed } from 'vue'
import { useAppStore } from '@/stores/app'

const appStore = useAppStore()

const loadingText = computed(() => {
  if (appStore.loadingProgress < 30) return 'Memuat aplikasi...'
  if (appStore.loadingProgress < 60) return 'Menyiapkan data...'
  if (appStore.loadingProgress < 90) return 'Hampir selesai...'
  return 'Selesai!'
})
</script>

<style scoped>
.splash-screen {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  overflow: hidden;
}

.splash-content {
  position: relative;
  z-index: 2;
  text-align: center;
}

/* Logo Animation */
.logo-container {
  margin-bottom: 30px;
}

.logo-circle {
  width: 120px;
  height: 120px;
  margin: 0 auto;
  animation: float 3s ease-in-out infinite;
}

.logo-svg {
  width: 100%;
  height: 100%;
  filter: drop-shadow(0 10px 30px rgba(0, 0, 0, 0.3));
}

.logo-ring {
  fill: none;
  stroke: white;
  stroke-width: 3;
  stroke-dasharray: 283;
  stroke-dashoffset: 283;
  animation: drawCircle 2s ease-in-out forwards;
}

.logo-text {
  font-size: 24px;
  font-weight: bold;
  fill: white;
  opacity: 0;
  animation: fadeInText 1s ease-in-out 1s forwards;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-20px); }
}

@keyframes drawCircle {
  to {
    stroke-dashoffset: 0;
  }
}

@keyframes fadeInText {
  to {
    opacity: 1;
  }
}

/* Company Name */
.company-name {
  color: white;
  font-size: 32px;
  font-weight: 700;
  margin-bottom: 40px;
  letter-spacing: 2px;
  opacity: 0;
  animation: slideUp 0.8s ease-out 0.5s forwards;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Loading Bar */
.loading-container {
  margin: 0 auto;
  width: 300px;
}

.loading-bar {
  width: 100%;
  height: 4px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 10px;
  overflow: hidden;
  backdrop-filter: blur(10px);
}

.loading-progress {
  height: 100%;
  background: linear-gradient(90deg, #fff, #f0f0f0);
  border-radius: 10px;
  transition: width 0.3s ease;
  box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
}

.loading-text {
  color: white;
  margin-top: 15px;
  font-size: 14px;
  font-weight: 500;
  opacity: 0.9;
}

/* Loading Dots */
.loading-dots {
  margin-top: 20px;
  display: flex;
  justify-content: center;
  gap: 8px;
}

.loading-dots span {
  width: 8px;
  height: 8px;
  background: white;
  border-radius: 50%;
  animation: bounce 1.4s infinite ease-in-out;
}

.loading-dots span:nth-child(1) {
  animation-delay: -0.32s;
}

.loading-dots span:nth-child(2) {
  animation-delay: -0.16s;
}

@keyframes bounce {
  0%, 80%, 100% {
    transform: scale(0);
    opacity: 0.5;
  }
  40% {
    transform: scale(1);
    opacity: 1;
  }
}

/* Background Animation */
.bg-animation {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  overflow: hidden;
  z-index: 1;
}

.circle {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  animation: float-circle 20s infinite ease-in-out;
}

.circle-1 {
  width: 300px;
  height: 300px;
  top: -100px;
  left: -100px;
  animation-delay: 0s;
}

.circle-2 {
  width: 200px;
  height: 200px;
  bottom: -50px;
  right: -50px;
  animation-delay: -5s;
}

.circle-3 {
  width: 150px;
  height: 150px;
  top: 50%;
  right: 10%;
  animation-delay: -10s;
}

@keyframes float-circle {
  0%, 100% {
    transform: translate(0, 0) scale(1);
  }
  33% {
    transform: translate(30px, -50px) scale(1.1);
  }
  66% {
    transform: translate(-20px, 20px) scale(0.9);
  }
}

/* Fade Out Transition */
.splash-fade-leave-active {
  transition: opacity 0.5s ease, transform 0.5s ease;
}

.splash-fade-leave-to {
  opacity: 0;
  transform: scale(1.1);
}
</style>