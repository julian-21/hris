<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold text-zinc-900 tracking-tight">Attendance</h1>
        <p class="text-sm text-zinc-600 mt-1.5 flex items-center gap-2">
          <Clock class="w-4 h-4" />
          Manage your daily attendance records
        </p>
      </div>
      <div class="flex items-center gap-3">
        <div class="px-5 py-3 bg-gradient-to-br from-white to-zinc-50 rounded-xl border border-zinc-200/60 shadow-sm">
          <p class="text-xs font-semibold text-zinc-500 mb-0.5 uppercase tracking-wider">Today</p>
          <p class="text-sm font-bold text-zinc-900">{{ formatDate(new Date()) }}</p>
        </div>
      </div>
    </div>

    <!-- Today's Attendance Card -->
    <div class="bg-gradient-to-br from-white to-zinc-50/30 rounded-2xl border border-zinc-200/60 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-zinc-200/60 bg-white/50 backdrop-blur-sm">
        <h2 class="text-lg font-bold text-zinc-900 flex items-center gap-2">
          <div class="p-1.5 bg-zinc-900 rounded-lg">
            <Calendar class="w-4 h-4 text-white" />
          </div>
          Today's Attendance
        </h2>
      </div>

      <div class="p-6">
        <!-- Loading State -->
        <div v-if="loadingToday" class="text-center py-16">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-zinc-100 rounded-2xl mb-4">
            <Loader2 class="w-8 h-8 text-zinc-400 animate-spin" />
          </div>
          <p class="text-sm font-medium text-zinc-900">Loading attendance data...</p>
          <p class="text-xs text-zinc-500 mt-1">Please wait a moment</p>
        </div>

        <!-- Already Checked In -->
        <div v-else-if="todayAttendance && todayAttendance.check_in" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Check In Card -->
            <div class="relative bg-gradient-to-br from-emerald-50 to-emerald-100/50 rounded-xl p-5 border border-emerald-200/60 shadow-sm overflow-hidden group hover:shadow-md transition-shadow duration-300">
              <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-200/20 rounded-full -mr-16 -mt-16 group-hover:scale-110 transition-transform duration-500"></div>
              <div class="relative">
                <div class="flex items-center justify-between mb-4">
                  <span class="text-sm font-bold text-emerald-900">Check In</span>
                  <div class="p-2.5 bg-gradient-to-br from-emerald-600 to-emerald-700 rounded-xl shadow-lg shadow-emerald-600/30">
                    <LogIn class="w-5 h-5 text-white" />
                  </div>
                </div>
                <p class="text-3xl font-bold text-emerald-900 mb-2 tracking-tight">
                  {{ formatTime(todayAttendance.check_in) }}
                </p>
                <p class="text-xs text-emerald-700 flex items-center gap-1.5 font-medium">
                  <Check class="w-3.5 h-3.5" /> Successfully checked in
                </p>
              </div>
            </div>

            <!-- Check Out Card -->
            <div class="relative bg-gradient-to-br from-blue-50 to-blue-100/50 rounded-xl p-5 border border-blue-200/60 shadow-sm overflow-hidden group hover:shadow-md transition-shadow duration-300">
              <div class="absolute top-0 right-0 w-32 h-32 bg-blue-200/20 rounded-full -mr-16 -mt-16 group-hover:scale-110 transition-transform duration-500"></div>
              <div class="relative">
                <div class="flex items-center justify-between mb-4">
                  <span class="text-sm font-bold text-blue-900">Check Out</span>
                  <div class="p-2.5 bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl shadow-lg shadow-blue-600/30">
                    <LogOut class="w-5 h-5 text-white" />
                  </div>
                </div>
                <p class="text-3xl font-bold text-blue-900 mb-2 tracking-tight">
                  {{ todayAttendance.check_out ? formatTime(todayAttendance.check_out) : '--:--' }}
                </p>
                <p class="text-xs text-blue-700 flex items-center gap-1.5 font-medium">
                  <Clock class="w-3.5 h-3.5" /> {{ todayAttendance.check_out ? 'Completed' : 'Not yet' }}
                </p>
              </div>
            </div>

            <!-- Location Card -->
            <div class="relative bg-gradient-to-br from-zinc-50 to-zinc-100/50 rounded-xl p-5 border border-zinc-200/60 shadow-sm overflow-hidden group hover:shadow-md transition-shadow duration-300">
              <div class="absolute top-0 right-0 w-32 h-32 bg-zinc-200/20 rounded-full -mr-16 -mt-16 group-hover:scale-110 transition-transform duration-500"></div>
              <div class="relative">
                <div class="flex items-center justify-between mb-4">
                  <span class="text-sm font-bold text-zinc-900">Location</span>
                  <div class="p-2.5 bg-gradient-to-br from-zinc-700 to-zinc-800 rounded-xl shadow-lg shadow-zinc-700/30">
                    <MapPin class="w-5 h-5 text-white" />
                  </div>
                </div>
                <p class="text-lg font-bold text-zinc-900 mb-2 truncate">
                  {{ todayAttendance.kantor?.nama || 'N/A' }}
                </p>
                <p class="text-xs text-zinc-600 flex items-center gap-1.5 font-medium">
                  <Building2 class="w-3.5 h-3.5" /> {{ todayAttendance.out_of_office ? 'Outside office' : 'At office' }}
                </p>
              </div>
            </div>
          </div>

          <!-- Check Out Button or Completion Message -->
          <div v-if="!todayAttendance.check_out">
            <button
              @click="handleCheckOut"
              :disabled="checkingOut"
              class="w-full bg-gradient-to-r from-zinc-900 to-zinc-800 hover:from-zinc-800 hover:to-zinc-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3 shadow-lg shadow-zinc-900/20 hover:shadow-xl hover:shadow-zinc-900/30 active:scale-[0.98]"
            >
              <LogOut v-if="!checkingOut" class="w-5 h-5" />
              <Loader2 v-else class="w-5 h-5 animate-spin" />
              <span>{{ checkingOut ? 'Processing Check Out...' : 'Check Out Now' }}</span>
            </button>
          </div>

          <div v-else class="flex items-center gap-4 p-5 bg-gradient-to-r from-emerald-50 to-emerald-100/50 rounded-xl border border-emerald-200/60 shadow-sm">
            <div class="p-3 bg-gradient-to-br from-emerald-600 to-emerald-700 rounded-xl shadow-lg shadow-emerald-600/30">
              <Check class="w-6 h-6 text-white" />
            </div>
            <div>
              <p class="font-bold text-emerald-900 text-lg">Attendance Completed</p>
              <p class="text-sm text-emerald-700 font-medium">Have a great day ahead!</p>
            </div>
          </div>
        </div>

        <!-- Not Checked In Yet -->
        <div v-else class="space-y-6">
          <div class="text-center py-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-zinc-100 to-zinc-200 rounded-2xl mb-5 shadow-sm">
              <Calendar class="w-10 h-10 text-zinc-600" />
            </div>
            <h3 class="text-xl font-bold text-zinc-900 mb-2">Ready to Start Your Day?</h3>
            <p class="text-sm text-zinc-600">You haven't checked in today yet</p>
          </div>

          <!-- Get Location Button -->
          <div v-if="!currentLocation">
            <button
              @click="handleAutoCheckIn"
              :disabled="checkingIn"
              class="w-full bg-gradient-to-r from-zinc-900 to-zinc-800 hover:from-zinc-800 hover:to-zinc-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3 shadow-lg shadow-zinc-900/20 hover:shadow-xl hover:shadow-zinc-900/30 active:scale-[0.98]"
            >
              <MapPin v-if="!checkingIn" class="w-5 h-5" />
              <Loader2 v-else class="w-5 h-5 animate-spin" />
              <span>{{ checkingIn ? 'Getting Your Location...' : 'Check In Now' }}</span>
            </button>
            <p class="text-xs text-center text-zinc-500 mt-3 flex items-center justify-center gap-1.5">
              <Info class="w-3.5 h-3.5" /> System will automatically detect your nearest office
            </p>
          </div>

          <!-- Location Error -->
          <div v-if="locationError" class="bg-gradient-to-br from-red-50 to-red-100/50 border border-red-200/60 rounded-xl p-5 shadow-sm">
            <div class="flex gap-3">
              <AlertCircle class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
              <div class="flex-1">
                <p class="text-sm font-bold text-red-900 mb-2">{{ locationError }}</p>
                <div v-if="locationError.includes('timeout')" class="mt-4 space-y-2 text-xs text-red-800">
                  <p class="font-semibold">How to fix:</p>
                  <ul class="list-disc ml-4 space-y-1.5 text-red-700">
                    <li>Make sure you're in an open area</li>
                    <li>Enable Location Services / GPS</li>
                    <li>Close other GPS apps</li>
                    <li>Wait for GPS to lock</li>
                  </ul>
                  <button 
                    @click="handleAutoCheckIn" 
                    class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg transition-all duration-200 shadow-sm hover:shadow-md active:scale-95"
                  >
                    <RotateCw class="w-3.5 h-3.5" /> Try Again
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Location Acquired -->
          <div v-if="currentLocation" class="bg-gradient-to-br from-blue-50 to-blue-100/50 border border-blue-200/60 rounded-xl p-5 shadow-sm">
            <div class="flex gap-3">
              <MapPin class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
              <div class="flex-1">
                <p class="text-sm font-bold text-blue-900 flex items-center gap-2 mb-1">
                  <Check class="w-4 h-4" /> Location Acquired
                </p>
                <p class="text-xs text-blue-700 font-mono bg-blue-100/50 px-2 py-1 rounded-md inline-block">
                  {{ currentLocation.latitude.toFixed(6) }}, {{ currentLocation.longitude.toFixed(6) }}
                </p>
              </div>
            </div>
          </div>

          <!-- Office Detected -->
          <div v-if="detectedKantor" class="bg-gradient-to-br from-emerald-50 to-emerald-100/50 border border-emerald-200/60 rounded-xl p-5 shadow-sm">
            <div class="flex gap-3">
              <Building2 class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" />
              <div class="flex-1">
                <p class="text-sm font-bold text-emerald-900 mb-1">Office Detected: {{ detectedKantor.nama }}</p>
                <p class="text-xs text-emerald-700">
                  Distance: <span class="font-semibold">{{ formatDistance(detectedKantor.distance) }}</span> (Within {{ detectedKantor.radius }}m radius)
                </p>
              </div>
            </div>
          </div>

          <!-- Outside Office Warning -->
          <div v-if="isOutOfOffice" class="bg-gradient-to-br from-orange-50 to-orange-100/50 border border-orange-200/60 rounded-xl p-5 shadow-sm">
            <div class="flex gap-3">
              <AlertTriangle class="w-5 h-5 text-orange-600 flex-shrink-0 mt-0.5" />
              <div class="flex-1">
                <p class="text-sm font-bold text-orange-900 mb-1">Outside Office Area</p>
                <p class="text-xs text-orange-700 mb-2">
                  Nearest: <span class="font-semibold">{{ formatDistance(nearestOfficeDistance) }}</span> from {{ nearestOfficeName }}
                </p>
                <p class="text-xs text-orange-800 font-semibold bg-orange-100/50 px-2 py-1 rounded-md inline-block">
                  Note required (minimum 10 characters)
                </p>
              </div>
            </div>
          </div>

          <!-- Attendance Note Input -->
          <div v-if="currentLocation" class="space-y-3">
            <label class="block">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-bold text-zinc-900 flex items-center gap-2">
                  Attendance Note 
                  <span v-if="isOutOfOffice" class="px-2 py-0.5 bg-red-100 text-red-700 text-[10px] font-bold rounded-full border border-red-200">* Required</span>
                  <span v-else class="text-zinc-500 text-xs font-normal">(optional)</span>
                </span>
                <span 
                  v-if="outOfOfficeReason.length > 0" 
                  :class="[
                    'text-xs font-bold px-2 py-0.5 rounded-full',
                    outOfOfficeReason.length >= 10 ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-red-100 text-red-700 border border-red-200'
                  ]"
                >
                  {{ outOfOfficeReason.length }} chars
                </span>
              </div>
            </label>
            <textarea
              v-model="outOfOfficeReason"
              rows="3"
              class="w-full px-4 py-3 border border-zinc-300 rounded-xl focus:ring-2 focus:ring-zinc-900 focus:border-zinc-900 transition-all text-sm bg-white shadow-sm resize-none"
              :placeholder="getReasonPlaceholder()"
            ></textarea>
            <div v-if="isOutOfOffice">
              <p v-if="outOfOfficeReason.length > 0 && outOfOfficeReason.length < 10" class="text-xs text-red-600 flex items-center gap-1.5 font-medium">
                <X class="w-3.5 h-3.5" /> Too short ({{ outOfOfficeReason.length }}/10 minimum)
              </p>
              <p v-else-if="outOfOfficeReason.length >= 10" class="text-xs text-emerald-600 flex items-center gap-1.5 font-medium">
                <Check class="w-3.5 h-3.5" /> Valid note
              </p>
              <p v-else class="text-xs text-zinc-500">Minimum 10 characters required</p>
            </div>
          </div>

          <!-- Confirm Check In Button -->
          <div v-if="currentLocation">
            <button
              @click="confirmCheckIn"
              :disabled="!canCheckIn || processingCheckIn"
              :class="[
                'w-full font-semibold py-4 px-6 rounded-xl transition-all duration-300 flex items-center justify-center gap-3 shadow-lg active:scale-[0.98]',
                canCheckIn && !processingCheckIn 
                  ? 'bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white shadow-emerald-600/30 hover:shadow-xl hover:shadow-emerald-600/40' 
                  : 'bg-zinc-200 text-zinc-400 cursor-not-allowed shadow-none'
              ]"
            >
              <Check v-if="!processingCheckIn" class="w-5 h-5" />
              <Loader2 v-else class="w-5 h-5 animate-spin" />
              <span>{{ processingCheckIn ? 'Processing...' : 'Confirm Check In' }}</span>
            </button>
          </div>

          <!-- Instructions -->
          <div class="bg-gradient-to-br from-zinc-50 to-zinc-100/50 rounded-xl p-5 border border-zinc-200/60 shadow-sm">
            <p class="text-sm font-bold text-zinc-900 mb-4 flex items-center gap-2">
              <Info class="w-4 h-4" /> Check-In Steps
            </p>
            <ol class="space-y-3 text-xs text-zinc-700">
              <li :class="['flex items-start gap-2.5 transition-all duration-200', currentLocation ? 'text-emerald-700 font-semibold' : '']">
                <span class="flex-shrink-0 flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold" :class="currentLocation ? 'bg-emerald-600 text-white' : 'bg-zinc-200 text-zinc-600'">
                  {{ currentLocation ? '✓' : '1' }}
                </span> 
                <span>Click "Check In Now" and allow location access</span>
              </li>
              <li :class="['flex items-start gap-2.5 transition-all duration-200', currentLocation ? 'text-emerald-700 font-semibold' : '']">
                <span class="flex-shrink-0 flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold" :class="currentLocation ? 'bg-emerald-600 text-white' : 'bg-zinc-200 text-zinc-600'">
                  {{ currentLocation ? '✓' : '2' }}
                </span>
                <span>System automatically detects nearest office</span>
              </li>
              <li v-if="isOutOfOffice" :class="['flex items-start gap-2.5 transition-all duration-200', outOfOfficeReason.length >= 10 ? 'text-emerald-700 font-semibold' : '']">
                <span class="flex-shrink-0 flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold" :class="outOfOfficeReason.length >= 10 ? 'bg-emerald-600 text-white' : 'bg-zinc-200 text-zinc-600'">
                  {{ outOfOfficeReason.length >= 10 ? '✓' : '3' }}
                </span>
                <span>Enter note (minimum 10 characters)</span>
              </li>
              <li :class="['flex items-start gap-2.5 transition-all duration-200', canCheckIn ? 'text-emerald-700 font-semibold' : '']">
                <span class="flex-shrink-0 flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold" :class="canCheckIn ? 'bg-emerald-600 text-white' : 'bg-zinc-200 text-zinc-600'">
                  {{ canCheckIn ? '✓' : isOutOfOffice ? '4' : '3' }}
                </span>
                <span>Confirm check-in</span>
              </li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Attendance History Table -->
    <div class="bg-gradient-to-br from-white to-zinc-50/30 rounded-2xl border border-zinc-200/60 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-zinc-200/60 flex justify-between items-center bg-white/50 backdrop-blur-sm">
        <h2 class="text-lg font-bold text-zinc-900 flex items-center gap-2">
          <div class="p-1.5 bg-zinc-900 rounded-lg">
            <Clock class="w-4 h-4 text-white" />
          </div>
          Attendance History
        </h2>
        <div v-if="loadingHistory" class="flex items-center gap-2 text-xs text-zinc-500">
          <Loader2 class="w-3.5 h-3.5 animate-spin" /> 
          <span class="font-medium">Updating...</span>
        </div>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-200">
          <thead class="bg-gradient-to-r from-zinc-50 to-zinc-100/50">
            <tr>
              <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-zinc-700 uppercase tracking-wider">Date</th>
              <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-zinc-700 uppercase tracking-wider">Employee</th>
              <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-zinc-700 uppercase tracking-wider">Check In</th>
              <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-zinc-700 uppercase tracking-wider">Check Out</th>
              <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-zinc-700 uppercase tracking-wider">Office</th>
              <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-zinc-700 uppercase tracking-wider">Location</th>
              <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-zinc-700 uppercase tracking-wider">Status</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-zinc-100">
            <tr 
              v-for="attendance in attendances" 
              :key="attendance.id" 
              class="hover:bg-zinc-50/80 transition-all duration-200 group"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <Calendar class="w-4 h-4 text-zinc-400 group-hover:text-zinc-600 transition-colors" />
                  <span class="text-sm font-semibold text-zinc-900">{{ formatDate(attendance.tanggal) }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-zinc-200 to-zinc-300 flex items-center justify-center text-xs font-bold text-zinc-700 shadow-sm">
                    {{ getInitials(attendance.user?.name) }}
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-zinc-900">{{ attendance.user?.name || 'Unknown' }}</p>
                    <p class="text-xs text-zinc-500">{{ attendance.user?.email || '-' }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <div class="p-1.5 bg-emerald-100 rounded-lg">
                    <LogIn class="w-3.5 h-3.5 text-emerald-600" />
                  </div>
                  <span class="text-sm text-zinc-900 font-mono font-semibold">{{ formatTime(attendance.check_in) }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div v-if="attendance.check_out" class="flex items-center gap-2">
                  <div class="p-1.5 bg-blue-100 rounded-lg">
                    <LogOut class="w-3.5 h-3.5 text-blue-600" />
                  </div>
                  <span class="text-sm text-zinc-900 font-mono font-semibold">{{ formatTime(attendance.check_out) }}</span>
                </div>
                <span v-else class="text-sm text-zinc-400 font-medium">-</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <Building2 class="w-4 h-4 text-zinc-400" />
                  <span class="text-sm text-zinc-900 font-medium">{{ attendance.kantor?.nama || '-' }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-col gap-2">
                  <span 
                    v-if="attendance.out_of_office" 
                    class="inline-flex items-center gap-1.5 w-fit px-3 py-1.5 bg-gradient-to-r from-orange-50 to-orange-100 text-orange-700 border border-orange-200/60 text-xs font-bold rounded-lg shadow-sm"
                  >
                    <div class="w-1.5 h-1.5 bg-orange-500 rounded-full animate-pulse"></div>
                    Outside
                  </span>
                  <span 
                    v-else 
                    class="inline-flex items-center gap-1.5 w-fit px-3 py-1.5 bg-gradient-to-r from-emerald-50 to-emerald-100 text-emerald-700 border border-emerald-200/60 text-xs font-bold rounded-lg shadow-sm"
                  >
                    <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></div>
                    At Office
                  </span>
                  <p 
                    v-if="attendance.out_of_office_reason" 
                    class="text-xs text-zinc-600 italic truncate max-w-[200px] bg-zinc-50 px-2 py-1 rounded-md border border-zinc-200/60" 
                    :title="attendance.out_of_office_reason"
                  >
                    "{{ attendance.out_of_office_reason }}"
                  </p>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span 
                  :class="[
                    'inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg border shadow-sm',
                    attendance.check_out 
                      ? 'bg-gradient-to-r from-zinc-100 to-zinc-200 text-zinc-700 border-zinc-300' 
                      : 'bg-gradient-to-r from-blue-50 to-blue-100 text-blue-700 border-blue-200'
                  ]"
                >
                  <div :class="['w-1.5 h-1.5 rounded-full', attendance.check_out ? 'bg-zinc-500' : 'bg-blue-500 animate-pulse']"></div>
                  {{ attendance.check_out ? 'Completed' : 'Active' }}
                </span>
              </td>
            </tr>
            <tr v-if="attendances.length === 0 && !loadingHistory">
              <td colspan="7" class="px-6 py-20 text-center">
                <div class="flex flex-col items-center justify-center">
                  <div class="w-16 h-16 bg-gradient-to-br from-zinc-100 to-zinc-200 rounded-2xl flex items-center justify-center mb-4 shadow-sm">
                    <CalendarX class="w-8 h-8 text-zinc-400" />
                  </div>
                  <p class="text-sm text-zinc-900 font-bold">No records found</p>
                  <p class="text-xs text-zinc-500 mt-1">Start by checking in today</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="px-6 py-4 border-t border-zinc-200/60 flex flex-col sm:flex-row items-center justify-between gap-4 bg-gradient-to-r from-zinc-50 to-zinc-100/50">
        <div class="text-sm text-zinc-600">
          Showing <span class="font-bold text-zinc-900">{{ pagination.from }}</span> to <span class="font-bold text-zinc-900">{{ pagination.to }}</span> of <span class="font-bold text-zinc-900">{{ pagination.total }}</span> results
        </div>
        
        <div class="flex items-center gap-2">
          <button 
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1 || loadingHistory"
            class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-zinc-700 bg-white border border-zinc-300 rounded-xl hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm hover:shadow-md active:scale-95"
          >
            <ChevronLeft class="w-4 h-4 mr-1" />
            Previous
          </button>
          
          <div class="hidden sm:flex items-center gap-2">
            <span class="px-4 py-2.5 text-sm font-bold text-white bg-gradient-to-r from-zinc-900 to-zinc-800 rounded-xl border border-zinc-700 shadow-lg shadow-zinc-900/20">
              Page {{ pagination.current_page }}
            </span>
          </div>

          <button 
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page || loadingHistory"
            class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-zinc-700 bg-white border border-zinc-300 rounded-xl hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm hover:shadow-md active:scale-95"
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
  ChevronLeft,
  ChevronRight
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
const loadingHistory = ref(false)
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

// === COMPUTED PROPERTIES ===
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

// Get user initials for avatar
const getInitials = (name) => {
  if (!name) return 'UN'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

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
    fetchAttendances(1)
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
    fetchAttendances(pagination.value.current_page)
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