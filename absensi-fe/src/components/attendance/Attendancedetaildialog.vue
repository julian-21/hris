<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="max-w-2xl">
      <DialogHeader>
        <DialogTitle>Detail Kehadiran</DialogTitle>
        <DialogDescription>
          {{ formatDate(data?.tanggal) }}
        </DialogDescription>
      </DialogHeader>

      <div v-if="data" class="space-y-4">
        <!-- Status Badge -->
        <div class="flex items-center gap-2">
          <Badge :variant="getStatusVariant(data.status_color)" class="text-sm">
            {{ data.status_label }}
          </Badge>
          <Badge v-if="data.is_late" variant="destructive">
            Terlambat {{ data.durasi_terlambat }} menit
          </Badge>
          <Badge v-if="data.out_of_office" variant="outline">
            Out of Office
          </Badge>
        </div>

        <!-- Attendance Info -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Informasi Kehadiran</CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label class="text-muted-foreground">Jadwal Check In</Label>
                <p class="font-mono text-sm mt-1">{{ data.jadwal_checkin || '-' }}</p>
              </div>
              <div>
                <Label class="text-muted-foreground">Check In Aktual</Label>
                <p class="font-mono text-sm mt-1" :class="data.is_late ? 'text-red-600' : 'text-green-600'">
                  {{ data.check_in || '-' }}
                </p>
              </div>
              <div>
                <Label class="text-muted-foreground">Jadwal Check Out</Label>
                <p class="font-mono text-sm mt-1">{{ data.jadwal_checkout || '-' }}</p>
              </div>
              <div>
                <Label class="text-muted-foreground">Check Out Aktual</Label>
                <p class="font-mono text-sm mt-1">{{ data.check_out || '-' }}</p>
              </div>
            </div>

            <Separator />

            <div>
              <Label class="text-muted-foreground">Lokasi Kantor</Label>
              <p class="text-sm mt-1">{{ data.kantor_nama || '-' }}</p>
            </div>

            <div v-if="data.out_of_office">
              <Label class="text-muted-foreground">Alasan Out of Office</Label>
              <p class="text-sm mt-1">{{ data.out_of_office_reason }}</p>
            </div>
          </CardContent>
        </Card>

        <!-- Leave Info (if applicable) -->
        <Card v-if="data.leave_status">
          <CardHeader>
            <CardTitle class="text-base">Informasi Cuti</CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <div>
              <Label class="text-muted-foreground">Jenis Cuti</Label>
              <p class="text-sm mt-1">{{ data.leave_status.leave_type_nama }}</p>
            </div>

            <div v-if="data.leave_status.tipe_durasi === 'setengah_hari'">
              <Label class="text-muted-foreground">Tipe Durasi</Label>
              <Badge variant="outline" class="mt-1">
                Setengah Hari ({{ data.leave_status.setengah_hari_tipe }})
              </Badge>
            </div>

            <div>
              <Label class="text-muted-foreground">Alasan</Label>
              <p class="text-sm mt-1">{{ data.leave_status.alasan }}</p>
            </div>

            <!-- Validation for Half Day Leave -->
            <Alert v-if="data.leave_status.tipe_durasi === 'setengah_hari'" class="mt-2">
              <InfoIcon class="h-4 w-4" />
              <AlertTitle>Catatan Cuti Setengah Hari</AlertTitle>
              <AlertDescription>
                <template v-if="data.leave_status.setengah_hari_tipe === 'pagi'">
                  Karyawan mengambil cuti setengah hari pagi. Seharusnya check-in setelah jam 12:00.
                  <span v-if="data.check_in && isCheckinBeforeNoon(data.check_in)" class="text-orange-600 font-medium">
                    ⚠️ Check-in sebelum jam 12:00 (anomali)
                  </span>
                  <span v-else-if="data.check_in" class="text-green-600 font-medium">
                    ✓ Check-in sesuai ketentuan
                  </span>
                </template>
                <template v-else-if="data.leave_status.setengah_hari_tipe === 'siang'">
                  Karyawan mengambil cuti setengah hari siang. Seharusnya check-in sebelum jam 12:00.
                  <span v-if="data.check_in && !isCheckinBeforeNoon(data.check_in)" class="text-orange-600 font-medium">
                    ⚠️ Check-in setelah jam 12:00 (anomali)
                  </span>
                  <span v-else-if="data.check_in" class="text-green-600 font-medium">
                    ✓ Check-in sesuai ketentuan
                  </span>
                </template>
              </AlertDescription>
            </Alert>
          </CardContent>
        </Card>

        <!-- Keterangan -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Keterangan</CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-sm">{{ data.keterangan }}</p>
          </CardContent>
        </Card>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="isOpen = false">Tutup</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue'
import { format } from 'date-fns'
import { id } from 'date-fns/locale'
import { InfoIcon } from 'lucide-vue-next'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'

const props = defineProps({
  data: {
    type: Object,
    default: null
  }
})

const isOpen = defineModel('open', { type: Boolean, default: false })

function getStatusVariant(color) {
  const variants = {
    green: 'default',
    red: 'destructive',
    yellow: 'warning',
    blue: 'secondary',
    orange: 'outline',
    gray: 'secondary'
  }
  return variants[color] || 'default'
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return format(new Date(dateString), 'EEEE, dd MMMM yyyy', { locale: id })
}

function isCheckinBeforeNoon(checkinTime) {
  if (!checkinTime) return false
  const hour = parseInt(checkinTime.split(':')[0])
  return hour < 12
}
</script>