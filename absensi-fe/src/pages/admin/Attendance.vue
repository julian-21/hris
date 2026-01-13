<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Attendance</h1>
        <p class="text-sm text-slate-600 mt-1">Manage your daily attendance records</p>
      </div>
      <div class="flex items-center gap-3">
        <div class="px-4 py-2 bg-white rounded-lg border border-slate-200">
          <p class="text-xs text-slate-500 mb-0.5">Today</p>
          <p class="text-sm font-semibold text-slate-900">{{ formatDate(new Date()) }}</p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 shadow-sm">
      <div class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-base font-semibold text-slate-900">Today's Attendance</h2>
      </div>

      <div class="p-6">
        <div v-if="loadingToday" class="text-center py-12">
          <Loader2 class="w-8 h-8 text-slate-400 animate-spin mx-auto mb-3" />
          <p class="text-sm text-slate-600">Loading attendance data...</p>
        </div>

        <div v-else-if="todayAttendance && todayAttendance.check_in" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-emerald-50 rounded-lg p-4 border border-emerald-200">
              <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-emerald-900">Check In</span>
                <div class="p-2 bg-emerald-600 rounded-lg">
                  <LogIn class="w-4 h-4 text-white" />
                </div>
              </div>
              <p class="text-2xl font-bold text-emerald-900 mb-1">
                {{ formatTime(todayAttendance.check_in) }}
              </p>
              <p class="text-xs text-emerald-700 flex items-center gap-1">
                <Check class="w-3 h-3" /> Successfully checked in
              </p>
            </div>

            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
              <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-blue-900">Check Out</span>
                <div class="p-2 bg-blue-600 rounded-lg">
                  <LogOut class="w-4 h-4 text-white" />
                </div>
              </div>
              <p class="text-2xl font-bold text-blue-900 mb-1">
                {{ todayAttendance.check_out ? formatTime(todayAttendance.check_out) : '--:--' }}
              </p>
              <p class="text-xs text-blue-700 flex items-center gap-1">
                <Clock class="w-3 h-3" /> {{ todayAttendance.check_out ? 'Completed' : 'Not yet' }}
              </p>
            </div>

            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
              <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-slate-900">Location</span>
                <div class="p-2 bg-slate-600 rounded-lg">
                  <MapPin class="w-4 h-4 text-white" />
                </div>
              </div>
              <p class="text-lg font-bold text-slate-900 mb-1 truncate">
                {{ todayAttendance.kantor?.nama || 'N/A' }}
              </p>
              <p class="text-xs text-slate-600 flex items-center gap-1">
                <Building2 class="w-3 h-3" /> {{ todayAttendance.out_of_office ? 'Outside office' : 'At office' }}
              </p>
            </div>
          </div>

          <div v-if="!todayAttendance.check_out">
            <button
              @click="handleCheckOut"
              :disabled="checkingOut"
              class="w-full bg-slate-900 hover:bg-slate-800 text-white font-medium py-3 px-4 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              <LogOut v-if="!checkingOut" class="w-5 h-5" />
              <Loader2 v-else class="w-5 h-5 animate-spin" />
              <span>{{ checkingOut ? 'Processing...' : 'Check Out Now' }}</span>
            </button>
          </div>

          <div v-else class="flex items-center justify-center gap-3 p-4 bg-emerald-50 rounded-lg border border-emerald-200">
            <div class="p-2 bg-emerald-600 rounded-lg">
              <Check class="w-5 h-5 text-white" />
            </div>
            <div>
              <p class="font-semibold text-emerald-900">Attendance Completed</p>
              <p class="text-sm text-emerald-700">Have a great day!</p>
            </div>
          </div>
        </div>

        <div v-else class="space-y-6">
          <div class="text-center py-6">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-slate-100 rounded-full mb-4">
              <Calendar class="w-8 h-8 text-slate-600" />
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-2">Ready to Start Your Day?</h3>
            <p class="text-sm text-slate-600">You haven't checked in today yet</p>
          </div>

          <div v-if="!currentLocation">
            <button
              @click="handleAutoCheckIn"
              :disabled="checkingIn"
              class="w-full bg-slate-900 hover:bg-slate-800 text-white font-medium py-3 px-4 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              <MapPin v-if="!checkingIn" class="w-5 h-5" />
              <Loader2 v-else class="w-5 h-5 animate-spin" />
              <span>{{ checkingIn ? 'Getting Location...' : 'Check In Now' }}</span>
            </button>
            <p class="text-xs text-center text-slate-500 mt-2 flex items-center justify-center gap-1">
              <Info class="w-3 h-3" /> System will automatically detect your nearest office
            </p>
          </div>

          <div v-if="locationError" class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex gap-3">
              <AlertCircle class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
              <div class="flex-1">
                <p class="text-sm font-semibold text-red-900 mb-2">{{ locationError }}</p>
                <div v-if="locationError.includes('timeout')" class="mt-3 space-y-2 text-xs text-red-800">
                  <p class="font-medium">How to fix:</p>
                  <ul class="list-disc ml-4 space-y-1">
                    <li>Make sure you're in an open area</li>
                    <li>Enable Location Services / GPS</li>
                    <li>Close other GPS apps</li>
                    <li>Wait for GPS to lock</li>
                  </ul>
                  <button @click="handleAutoCheckIn" class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-md transition-colors">
                    <RotateCw class="w-3 h-3" /> Try Again
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div v-if="currentLocation" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex gap-3">
              <MapPin class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
              <div class="flex-1">
                <p class="text-sm font-semibold text-blue-900 flex items-center gap-2">
                  <Check class="w-4 h-4" /> Location Acquired
                </p>
                <p class="text-xs text-blue-700 mt-1 font-mono">
                  {{ currentLocation.latitude.toFixed(6) }}, {{ currentLocation.longitude.toFixed(6) }}
                </p>
              </div>
            </div>
          </div>

          <div v-if="detectedKantor" class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
            <div class="flex gap-3">
              <Building2 class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" />
              <div class="flex-1">
                <p class="text-sm font-semibold text-emerald-900">Office Detected: {{ detectedKantor.nama }}</p>
                <p class="text-xs text-emerald-700 mt-1">
                  Distance: {{ formatDistance(detectedKantor.distance) }} (Within {{ detectedKantor.radius }}m radius)
                </p>
              </div>
            </div>
          </div>

          <div v-if="isOutOfOffice" class="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <div class="flex gap-3">
              <AlertTriangle class="w-5 h-5 text-orange-600 flex-shrink-0 mt-0.5" />
              <div class="flex-1">
                <p class="text-sm font-semibold text-orange-900">Outside Office Area</p>
                <p class="text-xs text-orange-700 mt-1">
                  Nearest: {{ formatDistance(nearestOfficeDistance) }} from {{ nearestOfficeName }}
                </p>
                <p class="text-xs text-orange-800 mt-2 font-medium">Note required (minimum 10 characters)</p>
              </div>
            </div>
          </div>

          <div v-if="currentLocation" class="space-y-2">
            <label class="block text-sm font-medium text-slate-900">
              <div class="flex items-center justify-between mb-2">
                <span class="flex items-center gap-2">
                  Attendance Note <span v-if="isOutOfOffice" class="text-red-600 text-xs">* Required</span> <span v-else class="text-slate-500 text-xs">(optional)</span>
                </span>
                <span v-if="outOfOfficeReason.length > 0" :class="['text-xs font-medium', outOfOfficeReason.length >= 10 ? 'text-emerald-600' : 'text-red-600']">
                  {{ outOfOfficeReason.length }} chars
                </span>
              </div>
            </label>
            <textarea
              v-model="outOfOfficeReason"
              rows="3"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-slate-900 transition-all text-sm"
              :placeholder="getReasonPlaceholder()"
            ></textarea>
            <div v-if="isOutOfOffice">
              <p v-if="outOfOfficeReason.length > 0 && outOfOfficeReason.length < 10" class="text-xs text-red-600 flex items-center gap-1">
                <X class="w-3 h-3" /> Too short ({{ outOfOfficeReason.length }}/10 minimum)
              </p>
              <p v-else-if="outOfOfficeReason.length >= 10" class="text-xs text-emerald-600 flex items-center gap-1">
                <Check class="w-3 h-3" /> Valid note
              </p>
              <p v-else class="text-xs text-slate-500">Minimum 10 characters required</p>
            </div>
          </div>

          <div v-if="currentLocation">
            <button
              @click="confirmCheckIn"
              :disabled="!canCheckIn || processingCheckIn"
              :class="['w-full font-medium py-3 px-4 rounded-lg transition-all flex items-center justify-center gap-2', canCheckIn && !processingCheckIn ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-slate-200 text-slate-400 cursor-not-allowed']"
            >
              <Check v-if="!processingCheckIn" class="w-5 h-5" />
              <Loader2 v-else class="w-5 h-5 animate-spin" />
              <span>{{ processingCheckIn ? 'Saving...' : 'Confirm Check In' }}</span>
            </button>
          </div>

          <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
            <p class="text-sm font-medium text-slate-900 mb-3 flex items-center gap-2">
              <Info class="w-4 h-4" /> Check-In Steps
            </p>
            <ol class="space-y-2 text-xs text-slate-700">
              <li :class="['flex items-start gap-2', currentLocation ? 'text-emerald-700 font-medium' : '']">
                <span class="flex-shrink-0">{{ currentLocation ? '✓' : '1.' }}</span> <span>Click "Check In Now" and allow location access</span>
              </li>
              <li :class="['flex items-start gap-2', currentLocation ? 'text-emerald-700 font-medium' : '']">
                <span class="flex-shrink-0">{{ currentLocation ? '✓' : '2.' }}</span> <span>System automatically detects nearest office</span>
              </li>
              <li v-if="isOutOfOffice" :class="['flex items-start gap-2', outOfOfficeReason.length >= 10 ? 'text-emerald-700 font-medium' : '']">
                <span class="flex-shrink-0">{{ outOfOfficeReason.length >= 10 ? '✓' : '3.' }}</span> <span>Enter note (minimum 10 characters)</span>
              </li>
              <li :class="['flex items-start gap-2', canCheckIn ? 'text-emerald-700 font-medium' : '']">
                <span class="flex-shrink-0">{{ canCheckIn ? '✓' : '4.' }}</span> <span>Confirm check-in</span>
              </li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 shadow-sm">
      <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50/50">
        <h2 class="text-base font-semibold text-slate-900">Attendance History</h2>
        <div v-if="loadingHistory" class="flex items-center gap-2 text-xs text-slate-500 animate-pulse">
            <Loader2 class="w-3 h-3 animate-spin" /> Updating...
        </div>
      </div>
      
      <div class="overflow-x-auto relative">
        <div v-if="loadingHistory" class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 flex items-center justify-center transition-all duration-300">
            </div>

        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Date</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Check In</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Check Out</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Office</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Location Status</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-slate-100">
            <tr v-for="attendance in attendances" :key="attendance.id" class="hover:bg-slate-50/80 transition-colors duration-150">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                {{ formatDate(attendance.tanggal) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 font-mono">
                {{ formatTime(attendance.check_in) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 font-mono">
                <span v-if="attendance.check_out">{{ formatTime(attendance.check_out) }}</span>
                <span v-else class="text-slate-400">-</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">
                {{ attendance.kantor?.nama || '-' }}
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-col gap-1">
                  <span v-if="attendance.out_of_office" class="inline-flex items-center gap-1.5 w-fit px-2.5 py-0.5 bg-orange-50 text-orange-700 border border-orange-200 text-xs font-medium rounded-full">
                    <div class="w-1.5 h-1.5 bg-orange-500 rounded-full"></div>
                    Outside
                  </span>
                  <span v-else class="inline-flex items-center gap-1.5 w-fit px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-medium rounded-full">
                    <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></div>
                    At Office
                  </span>
                  <p v-if="attendance.out_of_office_reason" class="text-xs text-slate-500 italic truncate max-w-[150px]" :title="attendance.out_of_office_reason">
                    "{{ attendance.out_of_office_reason }}"
                  </p>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span 
                  :class="[
                    'inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded-full border',
                    attendance.check_out 
                      ? 'bg-slate-100 text-slate-700 border-slate-200' 
                      : 'bg-blue-50 text-blue-700 border-blue-200'
                  ]"
                >
                  <div :class="['w-1.5 h-1.5 rounded-full', attendance.check_out ? 'bg-slate-500' : 'bg-blue-500 animate-pulse']"></div>
                  {{ attendance.check_out ? 'Completed' : 'Active' }}
                </span>
              </td>
            </tr>
            <tr v-if="attendances.length === 0 && !loadingHistory">
              <td colspan="6" class="px-6 py-16 text-center">
                <div class="flex flex-col items-center justify-center">
                  <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mb-3">
                    <CalendarX class="w-6 h-6 text-slate-400" />
                  </div>
                  <p class="text-sm text-slate-900 font-medium">No records found</p>
                  <p class="text-xs text-slate-500 mt-1">Start by checking in today</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="pagination.total > 0" class="px-6 py-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4 bg-slate-50/50">
        <div class="text-sm text-slate-500">
          Showing <span class="font-medium text-slate-900">{{ pagination.from }}</span> to <span class="font-medium text-slate-900">{{ pagination.to }}</span> of <span class="font-medium text-slate-900">{{ pagination.total }}</span> results
        </div>
        
        <div class="flex items-center gap-2">
          <button 
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1 || loadingHistory"
            class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm"
          >
            <ChevronLeft class="w-4 h-4 mr-1" />
            Previous
          </button>
          
          <div class="hidden sm:flex items-center gap-1">
             <span class="px-3 py-2 text-sm font-medium text-slate-900 bg-slate-100 rounded-md border border-slate-200">
                Page {{ pagination.current_page }}
             </span>
          </div>

          <button 
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page || loadingHistory"
            class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm"
          >
            Next
            <ChevronRight class="w-4 h-4 ml-1" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useGeolocation } from '../../composables/useGeolocation'
import { useToast } from '@/composables/useToast'
import api from '../../services/api'
import { format } from 'date-fns'
import { 
  Calendar, 
  Check, 
  Clock, 
  LogIn, 
  LogOut, 
  MapPin, 
  Building2,
  Loader2,
  Info,
  AlertCircle,
  AlertTriangle,
  CalendarX,
  X,
  RotateCw,
  ChevronLeft, // Icon Baru
  ChevronRight // Icon Baru
} from 'lucide-vue-next'

const { toast } = useToast()
const { getCurrentPosition, calculateDistance } = useGeolocation()

// State Data
const todayAttendance = ref(null)
const attendances = ref([])
const kantors = ref([])
const currentLocation = ref(null)
const detectedKantor = ref(null)
const locationError = ref(null)
const outOfOfficeReason = ref('')

// Loading States
const loadingToday = ref(false)
const loadingHistory = ref(false) // State loading khusus tabel
const checkingIn = ref(false)
const processingCheckIn = ref(false)
const checkingOut = ref(false)

// Pagination Data
const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    from: 0,
    to: 0
})

// === COMPUTED PROPERTIES (TIDAK BERUBAH) ===
const isOutOfOffice = computed(() => currentLocation.value && !detectedKantor.value)

const nearestOfficeDistance = computed(() => {
  if (!currentLocation.value || kantors.value.length === 0) return 0
  const distances = kantors.value.map(kantor => ({
    ...kantor,
    distance: calculateDistance(currentLocation.value.latitude, currentLocation.value.longitude, kantor.latitude, kantor.longitude)
  }))
  return Math.min(...distances.map(d => d.distance))
})

const nearestOfficeName = computed(() => {
  if (!currentLocation.value || kantors.value.length === 0) return ''
  const distances = kantors.value.map(kantor => ({
    ...kantor,
    distance: calculateDistance(currentLocation.value.latitude, currentLocation.value.longitude, kantor.latitude, kantor.longitude)
  }))
  const nearest = distances.reduce((prev, curr) => prev.distance < curr.distance ? prev : curr)
  return nearest.nama
})

const canCheckIn = computed(() => {
  if (!currentLocation.value) return false
  if (detectedKantor.value) return true
  return outOfOfficeReason.value.trim().length >= 10
})

// === METHODS ===

// Handle Page Change (Pagination)
const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        fetchAttendances(page)
    }
}

// Fetch Attendances (Updated for Pagination)
const fetchAttendances = async (page = 1) => {
  loadingHistory.value = true
  try {
    const response = await api.get('/attendance', {
      params: { 
          per_page: 10,
          page: page 
      }
    })
    
    // Mapping Data
    attendances.value = response.data.data
    
    // Mapping Pagination Meta
    pagination.value = {
        current_page: response.data.current_page,
        last_page: response.data.last_page,
        total: response.data.total,
        from: response.data.from,
        to: response.data.to
    }
  } catch (error) {
    console.error('Error fetching attendances:', error)
    toast({
        title: 'Error Loading History',
        description: 'Failed to load attendance records.',
        variant: 'destructive'
    })
  } finally {
    loadingHistory.value = false
  }
}

// === FITUR ASLI (TIDAK BERUBAH) ===

const handleAutoCheckIn = async () => {
  checkingIn.value = true
  locationError.value = null
  currentLocation.value = null
  detectedKantor.value = null
  try {
    const position = await getCurrentPosition()
    currentLocation.value = position
    await detectNearestOffice()
  } catch (error) {
    locationError.value = error.message
    toast({ title: 'Location Error', description: error.message, variant: 'destructive' })
  } finally {
    checkingIn.value = false
  }
}

const detectNearestOffice = async () => {
  if (!currentLocation.value) return
  const officesWithDistance = kantors.value.map(kantor => {
    const distance = calculateDistance(
      currentLocation.value.latitude,
      currentLocation.value.longitude,
      kantor.latitude,
      kantor.longitude
    )
    return { ...kantor, distance, isWithinRadius: distance <= kantor.radius }
  })
  const officeWithinRadius = officesWithDistance.filter(o => o.isWithinRadius).sort((a, b) => a.distance - b.distance)[0]
  if (officeWithinRadius) {
    detectedKantor.value = officeWithinRadius
  } else {
    detectedKantor.value = null
  }
}

const confirmCheckIn = async () => {
  if (!canCheckIn.value) {
    toast({ title: 'Validation Failed', description: 'Please complete all steps first.', variant: 'destructive' })
    return
  }
  processingCheckIn.value = true
  try {
    const payload = {
      latitude: currentLocation.value.latitude,
      longitude: currentLocation.value.longitude,
      kantor_id: detectedKantor.value?.id || kantors.value[0]?.id
    }
    if (!detectedKantor.value) {
      payload.out_of_office = true
      payload.out_of_office_reason = outOfOfficeReason.value.trim()
    } else if (outOfOfficeReason.value.trim().length > 0) {
      payload.out_of_office = false
      payload.out_of_office_reason = outOfOfficeReason.value.trim()
    }
    const response = await api.post('/attendance', payload)
    todayAttendance.value = response.data.attendance
    toast({ title: 'Success', description: 'Check-in successful!', variant: 'default' })
    resetForm()
    fetchAttendances(1) // Refresh tabel ke halaman 1
  } catch (error) {
    const message = error.response?.data?.message || 'Check-in failed'
    toast({ title: 'Error', description: message, variant: 'destructive' })
  } finally {
    processingCheckIn.value = false
  }
}

const handleCheckOut = async () => {
  if (!confirm('Are you sure you want to check out?')) return
  checkingOut.value = true
  try {
    const position = await getCurrentPosition()
    const response = await api.post(`/attendance/${todayAttendance.value.id}/checkout`, {
      latitude: position.latitude,
      longitude: position.longitude
    })
    todayAttendance.value = response.data.attendance
    toast({ title: 'Success', description: 'Checked out successfully!', variant: 'default' })
    fetchAttendances(pagination.value.current_page) // Refresh halaman tabel saat ini
  } catch (error) {
    if (error.message && error.message.includes('timeout')) {
      if (confirm('GPS Timeout. Check out without location data?')) await handleCheckOutWithoutGPS()
    } else {
      const message = error.response?.data?.message || 'Check-out failed'
      toast({ title: 'Error', description: message, variant: 'destructive' })
    }
  } finally {
    checkingOut.value = false
  }
}

const handleCheckOutWithoutGPS = async () => {
  checkingOut.value = true
  try {
    const response = await api.post(`/attendance/${todayAttendance.value.id}/checkout`, { latitude: 0, longitude: 0 })
    todayAttendance.value = response.data.attendance
    toast({ title: 'Success', description: 'Checked out (No GPS)', variant: 'default' })
    fetchAttendances(pagination.value.current_page)
  } catch (error) {
    toast({ title: 'Error', description: 'Check-out failed', variant: 'destructive' })
  } finally {
    checkingOut.value = false
  }
}

const resetForm = () => {
  outOfOfficeReason.value = ''
  currentLocation.value = null
  detectedKantor.value = null
  locationError.value = null
}

const fetchTodayAttendance = async () => {
  loadingToday.value = true
  try {
    const response = await api.get('/attendance/today')
    todayAttendance.value = response.data
  } catch (error) {
    console.error('Error fetching today data', error)
  } finally {
    loadingToday.value = false
  }
}

const fetchKantors = async () => {
  try {
    const response = await api.get('/kantors')
    kantors.value = Array.isArray(response.data) ? response.data : (response.data.data || [])
  } catch (error) {
    console.error('Error fetching offices', error)
  }
}

// Helpers Formatter
const formatDate = (date) => format(new Date(date), 'dd MMM yyyy')
const formatTime = (datetime) => datetime ? format(new Date(datetime), 'HH:mm') : '-'
const formatDistance = (meters) => meters < 1000 ? `${Math.round(meters)}m` : `${(meters / 1000).toFixed(2)}km`
const getReasonPlaceholder = () => {
    return isOutOfOffice.value ? "Example: Client meeting at [Location] until 2 PM" : "Optional note"
}

onMounted(() => {
  fetchTodayAttendance()
  fetchAttendances(1)
  fetchKantors()
})
</script>