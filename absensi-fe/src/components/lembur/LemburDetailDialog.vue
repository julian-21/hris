<template>
  <Dialog :open="open" @update:open="$emit('close')">
    <DialogContent class="sm:max-w-[600px]">
      <DialogHeader>
        <DialogTitle>Detail Lembur</DialogTitle>
        <DialogDescription>
          Informasi lengkap pengajuan lembur
        </DialogDescription>
      </DialogHeader>

      <div v-if="lembur" class="space-y-4">
        <!-- User Info -->
        <div class="bg-muted p-4 rounded-lg space-y-2">
          <div class="flex items-center gap-2">
            <User class="h-4 w-4 text-muted-foreground" />
            <span class="font-semibold">{{ lembur.user?.name }}</span>
          </div>
          <div class="text-sm text-muted-foreground">
            {{ lembur.user?.posisi }} • {{ lembur.user?.company }}
          </div>
        </div>

        <!-- Lembur Details -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1">
            <Label class="text-muted-foreground">Tanggal Lembur</Label>
            <div class="flex items-center gap-2">
              <Calendar class="h-4 w-4 text-muted-foreground" />
              <span class="font-medium">{{ formatDate(lembur.tanggal_lembur) }}</span>
            </div>
          </div>

          <div class="space-y-1">
            <Label class="text-muted-foreground">Durasi</Label>
            <div class="flex items-center gap-2">
              <Clock class="h-4 w-4 text-muted-foreground" />
              <span class="font-medium">{{ formatMinutes(lembur.lama_lembur) }}</span>
            </div>
          </div>

          <div class="space-y-1">
            <Label class="text-muted-foreground">Status</Label>
            <Badge :variant="getStatusBadgeVariant(lembur.status)">
              {{ lembur.status_label }}
            </Badge>
          </div>

          <div class="space-y-1">
            <Label class="text-muted-foreground">Final Status</Label>
            <Badge :variant="getStatusBadgeVariant(lembur.final_status)">
              {{ lembur.final_status_label }}
            </Badge>
          </div>

          <div class="space-y-1 col-span-2">
            <Label class="text-muted-foreground">Sisa Waktu Claim</Label>
            <div class="flex items-center gap-2">
              <Timer class="h-4 w-4 text-muted-foreground" />
              <span class="font-medium">{{ formatMinutes(lembur.sisa_waktu_claim) }}</span>
            </div>
          </div>
        </div>

        <!-- Alasan -->
        <div class="space-y-2">
          <Label class="text-muted-foreground">Alasan Lembur</Label>
          <div class="bg-muted p-3 rounded-md">
            <p class="text-sm whitespace-pre-wrap">{{ lembur.alasan_lembur }}</p>
          </div>
        </div>

        <!-- Timestamps -->
        <div class="border-t pt-4 grid grid-cols-2 gap-4 text-sm">
          <div>
            <Label class="text-muted-foreground">Dibuat</Label>
            <p>{{ formatDateTime(lembur.created_at) }}</p>
          </div>
          <div>
            <Label class="text-muted-foreground">Diupdate</Label>
            <p>{{ formatDateTime(lembur.updated_at) }}</p>
          </div>
        </div>
      </div>

      <DialogFooter>
        <Button @click="$emit('close')">Tutup</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { computed } from 'vue'
import { useLembur } from '@/composables/useLembur'

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { User, Calendar, Clock, Timer } from 'lucide-vue-next'

const props = defineProps({
  open: {
    type: Boolean,
    default: false
  },
  lembur: {
    type: Object,
    default: null
  }
})

defineEmits(['close'])

const { formatDate, formatMinutes, getStatusBadgeVariant } = useLembur()

const formatDateTime = (datetime) => {
  if (!datetime) return '-'
  return new Date(datetime).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>