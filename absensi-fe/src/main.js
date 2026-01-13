// main.js
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './assets/main.css'
import VueApexCharts from "vue3-apexcharts"

const app = createApp(App)
const pinia = createPinia()

// PENTING: Urutan ini!
app.use(pinia)  // 1. Pinia dulu
app.use(router) // 2. Baru router
app.use(VueApexCharts)
app.mount('#app')