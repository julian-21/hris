<template>
  <div class="container mx-auto p-6 space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">Klaim Lembur</h1>
        <p class="text-sm text-muted-foreground">Kelola klaim waktu lembur Anda</p>
      </div>
      <Button @click="openCreateModal">
        <Plus class="mr-2 h-4 w-4" />
        Ajukan Klaim
      </Button>
    </div>

    <Alert v-if="globalAlert.show" :variant="globalAlert.type === 'error' ? 'destructive' : 'default'">
      <AlertCircle v-if="globalAlert.type === 'error'" class="h-4 w-4" />
      <CheckCircle2 v-else class="h-4 w-4" />
      <AlertTitle>{{ globalAlert.type === 'error' ? 'Error' : 'Success' }}</AlertTitle>
      <AlertDescription>{{ globalAlert.message }}</AlertDescription>
    </Alert>

    <div class="grid gap-4 md:grid-cols-3">
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Waktu Tersedia</CardTitle>
          <Clock class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ formatMinutes(availableTime) }}</div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Klaim Disetujui</CardTitle>
          <CheckCircle2 class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ stats.approved }}</div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">Menunggu Persetujuan</CardTitle>
          <AlertCircle class="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ stats.waiting }}</div>
        </CardContent>
      </Card>
    </div>

    <Alert v-if="expiringSoon.length > 0" variant="warning">
      <AlertTriangle class="h-4 w-4" />
      <AlertTitle>Waktu Lembur Akan Expired</AlertTitle>
      <AlertDescription>
        Anda memiliki {{ expiringSoon.length }} waktu lembur yang akan expired dalam 30 hari ke depan.
        <ul class="list-disc list-inside mt-2 space-y-1">
          <li v-for="item in expiringSoon" :key="item.id">
            {{ formatMinutes(item.sisa_waktu_claim) }} - Expired dalam {{ item.days_until_expire }} hari
          </li>
        </ul>
      </AlertDescription>
    </Alert>

    <Card>
      <CardContent class="pt-6">
        <div class="grid gap-4 md:grid-cols-4">
          <div class="space-y-2">
            <Label>Status</Label>
            <Select v-model="filters.status" @update:modelValue="fetchAllClaims">
              <SelectTrigger>
                <SelectValue placeholder="Pilih status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Semua Status</SelectItem>
                <SelectItem value="waiting">Menunggu</SelectItem>
                <SelectItem value="approved">Disetujui</SelectItem>
                <SelectItem value="rejected">Ditolak</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label>Bulan</Label>
            <Select v-model="filters.month" @update:modelValue="fetchAllClaims">
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="(month, index) in months" :key="index" :value="index + 1">
                  {{ month }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label>Tahun</Label>
            <Select v-model="filters.year" @update:modelValue="fetchAllClaims">
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="year in years" :key="year" :value="year">
                  {{ year }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="flex items-end">
            <Button @click="resetFilters" variant="outline" class="w-full">
              Reset Filter
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>

    <Tabs v-model="activeTab" class="w-full">
      <TabsList class="grid w-full max-w-md grid-cols-2">
        <TabsTrigger value="personal">Klaim Saya</TabsTrigger>
        <TabsTrigger v-if="canApprove" value="approval">Approval</TabsTrigger>
      </TabsList>

      <TabsContent value="personal">
        <Card>
          <CardHeader>
            <CardTitle>Riwayat Klaim Pribadi</CardTitle>
            <CardDescription>Daftar klaim lembur yang Anda ajukan</CardDescription>
          </CardHeader>
          <CardContent class="p-0">
            <div v-if="loadingPersonal" class="p-8 text-center">
              <Loader2 class="h-8 w-8 animate-spin mx-auto" />
              <p class="mt-2 text-sm text-muted-foreground">Memuat data...</p>
            </div>

            <div v-else-if="personalClaims.length === 0" class="p-8 text-center">
              <p class="text-sm text-muted-foreground">Tidak ada data klaim pribadi</p>
              <p class="text-xs text-muted-foreground mt-1">Klik tombol "Ajukan Klaim" untuk membuat klaim baru</p>
            </div>

            <div v-else>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Tanggal</TableHead>
                    <TableHead>Waktu</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Aksi</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="claim in personalClaims" :key="claim.id" :class="getRowClass(claim)">
                    <TableCell>{{ formatDate(claim.date) }}</TableCell>
                    
                    <TableCell>
                      <span v-if="claim.status === 'rejected'" class="text-muted-foreground text-sm">
                        -
                      </span>
                      <div v-else-if="claim.status === 'waiting'" class="flex flex-col">
                        <span class="text-muted-foreground font-medium">
                          {{ formatMinutes(claim.time) }}
                        </span>
                        <span class="text-[10px] text-yellow-600 italic">
                          (Proses)
                        </span>
                      </div>
                      <span v-else-if="claim.status === 'approved'" class="text-green-600 font-bold flex items-center gap-1">
                        <CheckCircle2 class="h-3 w-3" />
                        {{ formatMinutes(claim.time) }}
                      </span>
                      <span v-else>{{ formatMinutes(claim.time) }}</span>
                    </TableCell>

                    <TableCell>
                      <Badge :variant="getStatusVariant(claim.status)">
                        {{ claim.status_label }}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div class="flex items-center gap-2">
                        <Button
                          v-if="claim.status === 'waiting'"
                          @click="handleDelete(claim)"
                          variant="ghost"
                          size="icon"
                          title="Hapus"
                        >
                          <Trash2 class="h-4 w-4" />
                        </Button>
                        <Button
                          @click="viewDetail(claim)"
                          variant="ghost"
                          size="icon"
                          title="Detail"
                        >
                          <Eye class="h-4 w-4" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>

              <div v-if="personalPagination.last_page > 1" class="flex items-center justify-between px-6 py-4 border-t">
                <div class="text-sm text-muted-foreground">
                  Halaman {{ personalPagination.current_page }} dari {{ personalPagination.last_page }} (Total: {{ personalPagination.total }} data)
                </div>
                <div class="flex gap-2">
                  <Button
                    @click="changePersonalPage(personalPagination.current_page - 1)"
                    :disabled="personalPagination.current_page === 1"
                    variant="outline"
                    size="sm"
                  >
                    Previous
                  </Button>
                  <Button
                    @click="changePersonalPage(personalPagination.current_page + 1)"
                    :disabled="personalPagination.current_page === personalPagination.last_page"
                    variant="outline"
                    size="sm"
                  >
                    Next
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
            <CardTitle>Approval Klaim Lembur</CardTitle>
            <CardDescription>Kelola approval klaim lembur karyawan</CardDescription>
          </CardHeader>
          <CardContent class="p-0">
            <div v-if="loadingApproval" class="p-8 text-center">
              <Loader2 class="h-8 w-8 animate-spin mx-auto" />
              <p class="mt-2 text-sm text-muted-foreground">Memuat data...</p>
            </div>

            <div v-else-if="approvalClaims.length === 0" class="p-8 text-center">
              <p class="text-sm text-muted-foreground">Tidak ada klaim yang perlu di-approve</p>
            </div>

            <div v-else>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Tanggal</TableHead>
                    <TableHead>Nama</TableHead>
                    <TableHead>Waktu</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Aksi</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="claim in approvalClaims" :key="claim.id" :class="getRowClass(claim)">
                    <TableCell>{{ formatDate(claim.date) }}</TableCell>
                    <TableCell>
                      <div class="font-medium">{{ claim.user?.name || '-' }}</div>
                      <div class="text-sm text-muted-foreground">{{ claim.user?.posisi || '-' }}</div>
                    </TableCell>
                    
                    <TableCell>
                      <span v-if="claim.status === 'rejected'" class="text-muted-foreground text-sm">
                        -
                      </span>
                      <div v-else-if="claim.status === 'waiting'" class="flex flex-col">
                        <span class="text-muted-foreground font-medium">
                          {{ formatMinutes(claim.time) }}
                        </span>
                        <span class="text-[10px] text-yellow-600 italic">
                          (Proses)
                        </span>
                      </div>
                      <span v-else-if="claim.status === 'approved'" class="text-green-600 font-bold flex items-center gap-1">
                        <CheckCircle2 class="h-3 w-3" />
                        {{ formatMinutes(claim.time) }}
                      </span>
                      <span v-else>{{ formatMinutes(claim.time) }}</span>
                    </TableCell>

                    <TableCell>
                      <Badge :variant="getStatusVariant(claim.status)">
                        {{ claim.status_label }}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div class="flex items-center gap-2">
                        <Button
                          v-if="claim.status === 'waiting'"
                          @click="handleApprove(claim)"
                          variant="ghost"
                          size="icon"
                          title="Setujui"
                        >
                          <Check class="h-4 w-4" />
                        </Button>
                        <Button
                          v-if="claim.status === 'waiting'"
                          @click="openRejectModal(claim)"
                          variant="ghost"
                          size="icon"
                          title="Tolak"
                        >
                          <X class="h-4 w-4" />
                        </Button>
                        <Button
                          @click="viewDetail(claim)"
                          variant="ghost"
                          size="icon"
                          title="Detail"
                        >
                          <Eye class="h-4 w-4" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>

              <div v-if="approvalPagination.last_page > 1" class="flex items-center justify-between px-6 py-4 border-t">
                <div class="text-sm text-muted-foreground">
                  Halaman {{ approvalPagination.current_page }} dari {{ approvalPagination.last_page }} (Total: {{ approvalPagination.total }} data)
                </div>
                <div class="flex gap-2">
                  <Button
                    @click="changeApprovalPage(approvalPagination.current_page - 1)"
                    :disabled="approvalPagination.current_page === 1"
                    variant="outline"
                    size="sm"
                  >
                    Previous
                  </Button>
                  <Button
                    @click="changeApprovalPage(approvalPagination.current_page + 1)"
                    :disabled="approvalPagination.current_page === approvalPagination.last_page"
                    variant="outline"
                    size="sm"
                  >
                    Next
                  </Button>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </TabsContent>
    </Tabs>

    <Dialog v-model:open="showCreateModal">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Ajukan Klaim Lembur</DialogTitle>
          <DialogDescription>Isi form berikut untuk mengajukan klaim lembur</DialogDescription>
        </DialogHeader>

        <Alert v-if="formAlert.show" :variant="formAlert.type === 'error' ? 'destructive' : 'default'">
          <AlertDescription>{{ formAlert.message }}</AlertDescription>
        </Alert>

        <form @submit.prevent="handleCreate" class="space-y-4">
          <div class="space-y-2">
            <Label for="date">Tanggal Claim <span class="text-destructive">*</span></Label>
            <Input id="date" v-model="formData.date" type="date" required />
          </div>

          <div class="space-y-2">
            <Label for="time">Waktu (menit) <span class="text-destructive">*</span></Label>
            <Input
              id="time"
              v-model.number="formData.time"
              type="number"
              min="1"
              :max="availableTime"
              required
              placeholder="Masukkan waktu dalam menit"
            />
            <p class="text-sm text-muted-foreground">
              Waktu tersedia: <span class="font-medium">{{ formatMinutes(availableTime) }}</span>
            </p>
          </div>

          <DialogFooter>
            <Button type="button" @click="closeCreateModal" variant="outline" :disabled="submitting">
              Batal
            </Button>
            <Button type="submit" :disabled="submitting">
              <Loader2 v-if="submitting" class="mr-2 h-4 w-4 animate-spin" />
              {{ submitting ? 'Menyimpan...' : 'Ajukan Klaim' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="showRejectModal">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Tolak Klaim</DialogTitle>
          <DialogDescription>Berikan alasan penolakan klaim ini</DialogDescription>
        </DialogHeader>

        <form @submit.prevent="handleReject" class="space-y-4">
          <div class="space-y-2">
            <Label for="reject-reason">Alasan Penolakan <span class="text-destructive">*</span></Label>
            <Textarea
              id="reject-reason"
              v-model="rejectReason"
              rows="4"
              required
              maxlength="500"
              placeholder="Jelaskan alasan penolakan..."
            />
            <p class="text-sm text-muted-foreground">
              {{ rejectReason.length }}/500 karakter
            </p>
          </div>

          <DialogFooter>
            <Button type="button" @click="closeRejectModal" variant="outline" :disabled="submitting">
              Batal
            </Button>
            <Button type="submit" variant="destructive" :disabled="submitting">
              <Loader2 v-if="submitting" class="mr-2 h-4 w-4 animate-spin" />
              {{ submitting ? 'Memproses...' : 'Tolak Klaim' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="showDetailModal">
      <DialogContent class="max-w-2xl">
        <DialogHeader>
          <DialogTitle>Detail Klaim</DialogTitle>
        </DialogHeader>

        <div v-if="selectedClaim" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-muted-foreground">Nama</p>
              <p class="font-medium">{{ selectedClaim.user?.name || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Posisi</p>
              <p class="font-medium">{{ selectedClaim.user?.posisi || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Perusahaan</p>
              <p class="font-medium">{{ selectedClaim.user?.company || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Email</p>
              <p class="font-medium text-sm">{{ selectedClaim.user?.email || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Tanggal Claim</p>
              <p class="font-medium">{{ formatDate(selectedClaim.date) }}</p>
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Waktu</p>
              <p class="font-medium">{{ formatMinutes(selectedClaim.time) }}</p>
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Status</p>
              <Badge :variant="getStatusVariant(selectedClaim.status)">
                {{ selectedClaim.status_label }}
              </Badge>
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Tanggal Diajukan</p>
              <p class="font-medium">{{ formatDateTime(selectedClaim.created_at) }}</p>
            </div>
          </div>

          <Alert v-if="selectedClaim.reject_reason" variant="destructive">
            <AlertTitle>Alasan Penolakan</AlertTitle>
            <AlertDescription>{{ selectedClaim.reject_reason }}</AlertDescription>
          </Alert>
        </div>

        <DialogFooter>
          <Button @click="showDetailModal = false">Tutup</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>

<script>
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { Clock, CheckCircle2, AlertCircle, Plus, Eye, Check, X, Trash2, AlertTriangle, Loader2 } from 'lucide-vue-next'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

export default {
  name: 'LemburClaimManagement',
  
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  
  components: {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Alert,
    AlertDescription,
    AlertTitle,
    Button,
    Input,
    Label,
    Textarea,
    Badge,
    Clock,
    CheckCircle2,
    AlertCircle,
    Plus,
    Eye,
    Check,
    X,
    Trash2,
    AlertTriangle,
    Loader2
  },
  
  data() {
    return {
      activeTab: 'personal',
      personalClaims: [],
      approvalClaims: [],
      loadingPersonal: true,
      loadingApproval: true,
      submitting: false,
      availableTime: 0,
      expiringSoon: [],
      
      filters: {
        status: 'all',
        month: new Date().getMonth() + 1,
        year: new Date().getFullYear(),
        personal_page: 1,
        approval_page: 1,
        per_page: 15
      },
      
      personalPagination: {
        current_page: 1,
        last_page: 1,
        total: 0
      },
      
      approvalPagination: {
        current_page: 1,
        last_page: 1,
        total: 0
      },
      
      stats: {
        approved: 0,
        waiting: 0,
        rejected: 0
      },
      
      showCreateModal: false,
      showRejectModal: false,
      showDetailModal: false,
      
      formData: {
        date: new Date().toISOString().split('T')[0],
        time: ''
      },
      
      rejectReason: '',
      selectedClaim: null,
      
      globalAlert: {
        show: false,
        type: '',
        message: ''
      },
      
      formAlert: {
        show: false,
        type: '',
        message: ''
      },
      
      months: [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
      ],
      
      canApprove: false
    }
  },
  
  computed: {
    years() {
      const currentYear = new Date().getFullYear()
      return Array.from({ length: 5 }, (_, i) => currentYear - i)
    }
  },
  
  mounted() {
    this.init()
  },
  
  methods: {
    // Helper untuk styling baris tabel (NEW)
    getRowClass(claim) {
      if (claim.status === 'rejected') return 'bg-red-50/50 opacity-70' // Merah pudar
      if (claim.status === 'approved') return 'bg-green-50/30' // Hijau sangat tipis
      return ''
    },

    async init() {
      await this.checkUserRole()
      await this.fetchCurrentUser()
      await this.fetchAvailableTime()
      await this.fetchAllClaims()
    },
    
    async fetchCurrentUser() {
      try {
        if (!this.authStore.user) {
          await this.authStore.fetchUser()
        }
        
        if (this.authStore.user) {
          localStorage.setItem('user_id', this.authStore.user.id)
          localStorage.setItem('user', JSON.stringify(this.authStore.user))
        }
      } catch (error) {
        console.error('Error fetching current user:', error)
      }
    },
    
    async checkUserRole() {
      try {
        this.canApprove = this.authStore.hasAnyRole(['Admin', 'HR', 'Direktur'])
      } catch (error) {
        console.error('Error checking user role:', error)
      }
    },
    
    async fetchAvailableTime() {
      try {
        const response = await api.get('/lembur-claims/available-time')
        if (response.data.success) {
          this.availableTime = response.data.data.available_time
          this.expiringSoon = response.data.data.expiring_soon
        }
      } catch (error) {
        console.error('Error fetching available time:', error)
      }
    },
    
    async fetchAllClaims() {
      await Promise.all([
        this.fetchPersonalClaims(),
        this.canApprove ? this.fetchApprovalClaims() : Promise.resolve()
      ])
      this.calculateStats()
    },
    
    async fetchPersonalClaims() {
      this.loadingPersonal = true
      try {
        const currentUserId = this.authStore.user?.id
        
        if (!currentUserId) {
          console.error('User ID tidak ditemukan')
          this.showGlobalAlert('error', 'User tidak ditemukan')
          return
        }
        
        const response = await api.get('/lembur-claims', {
          params: {
            status: this.filters.status,
            month: this.filters.month,
            year: this.filters.year,
            page: this.filters.personal_page,
            per_page: this.filters.per_page
          }
        })
        
        if (response.data.success) {
          if (this.canApprove) {
            this.personalClaims = response.data.data.data.filter(claim => 
              claim.user_id === currentUserId
            )
          } else {
            this.personalClaims = response.data.data.data
          }
          
          this.personalPagination = {
            current_page: response.data.data.current_page,
            last_page: response.data.data.last_page,
            total: this.canApprove ? this.personalClaims.length : response.data.data.total
          }
        }
      } catch (error) {
        console.error('Error fetching personal claims:', error)
        this.showGlobalAlert('error', 'Gagal memuat data klaim pribadi')
      } finally {
        this.loadingPersonal = false
      }
    },
    
    async fetchApprovalClaims() {
      this.loadingApproval = true
      try {
        const response = await api.get('/lembur-claims', {
          params: {
            status: this.filters.status,
            month: this.filters.month,
            year: this.filters.year,
            page: this.filters.approval_page,
            per_page: this.filters.per_page
          }
        })
        
        if (response.data.success) {
          this.approvalClaims = response.data.data.data
          this.approvalPagination = {
            current_page: response.data.data.current_page,
            last_page: response.data.data.last_page,
            total: response.data.data.total
          }
        }
      } catch (error) {
        console.error('Error fetching approval claims:', error)
        this.showGlobalAlert('error', 'Gagal memuat data approval')
      } finally {
        this.loadingApproval = false
      }
    },
    
    calculateStats() {
      const allClaims = [...this.personalClaims, ...this.approvalClaims]
      this.stats = {
        approved: allClaims.filter(c => c.status === 'approved').length,
        waiting: allClaims.filter(c => c.status === 'waiting').length,
        rejected: allClaims.filter(c => c.status === 'rejected').length
      }
    },
    
    changePersonalPage(page) {
      if (page < 1 || page > this.personalPagination.last_page) return
      this.filters.personal_page = page
      this.fetchPersonalClaims()
    },
    
    changeApprovalPage(page) {
      if (page < 1 || page > this.approvalPagination.last_page) return
      this.filters.approval_page = page
      this.fetchApprovalClaims()
    },
    
    openCreateModal() {
      this.formData = {
        date: new Date().toISOString().split('T')[0],
        time: ''
      }
      this.formAlert.show = false
      this.showCreateModal = true
    },
    
    closeCreateModal() {
      this.showCreateModal = false
      this.formData = {
        date: new Date().toISOString().split('T')[0],
        time: ''
      }
      this.formAlert.show = false
    },
    
    async handleCreate() {
      this.submitting = true
      this.formAlert.show = false
      
      try {
        const response = await api.post('/lembur-claims', this.formData)
        
        if (response.data.success) {
          this.showGlobalAlert('success', 'Klaim berhasil diajukan')
          this.closeCreateModal()
          await this.fetchAvailableTime()
          await this.fetchAllClaims()
        }
      } catch (error) {
        const message = error.response?.data?.message || 'Gagal mengajukan klaim'
        this.formAlert = {
          show: true,
          type: 'error',
          message: message
        }
      } finally {
        this.submitting = false
      }
    },
    
    async handleApprove(claim) {
      if (!confirm(`Setujui klaim dari ${claim.user?.name} sebesar ${this.formatMinutes(claim.time)}?`)) return
      
      try {
        const response = await api.post(`/lembur-claims/${claim.id}/approve`)
        
        if (response.data.success) {
          this.showGlobalAlert('success', 'Klaim berhasil disetujui')
          await this.fetchAvailableTime()
          await this.fetchAllClaims()
        }
      } catch (error) {
        const message = error.response?.data?.message || 'Gagal menyetujui klaim'
        this.showGlobalAlert('error', message)
      }
    },
    
    openRejectModal(claim) {
      this.selectedClaim = claim
      this.rejectReason = ''
      this.showRejectModal = true
    },
    
    closeRejectModal() {
      this.showRejectModal = false
      this.selectedClaim = null
      this.rejectReason = ''
    },
    
    async handleReject() {
      this.submitting = true
      
      try {
        const response = await api.post(
          `/lembur-claims/${this.selectedClaim.id}/reject`,
          { reject_reason: this.rejectReason }
        )
        
        if (response.data.success) {
          this.showGlobalAlert('success', 'Klaim berhasil ditolak')
          this.closeRejectModal()
          await this.fetchAllClaims()
        }
      } catch (error) {
        const message = error.response?.data?.message || 'Gagal menolak klaim'
        this.showGlobalAlert('error', message)
      } finally {
        this.submitting = false
      }
    },
    
    async handleDelete(claim) {
      if (!confirm('Yakin ingin menghapus klaim ini?')) return
      
      try {
        const response = await api.delete(`/lembur-claims/${claim.id}`)
        
        if (response.data.success) {
          this.showGlobalAlert('success', 'Klaim berhasil dihapus')
          await this.fetchAllClaims()
        }
      } catch (error) {
        const message = error.response?.data?.message || 'Gagal menghapus klaim'
        this.showGlobalAlert('error', message)
      }
    },
    
    viewDetail(claim) {
      this.selectedClaim = claim
      this.showDetailModal = true
    },
    
    resetFilters() {
      this.filters = {
        status: 'all',
        month: new Date().getMonth() + 1,
        year: new Date().getFullYear(),
        personal_page: 1,
        approval_page: 1,
        per_page: 15
      }
      this.fetchAllClaims()
    },
    
    showGlobalAlert(type, message) {
      this.globalAlert = { show: true, type, message }
      setTimeout(() => {
        this.globalAlert.show = false
      }, 5000)
    },
    
    formatDate(date) {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
      })
    },
    
    formatDateTime(datetime) {
      if (!datetime) return '-'
      return new Date(datetime).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    
    formatMinutes(minutes) {
      if (!minutes) return '0 menit'
      const hours = Math.floor(minutes / 60)
      const mins = minutes % 60
      
      if (hours > 0 && mins > 0) {
        return `${hours} jam ${mins} menit`
      } else if (hours > 0) {
        return `${hours} jam`
      } else {
        return `${mins} menit`
      }
    },
    
    getStatusVariant(status) {
      const variants = {
        waiting: 'secondary',
        approved: 'default',
        rejected: 'destructive'
      }
      return variants[status] || 'secondary'
    }
  }
}
</script>