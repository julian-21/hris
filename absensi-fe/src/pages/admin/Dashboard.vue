<template>
  <div class="min-h-screen bg-background">
    <div class="container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-8">
      
      <!-- Header Section -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight text-foreground">
            Dashboard
          </h1>
          <p class="text-sm text-muted-foreground mt-1">
            Selamat datang kembali, 
            <span class="font-semibold text-foreground">{{ authStore.user?.name }}</span>
            <span class="inline-block animate-wave">👋</span>
          </p>
        </div>
        
        <div class="flex items-center gap-2">
          <Badge variant="outline" class="hidden sm:flex items-center gap-2 px-3 py-1.5">
            <CalendarIcon class="h-3.5 w-3.5" />
            <span class="text-xs">{{ formattedDate }}</span>
          </Badge>
          <Button
            @click="refreshData"
            :disabled="isLoading"
            variant="outline"
            size="icon"
          >
            <RefreshCwIcon class="h-4 w-4" :class="{ 'animate-spin': isLoading }" />
          </Button>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <template v-if="isLoading">
          <!-- Loading Skeleton -->
          <Card v-for="i in 4" :key="`skeleton-${i}`">
            <CardContent class="pt-6">
              <div class="flex items-start justify-between mb-6">
                <Skeleton class="h-4 w-28" />
                <Skeleton class="h-11 w-11 rounded-xl" />
              </div>
              <Skeleton class="h-9 w-20 mb-4" />
              <Skeleton class="h-5 w-full" />
            </CardContent>
          </Card>
        </template>

        <template v-else>
          <!-- Stat Card -->
          <Card 
            v-for="stat in stats" 
            :key="stat.title"
            class="group hover:shadow-md transition-all duration-200"
          >
            <CardContent class="pt-6">
              <!-- Header -->
              <div class="flex items-start justify-between mb-6">
                <h3 class="text-sm font-medium text-muted-foreground">
                  {{ stat.title }}
                </h3>
                <div 
                  :class="`flex items-center justify-center h-11 w-11 rounded-xl transition-transform group-hover:scale-105 ${stat.bgColor}`"
                >
                  <component 
                    :is="getIconComponent(stat.icon)"
                    class="h-5 w-5"
                    :class="stat.iconColor"
                  />
                </div>
              </div>
              
              <!-- Value -->
              <div class="text-3xl font-bold text-foreground mb-3">
                {{ stat.value }}
              </div>
              
              <!-- Change Badge -->
              <div class="flex items-center gap-2">
                <Badge 
                  :variant="stat.changeType === 'increase' ? 'default' : 'destructive'"
                  class="font-semibold"
                >
                  <component 
                    :is="stat.changeType === 'increase' ? TrendingUpIcon : TrendingDownIcon" 
                    class="h-3 w-3 mr-1"
                  />
                  {{ stat.change }}
                </Badge>
                <span class="text-xs text-muted-foreground">
                  dari bulan lalu
                </span>
              </div>
            </CardContent>
          </Card>
        </template>
      </div>

      <!-- Main Grid -->
      <div class="grid gap-6 lg:grid-cols-12">
        
        <!-- Left Column (Chart + Actions) -->
        <div class="lg:col-span-8 space-y-6">
          
          <!-- Chart Card -->
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle>Grafik Kehadiran</CardTitle>
                  <CardDescription class="mt-1">
                    Overview kehadiran 7 hari terakhir
                  </CardDescription>
                </div>
                <Badge variant="outline" class="gap-1.5">
                  <div class="h-2 w-2 rounded-full bg-primary animate-pulse" />
                  <span>Hadir</span>
                </Badge>
              </div>
            </CardHeader>
            <CardContent>
              <div v-if="isLoading" class="flex items-center justify-center h-[280px]">
                <div class="text-center">
                  <Loader2Icon class="h-10 w-10 animate-spin text-muted-foreground mx-auto mb-3" />
                  <p class="text-sm text-muted-foreground">Memuat grafik...</p>
                </div>
              </div>
              <apexchart
                v-else
                type="area"
                height="280"
                :options="chartOptions"
                :series="chartSeries"
              />
            </CardContent>
          </Card>

          <!-- Quick Actions Desktop -->
          <Card class="hidden lg:block">
            <CardHeader>
              <CardTitle>Aksi Cepat</CardTitle>
              <CardDescription class="mt-1">
                Akses menu yang sering digunakan
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div class="grid grid-cols-4 gap-3">
                <Button
                  v-for="action in filteredQuickActions"
                  :key="action.label"
                  @click="router.push(action.path)"
                  variant="outline"
                  class="h-auto flex-col gap-3 py-4 hover:bg-accent group"
                >
                  <div class="flex items-center justify-center h-12 w-12 rounded-xl bg-accent group-hover:bg-primary/10 transition-colors">
                    <component 
                      :is="getIconComponent(action.iconName)"
                      class="h-6 w-6 text-muted-foreground group-hover:text-primary transition-colors"
                    />
                  </div>
                  <span class="text-sm font-medium">{{ action.label }}</span>
                </Button>
              </div>
            </CardContent>
          </Card>

          <!-- Quick Actions Mobile -->
          <Card class="lg:hidden">
            <CardHeader>
              <CardTitle>Aksi Cepat</CardTitle>
            </CardHeader>
            <CardContent class="p-0">
              <Separator />
              <div class="divide-y">
                <Button
                  v-for="action in filteredQuickActions"
                  :key="action.label"
                  @click="router.push(action.path)"
                  variant="ghost"
                  class="w-full justify-start h-auto py-4 px-6 rounded-none"
                >
                  <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-accent mr-3">
                    <component 
                      :is="getIconComponent(action.iconName)"
                      class="h-5 w-5 text-muted-foreground"
                    />
                  </div>
                  <span class="flex-1 text-left font-medium">{{ action.label }}</span>
                  <ChevronRightIcon class="h-5 w-5 text-muted-foreground" />
                </Button>
              </div>
            </CardContent>
          </Card>

        </div>

        <!-- Right Column (Leaves + Activities) -->
        <div class="lg:col-span-4 space-y-6">
          
          <!-- Leave Requests -->
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle>Permintaan Cuti</CardTitle>
                  <CardDescription class="mt-1">Terbaru</CardDescription>
                </div>
                <Button variant="ghost" size="sm" class="text-primary">
                  Lihat Semua
                </Button>
              </div>
            </CardHeader>
            <CardContent class="p-0">
              <Separator />
              
              <div class="max-h-[400px] overflow-y-auto">
                <!-- Loading -->
                <div v-if="isLoading" class="divide-y">
                  <div v-for="i in 3" :key="`leave-skeleton-${i}`" class="p-4">
                    <div class="flex items-center gap-3">
                      <Skeleton class="h-10 w-10 rounded-full flex-shrink-0" />
                      <div class="flex-1 space-y-2">
                        <Skeleton class="h-4 w-28" />
                        <Skeleton class="h-3 w-20" />
                      </div>
                      <Skeleton class="h-6 w-20 rounded-full" />
                    </div>
                  </div>
                </div>

                <!-- Empty -->
                <div 
                  v-else-if="recentLeaves.length === 0" 
                  class="flex flex-col items-center justify-center py-12"
                >
                  <div class="flex items-center justify-center h-16 w-16 rounded-full bg-muted mb-3">
                    <CalendarIcon class="h-8 w-8 text-muted-foreground" />
                  </div>
                  <p class="text-sm font-medium text-muted-foreground">
                    Tidak ada permintaan cuti
                  </p>
                </div>

                <!-- List -->
                <div v-else class="divide-y">
                  <div
                    v-for="leave in recentLeaves"
                    :key="leave.id"
                    class="p-4 hover:bg-accent/50 transition-colors cursor-pointer"
                  >
                    <div class="flex items-center gap-3">
                      <div class="relative flex-shrink-0">
                        <div class="flex items-center justify-center h-10 w-10 rounded-full bg-muted font-semibold text-sm">
                          {{ leave.initials }}
                        </div>
                        <div class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-background bg-green-500" />
                      </div>
                      
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold truncate">{{ leave.name }}</p>
                        <p class="text-xs text-muted-foreground mt-0.5">{{ leave.type }}</p>
                      </div>
                      
                      <div class="text-right">
                        <Badge :variant="getLeaveVariant(leave.status)">
                          {{ leave.statusText }}
                        </Badge>
                        <p class="text-[10px] text-muted-foreground mt-1">
                          {{ leave.date }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Activities -->
          <Card>
            <CardHeader>
              <CardTitle>Aktivitas Terbaru</CardTitle>
              <CardDescription class="mt-1">Real-time updates</CardDescription>
            </CardHeader>
            <CardContent class="p-0">
              <Separator />
              
              <div class="p-6 max-h-[400px] overflow-y-auto">
                <!-- Loading -->
                <div v-if="isLoading" class="space-y-4">
                  <div v-for="i in 3" :key="`activity-skeleton-${i}`" class="flex gap-3">
                    <Skeleton class="h-9 w-9 rounded-full flex-shrink-0" />
                    <div class="flex-1 space-y-2">
                      <Skeleton class="h-4 w-full" />
                      <Skeleton class="h-3 w-2/3" />
                    </div>
                  </div>
                </div>

                <!-- Empty -->
                <div 
                  v-else-if="recentActivities.length === 0" 
                  class="flex flex-col items-center justify-center py-12"
                >
                  <div class="flex items-center justify-center h-16 w-16 rounded-full bg-muted mb-3">
                    <ClockIcon class="h-8 w-8 text-muted-foreground" />
                  </div>
                  <p class="text-sm font-medium text-muted-foreground">
                    Belum ada aktivitas
                  </p>
                </div>

                <!-- Timeline -->
                <div v-else class="relative">
                  <div class="absolute left-[18px] top-2 bottom-2 w-px bg-border" />

                  <div 
                    v-for="activity in recentActivities" 
                    :key="activity.id"
                    class="relative flex gap-4 pb-5 last:pb-0"
                  >
                    <div 
                      :class="`relative z-10 flex items-center justify-center h-9 w-9 rounded-full border-2 border-background shadow-sm flex-shrink-0 ${activity.iconBg}`"
                    >
                      <div class="h-2 w-2 rounded-full bg-background" />
                    </div>
                    
                    <div class="flex-1 pt-1">
                      <p class="text-sm font-medium leading-tight">
                        {{ activity.text }}
                      </p>
                      <div class="flex items-center gap-1 mt-1.5 text-xs text-muted-foreground">
                        <ClockIcon class="h-3 w-3" />
                        {{ activity.time }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

        </div>
      </div>

    </div>

    <!-- Mobile Bottom Nav -->
    <div class="fixed bottom-0 left-0 right-0 z-50 lg:hidden border-t bg-background">
      <div class="grid grid-cols-4 gap-1 p-2">
        <Button
          v-for="action in filteredQuickActions"
          :key="`mobile-${action.label}`"
          @click="router.push(action.path)"
          variant="ghost"
          class="flex-col h-auto gap-1.5 py-3"
        >
          <component 
            :is="getIconComponent(action.iconName)"
            class="h-6 w-6 text-muted-foreground"
          />
          <span class="text-[10px] font-medium text-muted-foreground leading-tight">
            {{ action.label }}
          </span>
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Skeleton } from '@/components/ui/skeleton'

import { 
  CalendarIcon, 
  RefreshCwIcon, 
  TrendingUpIcon, 
  TrendingDownIcon,
  ChevronRightIcon,
  Loader2Icon,
  ClockIcon,
  UsersIcon,
  UserIcon,
  CheckCircleIcon,
  PlusIcon,
  FileTextIcon,
  BarChartIcon
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()
const isLoading = ref(true)

// Icon mapping untuk lucide-vue
const iconComponents = {
  IconUsers: UsersIcon,
  IconCalendar: CalendarIcon,
  IconClock: ClockIcon,
  IconUser: UserIcon,
  IconCheckCircle: CheckCircleIcon,
  IconPlus: PlusIcon,
  IconFileText: FileTextIcon,
  IconChart: BarChartIcon
}

const getIconComponent = (name) => iconComponents[name] || BarChartIcon

const stats = ref([])
const recentLeaves = ref([])
const recentActivities = ref([])
const chartData = ref([])

const formattedDate = computed(() => {
  return new Date().toLocaleDateString('id-ID', { 
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
  })
})

const quickActions = [
  { label: 'Absen', path: '/attendance', iconName: 'IconCheckCircle', permission: 'create attendance' },
  { label: 'Cuti', path: '/leave', iconName: 'IconPlus', permission: 'create leave' },
  { label: 'Lembur', path: '/overtime', iconName: 'IconFileText', permission: 'create overtime' },
  { label: 'Laporan', path: '/reports', iconName: 'IconChart', permission: 'view reports' }
]

const filteredQuickActions = computed(() => {
  return quickActions.filter(a => !a.permission || authStore.hasPermission(a.permission))
})

const chartSeries = computed(() => [{
  name: 'Kehadiran',
  data: chartData.value.map(d => d.value)
}])

const chartOptions = computed(() => ({
  chart: {
    type: 'area',
    height: 280,
    toolbar: { show: false },
    zoom: { enabled: false },
    fontFamily: 'inherit',
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800
    }
  },
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 3, colors: ['hsl(var(--primary))'] },
  fill: {
    type: 'gradient',
    gradient: {
      opacityFrom: 0.5,
      opacityTo: 0.05,
      stops: [0, 90, 100]
    },
    colors: ['hsl(var(--primary))']
  },
  colors: ['hsl(var(--primary))'],
  xaxis: {
    categories: chartData.value.map(d => d.date),
    labels: { 
      style: { colors: 'hsl(var(--muted-foreground))', fontSize: '12px' } 
    },
    axisBorder: { show: false },
    axisTicks: { show: false }
  },
  yaxis: {
    labels: {
      style: { colors: 'hsl(var(--muted-foreground))', fontSize: '12px' },
      formatter: (v) => Math.round(v)
    }
  },
  grid: {
    borderColor: 'hsl(var(--border))',
    strokeDashArray: 3,
    xaxis: { lines: { show: false } },
    yaxis: { lines: { show: true } }
  },
  tooltip: {
    theme: 'dark',
    y: { formatter: (v) => `${v} Hadir` }
  },
  markers: { size: 0, hover: { size: 6 } }
}))

const getLeaveVariant = (status) => {
  return { pending: 'secondary', approved: 'default', rejected: 'destructive' }[status] || 'outline'
}

const refreshData = () => {
  fetchDashboardData()
}

const fetchDashboardData = async () => {
  isLoading.value = true
  
  try {
    const [response] = await Promise.all([
      api.get('/dashboard'),
      new Promise(r => setTimeout(r, 800))
    ])
    
    stats.value = response.data.stats
    recentLeaves.value = response.data.recent_leaves
    recentActivities.value = response.data.recent_activities
    chartData.value = response.data.attendance_chart?.length > 0 
      ? response.data.attendance_chart 
      : [
          { date: 'Sen', value: 0 }, { date: 'Sel', value: 0 },
          { date: 'Rab', value: 0 }, { date: 'Kam', value: 0 },
          { date: 'Jum', value: 0 }, { date: 'Sab', value: 0 },
          { date: 'Min', value: 0 }
        ]
  } catch (error) {
    console.error('Error loading dashboard:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchDashboardData()
})
</script>

<style scoped>
@keyframes wave {
  0%, 100% { transform: rotate(0deg); }
  10%, 30% { transform: rotate(14deg); }
  20% { transform: rotate(-8deg); }
  40% { transform: rotate(-4deg); }
  50% { transform: rotate(10deg); }
}

.animate-wave {
  animation: wave 2s ease-in-out infinite;
  display: inline-block;
}
</style>