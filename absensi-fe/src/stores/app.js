// src/stores/app.js
import { defineStore } from 'pinia'

export const useAppStore = defineStore('app', {
  state: () => ({
    isLoading: true,
    splashComplete: false,
    loadingProgress: 0
  }),
  
  actions: {
    async initializeApp() {
      // Simulasi loading progress
      this.loadingProgress = 0
      
      // Increment progress
      const interval = setInterval(() => {
        if (this.loadingProgress < 90) {
          this.loadingProgress += 10
        }
      }, 100)
      
      // Simulasi loading data atau proses inisialisasi
      await new Promise(resolve => setTimeout(resolve, 1500))
      
      clearInterval(interval)
      this.loadingProgress = 100
      this.isLoading = false
      
      // Delay sebelum hilang dengan animasi
      setTimeout(() => {
        this.splashComplete = true
      }, 500)
    }
  }
})