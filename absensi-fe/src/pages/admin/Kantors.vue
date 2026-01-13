<template>
  <div>
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Manajemen Kantor</h1>
        <p class="text-gray-600 mt-1">Kelola data kantor dan lokasi</p>
      </div>
      <button
        @click="openModal()"
        class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors flex items-center"
      >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Tambah Kantor
      </button>
    </div>

    <!-- Kantors Grid -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      <p class="text-gray-600 mt-2">Memuat data...</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="kantor in kantors"
        :key="kantor.id"
        class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow"
      >
        <div class="p-6">
          <!-- Header -->
          <div class="flex justify-between items-start mb-4">
            <div>
              <h3 class="text-lg font-semibold text-gray-900">{{ kantor.nama }}</h3>
              <span
                :class="[
                  'inline-block px-2 py-1 text-xs font-semibold rounded-full mt-2',
                  kantor.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                ]"
              >
                {{ kantor.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </div>
            <div class="flex space-x-2">
              <button
                @click="openModal(kantor)"
                class="text-blue-600 hover:text-blue-800"
                title="Edit"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button
                @click="handleDelete(kantor)"
                class="text-red-600 hover:text-red-800"
                title="Hapus"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Address -->
          <div class="mb-4">
            <div class="flex items-start text-sm text-gray-600">
              <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span>{{ kantor.alamat }}</span>
            </div>
          </div>

          <!-- Coordinates -->
          <div class="mb-4 space-y-1">
            <div class="flex items-center text-xs text-gray-500">
              <span class="font-medium w-20">Latitude:</span>
              <span>{{ kantor.latitude }}</span>
            </div>
            <div class="flex items-center text-xs text-gray-500">
              <span class="font-medium w-20">Longitude:</span>
              <span>{{ kantor.longitude }}</span>
            </div>
            <div class="flex items-center text-xs text-gray-500">
              <span class="font-medium w-20">Radius:</span>
              <span>{{ kantor.radius }} meter</span>
            </div>
          </div>

          <!-- View on Map -->
          <a
            :href="`https://www.google.com/maps?q=${kantor.latitude},${kantor.longitude}`"
            target="_blank"
            class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            Lihat di Google Maps
          </a>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="kantors.length === 0" class="col-span-full text-center py-12">
        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
        <p class="text-gray-600">Belum ada data kantor</p>
        <button
          @click="openModal()"
          class="mt-4 text-indigo-600 hover:text-indigo-800 font-medium"
        >
          Tambah Kantor Pertama
        </button>
      </div>
    </div>

    <!-- Modal Form -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 overflow-y-auto"
      aria-labelledby="modal-title"
      role="dialog"
      aria-modal="true"
    >
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div
          @click="closeModal"
          class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          aria-hidden="true"
        ></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <form @submit.prevent="handleSubmit">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
              <h3 class="text-lg font-medium text-gray-900 mb-4">
                {{ isEdit ? 'Edit Kantor' : 'Tambah Kantor' }}
              </h3>

              <!-- Nama -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Nama Kantor <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.nama"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="Contoh: Kantor Pusat Jakarta"
                />
              </div>

              <!-- Alamat -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Alamat <span class="text-red-500">*</span>
                </label>
                <textarea
                  v-model="form.alamat"
                  required
                  rows="2"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="Alamat lengkap kantor"
                ></textarea>
              </div>

              <!-- Latitude & Longitude -->
              <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Latitude <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model.number="form.latitude"
                    type="number"
                    step="any"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="-6.2088"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Longitude <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model.number="form.longitude"
                    type="number"
                    step="any"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="106.8456"
                  />
                </div>
              </div>

              <!-- Radius -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Radius (meter) <span class="text-red-500">*</span>
                </label>
                <input
                  v-model.number="form.radius"
                  type="number"
                  required
                  min="1"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="100"
                />
                <p class="text-xs text-gray-500 mt-1">Jarak maksimal untuk check-in</p>
              </div>

              <!-- Status -->
              <div class="mb-4">
                <label class="flex items-center">
                  <input
                    v-model="form.is_active"
                    type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                  />
                  <span class="ml-2 text-sm text-gray-700">Kantor Aktif</span>
                </label>
              </div>

              <!-- Tip -->
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <p class="text-xs text-blue-800">
                  💡 <strong>Tip:</strong> Gunakan Google Maps untuk mendapatkan koordinat yang akurat. 
                  Klik kanan pada lokasi → pilih koordinat untuk menyalin.
                </p>
              </div>
            </div>

            <!-- Actions -->
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-3">
              <button
                type="submit"
                :disabled="saving"
                class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
              >
                {{ saving ? 'Menyimpan...' : (isEdit ? 'Update' : 'Simpan') }}
              </button>
              <button
                type="button"
                @click="closeModal"
                :disabled="saving"
                class="mt-3 w-full sm:mt-0 sm:w-auto inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                Batal
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'

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