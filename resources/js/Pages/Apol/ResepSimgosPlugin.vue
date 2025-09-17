<template>
    <div class="p-4 min-h-screen md:p-3 bg-white">
        <div class="mx-auto max-w-8xl">
            <ErrorFlash :flash="{ error: errorMessage }" @clearFlash="errorMessage = ''" />
            <SuccessFlash :flash="{ success: successMessage }" @clearFlash="successMessage = ''" />
            <!-- Filter Form -->
            <div class="p-1 mb-1 bg-white border border-gray-300">
                <form @submit.prevent="cariData">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                        <div class="col-span-3">
                            <input id="PAWAL" v-model="searchForm.PAWAL" type="date"
                                class="px-3 py-1.5 text-sm w-full border border-gray-300 focus:outline-none focus:ring-1 focus:ring-cyan-600 focus:border-transparent"
                                required />
                        </div>

                        <div class="col-span-3">
                            <input id="PAKHIR" v-model="searchForm.PAKHIR" type="date"
                                class="px-3 py-1.5 text-sm w-full border border-gray-300 focus:outline-none focus:ring-1 focus:ring-cyan-600 focus:border-transparent"
                                required />
                        </div>

                        <div class="col-span-3">
                            <select id="JENIS_RESEP" v-model="searchForm.JENIS_RESEP"
                                class="px-3 py-1.5 text-sm w-full border border-gray-300 focus:outline-none focus:ring-1 focus:ring-cyan-600 focus:border-transparent">
                                <option value="">Semua</option>
                                <option value="1">Kronis</option>
                                <option value="2">Kemoterapi</option>
                            </select>
                        </div>

                        <div class="col-span-2">
                            <input id="NORM" v-model="searchForm.NORM" type="text" placeholder="No RM"
                                class="px-3 py-1.5 text-sm w-full border border-gray-300 focus:outline-none focus:ring-1 focus:ring-cyan-600 focus:border-transparent" />
                        </div>

                        <div class="flex col-span-1">
                            <button type="submit" :disabled="isLoading"
                                class="flex items-center px-5 py-1 font-semibold text-white bg-cyan-700 transition duration-300 hover:bg-cyan-900 disabled:bg-cyan-600 w-full">
                                <font-awesome-icon v-if="!isLoading" icon="search" class="mr-2" />
                                <font-awesome-icon v-if="isLoading" icon="spinner" spin class="px-5" />
                                {{ isLoading ? '' : 'Filter' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Hasil -->
            <div class="mb-2">
                <div class="w-full border border-teal-300 overflow-hidden">
                    <table class="min-w-full table-auto bg-white">
                        <thead class="bg-teal-500">
                            <tr class="text-center">
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">No</th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">Nomor</th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">Masuk</th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">Keluar</th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">Referensi SJP</th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">Norm</th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">Nama</th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">No Kartu</th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">Dokter</th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">Asal Resep</th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">Tgl Resep</th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">Jns Resep</th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="resepList.length > 0">
                                <tr v-for="(item, index) in resepList" :key="index" class="text-center hover:bg-gray-50"
                                    :class="{
                                        'bg-teal-100 hover:bg-teal-200': item.STATUSKLAIM == 1,
                                        'bg-green-100 hover:bg-green-200': submittedKunjungan.includes(String(item.NOMOR))
                                    }" :data-idusersjp="item.IDUSERSJP"
                                    :data-kddokter="item.REFERENSI?.DPJP_PENJAMIN_RS?.DPJP_PENJAMIN"
                                    :data-noresep="item.REFERENSI?.NOMORRESEP?.NOMOR">
                                    <td class="px-2 py-2 text-sm border-b">{{ index + 1 }}</td>
                                    <td :class="[
                                        'px-2 py-2 text-sm border-b',
                                        getJenisKunjungan(item) == 3 ? 'font-semibold text-rose-600' : ''
                                    ]">
                                        {{ item.NOMOR }}
                                    </td>
                                    <td class="px-2 py-2 text-sm border-b">{{ formatDateTime(item.MASUK) }}</td>
                                    <td class="px-2 py-2 text-sm border-b">{{ formatDateTime(item.KELUAR) }}</td>
                                    <td class="px-2 py-2 text-sm border-b">{{ item.REFASALSJP || '-' }}</td>
                                    <td class="px-2 py-2 text-sm border-b">{{ item.NORM || '-' }}</td>
                                    <td class="px-2 py-2 text-sm border-b">{{ item.NAMA || '-' }}</td>
                                    <td class="px-2 py-2 text-sm border-b">{{ item.NOKARTU || '-' }}</td>
                                    <td class="px-2 py-2 text-sm border-b">{{ getDokterNama(item) || '-' }}</td>
                                    <td class="px-2 py-2 text-sm border-b">
                                        {{ getAsalResep(item) }}<span v-if="getPoliRsp(item)"> ({{ getPoliRsp(item)
                                            }})</span>
                                    </td>
                                    <td class="px-2 py-2 text-sm border-b">{{ formatDateTime(item.TGLPELRSP) }}</td>
                                    <td class="px-2 py-2 text-sm border-b">{{ getJenisResepText(item.JENISRESEP) }}</td>
                                    <td class="px-2 py-2 text-sm border-b">
                                        <Tooltip text="Detail" bgColor="bg-green-600">
                                            <button @click="openModalSimpan(item)"
                                                class="px-2 py-1 text-sm font-medium text-red-600 transition duration-300 hover:bg-red-200 hover:text-green-600 rounded">
                                                <font-awesome-icon icon="notes-medical" />
                                            </button>
                                        </Tooltip>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="12" class="px-3 py-8 text-center text-gray-500"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <CreateResepSimgosModal :show="showModalSimpan" :selected-item="selectedResep" @close="showModalSimpan = false"
        @saved="handleResepSaved" />
</template>

<script setup>
import { ref, reactive, onMounted } from "vue";
import dayjs from "dayjs";
import ErrorFlash from "@/Components/ErrorFlash.vue";
import SuccessFlash from "@/Components/SuccessFlash.vue";
import CreateResepSimgosModal from "./Partials/CreateResepSimgosModal.vue";
import Tooltip from "@/Components/Tooltip.vue";

const isLoading = ref(false);
const errorMessage = ref("");
const successMessage = ref("");
const resepList = ref([]);
const selectedResep = ref(null)
const showModalSimpan = ref(false)
const submittedKunjungan = ref([])

const openModalSimpan = (item) => {
    selectedResep.value = item
    showModalSimpan.value = true
}

const handleResepSaved = (data) => {
    const message = data?.metaData?.message || 'Resep berhasil disimpan'
    showMessage(message, 'success')
    showModalSimpan.value = false

    const nomorStr = String(data.NOMOR)
    if (!submittedKunjungan.value.includes(nomorStr)) {
        submittedKunjungan.value.push(nomorStr)
    }
}

const searchForm = reactive({
    PAWAL: "",
    PAKHIR: "",
    page: 1,
    start: 0,
    limit: 25,
    JENIS_RESEP: "",
    NORM: ""
});

onMounted(() => {
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);

    searchForm.PAWAL = dayjs(firstDay).format("YYYY-MM-DD");
    searchForm.PAKHIR = dayjs(lastDay).format("YYYY-MM-DD");
});

const showMessage = (msg, type = "error") => {
    if (type === "error") {
        errorMessage.value = msg;
        successMessage.value = "";
    } else {
        successMessage.value = msg;
        errorMessage.value = "";
    }
};

const cariData = async () => {
    if (!searchForm.PAWAL || !searchForm.PAKHIR) {
        showMessage("Periode awal dan akhir wajib diisi");
        return;
    }

    isLoading.value = true;
    resepList.value = [];

    try {
        const response = await axios.get("/resep-simgos", { params: searchForm });

        if (response.data && typeof response.data === "object") {
            const { data, total, detail, status } = response.data;

            if (Array.isArray(data) && data.length > 0) {
                resepList.value = data;
                showMessage(`Berhasil menemukan ${data.length} data`, "success");
            } else if (data === null || total === 0) {
                resepList.value = [];
                showMessage(status + detail);
            }
        } else {
            showMessage("Format response tidak sesuai");
        }
    } catch (err) {
        showMessage(err.response?.data?.message || "Terjadi kesalahan saat mengambil data");
    } finally {
        isLoading.value = false;
    }
};

const getDokterNama = (item) => {
    return item.REFERENSI?.DPJP_PENJAMIN_RS?.REFERENSI?.DOKTER?.NAMA || '-';
};

const getAsalResep = (item) => {
    return item.REFERENSI?.ASAL_RESEP?.DESKRIPSI || '-';
};

const getJenisKunjungan = (item) => {
    return item.REFERENSI?.ASAL_RESEP?.JENIS_KUNJUNGAN || '-';
};

const getPoliRsp = (item) => {
    const penjaminRuangan = item.REFERENSI?.ASAL_RESEP?.REFERENSI?.PENJAMIN_RUANGAN;
    return Array.isArray(penjaminRuangan) ? penjaminRuangan[0]?.RUANGAN_PENJAMIN || '' : '';
};

const getJenisResepText = (jenis) => {
    if (jenis === "1") return "PRB";
    if (jenis === "2") return "Kronis";
    if (jenis === "3") return "Kemoterapi";
    return "-";
};

const formatDateTime = (datetime) => {
    if (!datetime) return '-';
    return dayjs(datetime).format("DD-MM-YYYY");
};
</script>