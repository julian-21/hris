// main.js
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './assets/main.css'
import VueApexCharts from "vue3-apexcharts"
import '@fontsource/plus-jakarta-sans/400.css'
import '@fontsource/plus-jakarta-sans/500.css'
import '@fontsource/plus-jakarta-sans/600.css'
import '@fontsource/plus-jakarta-sans/700.css'
import '@fontsource/jetbrains-mono/400.css'
import '@fontsource/jetbrains-mono/500.css'


const app = createApp(App)
const pinia = createPinia()

// PENTING: Urutan ini!
app.use(pinia)  // 1. Pinia dulu
app.use(router) // 2. Baru router
app.use(VueApexCharts)
app.mount('#app')