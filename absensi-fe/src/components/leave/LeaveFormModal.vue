<template>
  <Dialog :open="true" @update:open="$emit('close')">
    <DialogContent class="max-w-2xl max-h-[90vh] flex flex-col p-0 gap-0">
      
      <div class="p-6 pb-2">
        <DialogHeader>
          <DialogTitle>
            {{ isEdit ? 'Edit Pengajuan Cuti' : 'Ajukan Cuti' }}
          </DialogTitle>
          <DialogDescription>
            Lengkapi form berikut untuk mengajukan cuti
          </DialogDescription>
        </DialogHeader>
      </div>

      <div class="flex-1 overflow-y-auto px-6 py-2">
        <form @submit.prevent="submit" id="leaveForm" class="space-y-6">

          <div class="space-y-2">
            <Label>Jenis Cuti</Label>
            <Select v-model="form.leave_type_id" @update:modelValue="onLeaveTypeChange">
              <SelectTrigger>
                <SelectValue placeholder="Pilih jenis cuti" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="type in safeLeaveTypes"
                  :key="type.id"
                  :value="String(type.id)"
                >
                  {{ type.nama }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="selectedLeaveType" class="text-xs text-muted-foreground">
              {{ selectedLeaveType.keterangan }}
            </p>
          </div>

          <Alert v-if="kuotaInfo" :variant="kuotaInfo.sisa < 0 ? 'destructive' : 'default'">
            <AlertTitle>
              Kuota {{ selectedLeaveType?.nama }}
            </AlertTitle>
            <AlertDescription>
              Total {{ kuotaInfo.kuota }} |
              Terpakai {{ kuotaInfo.terpakai }} |
              Sisa {{ kuotaInfo.sisa }}
            </AlertDescription>
          </Alert>

          <div class="space-y-2">
            <Label>Durasi</Label>
            <RadioGroup v-model="form.tipe_durasi" @update:modelValue="onDurasiChange">
              <div class="flex items-center space-x-2">
                <RadioGroupItem value="sehari" />
                <Label>Sehari</Label>
              </div>
              <div class="flex items-center space-x-2">
                <RadioGroupItem value="setengah_hari" />
                <Label>Setengah Hari</Label>
              </div>
              <div class="flex items-center space-x-2">
                <RadioGroupItem value="lebih_dari_sehari" />
                <Label>Lebih dari Sehari</Label>
              </div>
            </RadioGroup>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label>Tanggal Mulai</Label>
              <Input type="date" v-model="form.tanggal_mulai" :min="minDate" />
            </div>

            <div class="space-y-2">
              <Label>Tanggal Selesai</Label>
              <Input
                type="date"
                v-model="form.tanggal_selesai"
                :disabled="['sehari','setengah_hari'].includes(form.tipe_durasi)"
                :min="form.tanggal_mulai || minDate"
              />
            </div>
          </div>

          <div v-if="form.tipe_durasi === 'setengah_hari'" class="space-y-2">
            <Label>Waktu</Label>
            <RadioGroup v-model="form.setengah_hari_tipe">
              <div class="flex items-center space-x-2">
                <RadioGroupItem value="pagi" />
                <Label>Pagi (08:00–12:00)</Label>
              </div>
              <div class="flex items-center space-x-2">
                <RadioGroupItem value="siang" />
                <Label>Siang (13:00–17:00)</Label>
              </div>
            </RadioGroup>
          </div>

          <Alert v-if="calculatedDays !== null">
            <AlertDescription>
              Jumlah hari cuti: <strong>{{ calculatedDays }}</strong>
            </AlertDescription>
          </Alert>

          <div class="space-y-2">
            <Label>Alasan</Label>
            <Textarea v-model="form.alasan" rows="4" />
          </div>

          <div class="space-y-2">
            <Label>Dokumen Pendukung</Label>
            <Input type="file" @change="onFileChange" />
            <p v-if="existingDocument" class="text-xs text-muted-foreground">
              File saat ini: {{ getFileName(existingDocument) }}
            </p>
          </div>

          <Alert v-if="errorMessage" variant="destructive">
            <AlertDescription>{{ errorMessage }}</AlertDescription>
          </Alert>

        </form>
      </div>

      <div class="p-6 pt-4 border-t mt-auto">
        <DialogFooter>
          <Button type="button" variant="outline" @click="$emit('close')">
            Batal
          </Button>
          <Button type="submit" form="leaveForm" :disabled="loading">
            {{ loading ? 'Menyimpan...' : isEdit ? 'Update' : 'Ajukan' }}
          </Button>
        </DialogFooter>
      </div>

    </DialogContent>
  </Dialog>
</template>

<script>
import { ref, reactive, computed, watch, nextTick } from 'vue'
import { useToast } from '@/composables/useToast'
import api from '../../services/api'

// ===== shadcn-vue components =====
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '../../components/ui/dialog'

import { Button } from '../../components/ui/button'
import { Input } from '../../components/ui/input'
import { Textarea } from '../../components/ui/textarea'
import { Label } from '../../components/ui/label'

import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem,
} from '../../components/ui/select'

import {
  RadioGroup,
  RadioGroupItem,
} from '../../components/ui/radio-group'

import {
  Alert,
  AlertTitle,
  AlertDescription,
} from '../../components/ui/alert'


export default {
  name: 'LeaveFormModal',

  components: {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,

    Button,
    Input,
    Textarea,
    Label,

    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectItem,

    RadioGroup,
    RadioGroupItem,

    Alert,
    AlertTitle,
    AlertDescription,
  },

  props: {
    leave: {
      type: Object,
      default: null,
    },
    leaveTypes: {
      type: Array,
      default: () => [],
    },
  },
  emits: ['close', 'saved'],
  setup(props, { emit }) {
    const { toast } = useToast();
    const loading = ref(false);
    const errorMessage = ref('');
    const calculatedDays = ref(null);
    const existingDocument = ref(null);
    const kuotaInfo = ref(null);

    const form = reactive({
      leave_type_id: '',
      tipe_durasi: 'sehari',
      tanggal_mulai: '',
      tanggal_selesai: '',
      setengah_hari_tipe: '',
      alasan: '',
      dokumen_pendukung: null,
    });

    const isEdit = computed(() => !!props.leave);
    const minDate = computed(() => new Date().toISOString().split('T')[0]);

    // Safe access to leaveTypes
    const safeLeaveTypes = computed(() => {
      return Array.isArray(props.leaveTypes) ? props.leaveTypes : [];
    });

    const selectedLeaveType = computed(() => {
      if (!form.leave_type_id || safeLeaveTypes.value.length === 0) return null;
      return safeLeaveTypes.value.find(t => t.id == form.leave_type_id) || null;
    });

    const requiresDocument = computed(() => {
      if (!selectedLeaveType.value) return false;
      const requiresDoc = ['Sakit', 'Cuti Haji', 'Cuti Umrah', 'Cuti Melahirkan'];
      return requiresDoc.includes(selectedLeaveType.value.nama);
    });

    // Helper function untuk format tanggal ke YYYY-MM-DD
    const formatDateForInput = (date) => {
      if (!date) return '';
      
      // Jika sudah string YYYY-MM-DD, return aja
      if (typeof date === 'string' && date.match(/^\d{4}-\d{2}-\d{2}$/)) {
        return date;
      }
      
      // Jika object Date atau string lain, convert
      const d = new Date(date);
      if (isNaN(d.getTime())) return ''; // Invalid date
      
      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    };

    const onLeaveTypeChange = async () => {
      if (form.leave_type_id) {
        await loadKuotaInfo();
      }
    };

    const loadKuotaInfo = async () => {
      try {
        const response = await api.get('/leaves/statistics');
        const kuota = response.data.kuota_per_jenis?.find(k => k.leave_type_id == form.leave_type_id);
        kuotaInfo.value = kuota || null;
      } catch (error) {
        console.error('Error loading kuota:', error);
      }
    };

    const onDurasiChange = () => {
      if (['sehari', 'setengah_hari'].includes(form.tipe_durasi)) {
        form.tanggal_selesai = form.tanggal_mulai;
        if (form.tipe_durasi === 'sehari') {
          form.setengah_hari_tipe = '';
        }
      }
      calculateDays();
    };

    const calculateDays = () => {
      if (!form.tanggal_mulai || !form.tanggal_selesai) {
        calculatedDays.value = null;
        return;
      }

      if (form.tipe_durasi === 'setengah_hari') {
        calculatedDays.value = 0.5;
        return;
      }

      if (form.tipe_durasi === 'sehari') {
        calculatedDays.value = 1;
        return;
      }

      // Hitung hari kerja (exclude weekend)
      const start = new Date(form.tanggal_mulai);
      const end = new Date(form.tanggal_selesai);
      let count = 0;

      while (start <= end) {
        const day = start.getDay();
        if (day !== 0 && day !== 6) { // exclude weekend
          count++;
        }
        start.setDate(start.getDate() + 1);
      }

      calculatedDays.value = count;
    };

    const onFileChange = (e) => {
      const file = e.target.files[0];
      if (file) {
        if (file.size > 2 * 1024 * 1024) {
          // ✅ GANTI ALERT DENGAN TOAST
          toast({
            title: 'File Terlalu Besar',
            description: 'Ukuran file maksimal 2MB',
            variant: 'destructive'
          });
          e.target.value = '';
          return;
        }
        form.dokumen_pendukung = file;
      }
    };

    const getFileName = (path) => {
      return path ? path.split('/').pop() : '';
    };

    const submit = async () => {
      // Validate required document
      if (requiresDocument.value && !form.dokumen_pendukung && !existingDocument.value) {
        errorMessage.value = 'Dokumen pendukung wajib untuk jenis cuti ini';
        return;
      }

      loading.value = true;
      errorMessage.value = '';

      try {
        const formData = new FormData();
        formData.append('leave_type_id', form.leave_type_id);
        formData.append('tipe_durasi', form.tipe_durasi);
        formData.append('tanggal_mulai', form.tanggal_mulai);
        formData.append('tanggal_selesai', form.tanggal_selesai);
        
        if (form.setengah_hari_tipe) {
          formData.append('setengah_hari_tipe', form.setengah_hari_tipe);
        }
        
        formData.append('alasan', form.alasan);
        
        if (form.dokumen_pendukung) {
          formData.append('dokumen_pendukung', form.dokumen_pendukung);
        }

        if (isEdit.value) {
          formData.append('_method', 'PUT');
          await api.post(`/leaves/${props.leave.id}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
          });
          
          // ✅ GANTI ALERT DENGAN TOAST
          toast({
            title: 'Berhasil Diupdate',
            description: 'Pengajuan cuti berhasil diperbarui',
            variant: 'default'
          });
        } else {
          await api.post('/leaves', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
          });
          
          // ✅ GANTI ALERT DENGAN TOAST
          toast({
            title: 'Berhasil Diajukan',
            description: 'Pengajuan cuti berhasil dibuat',
            variant: 'default'
          });
        }

        emit('saved');
      } catch (error) {
        console.error('Error saving leave:', error);
        errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan';
        
        // Show validation errors
        if (error.response?.data?.errors) {
          const errors = error.response.data.errors;
          const errorList = Object.values(errors).flat().join(', ');
          errorMessage.value = errorList;
        }
        
        // ✅ TAMBAHKAN TOAST UNTUK ERROR
        toast({
          title: 'Gagal Menyimpan',
          description: errorMessage.value,
          variant: 'destructive'
        });
      } finally {
        loading.value = false;
      }
    };

    // Initialize form if editing
    watch(() => props.leave, (leave) => {
      if (leave) {
        console.log('🔍 Loading leave data:', leave);
        
        // Convert ke string untuk Select component
        form.leave_type_id = String(leave.leave_type_id);
        form.tipe_durasi = leave.tipe_durasi;
        
        // Format tanggal ke YYYY-MM-DD untuk input date
        form.tanggal_mulai = formatDateForInput(leave.tanggal_mulai);
        form.tanggal_selesai = formatDateForInput(leave.tanggal_selesai);
        
        form.setengah_hari_tipe = leave.setengah_hari_tipe || '';
        form.alasan = leave.alasan;
        existingDocument.value = leave.dokumen_pendukung;
        
        console.log('✅ Form after load:', form);
        
        // Tunggu nextTick supaya Vue udah update DOM
        nextTick(() => {
          calculateDays();
          if (form.leave_type_id) {
            loadKuotaInfo();
          }
        });
      }
    }, { immediate: true });

    // Watch tanggal_mulai for auto-fill tanggal_selesai
    watch(() => form.tanggal_mulai, (val) => {
      if (['sehari', 'setengah_hari'].includes(form.tipe_durasi)) {
        form.tanggal_selesai = val;
      }
      calculateDays();
    });

    return {
      loading,
      errorMessage,
      calculatedDays,
      existingDocument,
      kuotaInfo,
      form,
      isEdit,
      minDate,
      safeLeaveTypes,
      selectedLeaveType,
      requiresDocument,
      onLeaveTypeChange,
      onDurasiChange,
      calculateDays,
      onFileChange,
      getFileName,
      submit,
      formatDateForInput,
    };
  }
};
</script>