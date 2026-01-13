<template>
  <Dialog :open="true" @update:open="$emit('close')">
    <DialogContent class="max-w-3xl max-h-[90vh] p-0 gap-0 overflow-hidden">
      <!-- Header -->
      <DialogHeader class="px-6 py-5 border-b">
        <div class="flex items-start gap-4">
          <Avatar class="w-14 h-14">
            <AvatarImage :src="employee?.avatar_url || getDefaultAvatar()" />
            <AvatarFallback class="bg-gray-100">
              <User class="w-7 h-7 text-gray-400" />
            </AvatarFallback>
          </Avatar>
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <DialogTitle class="text-lg font-semibold">
                {{ employee?.name }}
              </DialogTitle>
              <Badge 
                :variant="employee?.is_active ? 'default' : 'secondary'" 
                class="text-xs">
                {{ employee?.is_active ? 'Aktif' : 'Tidak Aktif' }}
              </Badge>
            </div>
            <DialogDescription class="text-sm mt-0.5">
              {{ employee?.posisi }} • {{ employee?.email }}
            </DialogDescription>
          </div>
        </div>
      </DialogHeader>

      <!-- Loading -->
      <div v-if="loading" class="p-12 text-center">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-gray-900 mx-auto"></div>
        <p class="text-sm text-gray-500 mt-3">Memuat detail...</p>
      </div>

      <!-- Content -->
      <div v-else class="overflow-y-auto" style="max-height: calc(90vh - 180px);">
        <div class="p-6 space-y-6">
          <!-- Stats -->
          <div class="grid grid-cols-4 gap-4">
            <div class="border rounded-lg p-3">
              <div class="text-xs text-gray-500 mb-1">Bawahan</div>
              <div class="text-xl font-semibold">{{ stats?.total_bawahan || 0 }}</div>
            </div>
            <div class="border rounded-lg p-3">
              <div class="text-xs text-gray-500 mb-1">Sisa Cuti</div>
              <div class="text-xl font-semibold">{{ stats?.sisa_cuti || 0 }}</div>
            </div>
            <div class="border rounded-lg p-3">
              <div class="text-xs text-gray-500 mb-1">Hadir</div>
              <div class="text-xl font-semibold">{{ stats?.total_absensi_bulan_ini || 0 }}</div>
            </div>
            <div class="border rounded-lg p-3">
              <div class="text-xs text-gray-500 mb-1">Terlambat</div>
              <div class="text-xl font-semibold">{{ stats?.keterlambatan_bulan_ini || 0 }}</div>
            </div>
          </div>

          <!-- Info Grid -->
          <div class="grid grid-cols-2 gap-6">
            <!-- Informasi Pribadi -->
            <div class="space-y-4">
              <h3 class="text-sm font-semibold text-gray-900">Informasi Pribadi</h3>
              <div class="space-y-3 text-sm">
                <div class="flex gap-3">
                  <Mail class="w-4 h-4 text-gray-400 mt-0.5" />
                  <div class="flex-1">
                    <div class="text-xs text-gray-500">Email</div>
                    <div class="font-medium">{{ employee?.email }}</div>
                  </div>
                </div>
                <div class="flex gap-3">
                  <Phone class="w-4 h-4 text-gray-400 mt-0.5" />
                  <div class="flex-1">
                    <div class="text-xs text-gray-500">Telepon</div>
                    <div class="font-medium">{{ employee?.phone || '-' }}</div>
                  </div>
                </div>
                <div class="flex gap-3">
                  <Calendar class="w-4 h-4 text-gray-400 mt-0.5" />
                  <div class="flex-1">
                    <div class="text-xs text-gray-500">Bergabung</div>
                    <div class="font-medium">{{ formatDate(employee?.tanggal_bergabung) }}</div>
                  </div>
                </div>
                <div class="flex gap-3">
                  <Award class="w-4 h-4 text-gray-400 mt-0.5" />
                  <div class="flex-1">
                    <div class="text-xs text-gray-500">Masa Kerja</div>
                    <div class="font-medium">{{ stats?.masa_kerja || '-' }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Informasi Pekerjaan -->
            <div class="space-y-4">
              <h3 class="text-sm font-semibold text-gray-900">Informasi Pekerjaan</h3>
              <div class="space-y-3 text-sm">
                <div class="flex gap-3">
                  <Building class="w-4 h-4 text-gray-400 mt-0.5" />
                  <div class="flex-1">
                    <div class="text-xs text-gray-500">Perusahaan</div>
                    <div class="font-medium">{{ employee?.company || '-' }}</div>
                  </div>
                </div>
                <div class="flex gap-3">
                  <Briefcase class="w-4 h-4 text-gray-400 mt-0.5" />
                  <div class="flex-1">
                    <div class="text-xs text-gray-500">Posisi</div>
                    <div class="font-medium">{{ employee?.posisi || '-' }}</div>
                  </div>
                </div>
                <div class="flex gap-3">
                  <MapPin class="w-4 h-4 text-gray-400 mt-0.5" />
                  <div class="flex-1">
                    <div class="text-xs text-gray-500">Kantor</div>
                    <div class="font-medium">{{ employee?.kantor?.nama || '-' }}</div>
                  </div>
                </div>
                <div class="flex gap-3">
                  <Users class="w-4 h-4 text-gray-400 mt-0.5" />
                  <div class="flex-1">
                    <div class="text-xs text-gray-500">Atasan</div>
                    <div class="font-medium">{{ employee?.atasan?.name || '-' }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Daftar Bawahan -->
          <div v-if="employee?.bawahan && employee.bawahan.length > 0" class="space-y-3">
            <h3 class="text-sm font-semibold text-gray-900">
              Daftar Bawahan ({{ employee.bawahan.length }})
            </h3>
            <div class="grid grid-cols-2 gap-2">
              <div 
                v-for="bawahan in employee.bawahan" 
                :key="bawahan.id"
                class="border rounded-lg p-2.5 hover:bg-gray-50 transition">
                <div class="flex items-center gap-2.5">
                  <Avatar class="w-8 h-8">
                    <AvatarImage :src="bawahan.picture_url || getDefaultAvatar()" />
                    <AvatarFallback class="bg-gray-100 text-xs">
                      {{ bawahan.name.substring(0, 2).toUpperCase() }}
                    </AvatarFallback>
                  </Avatar>
                  <div class="min-w-0 flex-1">
                    <div class="text-sm font-medium truncate">{{ bawahan.name }}</div>
                    <div class="text-xs text-gray-500 truncate">{{ bawahan.posisi }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-3.5 bg-gray-50 border-t flex items-center justify-end gap-2">
        <Button 
          variant="outline"
          @click="$emit('close')"
          class="h-9">
          Tutup
        </Button>
        <Button 
          @click="$emit('edit', employee)"
          class="h-9 gap-2">
          <Edit class="w-3.5 h-3.5" />
          Edit
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { User, Mail, Phone, Calendar, Award, MapPin, Edit, Building, Briefcase, Users } from 'lucide-vue-next';
import api from '@/services/api';

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

const props = defineProps({
  employeeId: [Number, String]
});

const emit = defineEmits(['close', 'edit']);

const loading = ref(true);
const employee = ref(null);
const stats = ref(null);

onMounted(async () => {
  await loadEmployeeDetail();
});

const loadEmployeeDetail = async () => {
  try {
    const response = await api.get(`/employees/${props.employeeId}`);
    employee.value = response.data.employee;
    stats.value = response.data.stats;
  } catch (error) {
    console.error('Error loading employee detail:', error);
    alert('Gagal memuat detail karyawan');
  } finally {
    loading.value = false;
  }
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', { 
    day: 'numeric', 
    month: 'long', 
    year: 'numeric' 
  });
};

const getDefaultAvatar = () => {
  const name = employee.value?.name || 'User';
  const initials = name.split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
  return `https://ui-avatars.com/api/?name=${initials}&background=6b7280&color=fff&size=200`;
};
</script>