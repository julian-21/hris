<template>
  <div id="app">
    <RouterView />
    
    <!-- Toast Container -->
    <ToastContainer />
  </div>
</template>

<script setup>
import ToastContainer from '@/components/ToastContainer.vue'
</script>