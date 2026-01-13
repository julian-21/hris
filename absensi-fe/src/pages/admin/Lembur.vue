<template>
  <div class="container mx-auto p-6 space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold">Data Lembur</h1>
        <p class="text-muted-foreground">Kelola data lembur karyawan</p>
      </div>
      <div class="flex gap-2">
        <Button @click="$router.push('claim-lembur')" variant="outline">
          <FileCheck class="mr-2 h-4 w-4" />
          Lihat Claims
        </Button>
        <Button @click="openCreateDialog">
          <Plus class="mr-2 h-4 w-4" />
          Ajukan Lembur
        </Button>
      </div>
    </div>

    <Card v-if="statistics && statistics.total_expiring_soon > 0" class="bg-yellow-50 border-yellow-200">
      <CardContent class="pt-6">
        <div class="flex items-start gap-3">
          <AlertTriangle class="h-5 w-5 text-yellow-600 flex-shrink-0 mt-0.5" />
          <div class="flex-1">
            <h3 class="font-semibold text-yellow-900">Peringatan Lembur Akan Hangus!</h3>
            <p class="text-sm text-yellow-800 mt-1">
              Anda memiliki {{ statistics.total_expiring_soon }} lembur yang akan hangus dalam 30 hari. 
              Segera lakukan claim sebelum hangus!
            </p>
            <Button @click="showExpiringSoon" variant="outline" size="sm" class="mt-2">
              Lihat Detail
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>

    <div class="grid gap-4 md:grid-cols-5" v-if="statistics">
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Total Lembur</CardTitle>
          <Clock class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ formatMinutes(statistics.total_lembur) }}</div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Menunggu</CardTitle>
          <AlertCircle class="h-4 w-4 text-yellow-500" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ statistics.total_waiting }}</div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Disetujui</CardTitle>
          <CheckCircle2 class="h-4 w-4 text-green-500" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ statistics.total_accepted }}</div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Sisa Claim</CardTitle>
          <Timer class="h-4 w-4 text-blue-500" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ formatMinutes(statistics.total_sisa_claim) }}</div>
        </CardContent>
      </Card>

      <Card :class="statistics.total_expired > 0 ? 'bg-red-50' : ''">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Hangus</CardTitle>
          <XCircle class="h-4 w-4 text-red-500" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ statistics.total_expired }}</div>
        </CardContent>
      </Card>
    </div>

    <Card>
      <CardHeader>
        <div class="flex items-center justify-between">
          <CardTitle>Filter</CardTitle>
          <Button @click="resetFilters" variant="ghost" size="sm">
            <RotateCcw class="mr-2 h-4 w-4" />
            Reset Filter
          </Button>
        </div>
      </CardHeader>
      <CardContent>
        <div class="grid gap-4 md:grid-cols-4">
          <div class="space-y-2">
            <Label>Status</Label>
            <Select v-model="filters.status" @update:modelValue="handleFilterChange">
              <SelectTrigger>
                <SelectValue placeholder="Pilih status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Semua</SelectItem>
                <SelectItem value="waiting">Menunggu</SelectItem>
                <SelectItem value="accepted">Diterima</SelectItem>
                <SelectItem value="rejected">Ditolak</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label>Final Status</Label>
            <Select v-model="filters.final_status" @update:modelValue="handleFilterChange">
              <SelectTrigger>
                <SelectValue placeholder="Pilih final status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Semua</SelectItem>
                <SelectItem value="waiting">Menunggu</SelectItem>
                <SelectItem value="accepted">Diterima</SelectItem>
                <SelectItem value="rejected">Ditolak</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label>Bulan</Label>
            <Input 
              type="month" 
              v-model="monthInput"
              @change="handleMonthChange"
            />
          </div>

          <div class="space-y-2">
            <Label>Pencarian</Label>
            <div class="relative">
              <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
              <Input 
                placeholder="Cari nama/alasan..." 
                class="pl-8"
                v-model="filters.search"
                @input="debouncedSearch"
              />
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <Tabs v-model="activeTab" class="w-full">
      <TabsList class="grid w-full max-w-md grid-cols-2">
        <TabsTrigger value="personal">Lembur Saya</TabsTrigger>
        <TabsTrigger v-if="canApprove" value="approval">Approval Lembur</TabsTrigger>
      </TabsList>

      <TabsContent value="personal">
        <Card>
          <CardHeader>
            <CardTitle>Daftar Lembur Pribadi</CardTitle>
            <CardDescription>Data lembur yang Anda ajukan</CardDescription>
          </CardHeader>
          <CardContent>
            <div v-if="loadingPersonal" class="flex justify-center items-center py-8">
              <Loader2 class="h-8 w-8 animate-spin" />
            </div>

            <div v-else-if="personalLemburs.length === 0" class="text-center py-8 text-muted-foreground">
              <p>Tidak ada data lembur pribadi</p>
              <p class="text-xs mt-1">Klik tombol "Ajukan Lembur" untuk membuat data baru</p>
            </div>

            <div v-else class="space-y-4">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Tanggal</TableHead>
                    <TableHead>Durasi</TableHead>
                    <TableHead>Alasan</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Final Status</TableHead>
                    <TableHead>Sisa Claim</TableHead>
                    <TableHead>Expire</TableHead>
                    <TableHead class="text-right">Aksi</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="lembur in personalLemburs" :key="lembur.id" :class="getRowClass(lembur)">
                    <TableCell>{{ formatDate(lembur.tanggal_lembur) }}</TableCell>
                    <TableCell>
                      <Badge variant="outline">{{ formatMinutes(lembur.lama_lembur) }}</Badge>
                    </TableCell>
                    <TableCell class="max-w-xs truncate">{{ lembur.alasan_lembur }}</TableCell>
                    <TableCell>
                      <Badge :variant="getStatusBadgeVariant(lembur.status)">
                        {{ lembur.status_label || lembur.status }}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <Badge :variant="getStatusBadgeVariant(lembur.final_status)">
                        {{ lembur.final_status_label || lembur.final_status }}
                      </Badge>
                    </TableCell>
                    
                    <TableCell>
                      <span 
                        v-if="isRejected(lembur) || lembur.is_expired" 
                        class="text-muted-foreground text-sm"
                      >
                        -
                      </span>
                      <div 
                        v-else-if="!isFullyApproved(lembur)" 
                        class="flex flex-col"
                      >
                        <span class="text-muted-foreground font-medium">
                          {{ formatMinutes(lembur.sisa_waktu_claim) }}
                        </span>
                        <span class="text-[10px] text-yellow-600 italic">
                          (Proses)
                        </span>
                      </div>
                      <span 
                        v-else 
                        class="text-green-600 font-bold flex items-center gap-1"
                      >
                        <CheckCircle2 class="h-3 w-3" />
                        {{ formatMinutes(lembur.sisa_waktu_claim) }}
                      </span>
                    </TableCell>

                    <TableCell>
                      <div v-if="isRejected(lembur)" class="flex items-center gap-1 text-red-600">
                        <XCircle class="h-4 w-4" />
                        <span class="text-xs font-medium">Ditolak</span>
                      </div>
                      <div v-else-if="lembur.is_expired" class="flex items-center gap-1 text-red-600">
                        <XCircle class="h-4 w-4" />
                        <span class="text-xs font-medium">Hangus</span>
                      </div>
                      <div v-else-if="lembur.is_expiring_soon" class="flex items-center gap-1 text-yellow-600">
                        <AlertTriangle class="h-4 w-4" />
                        <span class="text-xs">{{ lembur.days_until_expire }}h</span>
                      </div>
                      <span v-else class="text-xs text-muted-foreground">
                        {{ formatDate(lembur.expire_at) }}
                      </span>
                    </TableCell>

                    <TableCell class="text-right">
                      <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                          <Button variant="ghost" size="icon">
                            <MoreHorizontal class="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem @click="viewDetail(lembur)">
                            <Eye class="mr-2 h-4 w-4" />
                            Detail
                          </DropdownMenuItem>

                          <template v-if="lembur.status === 'waiting'">
                            <DropdownMenuItem @click="editLembur(lembur)">
                              <Pencil class="mr-2 h-4 w-4" />
                              Edit
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem 
                              @click="handleDelete(lembur.id)"
                              class="text-red-600"
                            >
                              <Trash2 class="mr-2 h-4 w-4" />
                              Hapus
                            </DropdownMenuItem>
                          </template>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>

              <div v-if="personalPagination.last_page > 1" class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                  Menampilkan {{ personalLemburs.length }} dari {{ personalPagination.total }} data
                </div>
                <div class="flex items-center space-x-2">
                  <Button
                    variant="outline"
                    size="sm"
                    @click="changePersonalPage(personalPagination.current_page - 1)"
                    :disabled="personalPagination.current_page === 1"
                  >
                    <ChevronLeft class="h-4 w-4" />
                    Previous
                  </Button>
                  <div class="text-sm">
                    Halaman {{ personalPagination.current_page }} dari {{ personalPagination.last_page }}
                  </div>
                  <Button
                    variant="outline"
                    size="sm"
                    @click="changePersonalPage(personalPagination.current_page + 1)"
                    :disabled="personalPagination.current_page === personalPagination.last_page"
                  >
                    Next
                    <ChevronRight class="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </TabsContent>

      <TabsContent v-if="canApprove" value="approval">
        <Card>
          <CardHeader>
            <CardTitle>Approval Lembur Karyawan</CardTitle>
            <CardDescription>Kelola approval lembur seluruh karyawan</CardDescription>
          </CardHeader>
          <CardContent>
            <div v-if="loadingApproval" class="flex justify-center items-center py-8">
              <Loader2 class="h-8 w-8 animate-spin" />
            </div>

            <div v-else-if="approvalLemburs.length === 0" class="text-center py-8 text-muted-foreground">
              <p>Tidak ada data lembur yang perlu di-approve</p>
            </div>

            <div v-else class="space-y-4">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Tanggal</TableHead>
                    <TableHead>Nama</TableHead>
                    <TableHead>Durasi</TableHead>
                    <TableHead>Alasan</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Final Status</TableHead>
                    <TableHead>Sisa Claim</TableHead>
                    <TableHead>Expire</TableHead>
                    <TableHead class="text-right">Aksi</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="lembur in approvalLemburs" :key="lembur.id" :class="getRowClass(lembur)">
                    <TableCell>{{ formatDate(lembur.tanggal_lembur) }}</TableCell>
                    <TableCell>
                      <div class="font-medium">{{ lembur.user?.name || 'N/A' }}</div>
                      <div class="text-sm text-muted-foreground">{{ lembur.user?.posisi || '-' }}</div>
                    </TableCell>
                    <TableCell>
                      <Badge variant="outline">{{ formatMinutes(lembur.lama_lembur) }}</Badge>
                    </TableCell>
                    <TableCell class="max-w-xs truncate">{{ lembur.alasan_lembur }}</TableCell>
                    <TableCell>
                      <Badge :variant="getStatusBadgeVariant(lembur.status)">
                        {{ lembur.status_label || lembur.status }}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <Badge :variant="getStatusBadgeVariant(lembur.final_status)">
                        {{ lembur.final_status_label || lembur.final_status }}
                      </Badge>
                    </TableCell>
                    
                    <TableCell>
                      <span 
                        v-if="isRejected(lembur) || lembur.is_expired" 
                        class="text-muted-foreground text-sm"
                      >
                        -
                      </span>
                      <div 
                        v-else-if="!isFullyApproved(lembur)" 
                        class="flex flex-col"
                      >
                        <span class="text-muted-foreground font-medium">
                          {{ formatMinutes(lembur.sisa_waktu_claim) }}
                        </span>
                        <span class="text-[10px] text-yellow-600 italic">
                          (Proses)
                        </span>
                      </div>
                      <span 
                        v-else 
                        class="text-green-600 font-bold flex items-center gap-1"
                      >
                        <CheckCircle2 class="h-3 w-3" />
                        {{ formatMinutes(lembur.sisa_waktu_claim) }}
                      </span>
                    </TableCell>

                    <TableCell>
                      <div v-if="isRejected(lembur)" class="flex items-center gap-1 text-red-600">
                        <XCircle class="h-4 w-4" />
                        <span class="text-xs font-medium">Ditolak</span>
                      </div>
                      <div v-else-if="lembur.is_expired" class="flex items-center gap-1 text-red-600">
                        <XCircle class="h-4 w-4" />
                        <span class="text-xs font-medium">Hangus</span>
                      </div>
                      <div v-else-if="lembur.is_expiring_soon" class="flex items-center gap-1 text-yellow-600">
                        <AlertTriangle class="h-4 w-4" />
                        <span class="text-xs">{{ lembur.days_until_expire }}h</span>
                      </div>
                      <span v-else class="text-xs text-muted-foreground">
                        {{ formatDate(lembur.expire_at) }}
                      </span>
                    </TableCell>

                    <TableCell class="text-right">
                      <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                          <Button variant="ghost" size="icon">
                            <MoreHorizontal class="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem @click="viewDetail(lembur)">
                            <Eye class="mr-2 h-4 w-4" />
                            Detail
                          </DropdownMenuItem>

                          <template v-if="lembur.status === 'waiting'">
                            <DropdownMenuSeparator />
                            <DropdownMenuItem @click="handleApprove(lembur.id)">
                              <Check class="mr-2 h-4 w-4" />
                              Setujui
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="handleReject(lembur.id)">
                              <X class="mr-2 h-4 w-4" />
                              Tolak
                            </DropdownMenuItem>
                          </template>

                          <template v-if="lembur.status === 'accepted' && lembur.final_status === 'waiting'">
                            <DropdownMenuSeparator />
                            <DropdownMenuItem @click="handleFinalApprove(lembur.id)">
                              <CheckCheck class="mr-2 h-4 w-4" />
                              Final Approve
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="handleFinalReject(lembur.id)">
                              <XCircle class="mr-2 h-4 w-4" />
                              Final Reject
                            </DropdownMenuItem>
                          </template>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>

              <div v-if="approvalPagination.last_page > 1" class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                  Menampilkan {{ approvalLemburs.length }} dari {{ approvalPagination.total }} data
                </div>
                <div class="flex items-center space-x-2">
                  <Button
                    variant="outline"
                    size="sm"
                    @click="changeApprovalPage(approvalPagination.current_page - 1)"
                    :disabled="approvalPagination.current_page === 1"
                  >
                    <ChevronLeft class="h-4 w-4" />
                    Previous
                  </Button>
                  <div class="text-sm">
                    Halaman {{ approvalPagination.current_page }} dari {{ approvalPagination.last_page }}
                  </div>
                  <Button
                    variant="outline"
                    size="sm"
                    @click="changeApprovalPage(approvalPagination.current_page + 1)"
                    :disabled="approvalPagination.current_page === approvalPagination.last_page"
                  >
                    Next
                    <ChevronRight class="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </TabsContent>
    </Tabs>

    <LemburFormDialog
      :open="showFormDialog"
      :lembur="editingLembur"
      @close="closeFormDialog"
      @saved="handleSaved"
    />

    <LemburDetailDialog
      :open="showDetailDialog"
      :lembur="selectedLembur"
      @close="showDetailDialog = false"
    />

    <AlertDialog :open="showDeleteDialog">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Hapus Lembur?</AlertDialogTitle>
          <AlertDialogDescription>
            Tindakan ini tidak dapat dibatalkan. Data lembur akan dihapus permanen.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel @click="showDeleteDialog = false">Batal</AlertDialogCancel>
          <AlertDialogAction @click="confirmDelete">Hapus</AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useLemburStore } from '@/stores/lemburStore'
import { useAuthStore } from '@/stores/auth'
import { useLembur } from '@/composables/useLembur'
import { debounce } from 'lodash-es'

// Components & Icons
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import {
  DropdownMenu, DropdownMenuContent, DropdownMenuItem,
  DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  AlertDialog, AlertDialogAction, AlertDialogCancel,
  AlertDialogContent, AlertDialogDescription, AlertDialogFooter,
  AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import {
  Tabs, TabsContent, TabsList, TabsTrigger
} from '@/components/ui/tabs'
import {
  Plus, Clock, AlertCircle, CheckCircle2, Timer, Search, Loader2,
  MoreHorizontal, Eye, Pencil, Check, X, Trash2, CheckCheck, XCircle,
  ChevronLeft, ChevronRight, AlertTriangle, RotateCcw, FileCheck
} from 'lucide-vue-next'

import LemburFormDialog from '@/components/Lembur/LemburFormDialog.vue'
import LemburDetailDialog from '@/components/Lembur/LemburDetailDialog.vue'

const lemburStore = useLemburStore()
const authStore = useAuthStore()
const { formatDate, formatMinutes, getStatusBadgeVariant, showSuccessToast, handleApiError } = useLembur()
const { statistics } = storeToRefs(lemburStore)

// State
const activeTab = ref('personal')
const personalLemburs = ref([])
const approvalLemburs = ref([])
const loadingPersonal = ref(true)
const loadingApproval = ref(true)

// Pagination
const personalPagination = ref({
  current_page: 1, last_page: 1, total: 0
})
const approvalPagination = ref({
  current_page: 1, last_page: 1, total: 0
})

// Dialogs
const showFormDialog = ref(false)
const showDetailDialog = ref(false)
const showDeleteDialog = ref(false)
const editingLembur = ref(null)
const selectedLembur = ref(null)
const deletingId = ref(null)
const monthInput = ref('')

// Filters
const filters = ref({
  status: 'all', final_status: 'all', search: '', year: '', month: '',
  personal_page: 1, approval_page: 1, per_page: 15
})

// --- LOGIC PERBAIKAN ---

const isFullyApproved = (lembur) => {
  return lembur.status === 'accepted' && lembur.final_status === 'accepted'
}

const isRejected = (lembur) => {
  return lembur.status === 'rejected' || lembur.final_status === 'rejected'
}

const getRowClass = (lembur) => {
  if (isRejected(lembur)) return 'bg-red-50/50 opacity-70' // Rejected: merah pudar & transparan
  if (lembur.is_expired) return 'bg-gray-100 opacity-70' // Expired: abu pudar
  if (isFullyApproved(lembur)) return 'bg-green-50/30' // Approved: hijau sangat tipis
  return ''
}

// Role Access
const canApprove = computed(() => {
  return authStore.hasAnyRole(['Admin', 'HR', 'Direktur'])
})

const currentUserId = computed(() => {
  return authStore.user?.id || parseInt(localStorage.getItem('user_id'))
})

// Data Fetching
const fetchPersonalLemburs = async () => {
  loadingPersonal.value = true
  try {
    const params = {
      status: filters.value.status,
      final_status: filters.value.final_status,
      search: filters.value.search,
      year: filters.value.year,
      month: filters.value.month,
      page: filters.value.personal_page,
      per_page: filters.value.per_page,
      user_id: currentUserId.value
    }
    await lemburStore.fetchLemburs(params.page, params)
    
    // Store result to local state
    if (lemburStore.lemburs && Array.isArray(lemburStore.lemburs)) {
      personalLemburs.value = lemburStore.lemburs.filter(l => l.user_id === currentUserId.value)
      personalPagination.value = {
        current_page: lemburStore.pagination?.current_page || 1,
        last_page: lemburStore.pagination?.last_page || 1,
        total: personalLemburs.value.length
      }
    }
  } catch (error) {
    handleApiError(error)
  } finally {
    loadingPersonal.value = false
  }
}

const fetchApprovalLemburs = async () => {
  if (!canApprove.value) return
  loadingApproval.value = true
  try {
    const params = {
      status: filters.value.status,
      final_status: filters.value.final_status,
      search: filters.value.search,
      year: filters.value.year,
      month: filters.value.month,
      page: filters.value.approval_page,
      per_page: filters.value.per_page
    }
    await lemburStore.fetchLemburs(params.page, params)
    
    if (lemburStore.lemburs && Array.isArray(lemburStore.lemburs)) {
      approvalLemburs.value = lemburStore.lemburs
      approvalPagination.value = {
        current_page: lemburStore.pagination?.current_page || 1,
        last_page: lemburStore.pagination?.last_page || 1,
        total: lemburStore.pagination?.total || 0
      }
    }
  } catch (error) {
    handleApiError(error)
  } finally {
    loadingApproval.value = false
  }
}

// Global Loader
const loadData = async () => {
  await Promise.all([
    fetchPersonalLemburs(),
    canApprove.value ? fetchApprovalLemburs() : Promise.resolve(),
    lemburStore.fetchStatistics()
  ])
}

// Filter Handlers
const initFilters = () => {
  const now = new Date()
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  monthInput.value = `${year}-${month}`
  filters.value.year = year.toString()
  filters.value.month = month
}

const resetFilters = () => {
  filters.value = {
    status: 'all', final_status: 'all', search: '', year: '', month: '',
    personal_page: 1, approval_page: 1, per_page: 15
  }
  initFilters()
  loadData()
}

const handleFilterChange = () => {
  filters.value.personal_page = 1
  filters.value.approval_page = 1
  loadData()
}

const handleMonthChange = () => {
  if (monthInput.value) {
    const [year, month] = monthInput.value.split('-')
    filters.value.year = year
    filters.value.month = month
  } else {
    filters.value.year = ''
    filters.value.month = ''
  }
  handleFilterChange()
}

const debouncedSearch = debounce(() => {
  handleFilterChange()
}, 500)

// Pagination Handlers
const changePersonalPage = (page) => {
  if (page >= 1 && page <= personalPagination.value.last_page) {
    filters.value.personal_page = page
    fetchPersonalLemburs()
  }
}

const changeApprovalPage = (page) => {
  if (page >= 1 && page <= approvalPagination.value.last_page) {
    filters.value.approval_page = page
    fetchApprovalLemburs()
  }
}

// Dialog Logic
const openCreateDialog = () => {
  editingLembur.value = null
  showFormDialog.value = true
}

const editLembur = (lembur) => {
  editingLembur.value = lembur
  showFormDialog.value = true
}

const viewDetail = (lembur) => {
  selectedLembur.value = lembur
  showDetailDialog.value = true
}

const closeFormDialog = () => {
  showFormDialog.value = false
  editingLembur.value = null
}

const handleSaved = () => {
  closeFormDialog()
  showSuccessToast('Lembur berhasil disimpan')
  loadData()
}

// Actions
const handleApprove = async (id) => {
  try {
    await lemburStore.approveLembur(id)
    showSuccessToast('Lembur berhasil disetujui')
    loadData()
  } catch (error) {
    handleApiError(error)
  }
}

const handleReject = async (id) => {
  try {
    await lemburStore.rejectLembur(id)
    showSuccessToast('Lembur berhasil ditolak')
    loadData()
  } catch (error) {
    handleApiError(error)
  }
}

const handleFinalApprove = async (id) => {
  try {
    await lemburStore.finalApproveLembur(id)
    showSuccessToast('Lembur berhasil final approved')
    loadData()
  } catch (error) {
    handleApiError(error)
  }
}

const handleFinalReject = async (id) => {
  try {
    await lemburStore.finalRejectLembur(id)
    showSuccessToast('Lembur berhasil final rejected')
    loadData()
  } catch (error) {
    handleApiError(error)
  }
}

const handleDelete = (id) => {
  deletingId.value = id
  showDeleteDialog.value = true
}

const confirmDelete = async () => {
  try {
    await lemburStore.deleteLembur(deletingId.value)
    showSuccessToast('Lembur berhasil dihapus')
    showDeleteDialog.value = false
    deletingId.value = null
    loadData()
  } catch (error) {
    handleApiError(error)
  }
}

const showExpiringSoon = () => {
  console.log('Show expiring soon')
}

onMounted(async () => {
  if (!authStore.user) await authStore.fetchUser()
  initFilters()
  loadData()
})
</script>