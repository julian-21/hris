<template>
  <div class="container mx-auto px-4 py-8 max-w-7xl space-y-6">
    
    <Alert v-if="!authStore.isAuthenticated" variant="destructive">
      <AlertCircle class="h-4 w-4" />
      <AlertTitle>Authentication Required</AlertTitle>
      <AlertDescription class="mt-2">
        <p class="mb-2">Anda belum login. Silakan login terlebih dahulu.</p>
        <Button @click="handleAuthError" size="sm">Login</Button>
      </AlertDescription>
    </Alert>

    <template v-else>
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight">Manajemen Cuti</h1>
          <p class="text-sm text-muted-foreground mt-1">Kelola pengajuan cuti karyawan</p>
        </div>
        <Button @click="openFormModal" :disabled="loading || leaveTypes.length === 0">
          <Plus class="mr-2 h-4 w-4" />
          Ajukan Cuti
        </Button>
      </div>

      <div v-if="initialLoading" class="flex flex-col items-center justify-center py-32 space-y-4">
        <div class="relative">
          <div class="w-12 h-12 border-4 border-muted rounded-full"></div>
          <div class="absolute top-0 left-0 w-12 h-12 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
        </div>
        <div class="text-center">
          <h3 class="text-sm font-medium">Memuat Data</h3>
          <p class="text-xs text-muted-foreground mt-1">Mohon tunggu sebentar...</p>
        </div>
      </div>

      <Card v-else-if="errorState" class="border-destructive/50 border-dashed bg-destructive/5">
        <CardContent class="pt-6">
          <div class="flex flex-col items-center text-center space-y-3">
            <div class="p-2 bg-destructive/10 rounded-full">
              <AlertCircle class="h-6 w-6 text-destructive" />
            </div>
            <div>
              <h3 class="text-sm font-semibold text-destructive">Gagal Memuat Data</h3>
              <p class="text-xs text-muted-foreground mt-1">{{ errorState }}</p>
            </div>
            <Button @click="retryLoad" variant="outline" size="sm">
              <RefreshCw class="mr-2 h-3.5 w-3.5" />
              Coba Lagi
            </Button>
          </div>
        </CardContent>
      </Card>

      <template v-else>
        <div v-if="statistics" class="grid gap-4 md:grid-cols-4">
          <Card class="shadow-sm">
            <CardContent class="pt-6">
              <div class="flex items-center gap-4">
                <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                  <Calendar class="h-5 w-5" />
                </div>
                <div>
                  <p class="text-xs font-medium text-muted-foreground">Total Diajukan</p>
                  <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-bold">{{ statistics.total_cuti_diajukan || 0 }}</span>
                    <span class="text-xs text-muted-foreground">Hari</span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="shadow-sm">
            <CardContent class="pt-6">
              <div class="flex items-center gap-4">
                <div class="p-2 bg-green-50 text-green-600 rounded-lg">
                  <CheckCircle2 class="h-5 w-5" />
                </div>
                <div>
                  <p class="text-xs font-medium text-muted-foreground">Disetujui</p>
                  <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-bold">{{ statistics.total_cuti_disetujui || 0 }}</span>
                    <span class="text-xs text-muted-foreground">Hari</span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="shadow-sm">
            <CardContent class="pt-6">
              <div class="flex items-center gap-4">
                <div class="p-2 bg-yellow-50 text-yellow-600 rounded-lg">
                  <Clock class="h-5 w-5" />
                </div>
                <div>
                  <p class="text-xs font-medium text-muted-foreground">Menunggu</p>
                  <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-bold">{{ statistics.total_cuti_pending || 0 }}</span>
                    <span class="text-xs text-muted-foreground">Pengajuan</span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="shadow-sm">
            <CardContent class="pt-6">
              <div class="flex items-center gap-4">
                <div class="p-2 bg-red-50 text-red-600 rounded-lg">
                  <XCircle class="h-5 w-5" />
                </div>
                <div>
                  <p class="text-xs font-medium text-muted-foreground">Ditolak</p>
                  <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-bold">{{ statistics.total_cuti_ditolak || 0 }}</span>
                    <span class="text-xs text-muted-foreground">Pengajuan</span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <Card v-if="statistics?.kuota_per_jenis?.length > 0" class="shadow-sm">
          <CardHeader class="pb-3">
            <CardTitle class="text-base">Sisa Kuota Cuti {{ currentYear }}</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
              <div v-for="kuota in statistics.kuota_per_jenis" :key="kuota.leave_type_id" class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                  <span class="font-medium">{{ kuota.nama }}</span>
                  <span class="text-muted-foreground">{{ kuota.terpakai }} / {{ kuota.kuota }}</span>
                </div>
                <div class="h-2 w-full bg-secondary rounded-full overflow-hidden">
                  <div 
                    class="h-full rounded-full transition-all duration-500"
                    :class="kuota.sisa <= 0 ? 'bg-red-500' : (kuota.sisa < 3 ? 'bg-yellow-500' : 'bg-green-500')"
                    :style="{ width: `${Math.min((kuota.terpakai / kuota.kuota) * 100, 100)}%` }"
                  ></div>
                </div>
                <p class="text-xs text-muted-foreground text-right">Sisa: <span class="font-medium text-foreground">{{ kuota.sisa }}</span> hari</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <div class="space-y-6">
          <div class="flex p-1 bg-muted/50 rounded-lg w-full md:w-fit">
            <button 
              @click="switchTab('my-leaves')"
              :class="[
                'flex-1 md:flex-none px-4 py-2 text-sm font-medium rounded-md transition-all',
                activeTab === 'my-leaves' 
                  ? 'bg-background text-foreground shadow-sm' 
                  : 'text-muted-foreground hover:text-foreground hover:bg-background/50'
              ]"
            >
              Cuti Saya
            </button>
            <button 
              v-if="isApprover"
              @click="switchTab('approvals')"
              :class="[
                'flex-1 md:flex-none px-4 py-2 text-sm font-medium rounded-md transition-all flex items-center justify-center gap-2',
                activeTab === 'approvals' 
                  ? 'bg-background text-foreground shadow-sm' 
                  : 'text-muted-foreground hover:text-foreground hover:bg-background/50'
              ]"
            >
              Perlu Approval
              <span v-if="pendingApprovalsCount > 0" class="flex h-5 w-5 items-center justify-center rounded-full bg-destructive text-[10px] text-destructive-foreground">
                {{ pendingApprovalsCount }}
              </span>
            </button>
          </div>

          <div v-show="activeTab === 'my-leaves'" class="space-y-4">
            <div class="flex flex-col md:flex-row gap-4">
              <Select v-model="myLeavesFilters.status" @update:modelValue="loadMyLeaves">
                <SelectTrigger class="w-full md:w-[180px] bg-background">
                  <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Semua Status</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="approved">Disetujui</SelectItem>
                  <SelectItem value="rejected">Ditolak</SelectItem>
                </SelectContent>
              </Select>

              <Select v-model="myLeavesFilters.leave_type_id" @update:modelValue="loadMyLeaves">
                <SelectTrigger class="w-full md:w-[180px] bg-background">
                  <SelectValue placeholder="Jenis Cuti" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Semua Jenis</SelectItem>
                  <SelectItem v-for="type in leaveTypes" :key="type.id" :value="String(type.id)">
                    {{ type.nama }}
                  </SelectItem>
                </SelectContent>
              </Select>

              <Select v-model="myLeavesFilters.year" @update:modelValue="onMyLeavesYearChange">
                <SelectTrigger class="w-full md:w-[120px] bg-background">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="y in years" :key="y" :value="String(y)">{{ y }}</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <Card class="shadow-sm">
              <div class="rounded-md">
                <Table>
                  <TableHeader>
                    <TableRow class="bg-muted/50 hover:bg-muted/50">
                      <TableHead>Tanggal</TableHead>
                      <TableHead>Jenis</TableHead>
                      <TableHead>Durasi</TableHead>
                      <TableHead>Alasan</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead class="text-right">Aksi</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-if="myLeavesLoading">
                      <TableCell colspan="6" class="h-24 text-center">
                        <div class="flex items-center justify-center gap-2 text-muted-foreground">
                          <RefreshCw class="h-4 w-4 animate-spin" />
                          Memuat data...
                        </div>
                      </TableCell>
                    </TableRow>
                    
                    <TableRow v-else-if="myLeaves.length === 0">
                      <TableCell colspan="6" class="h-32 text-center">
                        <div class="flex flex-col items-center justify-center gap-2 text-muted-foreground">
                          <Calendar class="h-8 w-8 opacity-20" />
                          <p>Tidak ada data cuti ditemukan</p>
                        </div>
                      </TableCell>
                    </TableRow>

                    <TableRow v-else v-for="leave in myLeaves" :key="leave.id" class="hover:bg-muted/5">
                      <TableCell>
                        <div class="font-medium">{{ formatDate(leave.tanggal_mulai) }}</div>
                        <div v-if="leave.tanggal_mulai !== leave.tanggal_selesai" class="text-xs text-muted-foreground">
                          s/d {{ formatDate(leave.tanggal_selesai) }}
                        </div>
                      </TableCell>
                      <TableCell>{{ leave.leave_type?.nama }}</TableCell>
                      <TableCell>
                        <div class="flex items-center gap-1">
                          <span class="font-medium">{{ leave.jumlah_hari }}</span>
                          <span class="text-xs text-muted-foreground">hari</span>
                        </div>
                      </TableCell>
                      <TableCell>
                        <div class="max-w-[200px] truncate" :title="leave.alasan">
                          {{ leave.alasan }}
                        </div>
                      </TableCell>
                      <TableCell>
                        <Badge :variant="getBadgeVariant(leave.status)">
                          {{ leave.status_label }}
                        </Badge>
                      </TableCell>
                      <TableCell class="text-right">
                        <div class="flex justify-end gap-1">
                          <Button variant="ghost" size="icon" @click="viewDetail(leave)">
                            <Eye class="h-4 w-4" />
                          </Button>
                          <template v-if="leave.status === 'pending'">
                            <Button variant="ghost" size="icon" @click="editLeave(leave)">
                              <Pencil class="h-4 w-4" />
                            </Button>
                            <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive" @click="deleteLeave(leave)">
                              <Trash2 class="h-4 w-4" />
                            </Button>
                          </template>
                        </div>
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
              
              <div v-if="myLeavesPagination.total > 0" class="flex items-center justify-between p-4 border-t">
                <p class="text-xs text-muted-foreground">
                  {{ myLeaves.length }} dari {{ myLeavesPagination.total }} data
                </p>
                <div class="flex gap-2">
                  <Button 
                    variant="outline" 
                    size="sm" 
                    :disabled="myLeavesPagination.current_page === 1"
                    @click="changeMyLeavesPage(myLeavesPagination.current_page - 1)"
                  >
                    <ChevronLeft class="h-4 w-4 mr-1" /> Prev
                  </Button>
                  <Button 
                    variant="outline" 
                    size="sm"
                    :disabled="myLeavesPagination.current_page === myLeavesPagination.last_page"
                    @click="changeMyLeavesPage(myLeavesPagination.current_page + 1)"
                  >
                    Next <ChevronRight class="h-4 w-4 ml-1" />
                  </Button>
                </div>
              </div>
            </Card>
          </div>

          <div v-if="isApprover" v-show="activeTab === 'approvals'" class="space-y-4">
            <div class="flex gap-4">
              <Select v-model="approvalsFilters.leave_type_id" @update:modelValue="loadApprovals">
                <SelectTrigger class="w-[180px] bg-background">
                  <SelectValue placeholder="Jenis Cuti" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Semua Jenis</SelectItem>
                  <SelectItem v-for="type in leaveTypes" :key="type.id" :value="String(type.id)">
                    {{ type.nama }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <Card class="shadow-sm">
              <div class="rounded-md">
                <Table>
                  <TableHeader>
                    <TableRow class="bg-muted/50 hover:bg-muted/50">
                      <TableHead>Karyawan</TableHead>
                      <TableHead>Tanggal</TableHead>
                      <TableHead>Jenis</TableHead>
                      <TableHead>Durasi</TableHead>
                      <TableHead>Alasan</TableHead>
                      <TableHead class="text-right">Aksi</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-if="approvalsLoading">
                      <TableCell colspan="6" class="h-24 text-center">
                        <div class="flex items-center justify-center gap-2 text-muted-foreground">
                          <RefreshCw class="h-4 w-4 animate-spin" />
                          Memuat data...
                        </div>
                      </TableCell>
                    </TableRow>
                    
                    <TableRow v-else-if="approvals.length === 0">
                      <TableCell colspan="6" class="h-32 text-center">
                        <div class="flex flex-col items-center justify-center gap-2 text-muted-foreground">
                          <ClipboardCheck class="h-8 w-8 opacity-20" />
                          <p>Tidak ada pengajuan yang perlu disetujui</p>
                        </div>
                      </TableCell>
                    </TableRow>

                    <TableRow v-else v-for="leave in approvals" :key="leave.id" class="hover:bg-muted/5">
                      <TableCell>
                        <div class="font-medium">{{ leave.user?.name }}</div>
                        <div class="text-xs text-muted-foreground">{{ leave.user?.email }}</div>
                      </TableCell>
                      <TableCell>
                        <div class="font-medium">{{ formatDate(leave.tanggal_mulai) }}</div>
                        <div v-if="leave.tanggal_mulai !== leave.tanggal_selesai" class="text-xs text-muted-foreground">
                          s/d {{ formatDate(leave.tanggal_selesai) }}
                        </div>
                      </TableCell>
                      <TableCell>{{ leave.leave_type?.nama }}</TableCell>
                      <TableCell>
                        <span class="font-medium">{{ leave.jumlah_hari }}</span> hari
                      </TableCell>
                      <TableCell>
                        <div class="max-w-[200px] truncate" :title="leave.alasan">
                          {{ leave.alasan }}
                        </div>
                      </TableCell>
                      <TableCell class="text-right">
                        <div class="flex justify-end gap-1">
                          <Button variant="ghost" size="icon" @click="viewDetail(leave)">
                            <Eye class="h-4 w-4" />
                          </Button>
                          <Button size="icon" class="bg-green-600 hover:bg-green-700 h-8 w-8" @click="handleApproval(leave, 'approve')">
                            <Check class="h-4 w-4 text-white" />
                          </Button>
                          <Button size="icon" variant="destructive" class="h-8 w-8" @click="handleApproval(leave, 'reject')">
                            <X class="h-4 w-4" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
              
              <div v-if="approvalsPagination.total > 0" class="flex items-center justify-between p-4 border-t">
                <p class="text-xs text-muted-foreground">
                  {{ approvals.length }} dari {{ approvalsPagination.total }} data
                </p>
                <div class="flex gap-2">
                  <Button 
                    variant="outline" 
                    size="sm" 
                    :disabled="approvalsPagination.current_page === 1"
                    @click="changeApprovalsPage(approvalsPagination.current_page - 1)"
                  >
                    <ChevronLeft class="h-4 w-4 mr-1" /> Prev
                  </Button>
                  <Button 
                    variant="outline" 
                    size="sm"
                    :disabled="approvalsPagination.current_page === approvalsPagination.last_page"
                    @click="changeApprovalsPage(approvalsPagination.current_page + 1)"
                  >
                    Next <ChevronRight class="h-4 w-4 ml-1" />
                  </Button>
                </div>
              </div>
            </Card>
          </div>
        </div>
      </template>
    </template>

    <LeaveFormModal 
      v-if="showFormModal"
      :leave="selectedLeave"
      :leaveTypes="leaveTypes"
      @close="closeFormModal"
      @saved="onLeaveSaved"
    />

    <LeaveDetailModal 
      v-if="showDetailModal"
      :leave="selectedLeave"
      @close="closeDetailModal"
    />

    <ApprovalModal
      v-if="showApprovalModal"
      :leave="selectedLeave"
      :action="approvalAction"
      @close="closeApprovalModal"
      @confirmed="onApprovalConfirmed"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useToast } from '@/composables/useToast';
import api from '../../services/api';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';

import { 
  Plus, Calendar, CheckCircle2, Clock, XCircle, 
  AlertCircle, RefreshCw, User, ClipboardCheck,
  Eye, Pencil, Trash2, Check, X, ChevronLeft, ChevronRight
} from 'lucide-vue-next';

import { Card, CardContent, CardHeader, CardTitle } from '../../components/ui/card';
import { Button } from '../../components/ui/button';
import { Badge } from '../../components/ui/badge';
import { Alert, AlertDescription, AlertTitle } from '../../components/ui/alert';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../components/ui/table';

import LeaveFormModal from '../../components/leave/LeaveFormModal.vue';
import LeaveDetailModal from '../../components/leave/LeaveDetailModal.vue';
import ApprovalModal from '../../components/leave/ApprovalModal.vue';

// --- (Logic Script Sama Persis dengan Sebelumnya) ---
const authStore = useAuthStore();
const { toast } = useToast();

const activeTab = ref('my-leaves');

const myLeaves = ref([]);
const myLeavesLoading = ref(false);
const myLeavesFilters = reactive({
  status: 'all',
  leave_type_id: 'all',
  year: String(new Date().getFullYear()),
});
const myLeavesPagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
});

const approvals = ref([]);
const approvalsLoading = ref(false);
const approvalsFilters = reactive({
  leave_type_id: 'all',
  year: String(new Date().getFullYear()),
});
const approvalsPagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
});

const leaveTypes = ref([]);
const initialLoading = ref(true);
const errorState = ref('');
const showFormModal = ref(false);
const showDetailModal = ref(false);
const showApprovalModal = ref(false);
const selectedLeave = ref(null);
const approvalAction = ref('approve');
const statistics = ref(null);
const currentYear = ref(new Date().getFullYear());

const years = computed(() => {
  const current = new Date().getFullYear();
  return Array.from({ length: 5 }, (_, i) => current - i);
});

const isApprover = computed(() => {
  return authStore.canApprove || authStore.hasAnyRole(['Admin', 'HR', 'Direktur']);
});

const pendingApprovalsCount = computed(() => {
  return approvalsPagination.total || 0;
});

const loadLeaveTypes = async () => {
  try {
    const response = await api.get('/leaves/types');
    if (Array.isArray(response.data)) {
      leaveTypes.value = response.data;
      return true;
    } else {
      errorState.value = 'Format data jenis cuti tidak valid';
      toast({ title: 'Error', description: 'Format data jenis cuti tidak valid', variant: 'destructive' });
      return false;
    }
  } catch (error) {
    if (error.response?.status === 401) await authStore.logout();
    errorState.value = `Gagal memuat jenis cuti: ${error.response?.data?.message || error.message}`;
    return false;
  }
};

const loadMyLeaves = async (page = 1) => {
  myLeavesLoading.value = true;
  try {
    const params = { page, per_page: myLeavesPagination.per_page };
    if (myLeavesFilters.status && myLeavesFilters.status !== 'all') params.status = myLeavesFilters.status;
    if (myLeavesFilters.leave_type_id && myLeavesFilters.leave_type_id !== 'all') params.leave_type_id = myLeavesFilters.leave_type_id;
    if (myLeavesFilters.year) params.year = myLeavesFilters.year;

    const response = await api.get('/leaves', { params });
    
    if (response.data.data) {
      myLeaves.value = response.data.data;
      myLeavesPagination.current_page = response.data.current_page || 1;
      myLeavesPagination.last_page = response.data.last_page || 1;
      myLeavesPagination.total = response.data.total || 0;
    } else if (Array.isArray(response.data)) {
      myLeaves.value = response.data;
      myLeavesPagination.total = response.data.length;
    } else {
      myLeaves.value = [];
    }
    return true;
  } catch (error) {
    myLeaves.value = [];
    toast({ title: 'Error', description: 'Gagal memuat data cuti', variant: 'destructive' });
    return false;
  } finally {
    myLeavesLoading.value = false;
  }
};

const loadApprovals = async (page = 1) => {
  if (!isApprover.value) return;
  approvalsLoading.value = true;
  try {
    const params = { page, per_page: approvalsPagination.per_page };
    if (approvalsFilters.leave_type_id && approvalsFilters.leave_type_id !== 'all') params.leave_type_id = approvalsFilters.leave_type_id;
    if (approvalsFilters.year) params.year = approvalsFilters.year;

    const response = await api.get('/leaves/pending-approvals', { params });
    
    if (response.data.data) {
      approvals.value = response.data.data;
      approvalsPagination.current_page = response.data.current_page || 1;
      approvalsPagination.last_page = response.data.last_page || 1;
      approvalsPagination.total = response.data.total || 0;
    } else if (Array.isArray(response.data)) {
      approvals.value = response.data;
      approvalsPagination.total = response.data.length;
    } else {
      approvals.value = [];
    }
    return true;
  } catch (error) {
    approvals.value = [];
    toast({ title: 'Error', description: 'Gagal memuat data persetujuan', variant: 'destructive' });
    return false;
  } finally {
    approvalsLoading.value = false;
  }
};

const loadStatistics = async () => {
  try {
    const response = await api.get('/leaves/statistics', { params: { year: myLeavesFilters.year } });
    statistics.value = response.data || null;
    return true;
  } catch (error) {
    statistics.value = null;
    return false;
  }
};

const handleAuthError = () => window.location.href = '/login';

const retryLoad = async () => {
  errorState.value = '';
  initialLoading.value = true;
  await initializeApp();
  initialLoading.value = false;
};

const initializeApp = async () => {
  try {
    if (!authStore.isAuthenticated) return;
    if (!authStore.user) await authStore.fetchUser();
    
    const typesLoaded = await loadLeaveTypes();
    if (!typesLoaded) return;
    
    await loadMyLeaves();
    await loadStatistics();
    if (isApprover.value) await loadApprovals();
  } catch (error) {
    errorState.value = 'Terjadi kesalahan fatal. Silakan refresh halaman.';
  }
};

const openFormModal = async () => {
  if (leaveTypes.value.length === 0) await loadLeaveTypes();
  selectedLeave.value = null;
  showFormModal.value = true;
};

const closeFormModal = () => {
  showFormModal.value = false;
  selectedLeave.value = null;
};

const editLeave = async (leave) => {
  if (leaveTypes.value.length === 0) await loadLeaveTypes();
  selectedLeave.value = leave;
  showFormModal.value = true;
};

const viewDetail = (leave) => {
  selectedLeave.value = leave;
  showDetailModal.value = true;
};

const closeDetailModal = () => {
  showDetailModal.value = false;
  selectedLeave.value = null;
};

const deleteLeave = async (leave) => {
  if (!confirm(`Hapus pengajuan cuti ${leave.leave_type?.nama}?`)) return;
  try {
    await api.delete(`/leaves/${leave.id}`);
    toast({ title: 'Berhasil Dihapus', description: 'Pengajuan cuti berhasil dihapus', variant: 'default' });
    await Promise.all([loadMyLeaves(myLeavesPagination.current_page), loadStatistics()]);
  } catch (error) {
    toast({ title: 'Gagal Menghapus', description: 'Gagal menghapus cuti', variant: 'destructive' });
  }
};

const handleApproval = (leave, action) => {
  selectedLeave.value = leave;
  approvalAction.value = action;
  showApprovalModal.value = true;
};

const closeApprovalModal = () => {
  showApprovalModal.value = false;
  selectedLeave.value = null;
};

const onApprovalConfirmed = async () => {
  closeApprovalModal();
  await Promise.all([
    loadMyLeaves(myLeavesPagination.current_page),
    loadApprovals(approvalsPagination.current_page),
    loadStatistics()
  ]);
};

const onLeaveSaved = async () => {
  closeFormModal();
  await Promise.all([loadMyLeaves(myLeavesPagination.current_page), loadStatistics()]);
};

const onMyLeavesYearChange = async () => {
  await Promise.all([loadMyLeaves(1), loadStatistics()]);
};

const changeMyLeavesPage = (page) => {
  if (page >= 1 && page <= myLeavesPagination.last_page) loadMyLeaves(page);
};

const changeApprovalsPage = (page) => {
  if (page >= 1 && page <= approvalsPagination.last_page) loadApprovals(page);
};

const switchTab = (tab) => {
  activeTab.value = tab;
  if (tab === 'my-leaves' && myLeaves.value.length === 0) loadMyLeaves();
  else if (tab === 'approvals' && approvals.value.length === 0) loadApprovals();
};

onMounted(async () => {
  await initializeApp();
  initialLoading.value = false;
});

const formatDate = (date) => {
  try { return format(new Date(date), 'dd MMM yyyy', { locale: id }); } 
  catch (error) { return date; }
};

const getBadgeVariant = (status) => {
  const variants = { pending: 'secondary', approved: 'default', rejected: 'destructive' };
  return variants[status] || 'default';
};
</script>