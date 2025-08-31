<template>
  <Modal :show="show" max-width="4xl" @close="close">
    <div class="flex items-center justify-between px-5 py-4 border-b">
      <h3 class="text-lg font-semibold">Hapus Resep</h3>
    </div>

    <div class="px-5 py-4 space-y-4">
      <div v-if="isLoading" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-rose-600"></div>
      </div>

      <template v-else>
        <p class="text-sm text-gray-600">
          Pastikan <b class="text-red-600">No Resep</b>, <b class="text-red-600">No SEP Apotek/SJP</b>, dan <b
            class="text-red-600">Ref Asal SJP</b> sesuai.
        </p>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
          <div class="md:col-span-1">
            <label class="block mb-1 text-sm font-medium text-gray-700">No Resep</label>
            <p class="font-semibold text-green-600">{{ form.noresep }}</p>
          </div>

          <div class="md:col-span-1">
            <label class="block mb-1 text-sm font-medium text-gray-700">Nama</label>
            <p class="font-semibold text-green-600">{{ form.nama }}</p>
          </div>

          <div class="md:col-span-1">
            <label class="block mb-1 text-sm font-medium text-gray-700">No SEP Apotek/SJP</label>
            <p class="font-semibold text-green-600">{{ form.nosjp }}</p>
          </div>

          <div class="md:col-span-1">
            <label class="block mb-1 text-sm font-medium text-gray-700">Ref Asal SJP</label>
            <p class="font-semibold text-green-600">{{ form.refasalsjp }}</p>
          </div>
        </div>

        <!-- Toggle purge-first -->
        <div class="flex items-center gap-3 mt-2">
          <input id="purgeFirst" type="checkbox" v-model="purgeObatFirst"
            class="w-4 h-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500" />
          <label for="purgeFirst" class="text-sm text-gray-700">
            Hapus <b>semua obat</b> pada resep ini sebelum hapus resep
          </label>
        </div>

        <!-- Daftar obat -->
        <div class="border rounded-lg p-3">
          <div class="flex items-center justify-between">
            <h4 class="text-sm font-semibold text-gray-800">
              Daftar Obat ({{ isLoadingObatList ? 'memuat...' : obatList.length + ' item' }})
            </h4>
            <button class="text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50" @click="loadObatList"
              :disabled="isLoadingObatList">
              reload
            </button>
          </div>

          <div v-if="isLoadingObatList" class="text-xs text-gray-500 mt-2">Mengambil data...</div>
          <ul v-else class="mt-2 space-y-1 max-h-48 overflow-auto pr-1">
            <li v-for="(o, idx) in obatList" :key="idx"
              class="text-sm flex items-center justify-between border-b last:border-0 py-1">
              <div class="flex-1 min-w-0">
                <div class="font-medium truncate">{{ o.namaobat }}</div>
                <div class="text-xs text-gray-500">
                  Kode: <span class="font-mono text-blue-500">{{ o.kodeobat }}</span>
                  &middot; Tipe Obat: <span class="text-purple-500">{{ o.tipeobat }}</span>
                  &middot; Harga: <span class="text-teal-500">{{ o.harga }}</span>
                  &middot; Jumlah: <span class="text-cyan-500">{{ o.jumlah }}</span>
                  &middot; Signa 1 x 2: <span class="text-amber-500">{{ o.signa1 }} x {{ o.signa2 }}</span>
                </div>
              </div>
              <button @click="deleteSingleObat(o, idx)"
                class="ml-2 px-2 py-1 rounded text-sm text-rose-600 hover:bg-rose-100 disabled:opacity-50"
                :disabled="isDeleting">
                <font-awesome-icon icon="times" />
              </button>
            </li>
          </ul>

          <!-- Progress hapus obat -->
          <div v-if="isDeleting && purgeObatFirst" class="mt-3 text-xs">
            <div class="mb-1">
              Menghapus obat: {{ deleteStats.done }}/{{ deleteStats.total }} selesai, gagal:
              {{ deleteStats.failed }}
            </div>
            <div class="w-full bg-gray-200 rounded h-2 overflow-hidden">
              <div class="h-2 bg-rose-600" :style="{
                width: (deleteStats.total ? (deleteStats.done / deleteStats.total) * 100 : 0) + '%'
              }"></div>
            </div>
            <div v-if="deleteStats.failed > 0" class="mt-2 text-rose-600">
              Beberapa obat gagal dihapus. Resep tidak akan dihapus sampai semua obat terhapus.
            </div>
          </div>
        </div>
      </template>
    </div>

    <div v-if="!isLoading" class="flex items-center justify-end gap-3 px-4 py-4">
      <button @click="close" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
        Batal
      </button>
      <button :disabled="isDeleting || deleteDisabled || isLoadingObatList" @click="submitDelete"
        class="px-4 py-2 border border-rose-600 rounded-lg text-white bg-rose-600 hover:bg-rose-700 disabled:bg-rose-400">
        <font-awesome-icon v-if="isDeleting" icon="spinner" spin />
        {{ isDeleting ? 'Memproses...' : 'Hapus Resep' }}
      </button>
    </div>
  </Modal>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick } from 'vue'
import Modal from '@/Components/Modal.vue'
import axios from 'axios'

const emit = defineEmits(['close', 'deleted'])

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  selectedItem: {
    type: Object,
    default: () => ({})
  }
})

// Form data
const form = reactive({
  nosjp: '',
  refasalsjp: '',
  noresep: ''
})

// State variables
const isLoading = ref(false)
const isLoadingObatList = ref(false)
const isDeleting = ref(false)
const purgeObatFirst = ref(true)
const obatList = ref([])

// Delete statistics
const deleteStats = reactive({
  total: 0,
  done: 0,
  failed: 0,
  failures: []
})

// Computed properties
const deleteDisabled = computed(
  () => !form.nosjp || !form.refasalsjp || !form.noresep
)

// Watch for modal show changes
watch(
  () => props.show,
  async (isShowing) => {
    if (isShowing) {
      // Set loading state while preparing modal
      isLoading.value = true

      // Prefill form data from selected item
      if (props.selectedItem) {
        form.noresep = props.selectedItem.NORESEP
        form.nosjp = props.selectedItem.NOAPOTIK
        form.refasalsjp = props.selectedItem.NOSEP_KUNJUNGAN
        form.nama = props.selectedItem.NAMA
      }

      // Reset stats
      resetDeleteProgress()

      // Wait for modal to fully render before loading data
      await nextTick()

      // Load obat list after a short delay to ensure modal is visible
      setTimeout(() => {
        loadObatList().finally(() => {
          isLoading.value = false
        })
      }, 100)
    } else {
      // Reset when modal closes
      isLoading.value = false
      obatList.value = []
    }
  }
)

// Methods
const close = () => {
  emit('close')
}

const resetDeleteProgress = () => {
  deleteStats.total = 0
  deleteStats.done = 0
  deleteStats.failed = 0
  deleteStats.failures = []
}

const normalizeListObat = (list) => {
  if (!list) return []
  if (Array.isArray(list)) return list
  if (typeof list === 'object' && list.kodeobat) return [list]
  return []
}

const loadObatList = async () => {
  obatList.value = []
  if (!form.nosjp) return

  isLoadingObatList.value = true
  try {
    let res
    try {
      res = await axios.get(`/apol/pelayanan/obat/daftar/${encodeURIComponent(form.nosjp)}`, {
        timeout: 10000 // 10 second timeout
      })
    } catch {
      res = await axios.get('/apol/pelayanan/obat/daftar', {
        params: { nosep: form.nosjp },
        timeout: 10000
      })
    }

    const payload = res?.data || {}

    // Cari listobat di berbagai bentuk payload
    let list = []
    const candidates = [
      payload?.data?.listobat,
      payload?.data?.detailsep?.listobat,
    ]
    for (const cand of candidates) {
      const norm = normalizeListObat(cand)
      if (norm.length) {
        list = norm
        break
      }
    }

    obatList.value = list.map((o) => ({
      kodeobat: String(o.kodeobat ?? o.kdobat ?? '').trim(),
      namaobat: o.namaobat ?? o.nmobat ?? '-',
      tipeobat: (o.tipeobat ?? 'N').toString().toUpperCase(),
      harga: (o.harga ?? 'N').toString(),
      signa1: o.signa1 ?? o.signa ?? null,
      signa2: o.signa2 ?? null,
      jumlah: o.jumlah ?? o.qty ?? null,
      hari: o.hari ?? null,
      harga: o.harga ?? null
    }))
  } catch (e) {
    console.error('Gagal memuat daftar obat:', e)
    // Tetap lanjut meskipun gagal load obat list
  } finally {
    isLoadingObatList.value = false
  }
}

const deleteAllObatBeforeResep = async () => {
  resetDeleteProgress()
  deleteStats.total = obatList.value.length
  if (deleteStats.total === 0) return true

  for (const ob of obatList.value) {
    try {
      await axios.post('/apol/hapus-obat', {
        nosepapotek: String(form.nosjp).trim(),
        noresep: String(form.noresep).trim(),
        kodeobat: String(ob.kodeobat).trim(),
        tipeobat: ob.tipeobat || 'N',
        verify: true
      }, {
        timeout: 15000 // 15 second timeout for each deletion
      })
      deleteStats.done += 1
    } catch (err) {
      deleteStats.failed += 1
      deleteStats.failures.push({
        kodeobat: ob.kodeobat,
        message: err?.response?.data?.message || err.message || 'Gagal hapus obat'
      })
    }
  }

  // Refresh list untuk bukti final
  await loadObatList()

  // Return true if all deletions succeeded
  return deleteStats.failed === 0
}

const submitDelete = async () => {
  if (deleteDisabled.value) {
    return
  }

  isDeleting.value = true
  try {
    let obatDeletedSuccessfully = true
    if (purgeObatFirst.value) {
      obatDeletedSuccessfully = await deleteAllObatBeforeResep()

      // Kalau masih ada obat sisa, jangan lanjut hapus resep
      if (!obatDeletedSuccessfully || (obatList.value?.length ?? 0) > 0) {
        console.error(`Masih ada ${obatList.value.length} obat di resep ini. Resep belum dihapus.`)
        isDeleting.value = false
        return
      }
    }

    const { data } = await axios.post('/apol/hapus-resep', {
      nosjp: String(form.nosjp).trim(),
      refasalsjp: String(form.refasalsjp).trim(),
      noresep: String(form.noresep).trim()
    }, {
      timeout: 20000
    })

    if (data.success) {
      emit('deleted', form.nosjp)
      close()
    } else {
      console.error(data.message || 'Gagal menghapus resep')
      // Refresh obat untuk bukti
      await loadObatList()
    }
  } catch (e) {
    console.error('Terjadi kesalahan saat menghapus resep:', e)
  } finally {
    isDeleting.value = false
  }
}

const deleteSingleObat = async (obat, index) => {
  if (!form.nosjp || !form.noresep || !obat?.kodeobat) return

  isDeleting.value = true
  try {
    await axios.post('/apol/hapus-obat', {
      nosepapotek: String(form.nosjp).trim(),
      noresep: String(form.noresep).trim(),
      kodeobat: String(obat.kodeobat).trim(),
      tipeobat: obat.tipeobat || 'N',
      verify: true
    }, {
      timeout: 15000
    })

    // Kalau sukses, hapus dari array obatList
    obatList.value.splice(index, 1)
    console.log(`Obat ${obat.namaobat} berhasil dihapus`)
  } catch (err) {
    console.error('Gagal hapus obat:', err?.response?.data?.message || err.message)
  } finally {
    isDeleting.value = false
  }
}
</script>