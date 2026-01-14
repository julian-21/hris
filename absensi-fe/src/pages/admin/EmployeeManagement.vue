<template>
  <div class="employee-management p-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div class="space-y-1">
        <h1 class="text-3xl font-bold tracking-tight">Manajemen Karyawan</h1>
        <p class="text-sm text-muted-foreground">Kelola data karyawan perusahaan</p>
      </div>
      <Button @click="openModal('add')" size="default" class="w-full md:w-auto">
        <Plus class="mr-2 h-4 w-4" />
        Tambah Karyawan
      </Button>
    </div>

    <!-- Stats Cards -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4" v-if="stats">
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium pb-2">Total Karyawan</CardTitle>
          <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center">
            <Users class="h-4 w-4 text-primary" />
          </div>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ stats.total_employees || 0 }}</div>
          <p class="text-xs text-muted-foreground">
            {{ stats.active_employees || 0 }} karyawan aktif
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium pb-2">Perusahaan</CardTitle>
          <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center">
            <Building2 class="h-4 w-4 text-primary" />
          </div>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ stats.by_company?.length || 0 }}</div>
          <p class="text-xs text-muted-foreground">Unit bisnis</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium pb-2">Kantor Aktif</CardTitle>
          <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center">
            <MapPin class="h-4 w-4 text-primary" />
          </div>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ stats.by_office?.length || 0 }}</div>
          <p class="text-xs text-muted-foreground">Lokasi kerja</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium pb-2">Bergabung Bulan Ini</CardTitle>
          <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center">
            <Award class="h-4 w-4 text-primary" />
          </div>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ stats.new_this_month || 0 }}</div>
          <p class="text-xs text-muted-foreground">Karyawan baru</p>
        </CardContent>
      </Card>
    </div>

    <!-- View Mode & Filters -->
    <Card>
      <CardHeader class="pb-4">
        <div class="flex items-center justify-between">
          <CardTitle class="text-base font-medium">Filter & Tampilan</CardTitle>
          <Button 
            v-if="hasActiveFilters" 
            @click="resetFilters" 
            variant="ghost" 
            size="sm"
            class="h-8 px-2 lg:px-3">
            <RotateCcw class="mr-2 h-3 w-3" />
            Reset Filter
          </Button>
        </div>
      </CardHeader>
      <CardContent class="space-y-4">
        <!-- View Mode Tabs -->
        <Tabs v-model="viewMode" class="w-full">
          <TabsList class="grid w-full grid-cols-3">
            <TabsTrigger value="list" @click="viewMode = 'list'">Daftar</TabsTrigger>
            <TabsTrigger value="cards" @click="viewMode = 'cards'">Kartu</TabsTrigger>
            <TabsTrigger value="hierarchy" @click="handleHierarchyClick">Hierarki</TabsTrigger>
          </TabsList>
        </Tabs>

        <!-- Filters -->
        <div class="grid gap-3 grid-cols-1 md:grid-cols-2 lg:grid-cols-5">
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input 
              v-model="filters.search" 
              @input="debounceSearch"
              placeholder="Cari karyawan..." 
              class="pl-9 h-10"
            />
          </div>

          <Select v-model="filters.company" @update:model-value="loadEmployees">
            <SelectTrigger class="h-10">
              <SelectValue placeholder="Semua Perusahaan" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="company in options.companies" :key="company" :value="company">
                {{ company }}
              </SelectItem>
            </SelectContent>
          </Select>

          <Select v-model="filters.role" @update:model-value="loadEmployees">
            <SelectTrigger class="h-10">
              <SelectValue placeholder="Semua Role" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="role in options.roles" :key="role.id" :value="role.name">
                {{ role.name }}
              </SelectItem>
            </SelectContent>
          </Select>

          <Select v-model="filters.kantor_id" @update:model-value="loadEmployees">
            <SelectTrigger class="h-10">
              <SelectValue placeholder="Semua Kantor" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="office in options.offices" :key="office.id" :value="office.id.toString()">
                {{ office.nama }}
              </SelectItem>
            </SelectContent>
          </Select>

          <Select v-model="filters.is_active" @update:model-value="loadEmployees">
            <SelectTrigger class="h-10">
              <SelectValue placeholder="Semua Status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="1">Aktif</SelectItem>
              <SelectItem value="0">Nonaktif</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </CardContent>
    </Card>

    <!-- Error State -->
    <Alert v-if="error && !loading" variant="destructive">
      <AlertCircle class="h-4 w-4" />
      <AlertTitle>Gagal Memuat Data</AlertTitle>
      <AlertDescription class="flex items-center gap-2">
        {{ error }}
        <Button @click="loadEmployees" variant="link" class="h-auto p-0 text-destructive hover:text-destructive/90">
          Coba Lagi
        </Button>
      </AlertDescription>
    </Alert>

    <!-- Loading State -->
    <Card v-if="loading">
    <CardContent class="flex flex-col items-center justify-center min-h-[300px]">
        <Loader2 class="h-8 w-8 animate-spin text-primary mb-4" />
        <p class="text-sm text-muted-foreground">Memuat data karyawan...</p>
    </CardContent>
    </Card>

    <!-- Empty State -->
    <Card v-else-if="!employees.data || employees.data.length === 0">
      <CardContent class="flex flex-col items-center justify-center py-16">
        <div class="rounded-full bg-muted p-6 mb-4">
          <Users class="h-12 w-12 text-muted-foreground" />
        </div>
        <h3 class="text-lg font-semibold mb-2">Belum Ada Data Karyawan</h3>
        <p class="text-sm text-muted-foreground mb-6 text-center max-w-sm">
          {{ hasActiveFilters ? 'Tidak ada karyawan yang sesuai dengan filter. Coba ubah kriteria pencarian.' : 'Mulai tambahkan karyawan pertama Anda untuk mengelola tim.' }}
        </p>
        <div class="flex gap-2">
          <Button v-if="hasActiveFilters" @click="resetFilters" variant="outline">
            <RotateCcw class="mr-2 h-4 w-4" />
            Reset Filter
          </Button>
          <Button @click="openModal('add')">
            <Plus class="mr-2 h-4 w-4" />
            Tambah Karyawan
          </Button>
        </div>
      </CardContent>
    </Card>

    <!-- List View -->
    <Card v-else-if="viewMode === 'list'" class="overflow-hidden">
      <div class="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-[280px]">Karyawan</TableHead>
              <TableHead class="w-[180px]">Perusahaan</TableHead>
              <TableHead class="w-[160px]">Posisi</TableHead>
              <TableHead class="w-[160px]">Kantor</TableHead>
              <TableHead class="w-[160px]">Atasan</TableHead>
              <TableHead class="w-[220px]">Masa Kerja</TableHead>
              <TableHead class="w-[100px]">Status</TableHead>
              <TableHead class="w-[140px] text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="emp in employees.data" :key="emp.id" class="group">
              <TableCell>
                <div class="flex items-center gap-3">
                  <Avatar class="h-10 w-10">
                    <AvatarImage 
                      :src="getEmployeePhoto(emp)" 
                      :alt="emp.name"
                      @error="handleImageError" 
                    />
                    <AvatarFallback>{{ getInitials(emp.name) }}</AvatarFallback>
                  </Avatar>
                  <div class="min-w-0 flex-1">
                    <p class="font-medium text-sm truncate">{{ emp.name }}</p>
                    <p class="text-xs text-muted-foreground truncate">{{ emp.email }}</p>
                  </div>
                </div>
              </TableCell>
              <TableCell class="text-sm">{{ emp.company || '-' }}</TableCell>
              <TableCell class="text-sm">{{ emp.posisi || '-' }}</TableCell>
              <TableCell class="text-sm">{{ emp.kantor?.nama || '-' }}</TableCell>
              <TableCell class="text-sm">{{ emp.atasan?.name || '-' }}</TableCell>
              <TableCell class="text-sm">{{ emp.masa_kerja || '-' }}</TableCell>
              <TableCell>
                <Badge :variant="emp.is_active ? 'default' : 'destructive'" class="text-xs font-normal">
                  {{ emp.is_active ? 'Aktif' : 'Nonaktif' }}
                </Badge>
              </TableCell>
              <TableCell>
                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <Button @click="viewEmployee(emp.id)" variant="ghost" size="icon" class="h-8 w-8" title="Lihat Detail">
                    <Eye class="h-4 w-4" />
                  </Button>
                  <Button @click="openModal('edit', emp)" variant="ghost" size="icon" class="h-8 w-8" title="Edit">
                    <Edit class="h-4 w-4" />
                  </Button>
                  <Button @click="deleteEmployee(emp.id)" variant="ghost" size="icon" class="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10" title="Hapus">
                    <Trash2 class="h-4 w-4" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Pagination -->
      <CardFooter class="flex items-center justify-between border-t pt-4 px-6 py-4">
        <p class="text-sm text-muted-foreground">
          Menampilkan <span class="font-medium">{{ employees.from || 0 }}</span> - <span class="font-medium">{{ employees.to || 0 }}</span> dari <span class="font-medium">{{ employees.total || 0 }}</span> karyawan
        </p>
        <div class="flex gap-2">
          <Button 
            @click="changePage(employees.current_page - 1)" 
            :disabled="!employees.prev_page_url"
            variant="outline"
            size="sm">
            Sebelumnya
          </Button>
          <Button 
            @click="changePage(employees.current_page + 1)" 
            :disabled="!employees.next_page_url"
            variant="outline"
            size="sm">
            Selanjutnya
          </Button>
        </div>
      </CardFooter>
    </Card>

    <!-- Cards View -->
    <div v-else-if="viewMode === 'cards'" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <Card v-for="emp in employees.data" :key="emp.id" class="overflow-hidden group hover:shadow-md transition-shadow">
        <CardHeader class="pb-3">
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3 flex-1 min-w-0">
              <Avatar class="h-12 w-12">
                <AvatarImage 
                  :src="getEmployeePhoto(emp)" 
                  :alt="emp.name"
                  @error="handleImageError"
                />
                <AvatarFallback>{{ getInitials(emp.name) }}</AvatarFallback>
              </Avatar>
              <div class="min-w-0 flex-1">
                <CardTitle class="text-base truncate">{{ emp.name }}</CardTitle>
                <p class="text-sm text-muted-foreground truncate">{{ emp.email }}</p>
              </div>
            </div>
            <Badge :variant="emp.is_active ? 'default' : 'destructive'" class="text-xs font-normal shrink-0 ml-2">
              {{ emp.is_active ? 'Aktif' : 'Nonaktif' }}
            </Badge>
          </div>
        </CardHeader>
        <CardContent class="space-y-2">
          <div class="flex items-center gap-2 text-sm">
            <Building2 class="h-4 w-4 text-muted-foreground shrink-0" />
            <span class="truncate">{{ emp.company || '-' }}</span>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <Award class="h-4 w-4 text-muted-foreground shrink-0" />
            <span class="truncate">{{ emp.posisi || '-' }}</span>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <MapPin class="h-4 w-4 text-muted-foreground shrink-0" />
            <span class="truncate">{{ emp.kantor?.nama || '-' }}</span>
          </div>
          <div class="flex items-center gap-2 text-sm" v-if="emp.atasan">
            <UserCircle class="h-4 w-4 text-muted-foreground shrink-0" />
            <span class="truncate">Atasan: {{ emp.atasan.name }}</span>
          </div>
        </CardContent>
        <CardFooter class="flex gap-2 pt-3 border-t">
          <Button @click="viewEmployee(emp.id)" variant="outline" size="sm" class="flex-1">
            <Eye class="mr-2 h-3 w-3" />
            Detail
          </Button>
          <Button @click="openModal('edit', emp)" variant="outline" size="sm" class="flex-1">
            <Edit class="mr-2 h-3 w-3" />
            Edit
          </Button>
          <Button @click="deleteEmployee(emp.id)" variant="destructive" size="sm" class="px-3">
            <Trash2 class="h-3 w-3" />
          </Button>
        </CardFooter>
      </Card>

      <!-- Pagination for Cards -->
      <Card class="col-span-full">
        <CardFooter class="flex items-center justify-between pt-4 px-6 py-4">
          <p class="text-sm text-muted-foreground">
            Menampilkan <span class="font-medium">{{ employees.from || 0 }}</span> - <span class="font-medium">{{ employees.to || 0 }}</span> dari <span class="font-medium">{{ employees.total || 0 }}</span> karyawan
          </p>
          <div class="flex gap-2">
            <Button 
              @click="changePage(employees.current_page - 1)" 
              :disabled="!employees.prev_page_url"
              variant="outline"
              size="sm">
              Sebelumnya
            </Button>
            <Button 
              @click="changePage(employees.current_page + 1)" 
              :disabled="!employees.next_page_url"
              variant="outline"
              size="sm">
              Selanjutnya
            </Button>
          </div>
        </CardFooter>
      </Card>
    </div>

    <!-- Hierarchy View -->
    <Card v-else-if="viewMode === 'hierarchy'">
      <CardHeader>
        <CardTitle class="text-base font-medium">Hierarki Organisasi</CardTitle>
      </CardHeader>
      <CardContent>
        <div v-if="hierarchyData.length === 0" class="flex flex-col items-center justify-center py-16">
          <div class="rounded-full bg-muted p-6 mb-4">
            <Users class="h-12 w-12 text-muted-foreground" />
          </div>
          <h3 class="text-lg font-semibold mb-2">Tidak Ada Data Hierarki</h3>
          <p class="text-sm text-muted-foreground text-center max-w-sm">
            Belum ada struktur hierarki yang tersedia untuk ditampilkan.
          </p>
        </div>
        <div v-else class="space-y-4">
          <EmployeeHierarchyNode v-for="emp in hierarchyData" :key="emp.id" :employee="emp" :level="0" />
        </div>
      </CardContent>
    </Card>

    <!-- Modal -->
    <EmployeeModal 
      v-if="showModal"
      :mode="modalMode"
      :employee="selectedEmployee"
      :options="options"
      @close="closeModal"
      @saved="handleSaved"
    />

    <!-- Detail Modal -->
    <EmployeeDetailModal 
      v-if="showDetailModal"
      :employeeId="selectedEmployeeId"
      @close="showDetailModal = false"
      @edit="openModal('edit', $event)"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Users, Plus, Search, Edit, Trash2, Eye, Building2, UserCircle, MapPin, Award, AlertCircle, RotateCcw, Loader2 } from 'lucide-vue-next';
import axios from 'axios';

// shadcn/ui components
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

// Custom components
import EmployeeModal from '@/components/employee/EmployeeModal.vue';
import EmployeeDetailModal from '@/components/employee/EmployeeDetailModal.vue';
import EmployeeHierarchyNode from '@/components/employee/EmployeeHierarchyNode.vue';

// Setup axios
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'https://mbg.erpdis.com/api',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => Promise.reject(error)
);

const loading = ref(true);
const error = ref(null);
const employees = ref({ data: [] });
const stats = ref(null);
const options = ref({ roles: [], offices: [], companies: [], positions: [], potential_atasan: [] });
const hierarchyData = ref([]);
const viewMode = ref('list');
const showModal = ref(false);
const showDetailModal = ref(false);
const modalMode = ref('add');
const selectedEmployee = ref(null);
const selectedEmployeeId = ref(null);

const filters = ref({
  search: '',
  company: '',
  role: '',
  kantor_id: '',
  is_active: '',
  page: 1
});

let searchTimeout = null;

const hasActiveFilters = computed(() => {
  return filters.value.search || 
         filters.value.company || 
         filters.value.role || 
         filters.value.kantor_id || 
         filters.value.is_active;
});

// Helper untuk get foto karyawan dengan fallback
const getEmployeePhoto = (employee) => {
  // Priority: picture_url (dari accessor) > avatar_url > default
  if (employee.picture_url) return employee.picture_url;
  if (employee.avatar_url) return employee.avatar_url;
  return getDefaultAvatar(employee.name);
};

// Helper untuk get initials
const getInitials = (name) => {
  if (!name) return 'U';
  const names = name.split(' ');
  if (names.length === 1) return names[0].substring(0, 2).toUpperCase();
  return (names[0][0] + names[names.length - 1][0]).toUpperCase();
};

// Handle image error
const handleImageError = (event) => {
  // Fallback ke default avatar jika image gagal load
  const name = event.target.alt || 'User';
  event.target.src = getDefaultAvatar(name);
};

const getDefaultAvatar = (name) => {
  if (!name) return 'https://ui-avatars.com/api/?name=U&background=random&color=fff&size=200';
  const initials = name.split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
  return `https://ui-avatars.com/api/?name=${initials}&background=random&color=fff&size=200`;
};

onMounted(async () => {
  await loadEmployees();
  await loadStats();
  await loadOptions();
});

const loadEmployees = async () => {
  loading.value = true;
  error.value = null;
  
  try {
    const params = new URLSearchParams();
    Object.keys(filters.value).forEach(key => {
      if (filters.value[key]) params.append(key, filters.value[key]);
    });

    const response = await api.get(`/employees?${params.toString()}`);
    employees.value = response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal memuat data karyawan. Silakan cek koneksi dan coba lagi.';
  } finally {
    loading.value = false;
  }
};

const loadStats = async () => {
  try {
    const response = await api.get('/employees/stats');
    stats.value = response.data;
  } catch (err) {
    console.error('Error loading stats:', err);
  }
};

const loadOptions = async () => {
  try {
    const response = await api.get('/employees/options');
    options.value = response.data;
  } catch (err) {
    console.error('Error loading options:', err);
  }
};

const loadHierarchy = async () => {
  viewMode.value = 'hierarchy';
  try {
    const response = await api.get('/employees/hierarchy');
    hierarchyData.value = response.data;
  } catch (err) {
    console.error('Error loading hierarchy:', err);
    alert('Gagal memuat hierarki karyawan');
  }
};

const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadEmployees();
  }, 500);
};

const changePage = (page) => {
  filters.value.page = page;
  loadEmployees();
};

const resetFilters = () => {
  filters.value = {
    search: '',
    company: '',
    role: '',
    kantor_id: '',
    is_active: '',
    page: 1
  };
  loadEmployees();
};

const handleHierarchyClick = () => {
  viewMode.value = 'hierarchy';
  loadHierarchy();
};

const openModal = (mode, employee = null) => {
  modalMode.value = mode;
  selectedEmployee.value = employee;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  selectedEmployee.value = null;
};

const handleSaved = () => {
  closeModal();
  loadEmployees();
  loadStats();
};

const viewEmployee = (id) => {
  selectedEmployeeId.value = id;
  showDetailModal.value = true;
};

const deleteEmployee = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus karyawan ini?')) return;

  try {
    await api.delete(`/employees/${id}`);
    alert('Karyawan berhasil dihapus');
    loadEmployees();
    loadStats();
  } catch (err) {
    console.error('Error deleting employee:', err);
    alert(err.response?.data?.message || 'Gagal menghapus karyawan');
  }
};
</script>