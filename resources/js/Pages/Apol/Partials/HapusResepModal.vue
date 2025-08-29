<template>
  <Modal :show="show" max-width="2xl" @close="close">
    <div class="flex items-center justify-between px-5 py-4 border-b">
      <h3 class="text-lg font-semibold">Hapus Resep</h3>
      <button @click="close" class="text-gray-500 hover:text-gray-700">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
            clip-rule="evenodd"
          ></path>
        </svg>
      </button>
    </div>

    <div class="px-5 py-4 space-y-4">
      <div v-if="isLoading" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-rose-600"></div>
      </div>
      
      <template v-else>
        <p class="text-sm text-gray-600">
          Pastikan <b>No Resep</b>, <b>No SEP Apotek/SJP</b>, dan <b>Ref Asal SJP</b> sesuai.
          Centang opsi di bawah untuk menghapus <b>semua obat</b> lebih dulu (disarankan).
        </p>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div class="md:col-span-1">
            <label class="block mb-1 text-sm font-medium text-gray-700">No Resep</label>
            <input
              v-model="form.noresep"
              type="text"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent"
              placeholder="Contoh: 0SI44"
              readonly
            />
          </div>

          <div class="md:col-span-1">
            <label class="block mb-1 text-sm font-medium text-gray-700">No SEP Apotek/SJP</label>
            <input
              v-model="form.nosjp"
              type="text"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent"
              placeholder="Contoh: 1801A00104190000001"
              readonly
            />
          </div>

          <div class="md:col-span-1">
            <label class="block mb-1 text-sm font-medium text-gray-700">Ref Asal SJP</label>
            <input
              v-model="form.refasalsjp"
              type="text"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent"
              placeholder="Contoh: 1801R0010419V000001"
              readonly
            />
          </div>
        </div>

        <!-- Toggle purge-first -->
        <div class="flex items-center gap-3 mt-2">
          <input
            id="purgeFirst"
            type="checkbox"
            v-model="purgeObatFirst"
            class="w-4 h-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500"
          />
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
            <button
              class="text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50"
              @click="loadObatList"
              :disabled="isLoadingObatList"
            >
              Muat Ulang
            </button>
          </div>

          <div v-if="isLoadingObatList" class="text-xs text-gray-500 mt-2">Mengambil data...</div>
          <div v-else-if="obatList.length === 0" class="text-xs text-gray-500 mt-2">Tidak ada obat.</div>
          <ul v-else class="mt-2 space-y-1 max-h-48 overflow-auto pr-1">
            <li
              v-for="(o, idx) in obatList"
              :key="idx"
              class="text-sm flex items-center justify-between border-b last:border-0 py-1"
            >
              <div class="flex-1 min-w-0">
                <div class="font-medium truncate">{{ o.namaobat }}</div>
                <div class="text-xs text-gray-500">
                  Kode: <span class="font-mono">{{ o.kodeobat }}</span>
                  &middot; Tipe: {{ o.tipeobat || 'N' }}
                  <template v-if="o.jumlah"> &middot; Jml: {{ o.jumlah }}</template>
                </div>
              </div>
            </li>
          </ul>

          <!-- Progress hapus obat -->
          <div v-if="isDeleting && purgeObatFirst" class="mt-3 text-xs">
            <div class="mb-1">
              Menghapus obat: {{ deleteStats.done }}/{{ deleteStats.total }} selesai, gagal:
              {{ deleteStats.failed }}
            </div>
            <div class="w-full bg-gray-200 rounded h-2 overflow-hidden">
              <div
                class="h-2 bg-rose-600"
                :style="{
                  width: (deleteStats.total ? (deleteStats.done / deleteStats.total) * 100 : 0) + '%'
                }"
              ></div>
            </div>
            <div v-if="deleteStats.failed > 0" class="mt-2 text-rose-600">
              Beberapa obat gagal dihapus. Resep tidak akan dihapus sampai semua obat terhapus.
            </div>
          </div>
        </div>
      </template>
    </div>

    <div v-if="!isLoading" class="flex items-center justify-end gap-3 px-5 py-4 border-t">
      <button
        @click="close"
        class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
      >
        Batal
      </button>
      <button
        :disabled="isDeleting || deleteDisabled || isLoadingObatList"
        @click="submitDelete"
        class="inline-flex items-center gap-2 px-5 py-2 rounded-lg text-white bg-rose-600 hover:bg-rose-700 disabled:bg-rose-400"
      >
        <svg v-if="isDeleting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          />
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          />
        </svg>
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
        form.noresep = props.selectedItem.NORESEP ?? props.selectedItem.noresep ?? ''
        form.nosjp = props.selectedItem.NOSJP ?? props.selectedItem.nosjp ?? props.selectedItem.NOAPOTIK ?? props.selectedItem.noapotik ?? ''
        form.refasalsjp = props.selectedItem.REFASALSJP ?? props.selectedItem.refasalsjp ?? 
                          props.selectedItem.NOSEP_KUNJUNGAN ?? props.selectedItem.ref_asal_sjp ?? ''
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
      payload?.listobat,
      payload?.detailsep?.listobat,
      payload?.data?.listobat,
      payload?.data?.detailsep?.listobat,
      payload?.response?.listobat,
      payload?.response?.detailsep?.listobat
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
      tipeobat: (o.tipeobat ?? o.tipeObat ?? 'N').toString().toUpperCase(),
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
    // 1) Opsional: hapus semua obat dulu
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

    // 2) Hapus resep
    const { data } = await axios.post('/apol/hapus-resep', {
      nosjp: String(form.nosjp).trim(),
      refasalsjp: String(form.refasalsjp).trim(),
      noresep: String(form.noresep).trim()
    }, {
      timeout: 20000 // 20 second timeout for resep deletion
    })

    if (data.success) {
      emit('deleted', form.noresep)
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
</script>