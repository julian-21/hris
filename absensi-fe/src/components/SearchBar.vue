<template>
  <div class="relative w-full sm:w-auto" v-click-outside="closeResults">
    <div class="relative">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground pointer-events-none" />
      
      <input 
        v-model="searchQuery"
        @input="handleInput"
        @keyup.enter="handleSearch"
        @focus="showResults = searchResults !== null"
        type="text" 
        placeholder="Cari Karyawan, absensi, cuti..." 
        class="h-10 w-full sm:w-80 rounded-lg border border-input bg-background pl-10 pr-24 text-sm outline-none transition-all placeholder:text-muted-foreground focus:border-ring focus:ring-2 focus:ring-ring/20 sm:focus:w-96"
      />
      
      <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-2">
        <div v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent"></div>
        <button 
          v-else-if="searchQuery" 
          @click="clearSearch"
          class="text-muted-foreground hover:text-foreground transition-colors p-1 hover:bg-accent rounded"
        >
          <X class="w-4 h-4" />
        </button>
        <div v-else class="hidden lg:flex items-center gap-1 text-[11px] text-muted-foreground font-medium">
          <kbd class="pointer-events-none inline-flex h-5 select-none items-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium">
            <span class="text-xs">⌘</span>K
          </kbd>
        </div>
      </div>
    </div>

    <Transition
      enter-active-class="transition ease-out duration-150"
      enter-from-class="opacity-0 scale-95 translate-y-1"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div 
        v-if="showResults && searchResults"
        class="absolute top-full mt-2 rounded-xl border border-border bg-popover shadow-2xl z-50 overflow-hidden
               w-[calc(100vw-2rem)] left-1/2 -translate-x-1/2 sm:left-0 sm:translate-x-0 sm:w-[500px]"
      >
        <div class="overflow-y-auto max-h-[60vh] sm:max-h-[600px]">
          
          <div v-if="!hasResults" class="p-8 sm:p-12 text-center">
            <div class="w-12 h-12 sm:w-16 sm:h-16 mx-auto mb-4 rounded-full bg-muted flex items-center justify-center">
              <Search class="w-6 h-6 sm:w-8 sm:h-8 text-muted-foreground/50" />
            </div>
            <p class="text-sm font-medium text-foreground mb-1">Tidak ada hasil</p>
            <p class="text-xs text-muted-foreground">Tidak ditemukan hasil untuk "{{ searchQuery }}"</p>
          </div>

          <div v-else>
            <div v-if="searchResults.employees?.length > 0" class="border-b border-border">
              <div class="px-4 py-3 bg-muted/50 sticky top-0 z-10 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                  <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Karyawan</h3>
                  <span class="text-xs text-muted-foreground">{{ searchResults.employees.length }} hasil</span>
                </div>
              </div>
              <div class="p-2">
                <div
                  v-for="employee in searchResults.employees"
                  :key="employee.id"
                  @click="goToResult(employee)"
                  class="flex items-center gap-3 p-3 rounded-lg hover:bg-accent cursor-pointer transition-all group"
                >
                  <div class="w-10 h-10 shrink-0 rounded-lg bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center text-sm font-bold text-primary">
                    {{ getInitials(employee.name) }}
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold truncate group-hover:text-primary transition-colors">{{ employee.name }}</p>
                    <div class="flex items-center gap-2 mt-0.5">
                      <p class="text-xs text-muted-foreground truncate">{{ employee.email }}</p>
                    </div>
                  </div>
                  <div class="flex flex-col items-end gap-1 shrink-0">
                    <span class="text-[10px] sm:text-xs font-medium text-muted-foreground px-2 py-1 bg-muted rounded">{{ employee.posisi }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="searchResults.attendances?.length > 0" class="border-b border-border">
              <div class="px-4 py-3 bg-muted/50 sticky top-0 z-10 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                  <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Kehadiran</h3>
                  <span class="text-xs text-muted-foreground">{{ searchResults.attendances.length }} hasil</span>
                </div>
              </div>
              <div class="p-2">
                <div
                  v-for="attendance in searchResults.attendances"
                  :key="attendance.id"
                  @click="goToResult(attendance)"
                  class="flex items-center gap-3 p-3 rounded-lg hover:bg-accent cursor-pointer transition-all group"
                >
                  <div class="w-10 h-10 shrink-0 rounded-lg bg-gradient-to-br from-green-500/20 to-green-500/5 flex items-center justify-center">
                    <Clock class="w-5 h-5 text-green-600" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold truncate group-hover:text-primary transition-colors">{{ attendance.employee_name }}</p>
                    <p class="text-xs text-muted-foreground mt-0.5">{{ formatDate(attendance.date) }}</p>
                  </div>
                  <span class="text-[10px] sm:text-xs font-medium px-2.5 py-1 rounded-md whitespace-nowrap shrink-0" :class="getStatusClass(attendance.status)">
                    {{ attendance.status }}
                  </span>
                </div>
              </div>
            </div>

            <div v-if="searchResults.leaves?.length > 0" class="border-b border-border">
              <div class="px-4 py-3 bg-muted/50 sticky top-0 z-10 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                  <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Cuti</h3>
                  <span class="text-xs text-muted-foreground">{{ searchResults.leaves.length }} hasil</span>
                </div>
              </div>
              <div class="p-2">
                <div
                  v-for="leave in searchResults.leaves"
                  :key="leave.id"
                  @click="goToResult(leave)"
                  class="flex items-center gap-3 p-3 rounded-lg hover:bg-accent cursor-pointer transition-all group"
                >
                  <div class="w-10 h-10 shrink-0 rounded-lg bg-gradient-to-br from-blue-500/20 to-blue-500/5 flex items-center justify-center">
                    <Calendar class="w-5 h-5 text-blue-600" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold truncate group-hover:text-primary transition-colors">{{ leave.employee_name }}</p>
                    <p class="text-xs text-muted-foreground truncate mt-0.5">{{ leave.reason }}</p>
                  </div>
                  <span class="text-[10px] sm:text-xs font-medium px-2.5 py-1 rounded-md whitespace-nowrap shrink-0" :class="getStatusClass(leave.status)">
                    {{ leave.status }}
                  </span>
                </div>
              </div>
            </div>

            <div v-if="searchResults.overtimes?.length > 0">
              <div class="px-4 py-3 bg-muted/50 sticky top-0 z-10 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                  <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Lembur</h3>
                  <span class="text-xs text-muted-foreground">{{ searchResults.overtimes.length }} hasil</span>
                </div>
              </div>
              <div class="p-2">
                <div
                  v-for="overtime in searchResults.overtimes"
                  :key="overtime.id"
                  @click="goToResult(overtime)"
                  class="flex items-center gap-3 p-3 rounded-lg hover:bg-accent cursor-pointer transition-all group"
                >
                  <div class="w-10 h-10 shrink-0 rounded-lg bg-gradient-to-br from-purple-500/20 to-purple-500/5 flex items-center justify-center">
                    <ClipboardList class="w-5 h-5 text-purple-600" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold truncate group-hover:text-primary transition-colors">{{ overtime.employee_name }}</p>
                    <p class="text-xs text-muted-foreground truncate mt-0.5">{{ overtime.description }}</p>
                  </div>
                  <span class="text-[10px] sm:text-xs font-medium text-muted-foreground px-2.5 py-1 bg-muted rounded shrink-0">{{ overtime.hours }} jam</span>
                </div>
              </div>
            </div>
            </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { computed } from 'vue'
// Pastikan path composable ini benar sesuai struktur project Anda
import { useSearch } from '@/composables/useSearch'
import { Search, X, Clock, Calendar, ClipboardList } from 'lucide-vue-next'

const { searchQuery, searchResults, loading, showResults, hasResults, search, clearSearch, goToResult } = useSearch()

let searchTimeout = null

const handleInput = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => { search() }, 300)
}

const handleSearch = () => { search() }
const closeResults = () => { showResults.value = false }

const getInitials = (name) => {
  if (!name) return '??'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const getStatusClass = (status) => {
  const classes = {
    'approved': 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400',
    'pending': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-400',
    'rejected': 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400',
    'hadir': 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400',
    'izin': 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400',
    'sakit': 'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-400',
    'alpha': 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400'
  }
  return classes[status?.toLowerCase()] || 'bg-muted text-muted-foreground'
}

// Custom Directive untuk mendeteksi klik di luar elemen
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => { if (!(el === event.target || el.contains(event.target))) binding.value() }
    document.addEventListener('click', el.clickOutsideEvent)
  },
  unmounted(el) { document.removeEventListener('click', el.clickOutsideEvent) }
}
</script>