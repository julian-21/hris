<template>
  <Dialog :open="true" @update:open="$emit('close')">
    <DialogContent class="max-w-3xl max-h-[90vh] p-0 gap-0 overflow-hidden">
      <!-- Header Simple -->
      <DialogHeader class="px-6 py-5 border-b">
        <div class="flex items-center justify-between">
          <div class="space-y-1">
            <DialogTitle class="text-xl font-semibold text-gray-900">
              {{ mode === 'add' ? 'Tambah Karyawan Baru' : 'Edit Data Karyawan' }}
            </DialogTitle>
            <DialogDescription class="text-sm text-gray-600">
              {{ mode === 'add' ? 'Lengkapi form berikut untuk mengajukan karyawan baru' : 'Perbarui informasi karyawan yang sudah ada' }}
            </DialogDescription>
          </div>
        </div>
      </DialogHeader>

      <!-- Form Content -->
      <div class="overflow-y-auto" style="max-height: calc(90vh - 160px);">
        <form @submit.prevent="handleSubmit" class="px-6 py-5 space-y-5">
          
          <!-- Foto Profil -->
          <div class="space-y-2">
            <Label class="text-sm font-medium text-gray-900">Foto Profil</Label>
            <div class="flex items-center gap-4">
              <Avatar class="w-20 h-20 border-2 border-gray-200">
                <AvatarImage :src="previewImage || formData.avatar_url || getDefaultAvatar()" />
                <AvatarFallback class="bg-gray-100">
                  <User class="w-8 h-8 text-gray-400" />
                </AvatarFallback>
              </Avatar>
              
              <div class="flex-1 space-y-2">
                <input 
                  type="file" 
                  ref="fileInput"
                  @change="handleFileChange"
                  accept="image/*"
                  class="hidden">
                <div class="flex gap-2">
                  <Button 
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="$refs.fileInput.click()"
                    class="text-sm">
                    Pilih Foto
                  </Button>
                  <Button 
                    v-if="previewImage || formData.avatar_url"
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="previewImage = null; formData.avatar_url = ''; formData.picture = null"
                    class="text-sm text-red-600 hover:text-red-700">
                    Hapus Foto
                  </Button>
                </div>
                <p class="text-xs text-gray-500">Format: JPG, PNG. Maksimal 2MB</p>
              </div>
            </div>
          </div>

          <Separator />

          <!-- Nama Lengkap -->
          <div class="space-y-2">
            <Label for="name" class="text-sm font-medium text-gray-900">
              Nama Lengkap <span class="text-red-500">*</span>
            </Label>
            <Input 
              id="name"
              v-model="formData.name" 
              type="text" 
              required
              placeholder="Masukkan nama lengkap"
              class="h-10" />
          </div>

          <!-- Email -->
          <div class="space-y-2">
            <Label for="email" class="text-sm font-medium text-gray-900">
              Email <span class="text-red-500">*</span>
            </Label>
            <Input 
              id="email"
              v-model="formData.email" 
              type="email" 
              required
              placeholder="nama@email.com"
              class="h-10" />
          </div>

          <!-- Nomor Telepon -->
          <div class="space-y-2">
            <Label for="phone" class="text-sm font-medium text-gray-900">Nomor Telepon</Label>
            <Input 
              id="phone"
              v-model="formData.phone" 
              type="tel" 
              placeholder="08xx-xxxx-xxxx"
              class="h-10" />
          </div>

          <!-- Password -->
          <div class="space-y-2">
            <Label for="password" class="text-sm font-medium text-gray-900">
              Password 
              <span v-if="mode === 'add'" class="text-red-500">*</span>
              <span v-else class="text-xs text-gray-500 font-normal">(Kosongkan jika tidak diubah)</span>
            </Label>
            <Input 
              id="password"
              v-model="formData.password" 
              type="password" 
              :required="mode === 'add'"
              placeholder="Minimal 8 karakter"
              class="h-10" />
          </div>

          <Separator />

          <!-- Perusahaan -->
          <div class="space-y-2">
            <Label for="company" class="text-sm font-medium text-gray-900">
              Perusahaan <span class="text-red-500">*</span>
            </Label>
            <Select v-model="formData.company" required>
              <SelectTrigger id="company" class="h-10">
                <SelectValue placeholder="Pilih perusahaan" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem 
                  v-for="company in options.companies" 
                  :key="company" 
                  :value="company">
                  {{ company }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Posisi/Jabatan -->
          <div class="space-y-2">
            <Label for="posisi" class="text-sm font-medium text-gray-900">
              Posisi/Jabatan <span class="text-red-500">*</span>
            </Label>
            <Input 
              id="posisi"
              v-model="formData.posisi" 
              type="text" 
              required
              list="positions-list"
              placeholder="Masukkan posisi"
              class="h-10" />
            <datalist id="positions-list">
              <option v-for="pos in options.positions" :key="pos" :value="pos"></option>
            </datalist>
          </div>

          <!-- Role -->
          <div class="space-y-2">
            <Label for="role" class="text-sm font-medium text-gray-900">
              Role <span class="text-red-500">*</span>
            </Label>
            <Select v-model="formData.role" required>
              <SelectTrigger id="role" class="h-10">
                <SelectValue placeholder="Pilih role" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem 
                  v-for="role in options.roles" 
                  :key="role.id" 
                  :value="role.name">
                  {{ role.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Kantor -->
          <div class="space-y-2">
            <Label for="kantor" class="text-sm font-medium text-gray-900">Kantor</Label>
            <Select v-model="formData.kantor_id">
              <SelectTrigger id="kantor" class="h-10">
                <SelectValue placeholder="Pilih kantor" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem 
                  v-for="office in options.offices" 
                  :key="office.id" 
                  :value="office.id">
                  {{ office.nama }} - {{ office.alamat }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Atasan -->
          <div class="space-y-2">
            <Label for="atasan" class="text-sm font-medium text-gray-900">Atasan Langsung</Label>
            <Select v-model="formData.atasan_id">
              <SelectTrigger id="atasan" class="h-10">
                <SelectValue placeholder="Tanpa atasan" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem 
                  v-for="atasan in filteredAtasan" 
                  :key="atasan.id" 
                  :value="atasan.id">
                  {{ atasan.name }} - {{ atasan.posisi }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <Separator />

          <!-- Tanggal Bergabung -->
          <div class="space-y-2">
            <Label for="tanggal_bergabung" class="text-sm font-medium text-gray-900">
              Tanggal Bergabung <span class="text-red-500">*</span>
            </Label>
            <Input 
              id="tanggal_bergabung"
              v-model="formData.tanggal_bergabung" 
              type="date" 
              required
              class="h-10" />
          </div>

          <!-- Jatah Cuti Tambahan -->
          <div class="space-y-2">
            <Label for="jatah_cuti" class="text-sm font-medium text-gray-900">Jatah Cuti Tambahan (Hari)</Label>
            <Input 
              id="jatah_cuti"
              v-model.number="formData.jatah_cuti_tambahan" 
              type="number" 
              min="0"
              step="0.5"
              placeholder="0"
              class="h-10" />
            <p class="text-xs text-gray-500">Default: 12 hari per tahun</p>
          </div>

          <!-- Status Aktif -->
          <div class="space-y-2">
            <Label class="text-sm font-medium text-gray-900">Status Karyawan</Label>
            <div class="flex items-center space-x-2">
              <Checkbox 
                id="is_active"
                v-model:checked="formData.is_active" />
              <Label 
                for="is_active" 
                class="text-sm font-normal cursor-pointer">
                Status Aktif
              </Label>
            </div>
          </div>

        </form>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 bg-gray-50 border-t flex justify-end gap-3">
        <Button 
          type="button"
          variant="outline"
          @click="$emit('close')"
          class="h-9 px-4">
          Batal
        </Button>
        <Button 
          type="submit"
          @click="handleSubmit"
          class="h-9 px-4 bg-gray-900 hover:bg-gray-800 text-white"
          :disabled="submitting">
          <span v-if="submitting">Menyimpan...</span>
          <span v-else>{{ mode === 'add' ? 'Ajukan' : 'Simpan' }}</span>
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { X, User } from 'lucide-vue-next';
import api from '@/services/api';

// shadcn/ui components
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';

const props = defineProps({
  mode: String,
  employee: Object,
  options: Object
});

const emit = defineEmits(['close', 'saved']);

const fileInput = ref(null);
const previewImage = ref(null);
const submitting = ref(false);

const formData = reactive({
  name: '',
  email: '',
  phone: '',
  password: '',
  company: '',
  posisi: '',
  role: '',
  kantor_id: null,
  atasan_id: null,
  tanggal_bergabung: '',
  jatah_cuti_tambahan: 0,
  is_active: true,
  picture: null,
  avatar_url: ''
});

// Filter atasan (exclude diri sendiri saat edit)
const filteredAtasan = computed(() => {
  if (props.mode === 'edit' && props.employee) {
    return props.options.potential_atasan.filter(a => a.id !== props.employee.id);
  }
  return props.options.potential_atasan;
});

// Initialize form data saat edit
watch(() => props.employee, (employee) => {
  if (employee && props.mode === 'edit') {
    formData.name = employee.name;
    formData.email = employee.email;
    formData.phone = employee.phone || '';
    formData.company = employee.company || '';
    formData.posisi = employee.posisi || '';
    formData.role = employee.roles?.[0]?.name || employee.role_name || '';
    formData.kantor_id = employee.kantor_id || null;
    formData.atasan_id = employee.atasan_id || null;
    formData.tanggal_bergabung = employee.tanggal_bergabung || '';
    formData.jatah_cuti_tambahan = employee.jatah_cuti_tambahan || 0;
    formData.is_active = employee.is_active !== false;
    formData.avatar_url = employee.avatar_url || employee.picture_url || '';
  }
}, { immediate: true });

const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    if (file.size > 2 * 1024 * 1024) {
      alert('Ukuran file maksimal 2MB');
      return;
    }
    
    formData.picture = file;
    
    const reader = new FileReader();
    reader.onload = (e) => {
      previewImage.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const getDefaultAvatar = () => {
  const name = formData.name || 'User';
  const initials = name.split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
  return `https://ui-avatars.com/api/?name=${initials}&background=6b7280&color=fff&size=200`;
};

const handleSubmit = async () => {
  submitting.value = true;
  
  try {
    const submitData = new FormData();
    
    // Append all form fields
    Object.keys(formData).forEach(key => {
      if (key === 'picture' && formData[key]) {
        submitData.append('picture', formData[key]);
      } else if (key !== 'picture' && key !== 'avatar_url') {
        // Only append if value is not null/undefined
        if (formData[key] !== null && formData[key] !== undefined && formData[key] !== '') {
          // Convert boolean to number for Laravel
          if (key === 'is_active') {
            submitData.append(key, formData[key] ? '1' : '0');
          } else {
            submitData.append(key, formData[key]);
          }
        }
      }
    });

    let response;
    
    if (props.mode === 'add') {
      response = await api.post('/employees', submitData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    } else {
      // Untuk edit, pastikan employee.id ada
      if (!props.employee?.id) {
        throw new Error('Employee ID tidak ditemukan');
      }
      
      // Laravel doesn't support PUT with FormData directly, use POST with _method
      submitData.append('_method', 'PUT');
      response = await api.post(`/employees/${props.employee.id}`, submitData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    }

    alert(response.data.message);
    emit('saved');
    emit('close');
  } catch (error) {
    console.error('Error saving employee:', error);
    
    let errorMessage = 'Gagal menyimpan data karyawan';
    
    if (error.response) {
      // Server responded with error
      if (error.response.status === 404) {
        errorMessage = 'Endpoint tidak ditemukan. Pastikan route sudah benar.';
      } else if (error.response.status === 401) {
        errorMessage = 'Unauthorized. Silakan login kembali.';
      } else if (error.response.status === 403) {
        errorMessage = 'Anda tidak memiliki akses untuk melakukan aksi ini.';
      } else if (error.response.status === 422) {
        // Validation error
        const errors = error.response.data.errors;
        if (errors) {
          errorMessage = Object.values(errors).flat().join('\n');
        } else {
          errorMessage = error.response.data.message || 'Validasi gagal';
        }
      } else {
        errorMessage = error.response.data.message || error.response.data.error || errorMessage;
      }
    } else if (error.request) {
      // Request made but no response
      errorMessage = 'Tidak ada respon dari server. Periksa koneksi internet Anda.';
    } else {
      // Error in request setup
      errorMessage = error.message;
    }
    
    alert(errorMessage);
  } finally {
    submitting.value = false;
  }
};
</script>