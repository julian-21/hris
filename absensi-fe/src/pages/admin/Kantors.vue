<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-start">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Manajemen Kantor</h1>
        <p class="text-muted-foreground mt-2">Kelola data kantor dan lokasi</p>
      </div>
      <Button @click="openModal()" size="default">
        <Plus class="mr-2 h-4 w-4" />
        Tambah Kantor
      </Button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-12">
      <Loader2 class="h-8 w-8 animate-spin text-primary" />
      <p class="text-muted-foreground mt-4">Memuat data...</p>
    </div>

    <!-- Kantors Grid -->
    <div v-else-if="kantors.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      <Card v-for="kantor in kantors" :key="kantor.id" class="overflow-hidden">
        <CardHeader class="pb-3">
          <div class="flex items-start justify-between">
            <div class="space-y-2 flex-1">
              <CardTitle class="text-xl">{{ kantor.nama }}</CardTitle>
              <Badge :variant="kantor.is_active ? 'default' : 'secondary'">
                {{ kantor.is_active ? 'Aktif' : 'Nonaktif' }}
              </Badge>
            </div>
            <div class="flex gap-2">
              <Button 
                @click="openModal(kantor)" 
                variant="ghost" 
                size="icon"
                class="h-8 w-8"
              >
                <Pencil class="h-4 w-4" />
              </Button>
              <Button 
                @click="handleDelete(kantor)" 
                variant="ghost" 
                size="icon"
                class="h-8 w-8 text-destructive hover:text-destructive"
              >
                <Trash2 class="h-4 w-4" />
              </Button>
            </div>
          </div>
        </CardHeader>
        
        <CardContent class="space-y-4">
          <!-- Address -->
          <div class="flex items-start gap-2">
            <MapPin class="h-4 w-4 mt-0.5 text-muted-foreground flex-shrink-0" />
            <p class="text-sm text-muted-foreground">{{ kantor.alamat }}</p>
          </div>

          <!-- Coordinates -->
          <div class="space-y-2 rounded-lg bg-muted/50 p-3">
            <div class="flex items-center justify-between text-sm">
              <span class="text-muted-foreground">Latitude:</span>
              <span class="font-mono">{{ kantor.latitude }}</span>
            </div>
            <Separator />
            <div class="flex items-center justify-between text-sm">
              <span class="text-muted-foreground">Longitude:</span>
              <span class="font-mono">{{ kantor.longitude }}</span>
            </div>
            <Separator />
            <div class="flex items-center justify-between text-sm">
              <span class="text-muted-foreground">Radius:</span>
              <span class="font-medium">{{ kantor.radius }} meter</span>
            </div>
          </div>

          <!-- View on Map -->
          <Button 
            variant="outline" 
            size="sm" 
            class="w-full"
            as-child
          >
            <a 
              :href="`https://www.google.com/maps?q=${kantor.latitude},${kantor.longitude}`"
              target="_blank"
              class="flex items-center justify-center"
            >
              <Map class="mr-2 h-4 w-4" />
              Lihat di Google Maps
            </a>
          </Button>
        </CardContent>
      </Card>
    </div>

    <!-- Empty State -->
    <Card v-else class="border-dashed">
      <CardContent class="flex flex-col items-center justify-center py-12">
        <div class="rounded-full bg-muted p-4 mb-4">
          <Building2 class="h-10 w-10 text-muted-foreground" />
        </div>
        <h3 class="text-lg font-semibold mb-2">Belum ada data kantor</h3>
        <p class="text-muted-foreground text-sm mb-4">Mulai dengan menambahkan kantor pertama Anda</p>
        <Button @click="openModal()" variant="outline">
          <Plus class="mr-2 h-4 w-4" />
          Tambah Kantor Pertama
        </Button>
      </CardContent>
    </Card>

    <!-- Modal Form -->
    <Dialog v-model:open="showModal">
      <DialogContent class="sm:max-w-[600px]">
        <DialogHeader>
          <DialogTitle>{{ isEdit ? 'Edit Kantor' : 'Tambah Kantor' }}</DialogTitle>
          <DialogDescription>
            {{ isEdit ? 'Perbarui informasi kantor' : 'Tambahkan kantor baru ke sistem' }}
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="handleSubmit" class="space-y-6">
          <div class="space-y-4">
            <!-- Nama -->
            <div class="space-y-2">
              <Label for="nama">
                Nama Kantor <span class="text-destructive">*</span>
              </Label>
              <Input
                id="nama"
                v-model="form.nama"
                type="text"
                required
                placeholder="Contoh: Kantor Pusat Jakarta"
              />
            </div>

            <!-- Alamat -->
            <div class="space-y-2">
              <Label for="alamat">
                Alamat <span class="text-destructive">*</span>
              </Label>
              <Textarea
                id="alamat"
                v-model="form.alamat"
                required
                rows="3"
                placeholder="Alamat lengkap kantor"
              />
            </div>

            <!-- Latitude & Longitude -->
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="latitude">
                  Latitude <span class="text-destructive">*</span>
                </Label>
                <Input
                  id="latitude"
                  v-model.number="form.latitude"
                  type="number"
                  step="any"
                  required
                  placeholder="-6.2088"
                />
              </div>
              <div class="space-y-2">
                <Label for="longitude">
                  Longitude <span class="text-destructive">*</span>
                </Label>
                <Input
                  id="longitude"
                  v-model.number="form.longitude"
                  type="number"
                  step="any"
                  required
                  placeholder="106.8456"
                />
              </div>
            </div>

            <!-- Radius -->
            <div class="space-y-2">
              <Label for="radius">
                Radius (meter) <span class="text-destructive">*</span>
              </Label>
              <Input
                id="radius"
                v-model.number="form.radius"
                type="number"
                required
                min="1"
                placeholder="100"
              />
              <p class="text-xs text-muted-foreground">Jarak maksimal untuk check-in</p>
            </div>

            <!-- Status -->
            <div class="flex items-center space-x-2">
              <Checkbox 
                id="is_active" 
                v-model:checked="form.is_active"
              />
              <Label 
                for="is_active" 
                class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
              >
                Kantor Aktif
              </Label>
            </div>

            <!-- Tip -->
            <Alert>
              <Lightbulb class="h-4 w-4" />
              <AlertTitle>Tips</AlertTitle>
              <AlertDescription class="text-xs">
                Gunakan Google Maps untuk mendapatkan koordinat yang akurat. 
                Klik kanan pada lokasi → pilih koordinat untuk menyalin.
              </AlertDescription>
            </Alert>
          </div>

          <DialogFooter>
            <Button 
              type="button" 
              variant="outline" 
              @click="closeModal"
              :disabled="saving"
            >
              Batal
            </Button>
            <Button 
              type="submit" 
              :disabled="saving"
            >
              <Loader2 v-if="saving" class="mr-2 h-4 w-4 animate-spin" />
              {{ saving ? 'Menyimpan...' : (isEdit ? 'Update' : 'Simpan') }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'

// Shadcn UI Components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import { Separator } from '@/components/ui/separator'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'

// Lucide Icons
import { 
  Plus, 
  Pencil, 
  Trash2, 
  MapPin, 
  Map, 
  Building2, 
  Loader2,
  Lightbulb 
} from 'lucide-vue-next'

const kantors = ref([])
const loading = ref(false)
const showModal = ref(false)
const saving = ref(false)
const isEdit = ref(false)
const editId = ref(null)

const form = ref({
  nama: '',
  alamat: '',
  latitude: null,
  longitude: null,
  radius: 100,
  is_active: true
})

const fetchKantors = async () => {
  loading.value = true
  try {
    const response = await api.get('/kantors')
    console.log('Kantors response:', response.data)
    
    // Handle different response formats
    if (Array.isArray(response.data)) {
      kantors.value = response.data
    } else if (response.data.data) {
      kantors.value = response.data.data
    } else {
      kantors.value = []
    }
  } catch (error) {
    console.error('Error fetching kantors:', error)
    alert('Gagal memuat data kantor')
  } finally {
    loading.value = false
  }
}

const openModal = (kantor = null) => {
  if (kantor) {
    isEdit.value = true
    editId.value = kantor.id
    form.value = {
      nama: kantor.nama,
      alamat: kantor.alamat,
      latitude: kantor.latitude,
      longitude: kantor.longitude,
      radius: kantor.radius,
      is_active: kantor.is_active
    }
  } else {
    isEdit.value = false
    editId.value = null
    form.value = {
      nama: '',
      alamat: '',
      latitude: null,
      longitude: null,
      radius: 100,
      is_active: true
    }
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  isEdit.value = false
  editId.value = null
}

const handleSubmit = async () => {
  saving.value = true
  try {
    if (isEdit.value) {
      await api.put(`/kantors/${editId.value}`, form.value)
      alert('✅ Kantor berhasil diupdate')
    } else {
      await api.post('/kantors', form.value)
      alert('✅ Kantor berhasil ditambahkan')
    }
    
    closeModal()
    fetchKantors()
  } catch (error) {
    console.error('Error saving kantor:', error)
    const message = error.response?.data?.message || 'Gagal menyimpan data kantor'
    alert('❌ ' + message)
  } finally {
    saving.value = false
  }
}

const handleDelete = async (kantor) => {
  if (!confirm(`Apakah Anda yakin ingin menghapus kantor "${kantor.nama}"?`)) {
    return
  }

  try {
    await api.delete(`/kantors/${kantor.id}`)
    alert('✅ Kantor berhasil dihapus')
    fetchKantors()
  } catch (error) {
    console.error('Error deleting kantor:', error)
    const message = error.response?.data?.message || 'Gagal menghapus kantor'
    alert('❌ ' + message)
  }
}

onMounted(() => {
  fetchKantors()
})
</script>