<template>
    <Modal :show="show" max-width="4xl" @close="close">
        <div class="flex items-center justify-between px-5 py-4 border-b">
            <h3 class="text-lg font-semibold">Buat Resep Klaim Obat Kronis {{ props.selectedItem.NOMOR }}</h3>
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
                <!-- Editable Tanggal -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <FormField label="No Resep" :value="form.noresep" />
                    <FormField label="ID User SJP" :value="form.idusersjp" />
                    <FormField label="Poli Resep" :value="form.polirsp" />
                    <FormField label="Ref Asal SJP" :value="form.refasalsjp" />

                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Tgl SJP</label>
                        <span v-if="!editTanggal.tglsjp" @dblclick="editTanggal.tglsjp = true"
                            class="cursor-pointer block text-sm font-semibold text-green-600">
                            {{ form.tglsjp }}
                        </span>
                        <input v-else type="datetime-local" v-model="form.tglsjp" @blur="editTanggal.tglsjp = false"
                            class="w-full border rounded px-3 py-2 text-sm border-gray-300" />
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Tgl Resep</label>
                        <span v-if="!editTanggal.tglrsp" @dblclick="editTanggal.tglrsp = true"
                            class="cursor-pointer block text-sm font-semibold text-green-600">
                            {{ form.tglrsp }}
                        </span>
                        <input v-else type="date" v-model="form.tglrsp" @blur="editTanggal.tglrsp = false"
                            class="w-full border rounded px-3 py-2 text-sm border-gray-300" />
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Tgl Pelayanan Resep</label>
                        <span v-if="!editTanggal.tglpelrsp" @dblclick="editTanggal.tglpelrsp = true"
                            class="cursor-pointer block text-sm font-semibold text-green-600">
                            {{ form.tglpelrsp }}
                        </span>
                        <input v-else type="date" v-model="form.tglpelrsp" @blur="editTanggal.tglpelrsp = false"
                            class="w-full border rounded px-3 py-2 text-sm border-gray-300" />
                    </div>

                    <FormField label="Kode Dokter" :value="form.kddokter" />
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Jenis Obat</label>
                        <select v-model="form.kdjnsobat"
                            class="w-full border rounded px-3 py-2 text-sm border-gray-300">
                            <option value="1">PRB</option>
                            <option value="2">Kronis</option>
                            <option value="3">Kemoterapi</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Iterasi</label>
                        <select v-model="form.iterasi" class="w-full border rounded px-3 py-2 text-sm border-gray-300">
                            <option value="0">Non Iterasi/Iterasi Lanjutan</option>
                            <option value="1">Iterasi 1</option>
                            <option value="2">Iterasi 2</option>
                        </select>
                    </div>
                </div>

                <!-- Detail Obat -->
                <ul v-if="resepDetil.length > 0" class="mt-2 space-y-1 max-h-48 overflow-auto pr-1">
                    <li v-for="(o, idx) in resepDetil" :key="o.ID"
                        class="text-sm flex items-center justify-between border-b last:border-0 py-1">
                        <div class="flex-1 min-w-0">
                            <div class="font-medium truncate">
                                {{ o.REFERENSI?.BARANG?.NAMA }}
                                <span v-if="o.RACIKAN == 1"
                                    class="px-1 rounded-md bg-red-100 text-xs ml-1 text-red-800">{{ o.REFERENSI?.JNSROBT
                                    }}</span>
                            </div>
                            <div class="text-xs text-gray-500 space-y-0.5">
                                <div>
                                    Kode:
                                    <span class="font-mono text-blue-500">{{ o.REFERENSI?.DPHO?.kodeobat }}</span>
                                    &middot; Obat:
                                    <span class="text-purple-500">{{ o.REFERENSI?.DPHO?.namaobat }}</span>
                                    &middot; Harga:
                                    <span class="text-teal-500">{{ o.REFERENSI?.DPHO?.harga }}</span>
                                </div>

                                <div>
                                    Jumlah:
                                    <span v-if="!editIndexMap[idx]?.jumlah" @dblclick="enableEdit(idx, 'jumlah')"
                                        class="text-cyan-500 cursor-pointer">
                                        {{ o.JUMLAH }}
                                    </span>
                                    <input v-else type="number" v-model="o.JUMLAH" @blur="disableEdit(idx, 'jumlah')"
                                        class="border px-1 py-0.5 text-xs w-16 rounded" />
                                    &middot; Hari:
                                    <span v-if="!editIndexMap[idx]?.hari" @dblclick="enableEdit(idx, 'hari')"
                                        class="text-rose-500 cursor-pointer">
                                        {{ o.HARI }}
                                    </span>
                                    <input v-else type="number" v-model="o.HARI" @blur="disableEdit(idx, 'hari')"
                                        class="border px-1 py-0.5 text-xs w-16 rounded" />
                                </div>

                                <div>
                                    Signa:
                                    <span v-if="!editIndexMap[idx]?.signa1" @dblclick="enableEdit(idx, 'signa1')"
                                        class="text-amber-500 cursor-pointer">
                                        {{ o.SIGNA1 || o.REFERENSI?.FREKUENSIATURAN?.SIGNA1 }}
                                    </span>
                                    <input v-else type="number" v-model="o.SIGNA1" @blur="disableEdit(idx, 'signa1')"
                                        class="border px-1 py-0.5 text-xs w-12 rounded" />
                                    x
                                    <span v-if="!editIndexMap[idx]?.signa2" @dblclick="enableEdit(idx, 'signa2')"
                                        class="text-amber-500 cursor-pointer">
                                        {{ o.SIGNA2 || o.REFERENSI?.FREKUENSIATURAN?.SIGNA2 }}
                                    </span>
                                    <input v-else type="number" v-model="o.SIGNA2" @blur="disableEdit(idx, 'signa2')"
                                        class="border px-1 py-0.5 text-xs w-12 rounded" />
                                    &middot; Frekuensi:
                                    <span class="text-indigo-500">{{ o.REFERENSI?.FREKUENSIATURAN?.FREKUENSI }}</span>
                                    &middot; Permintaan:
                                    <span class="text-orange-500">{{ o.PERMINTAAN }}</span>
                                </div>
                                <div>
                                    Status:
                                    <span :class="o.REFERENSI?.LOG?.STATUS == 0 ? 'text-rose-600' : 'text-green-600'">
                                        {{ o.REFERENSI?.LOG?.STATUS == undefined ? 'Belum kirim' :
                                        o.REFERENSI?.LOG?.RESPONSE }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </template>
        </div>

        <div class="flex items-center justify-end gap-2 px-4 py-4">
            <button @click="close" class="px-2 py-1 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                Batal
            </button>
            <button :disabled="isSubmitting" @click="submitResep"
                class="px-2 py-1 border rounded-md text-white bg-green-600 hover:bg-green-700 disabled:bg-green-400">
                <font-awesome-icon v-if="isSubmitting" icon="spinner" spin />
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
            tglrsp: dayjs(props.selectedItem.MASUK).format('YYYY-MM-DD') ?? '',
            tglpelrsp: dayjs(props.selectedItem.TGLPELRSP).format('YYYY-MM-DD') ?? '',
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
            emit('saved', {
                ...data,
                NOMOR: props.selectedItem.NOMOR ?? ''
            })
            close()
        }else {
            form.errors = { general: data.message || 'Gagal simpan resep' }
            showError.value = true

        }
    } catch (err) {
        if (err.response?.data?.errors) {
            form.errors = err.response.data.errors
        } else {
            form.errors = { general: err.response?.data?.message || 'Error saat simpan resep' }
            showError.value = true

        }
    } finally {
        isSubmitting.value = false
    }
}

const getPoliRsp = (item) => {
    const penjaminRuangan = item.REFERENSI?.ASAL_RESEP?.REFERENSI?.PENJAMIN_RUANGAN
    return Array.isArray(penjaminRuangan) ? penjaminRuangan[0]?.RUANGAN_PENJAMIN || '' : ''
}
const editIndexMap = reactive({})
const editTanggal = reactive({
    tglsjp: false,
    tglrsp: false,
    tglpelrsp: false
})

const enableEdit = (idx, field) => {
    if (!editIndexMap[idx]) editIndexMap[idx] = {}
    editIndexMap[idx][field] = true
}
const disableEdit = (idx, field) => {
    if (editIndexMap[idx]) editIndexMap[idx][field] = false
}

// Sync Jumlah dengan Hari
watch(resepDetil, (newVal) => {
    newVal.forEach((item, idx) => {
        if (editIndexMap[idx]?.hari) {
            item.JUMLAH = item.HARI
        }
    })
}, { deep: true })
</script>
