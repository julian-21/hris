<template>
  <div class="container mx-auto py-8 px-4 space-y-8 max-w-7xl animate-in fade-in duration-500">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="space-y-1">
        <h1 class="text-3xl font-bold tracking-tight text-foreground">Rekap Kehadiran</h1>
        <p class="text-muted-foreground text-base">
          Monitor performa, keterlambatan, dan analisis kehadiran tim Anda.
        </p>
      </div>
      
      <Button 
        @click="exportToExcel" 
        variant="outline" 
        class="gap-2 shadow-sm hover:bg-green-50 hover:text-green-700 hover:border-green-200 transition-all"
      >
        <Download class="h-4 w-4" />
        Export Excel
      </Button>
    </div>

    <Card class="border shadow-sm bg-card/50 backdrop-blur-sm">
      <CardHeader class="pb-4">
        <div class="flex items-center gap-2">
          <div class="p-2 bg-primary/10 rounded-lg">
            <FilterIcon class="h-4 w-4 text-primary" />
          </div>
          <div>
            <CardTitle class="text-lg">Filter Data</CardTitle>
            <CardDescription>Tentukan parameter laporan yang ingin ditampilkan</CardDescription>
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <div class="grid grid-cols-1 gap-4">
          <!-- Row 1: Karyawan -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="space-y-2.5 lg:col-span-2">
              <Label for="user" class="text-sm font-medium">Karyawan</Label>
              <Select v-model="filters.user_id">
                <SelectTrigger id="user" class="h-10 transition-all focus:ring-2 focus:ring-primary/20">
                  <SelectValue placeholder="Pilih karyawan" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Semua Karyawan</SelectItem>
                  <SelectItem 
                    v-for="user in users" 
                    :key="user.id" 
                    :value="user.id.toString()"
                  >
                    {{ user.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <!-- Row 2: Date Range & Buttons -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">
            <div class="lg:col-span-4 space-y-2.5">
              <Label for="start-date">Dari Tanggal</Label>
              <Input 
                id="start-date" 
                type="date" 
                v-model="filters.start_date"
                class="h-10 block"
              />
            </div>

            <div class="lg:col-span-4 space-y-2.5">
              <Label for="end-date">Sampai Tanggal</Label>
              <Input 
                id="end-date" 
                type="date" 
                v-model="filters.end_date"
                class="h-10 block"
              />
            </div>

            <div class="lg:col-span-4 flex gap-2">
              <Button 
                  @click="resetFilters" 
                  variant="ghost" 
                  size="icon"
                  class="h-10 w-10 shrink-0 hover:bg-muted text-muted-foreground"
                  title="Reset Filter"
              >
                  <RefreshCw class="h-4 w-4" />
              </Button>
              <Button 
                  @click="loadReport" 
                  :disabled="loading" 
                  class="flex-1 h-10 shadow-md hover:shadow-lg transition-all"
              >
                <Loader2 v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
                {{ loading ? 'Memuat...' : 'Tampilkan' }}
              </Button>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <Transition name="fade-slide" mode="out-in">
        <div v-if="reportData" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card class="border shadow-sm hover:shadow-md transition-all duration-300 group overflow-hidden relative">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <CheckCircle2 class="h-16 w-16 text-emerald-600" />
            </div>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Total Hadir</CardTitle>
            <div class="h-8 w-8 rounded-full bg-emerald-100 flex items-center justify-center">
                <CheckCircle2 class="h-4 w-4 text-emerald-600" />
            </div>
            </CardHeader>
            <CardContent>
            <div class="text-3xl font-bold text-foreground">{{ reportData.summary.total_hadir }}</div>
            <p class="text-xs font-medium text-emerald-600 mt-1 flex items-center">
                {{ reportData.summary.persentase_kehadiran }}% 
                <span class="text-muted-foreground ml-1 font-normal">tingkat kehadiran</span>
            </p>
            </CardContent>
        </Card>

        <Card class="border shadow-sm hover:shadow-md transition-all duration-300 group overflow-hidden relative">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <Clock class="h-16 w-16 text-orange-600" />
            </div>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Terlambat</CardTitle>
            <div class="h-8 w-8 rounded-full bg-orange-100 flex items-center justify-center">
                <Clock class="h-4 w-4 text-orange-600" />
            </div>
            </CardHeader>
            <CardContent>
            <div class="text-3xl font-bold text-foreground">{{ reportData.summary.total_terlambat }}</div>
            <p class="text-xs text-muted-foreground mt-1">
                Insiden keterlambatan
            </p>
            </CardContent>
        </Card>

        <Card class="border shadow-sm hover:shadow-md transition-all duration-300 group overflow-hidden relative">
             <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <Calendar class="h-16 w-16 text-blue-600" />
            </div>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Cuti / Izin</CardTitle>
            <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                <Calendar class="h-4 w-4 text-blue-600" />
            </div>
            </CardHeader>
            <CardContent>
            <div class="text-3xl font-bold text-foreground">{{ reportData.summary.total_cuti }}</div>
            <p class="text-xs text-muted-foreground mt-1">Hari disetujui</p>
            </CardContent>
        </Card>

        <Card class="border shadow-sm hover:shadow-md transition-all duration-300 group overflow-hidden relative">
             <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <XCircle class="h-16 w-16 text-rose-600" />
            </div>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Alpha</CardTitle>
            <div class="h-8 w-8 rounded-full bg-rose-100 flex items-center justify-center">
                <XCircle class="h-4 w-4 text-rose-600" />
            </div>
            </CardHeader>
            <CardContent>
            <div class="text-3xl font-bold text-foreground">{{ reportData.summary.total_alpha }}</div>
            <p class="text-xs text-rose-600 mt-1 font-medium">Tanpa keterangan</p>
            </CardContent>
        </Card>
        </div>
    </Transition>

    <Card v-if="reportData" class="border shadow-md overflow-hidden flex flex-col">
      <CardHeader class="border-b bg-muted/30 px-6 py-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <CardTitle class="text-xl">Detail Kehadiran</CardTitle>
            <CardDescription class="mt-1 flex items-center gap-2">
              <CalendarRange class="h-3.5 w-3.5" />
              {{ formatDate(reportData.start_date) }} - {{ formatDate(reportData.end_date) }}
              <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-primary/10 text-primary text-xs font-medium">
                {{ reportData.user ? reportData.user.name : 'Semua Karyawan' }}
              </span>
            </CardDescription>
          </div>
          
          <div class="relative w-full md:w-72">
            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input 
              v-model="searchQuery" 
              placeholder="Cari nama, status, atau tanggal..." 
              class="pl-9 bg-background/50 focus:bg-background transition-colors"
            />
          </div>
        </div>
      </CardHeader>

      <CardContent class="p-0 relative min-h-[400px]">
        <div v-if="loading" class="absolute inset-0 bg-background/60 backdrop-blur-sm z-10 flex flex-col items-center justify-center">
            <Loader2 class="h-10 w-10 animate-spin text-primary mb-2" />
            <p class="text-sm text-muted-foreground font-medium animate-pulse">Memperbarui data...</p>
        </div>

        <div class="overflow-x-auto">
          <Table>
            <TableHeader class="bg-muted/30 sticky top-0 z-0">
              <TableRow class="hover:bg-transparent">
                <TableHead class="w-[200px] font-semibold">Karyawan</TableHead>
                <TableHead class="font-semibold">Tanggal & Hari</TableHead>
                <TableHead class="font-semibold">Status</TableHead>
                <TableHead class="font-semibold text-center">Check In</TableHead>
                <TableHead class="font-semibold text-center">Check Out</TableHead>
                <TableHead class="font-semibold">Lokasi Kantor</TableHead>
                <TableHead class="font-semibold">Keterangan</TableHead>
              </TableRow>
            </TableHeader>
            
            <TableBody>
                <TableRow v-if="paginatedData.length === 0 && !loading">
                  <TableCell colspan="7" class="h-[300px]">
                    <div class="flex flex-col items-center justify-center text-center text-muted-foreground space-y-3">
                      <div class="p-4 bg-muted rounded-full">
                        <SearchX class="h-8 w-8 text-muted-foreground" />
                      </div>
                      <div>
                        <p class="font-semibold text-foreground">Data tidak ditemukan</p>
                        <p class="text-sm">Coba ubah filter atau kata kunci pencarian Anda.</p>
                      </div>
                    </div>
                  </TableCell>
                </TableRow>

                <TableRow
                  v-for="(item, index) in paginatedData"
                  :key="item.user_id + '-' + item.tanggal"
                  class="group transition-colors hover:bg-muted/40"
                  :class="getRowClass(item)"
                >
                    <TableCell class="font-medium align-top">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold uppercase">
                                {{ item.user_name.substring(0,2) }}
                            </div>
                            <span class="text-sm">{{ item.user_name }}</span>
                        </div>
                    </TableCell>

                    <TableCell class="align-top">
                      <div class="flex flex-col">
                        <span class="font-medium text-sm">{{ formatDate(item.tanggal) }}</span>
                        <div class="flex items-center gap-1.5 mt-1">
                             <span class="text-xs text-muted-foreground">{{ item.hari }}</span>
                             <Badge v-if="item.is_weekend" variant="secondary" class="h-5 px-1.5 text-[10px] bg-slate-100 text-slate-600">
                                Weekend
                            </Badge>
                        </div>
                      </div>
                    </TableCell>

                    <TableCell class="align-top">
                        <div class="flex flex-wrap gap-1.5">
                            <Badge :variant="getStatusVariant(item.status_color)" class="shadow-sm">
                                {{ item.status_label }}
                            </Badge>
                            <Badge v-if="item.is_late" variant="destructive" class="bg-red-50 text-red-600 border-red-200 hover:bg-red-100 shadow-none">
                                <Clock class="h-3 w-3 mr-1" />
                                +{{ item.durasi_terlambat }}m
                            </Badge>
                             <Badge v-if="item.out_of_office" variant="outline" class="bg-blue-50 text-blue-600 border-blue-200">
                                Remote
                            </Badge>
                        </div>
                    </TableCell>

                    <TableCell class="text-center font-mono text-sm align-top">
                        <span v-if="item.check_in" class="bg-slate-100 px-2 py-1 rounded text-slate-700 font-medium">
                            {{ item.check_in }}
                        </span>
                        <span v-else class="text-muted-foreground/50">-</span>
                    </TableCell>
                    <TableCell class="text-center font-mono text-sm align-top">
                         <span v-if="item.check_out" class="bg-slate-100 px-2 py-1 rounded text-slate-700 font-medium">
                            {{ item.check_out }}
                        </span>
                        <span v-else class="text-muted-foreground/50">-</span>
                    </TableCell>

                    <TableCell class="align-top">
                       <div class="flex items-center gap-1.5 text-sm" v-if="item.kantor_nama">
                            <MapPin class="h-3.5 w-3.5 text-muted-foreground" />
                            {{ item.kantor_nama }}
                       </div>
                       <span v-else class="text-muted-foreground/50 text-sm">-</span>
                    </TableCell>

                    <TableCell class="align-top">
                      <div class="flex items-start justify-between gap-2 max-w-[250px]">
                        <div class="text-sm text-muted-foreground truncate">
                            <span v-if="item.keterangan">{{ item.keterangan }}</span>
                            <span v-else-if="item.leave_status" class="italic">
                                {{ item.leave_status.leave_type_nama }}
                            </span>
                            <span v-else class="opacity-50">-</span>
                        </div>
                        
                        <Button 
                          variant="ghost" 
                          size="icon"
                          @click="showDetail(item)"
                          class="h-6 w-6 opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                          <Info class="h-4 w-4 text-primary" />
                        </Button>
                      </div>
                    </TableCell>
                </TableRow>
            </TableBody>
          </Table>
        </div>
        
        <div class="border-t p-4 flex flex-col sm:flex-row items-center justify-between gap-4 bg-muted/10">
            <p class="text-sm text-muted-foreground">
                Menampilkan <span class="font-medium text-foreground">{{ paginatedData.length }}</span> dari <span class="font-medium text-foreground">{{ filteredReportData.length }}</span> data
            </p>

            <div class="flex items-center gap-1">
                <Button 
                    variant="outline" 
                    size="sm" 
                    :disabled="currentPage === 1"
                    @click="changePage(currentPage - 1)"
                    class="h-8 w-8 p-0"
                >
                    <ChevronLeft class="h-4 w-4" />
                </Button>

                <div class="flex items-center gap-1 mx-2">
                     <Button
                        v-for="p in displayedPages"
                        :key="p"
                        size="sm"
                        :variant="p === currentPage ? 'default' : 'ghost'"
                        @click="changePage(p)"
                        class="h-8 w-8 p-0 text-xs"
                    >
                        {{ p }}
                    </Button>
                </div>

                <Button 
                    variant="outline" 
                    size="sm" 
                    :disabled="currentPage === totalPages"
                    @click="changePage(currentPage + 1)"
                    class="h-8 w-8 p-0"
                >
                    <ChevronRight class="h-4 w-4" />
                </Button>
            </div>
        </div>
      </CardContent>
    </Card>

    <div v-if="!reportData && !loading" class="flex flex-col items-center justify-center py-16 text-center animate-in zoom-in-95 duration-500">
        <div class="bg-muted/50 p-6 rounded-full mb-6">
            <FileText class="h-12 w-12 text-muted-foreground/50" />
        </div>
        <h3 class="text-xl font-semibold mb-2">Belum ada laporan ditampilkan</h3>
        <p class="text-muted-foreground max-w-sm mb-8">
            Silakan pilih karyawan dan rentang tanggal pada panel filter di atas, lalu klik "Tampilkan".
        </p>
    </div>

    <AttendanceDetailDialog 
      v-model:open="showDetailDialog" 
      :data="selectedDetail" 
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from '@/composables/useToast'
import api from '@/services/api'
import { format } from 'date-fns'
import { id } from 'date-fns/locale'
import { exportAttendanceReport } from '@/utils/excelExport'

// Icon Import (Lucide Vue)
import { 
  CheckCircle2, 
  XCircle, 
  Clock, 
  Calendar, 
  Download, 
  Loader2,
  FileText,
  Info,
  Filter as FilterIcon,
  RefreshCw,
  Search,
  SearchX,
  MapPin,
  CalendarRange,
  ChevronLeft,
  ChevronRight
} from 'lucide-vue-next'

// UI Components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { 
  Select, 
  SelectContent, 
  SelectItem, 
  SelectTrigger, 
  SelectValue 
} from '@/components/ui/select'
import { 
  Table, 
  TableBody, 
  TableCell, 
  TableHead, 
  TableHeader, 
  TableRow 
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import AttendanceDetailDialog from '../../components/attendance/AttendanceDetailDialog.vue'

const { toast } = useToast()

// Data State
const loading = ref(false)
const users = ref([])
const reportData = ref(null)
const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(10)
const selectedDetail = ref(null)
const showDetailDialog = ref(false)

// Filters State
const filters = ref({
  user_id: 'all',
  start_date: getFirstDayOfMonth(),
  end_date: getLastDayOfMonth()
})

// --- Computed Properties ---

const filteredReportData = computed(() => {
  if (!reportData.value) return []

  const q = searchQuery.value.toLowerCase()

  return reportData.value.data.filter(item => {
    return (
      item.user_name.toLowerCase().includes(q) ||
      item.tanggal.includes(q) ||
      item.hari.toLowerCase().includes(q) ||
      item.status_label.toLowerCase().includes(q) ||
      (item.keterangan && item.keterangan.toLowerCase().includes(q))
    )
  })
})

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredReportData.value.slice(start, start + perPage.value)
})

const totalPages = computed(() => {
  return Math.ceil(filteredReportData.value.length / perPage.value) || 1
})

// Pagination Smart Display Logic
const displayedPages = computed(() => {
    const delta = 2;
    const range = [];
    for (let i = Math.max(2, currentPage.value - delta); i <= Math.min(totalPages.value - 1, currentPage.value + delta); i++) {
        range.push(i);
    }
    if (currentPage.value - delta > 2) {
        range.unshift("...");
    }
    if (currentPage.value + delta < totalPages.value - 1) {
        range.push("...");
    }
    range.unshift(1);
    if (totalPages.value !== 1) {
        range.push(totalPages.value);
    }
    // Simplifikasi jika halaman sedikit
    if(totalPages.value <= 5) {
        return Array.from({length: totalPages.value}, (_, i) => i + 1)
    }
    return range.filter(p => typeof p === 'number'); 
})

// --- Methods ---

function changePage(page) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
}

async function loadUsers() {
  try {
    const response = await api.get('/attendance-reports/users')
    users.value = response.data
  } catch (error) {
    console.error('Error loading users:', error)
    toast({
      title: 'Error',
      description: 'Gagal memuat daftar karyawan',
      variant: 'destructive'
    })
  }
}

async function loadReport() {
  if (!filters.value.start_date || !filters.value.end_date) {
    toast({
      title: 'Validasi',
      description: 'Mohon pilih tanggal mulai dan selesai',
      variant: 'destructive'
    })
    return
  }

  loading.value = true

  try {
    const params = {
      start_date: filters.value.start_date,
      end_date: filters.value.end_date
    }
    
    if (filters.value.user_id !== 'all') {
      params.user_id = filters.value.user_id
    }

    const response = await api.get('/attendance-reports', { params })
    reportData.value = response.data
    currentPage.value = 1
    
    toast({
      title: 'Data Diperbarui',
      description: 'Laporan kehadiran berhasil dimuat',
      variant: 'default'
    })
  } catch (error) {
    console.error('Error loading report:', error)
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Gagal memuat data',
      variant: 'destructive'
    })
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.value = {
    user_id: 'all',
    start_date: getFirstDayOfMonth(),
    end_date: getLastDayOfMonth()
  }
  reportData.value = null
  searchQuery.value = ''
}

function showDetail(item) {
  selectedDetail.value = item
  showDetailDialog.value = true
}

function getStatusVariant(color) {
  const variants = {
    green: 'default', 
    red: 'destructive',
    yellow: 'secondary', 
    blue: 'outline',
    orange: 'secondary',
    gray: 'secondary'
  }
  return variants[color] || 'secondary'
}

function getRowClass(item) {
  let classes = ''
  if (item.is_weekend) classes += ' bg-slate-50/50'
  if (item.status === 'alpha') classes += ' bg-red-50/30'
  return classes
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return format(new Date(dateString), 'dd MMM yyyy', { locale: id })
}

function getFirstDayOfMonth() {
  const date = new Date()
  return new Date(date.getFullYear(), date.getMonth(), 1)
    .toISOString()
    .split('T')[0]
}

function getLastDayOfMonth() {
  const date = new Date()
  return new Date(date.getFullYear(), date.getMonth() + 1, 0)
    .toISOString()
    .split('T')[0]
}

async function exportToExcel() {
  if (!reportData.value) {
    toast({
      title: 'Info',
      description: 'Silakan muat data laporan terlebih dahulu',
      variant: 'secondary'
    })
    return
  }

  try {
    exportAttendanceReport(reportData.value)
    toast({
      title: 'Export Berhasil',
      description: 'File Excel sedang diunduh',
      variant: 'default'
    })
  } catch (error) {
    console.error('Error exporting:', error)
    toast({
      title: 'Gagal Export',
      description: 'Terjadi kesalahan saat membuat file Excel',
      variant: 'destructive'
    })
  }
}

// Lifecycle
onMounted(() => {
  loadUsers()
})
</script>

<style scoped>
/* Transisi untuk Summary Cards (KPI) */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.4s ease;
}
.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(20px);
}
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}
</style>