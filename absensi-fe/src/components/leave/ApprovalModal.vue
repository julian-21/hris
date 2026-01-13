<template>
  <Dialog :open="true" @update:open="$emit('close')">
    <DialogContent class="max-w-2xl">
      <DialogHeader>
        <DialogTitle>
          {{ action === 'approve' ? 'Setujui' : 'Tolak' }} Pengajuan Cuti
        </DialogTitle>
        <DialogDescription>
          Konfirmasi {{ action === 'approve' ? 'persetujuan' : 'penolakan' }} pengajuan cuti karyawan
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-6" v-if="leave">
        <!-- Info Box -->
        <Alert :variant="action === 'approve' ? 'default' : 'destructive'">
          <AlertCircle class="h-4 w-4" />
          <AlertTitle>
            Anda akan {{ action === 'approve' ? 'menyetujui' : 'menolak' }} pengajuan cuti
          </AlertTitle>
          <AlertDescription class="mt-3 space-y-2">
            <div class="grid grid-cols-2 gap-2">
              <div>
                <span class="text-xs text-muted-foreground">Karyawan:</span>
                <p class="text-sm font-semibold">{{ leave.user?.name }}</p>
              </div>
              <div>
                <span class="text-xs text-muted-foreground">Jenis Cuti:</span>
                <p class="text-sm font-semibold">{{ leave.leave_type?.nama }}</p>
              </div>
              <div>
                <span class="text-xs text-muted-foreground">Periode:</span>
                <p class="text-sm font-semibold">
                  {{ formatDate(leave.tanggal_mulai) }}
                  <span v-if="leave.tanggal_mulai !== leave.tanggal_selesai">
                    s/d {{ formatDate(leave.tanggal_selesai) }}
                  </span>
                </p>
              </div>
              <div>
                <span class="text-xs text-muted-foreground">Durasi:</span>
                <p class="text-sm font-semibold">{{ leave.jumlah_hari }} hari</p>
              </div>
            </div>
          </AlertDescription>
        </Alert>

        <!-- Catatan -->
        <div class="space-y-2">
          <Label :class="{ 'after:content-[\'*\'] after:ml-0.5 after:text-destructive': action === 'reject' }">
            Catatan
          </Label>
          <Textarea 
            v-model="catatan" 
            rows="4"
            :placeholder="action === 'approve' 
              ? 'Catatan untuk persetujuan (opsional)' 
              : 'Alasan penolakan (wajib diisi)'"
            :required="action === 'reject'"
            :minlength="action === 'reject' ? 10 : 0"
          />
          <p v-if="action === 'reject'" class="text-xs text-muted-foreground">
            Minimal 10 karakter
          </p>
        </div>

        <!-- Error Message -->
        <Alert v-if="errorMessage" variant="destructive">
          <AlertCircle class="h-4 w-4" />
          <AlertDescription>{{ errorMessage }}</AlertDescription>
        </Alert>

        <!-- Actions -->
        <DialogFooter>
          <Button type="button" variant="outline" @click="$emit('close')">
            Batal
          </Button>
          <Button 
            type="submit" 
            :variant="action === 'reject' ? 'destructive' : 'default'"
            :disabled="loading"
          >
            {{ loading ? 'Memproses...' : (action === 'approve' ? 'Setujui' : 'Tolak') }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref } from 'vue';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { AlertCircle } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';  // ← TAMBAHKAN INI
import api from '../../services/api';

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '../../components/ui/dialog';

import {
  Alert,
  AlertDescription,
  AlertTitle,
} from '../../components/ui/alert';

import { Button } from '../../components/ui/button';
import { Label } from '../../components/ui/label';
import { Textarea } from '../../components/ui/textarea';

const props = defineProps({
  leave: {
    type: Object,
    default: null
  },
  action: {
    type: String,
    required: true,
    validator: (value) => ['approve', 'reject'].includes(value)
  }
});

const emit = defineEmits(['close', 'confirmed']);

const { toast } = useToast();  // ← TAMBAHKAN INI
const loading = ref(false);
const errorMessage = ref('');
const catatan = ref('');

const formatDate = (date) => {
  return format(new Date(date), 'dd MMMM yyyy', { locale: id });
};

const submit = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const url = `/leaves/${props.leave.id}/${props.action}`;
    const data = catatan.value ? { catatan_approval: catatan.value } : {};

    await api.post(url, data);

    // ✅ GANTI ALERT DENGAN TOAST
    toast({
      title: props.action === 'approve' ? 'Berhasil Disetujui' : 'Berhasil Ditolak',
      description: `Pengajuan cuti ${props.leave.user?.name} berhasil ${props.action === 'approve' ? 'disetujui' : 'ditolak'}`,
      variant: 'default'
    });
    
    emit('confirmed');
  } catch (error) {
    console.error('Error processing approval:', error);
    errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan';
    
    // ✅ TAMBAHKAN TOAST UNTUK ERROR
    toast({
      title: 'Gagal Memproses',
      description: error.response?.data?.message || 'Terjadi kesalahan saat memproses approval',
      variant: 'destructive'
    });
  } finally {
    loading.value = false;
  }
};
</script>