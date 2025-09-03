<template>
    <Modal :show="show" max-width="4xl" @close="close">
        <div class="flex items-center justify-between px-5 py-4 border-b">
            <h3 class="text-lg font-semibold">Buat Resep Klaim Obat Kronis</h3>
        </div>

        <div class="px-5 py-4 space-y-4">
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
            </template>
        </div>

        <div class="flex items-center justify-end gap-3 px-4 py-4">
            <button @click="close" class="px-2 py-1 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                Batal
            </button>
            <button :disabled="isSubmitting" @click="submitResep"
                class="px-2 py-1 border border-green-600 rounded-md text-white bg-green-600 hover:bg-green-700 disabled:bg-green-400">
                {{ isSubmitting ? 'Memproses...' : 'Simpan Resep' }}
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
    iterasi: '0'
})

watch(() => props.show, async (isOpen) => {
    if (isOpen && props.selectedItem) {
        isLoading.value = true

        Object.assign(form, {
            noresep: props.selectedItem.REFERENSI?.NOMORRESEP?.NOMOR ?? '',
            idusersjp: props.selectedItem.IDUSERSJP ?? '',
            polirsp: getPoliRsp(props.selectedItem) ?? '',
            refasalsjp: props.selectedItem.REFASALSJP ?? '',
            tglsjp : dayjs().format('YYYY-MM-DD HH:mm:ss'),
            tglrsp: props.selectedItem.MASUK ?? '',
            tglpelrsp: props.selectedItem.TGLPELRSP ?? '',
            kddokter: props.selectedItem.REFERENSI?.DPJP_PENJAMIN_RS?.DPJP_PENJAMIN ?? '',
            kdjnsobat: '1',
            iterasi: '0'
        })

        isLoading.value = false
    }
})

const close = () => emit('close')

const submitResep = async () => {
    isSubmitting.value = true
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
            iterasi: form.iterasi
        }

        const { data } = await axios.post('/apol/simpan-resep', payload)
        
        if (data.success) {
            emit('saved', data)
            close()
        } else {
            alert(data.message || 'Gagal simpan resep')
            console.error('Simpan resep gagal:', data)
        }
    } catch (err) {
        const errorMsg = err.response?.data?.message || 'Error saat simpan resep'
        alert(errorMsg)
        console.error('Simpan resep error:', err.response?.data || err)
    } finally {
        isSubmitting.value = false
    }
}
const getPoliRsp = (item) => {
    const penjaminRuangan = item.REFERENSI?.ASAL_RESEP?.REFERENSI?.PENJAMIN_RUANGAN;
    return Array.isArray(penjaminRuangan) ? penjaminRuangan[0]?.RUANGAN_PENJAMIN || '' : '';
};
</script>
