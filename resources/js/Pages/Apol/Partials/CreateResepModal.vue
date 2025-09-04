<template>
    <Modal :show="show" max-width="4xl" @close="close">
        <div class="flex items-center justify-between px-5 py-4 border-b">
            <h3 class="text-lg font-semibold">Buat Resep Klaim Obat Kronis</h3>
        </div>

        <div class="px-5 py-4 space-y-4">
            <div v-if="showError && form.errors.general" class="mb-4 p-4 bg-red-50 border-l-4 border-red-500">
                <div class="flex">
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">
                            {{ form.errors.general }}
                        </h3>
                    </div>
                </div>
            </div>

            <div v-if="isLoading" class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-green-600"></div>
            </div>

            <template v-else>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <FormField label="No Resep" :value="form.noresep" />
                    <FormField label="ID User SJP" :value="form.idusersjp" />
                    <FormField label="Poli Resep" :value="form.polirsp" />
                    <FormField label="Ref Asal SJP" :value="form.refasalsjp" />
                    <FormField label="Tgl SJP" :value="form.tglsjp" />
                    <FormField label="Tgl Resep" :value="form.tglrsp" />
                    <FormField label="Tgl Pelayanan Resep" :value="form.tglpelrsp" />
                    <FormField label="Kode Dokter" :value="form.kddokter" />
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Jenis Obat</label>
                        <select v-model="form.kdjnsobat"
                            class="w-full border rounded px-3 py-2 text-sm border-gray-300">
                            <option value="1">1. Obat PRB</option>
                            <option value="2">2. Obat Kronis Belum Stabil</option>
                            <option value="3">3. Obat Kemoterapi</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Iterasi</label>
                        <select v-model="form.iterasi" class="w-full border rounded px-3 py-2 text-sm border-gray-300">
                            <option value="0">0. Non Iterasi</option>
                            <option value="1">1. Iterasi</option>
                            <option value="2">2. Iterasi Lanjutan</option>
                        </select>
                    </div>
                </div>

                <!-- Detail Obat -->
                <div v-if="resepDetil.length > 0" class="mt-6">
                    <h4 class="text-md font-semibold mb-2">Detail Obat</h4>
                    <ul v-if="resepDetil.length > 0" class="mt-2 space-y-1 max-h-48 overflow-auto pr-1">
                        <li v-for="(o, idx) in resepDetil" :key="o.ID"
                            class="text-sm flex items-center justify-between border-b last:border-0 py-1">
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate">{{ o.REFERENSI?.BARANG?.NAMA }}
                                    <span v-if="o.RACIKAN == 1"
                                        class="px-1 rounded-md bg-red-100 text-xs ml-1 text-red-800">{{
                                        o.REFERENSI?.JNSROBT }}</span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    Kode:
                                    <span class="font-mono text-blue-500">{{ o.REFERENSI?.DPHO?.kodeobat }}</span>
                                    &middot; Obat:
                                    <span class="text-purple-500">{{ o.REFERENSI?.DPHO?.namaobat }}</span>
                                    &middot; Harga:
                                    <span class="text-teal-500">{{ o.REFERENSI?.DPHO?.harga }}</span>
                                    &middot; Jumlah:
                                    <span class="text-cyan-500">{{ o.JUMLAH }}</span>
                                    &middot; Signa:
                                    <span class="text-amber-500">
                                        {{ o.SIGNA1 || o.REFERENSI?.FREKUENSIATURAN?.SIGNA1 }} x
                                        {{ o.SIGNA2 || o.REFERENSI?.FREKUENSIATURAN?.SIGNA2 }}
                                    </span>
                                    &middot; Hari:
                                    <span class="text-rose-500">{{ o.HARI }}</span>
                                    &middot; Frekuensi:
                                    <span class="text-indigo-500">{{ o.REFERENSI?.FREKUENSIATURAN?.FREKUENSI }}</span>
                                    &middot; Permintaan:
                                    <span class="text-orange-500">{{ o.PERMINTAAN }}</span>
                                </div>
                            </div>
                        </li>
                    </ul>

                </div>
            </template>
        </div>

        <div class="flex items-center justify-end gap-2 px-4 py-4">
            <button @click="close" class="px-2 py-1 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                Batal
            </button>
            <button :disabled="isSubmitting" @click="submitResep"
                class="px-2 py-1 border border-green-600 rounded-md text-white bg-green-600 hover:bg-green-700 disabled:bg-green-400">
                <font-awesome-icon v-if="isSubmitting" icon="spinner" spin/>
                {{ isSubmitting ? 'Mengirim...' : 'Kirim ke Apotek Online BPJS' }}
            </button>
        </div>
    </Modal>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import axios from 'axios'
import Modal from '@/Components/Modal.vue'
import FormField from '@/Components/FormField.vue'
import dayjs from 'dayjs'

const emit = defineEmits(['close', 'saved'])

const props = defineProps({
    show: Boolean,
    selectedItem: Object
})

const isLoading = ref(false)
const isSubmitting = ref(false)
const showError = ref(false)
let errorTimer = null

const form = reactive({
    noresep: '',
    idusersjp: '',
    polirsp: '',
    refasalsjp: '',
    tglsjp: '',
    tglrsp: '',
    tglpelrsp: '',
    kddokter: '',
    kdjnsobat: '1',
    iterasi: '0',
    kunjungan: '',
    errors: {}
})

const resepDetil = ref([])

watch(() => props.show, async (isOpen) => {
    if (isOpen && props.selectedItem) {
        isLoading.value = true
        resepDetil.value = []

        Object.assign(form, {
            noresep: props.selectedItem.REFERENSI?.NOMORRESEP?.NOMOR ?? '',
            idusersjp: props.selectedItem.IDUSERSJP ?? '',
            polirsp: getPoliRsp(props.selectedItem) ?? '',
            refasalsjp: props.selectedItem.REFASALSJP ?? '',
            tglsjp: dayjs().format('YYYY-MM-DD HH:mm:ss'),
            tglrsp: props.selectedItem.MASUK ?? '',
            tglpelrsp: props.selectedItem.TGLPELRSP ?? '',
            kddokter: props.selectedItem.REFERENSI?.DPJP_PENJAMIN_RS?.DPJP_PENJAMIN ?? '',
            kdjnsobat: '1',
            iterasi: '0',
            kunjungan: props.selectedItem.NOMOR ?? ''
        })


        try {
            const { data } = await axios.get('/resep-detil', {
                params: { RESEP: props.selectedItem.NOMOR }
            })
            if (data.success !== false) {
                resepDetil.value = data.data || []
            }
        } catch (e) {
            console.error('Gagal ambil detail resep', e)
        }

        isLoading.value = false
    }
})


const close = () => {
    form.errors = {}
    showError.value = false
    clearTimeout(errorTimer)
    emit('close')
}

const submitResep = async () => {
    isSubmitting.value = true
    form.errors = {}

    try {
        const payload = {
            TGLSJP: form.tglsjp,
            REFASALSJP: form.refasalsjp,
            POLIRSP: form.polirsp,
            KDJNSOBAT: form.kdjnsobat,
            NORESEP: form.noresep,
            IDUSERSJP: form.idusersjp,
            TGLRSP: form.tglrsp,
            TGLPELRSP: form.tglpelrsp,
            KdDokter: form.kddokter,
            iterasi: form.iterasi,
            KUNJUNGAN: form.kunjungan,
            DETAIL: resepDetil.value
        }

        const { data } = await axios.post('/apol/simpan-resep', payload)

        if (data.success) {
            emit('saved', data)
            close()
        } else {
            form.errors = { general: data.message || 'Gagal simpan resep' }
            showError.value = true

            setTimeout(() => {
                showError.value = false
            }, 5000)

        }
    } catch (err) {
        if (err.response?.data?.errors) {
            form.errors = err.response.data.errors
        } else {
            form.errors = { general: err.response?.data?.message || 'Error saat simpan resep' }
            showError.value = true

            setTimeout(() => {
                showError.value = false
            }, 9000)

        }
    } finally {
        isSubmitting.value = false
    }
}

const getPoliRsp = (item) => {
    const penjaminRuangan = item.REFERENSI?.ASAL_RESEP?.REFERENSI?.PENJAMIN_RUANGAN
    return Array.isArray(penjaminRuangan) ? penjaminRuangan[0]?.RUANGAN_PENJAMIN || '' : ''
}
</script>
