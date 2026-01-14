<template>
  <Dialog :open="true" @update:open="$emit('close')">
    <DialogContent class="max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
      <DialogHeader>
        <DialogTitle>Detail Pengajuan Cuti</DialogTitle>
        <DialogDescription>
          Informasi lengkap pengajuan cuti karyawan
        </DialogDescription>
      </DialogHeader>

      <div v-if="leave" class="flex-1 overflow-y-auto space-y-6 py-6 pr-2">
        <!-- Info Cards Grid -->
        <div class="grid grid-cols-2 gap-4">
          <!-- Karyawan -->
          <div class="p-4 border rounded-lg bg-card">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-500/10">
                <User :size="20" class="text-blue-600" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-xs text-muted-foreground font-medium">Karyawan</p>
                <p class="text-sm font-semibold truncate">{{ leave.user?.name || 'N/A' }}</p>
              </div>
            </div>
          </div>

          <!-- Jenis Cuti -->
          <div class="p-4 border rounded-lg bg-card">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-500/10">
                <Calendar :size="20" class="text-purple-600" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-xs text-muted-foreground font-medium">Jenis Cuti</p>
                <p class="text-sm font-semibold truncate">{{ leave.leave_type?.nama || 'N/A' }}</p>
              </div>
            </div>
          </div>

          <!-- Status -->
          <div class="p-4 border rounded-lg bg-card">
            <div class="flex items-center gap-3">
              <div 
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                :class="getStatusIconBg(leave.status)"
              >
                <component :is="getStatusIcon(leave.status)" :size="20" :class="getStatusIconColor(leave.status)" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-xs text-muted-foreground font-medium">Status</p>
                <Badge :variant="getBadgeVariant(leave.status)" class="text-xs font-semibold mt-1">
                  {{ leave.status_label }}
                </Badge>
              </div>
            </div>
          </div>

          <!-- Durasi -->
          <div class="p-4 border rounded-lg bg-card">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-orange-500/10">
                <Clock :size="20" class="text-orange-600" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-xs text-muted-foreground font-medium">Durasi</p>
                <p class="text-sm font-semibold truncate">{{ leave.tipe_durasi_label }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Detail Information -->
        <div class="space-y-4">
          <!-- Periode Cuti -->
          <div class="space-y-2">
            <Label class="text-sm font-medium">Periode Cuti</Label>
            <div class="p-3 border rounded-lg bg-muted/50">
              <p class="text-sm font-semibold">{{ formatDate(leave.tanggal_mulai) }}</p>
              <p v-if="leave.tanggal_mulai !== leave.tanggal_selesai" class="text-xs text-muted-foreground mt-1">
                s/d {{ formatDate(leave.tanggal_selesai) }}
              </p>
            </div>
          </div>

          <!-- Jumlah Hari -->
          <div class="space-y-2">
            <Label class="text-sm font-medium">Jumlah Hari</Label>
            <div class="p-3 border rounded-lg bg-muted/50">
              <Badge variant="secondary" class="font-semibold">
                {{ leave.jumlah_hari }} hari
              </Badge>
            </div>
          </div>

          <!-- Alasan -->
          <div class="space-y-2">
            <Label class="text-sm font-medium">Alasan</Label>
            <div class="p-3 border rounded-lg bg-muted/50">
              <p class="text-sm leading-relaxed">{{ leave.alasan }}</p>
            </div>
          </div>

          <!-- Dokumen Pendukung -->
          <div v-if="leave.dokumen_pendukung" class="space-y-2">
            <Label class="text-sm font-medium">Dokumen Pendukung</Label>
            <Button variant="outline" size="sm" as-child class="w-full justify-start">
              <a :href="getDocumentUrl(leave.dokumen_pendukung)" target="_blank" class="flex items-center gap-2">
                <Eye :size="16" />
                Lihat Dokumen
              </a>
            </Button>
          </div>

          <!-- Approval Section -->
          <div v-if="leave.approved_by || leave.catatan_approval" class="space-y-2">
            <Label class="text-sm font-medium">Informasi Approval</Label>
            <div class="p-4 border rounded-lg bg-blue-50/50 space-y-3">
              <div v-if="leave.approved_by" class="flex items-center justify-between">
                <span class="text-xs text-muted-foreground">Diproses oleh</span>
                <span class="text-sm font-semibold">{{ leave.approver?.name }}</span>
              </div>

              <div v-if="leave.approved_at" class="flex items-center justify-between">
                <span class="text-xs text-muted-foreground">Tanggal Proses</span>
                <span class="text-sm font-semibold">{{ formatDateTime(leave.approved_at) }}</span>
              </div>

              <div v-if="leave.catatan_approval" class="pt-2 border-t">
                <p class="text-xs text-muted-foreground mb-2">Catatan</p>
                <p class="text-sm leading-relaxed">{{ leave.catatan_approval }}</p>
              </div>
            </div>
          </div>

          <!-- Tanggal Pengajuan -->
          <div class="space-y-2">
            <Label class="text-sm font-medium">Diajukan Pada</Label>
            <div class="p-3 border rounded-lg bg-muted/50">
              <p class="text-sm font-semibold">{{ formatDateTime(leave.created_at) }}</p>
            </div>
          </div>
        </div>
      </div>

      <DialogFooter class="mt-4">
        <Button @click="$emit('close')" class="w-full">
          Tutup
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { User, Calendar, CheckCircle2, Clock, Eye, XCircle, AlertCircle } from 'lucide-vue-next';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';

const props = defineProps({
  leave: {
    type: Object,
    default: null
  },
});

defineEmits(['close']);

const formatDate = (date) => {
  return format(new Date(date), 'dd MMMM yyyy', { locale: id });
};

const formatDateTime = (date) => {
  return format(new Date(date), 'dd MMMM yyyy HH:mm', { locale: id });
};

// ✅ DIPERBAIKI
const getDocumentUrl = (path) => {
  if (!path) return '';
  
  // Jika path sudah full URL, return langsung
  if (path.startsWith('http://') || path.startsWith('https://')) {
    return path;
  }
  
  // Hapus leading slash jika ada
  const cleanPath = path.startsWith('/') ? path.substring(1) : path;
  
  // Return URL yang benar
  return `https://mbg.erpdis.com/storage/${cleanPath}`;
};

const getBadgeVariant = (status) => {
  const variants = {
    pending: 'secondary',
    approved: 'default',
    rejected: 'destructive'
  };
  return variants[status] || 'default';
};

const getStatusIcon = (status) => {
  const icons = {
    pending: AlertCircle,
    approved: CheckCircle2,
    rejected: XCircle
  };
  return icons[status] || AlertCircle;
};

const getStatusIconBg = (status) => {
  const backgrounds = {
    pending: 'bg-yellow-500/10',
    approved: 'bg-green-500/10',
    rejected: 'bg-red-500/10'
  };
  return backgrounds[status] || 'bg-gray-500/10';
};

const getStatusIconColor = (status) => {
  const colors = {
    pending: 'text-yellow-600',
    approved: 'text-green-600',
    rejected: 'text-red-600'
  };
  return colors[status] || 'text-gray-600';
};
</script>