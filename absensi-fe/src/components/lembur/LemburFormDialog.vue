<template>
  <Dialog :open="open" @update:open="handleClose">
    <DialogContent class="sm:max-w-[500px]">
      <DialogHeader>
        <DialogTitle>{{ isEdit ? 'Edit Lembur' : 'Ajukan Lembur' }}</DialogTitle>
        <DialogDescription>
          {{ isEdit ? 'Ubah data lembur' : 'Isi form untuk mengajukan lembur' }}
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Tanggal Lembur -->
        <div class="space-y-2">
          <Label for="tanggal_lembur">
            Tanggal Lembur <span class="text-red-500">*</span>
          </Label>
          <Input
            id="tanggal_lembur"
            type="date"
            v-model="form.tanggal_lembur"
            :class="{ 'border-red-500': errors.tanggal_lembur }"
            required
          />
          <p v-if="errors.tanggal_lembur" class="text-sm text-red-500">
            {{ errors.tanggal_lembur }}
          </p>
        </div>

        <!-- Lama Lembur -->
        <div class="space-y-2">
          <Label for="lama_lembur">
            Durasi Lembur (dalam menit) <span class="text-red-500">*</span>
          </Label>
          <div class="flex gap-2">
            <div class="flex-1">
              <Input
                id="hours"
                type="number"
                v-model.number="hours"
                placeholder="Jam"
                min="0"
                @input="updateLamaLembur"
              />
            </div>
            <div class="flex-1">
              <Input
                id="minutes"
                type="number"
                v-model.number="minutes"
                placeholder="Menit"
                min="0"
                max="59"
                @input="updateLamaLembur"
              />
            </div>
          </div>
          <p class="text-sm text-muted-foreground">
            Total: {{ formatMinutes(form.lama_lembur) }}
          </p>
          <p v-if="errors.lama_lembur" class="text-sm text-red-500">
            {{ errors.lama_lembur }}
          </p>
        </div>

        <!-- Alasan Lembur -->
        <div class="space-y-2">
          <Label for="alasan_lembur">
            Alasan Lembur <span class="text-red-500">*</span>
          </Label>
          <Textarea
            id="alasan_lembur"
            v-model="form.alasan_lembur"
            placeholder="Jelaskan alasan lembur..."
            rows="4"
            :class="{ 'border-red-500': errors.alasan_lembur }"
            required
          />
          <p class="text-sm text-muted-foreground">
            {{ form.alasan_lembur.length }}/1000 karakter
          </p>
          <p v-if="errors.alasan_lembur" class="text-sm text-red-500">
            {{ errors.alasan_lembur }}
          </p>
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="handleClose">
            Batal
          </Button>
          <Button type="submit" :disabled="submitting">
            <Loader2 v-if="submitting" class="mr-2 h-4 w-4 animate-spin" />
            {{ isEdit ? 'Update' : 'Simpan' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useLemburStore } from '@/stores/lemburStore'
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
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Loader2 } from 'lucide-vue-next'

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

const emit = defineEmits(['close', 'saved'])

const lemburStore = useLemburStore()
const { formatMinutes, handleApiError } = useLembur()

const isEdit = computed(() => !!props.lembur)
const submitting = ref(false)

const form = ref({
  tanggal_lembur: '',
  lama_lembur: 0,
  alasan_lembur: ''
})

const hours = ref(0)
const minutes = ref(0)
const errors = ref({})

// PENTING: Definisikan fungsi SEBELUM digunakan di watch
const resetForm = () => {
  form.value = {
    tanggal_lembur: '',
    lama_lembur: 0,
    alasan_lembur: ''
  }
  hours.value = 0
  minutes.value = 0
  errors.value = {}
}

const updateLamaLembur = () => {
  form.value.lama_lembur = (hours.value * 60) + minutes.value
}

const validateForm = () => {
  errors.value = {}
  
  if (!form.value.tanggal_lembur) {
    errors.value.tanggal_lembur = 'Tanggal lembur wajib diisi'
  }
  
  if (form.value.lama_lembur < 1) {
    errors.value.lama_lembur = 'Durasi lembur minimal 1 menit'
  }
  
  if (!form.value.alasan_lembur) {
    errors.value.alasan_lembur = 'Alasan lembur wajib diisi'
  } else if (form.value.alasan_lembur.length > 1000) {
    errors.value.alasan_lembur = 'Alasan lembur maksimal 1000 karakter'
  }
  
  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) return
  
  submitting.value = true
  
  try {
    if (isEdit.value) {
      await lemburStore.updateLembur(props.lembur.id, form.value)
    } else {
      await lemburStore.createLembur(form.value)
    }
    
    emit('saved')
    resetForm()
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      handleApiError(error)
    }
  } finally {
    submitting.value = false
  }
}

const handleClose = () => {
  resetForm()
  emit('close')
}

// Watch for prop changes - SETELAH resetForm didefinisikan
watch(() => props.lembur, (newLembur) => {
  if (newLembur) {
    form.value = {
      tanggal_lembur: newLembur.tanggal_lembur,
      lama_lembur: newLembur.lama_lembur,
      alasan_lembur: newLembur.alasan_lembur
    }
    // Convert minutes to hours and minutes
    hours.value = Math.floor(newLembur.lama_lembur / 60)
    minutes.value = newLembur.lama_lembur % 60
  } else {
    resetForm()
  }
}, { immediate: true })
</script>