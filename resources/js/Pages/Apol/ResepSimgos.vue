<template>

    <Head title="Resep SIMGOS" />
    <ApolLayout>
        <div class="p-4 min-h-screen bg-gradient-to-br from-blue-100 to-green-100 md:p-6">
            <div class="mx-auto max-w-8xl pt-6 sm:pt-20">
                <div
                    class="flex flex-col px-6 py-4 mb-6 text-white bg-gradient-to-r from-indigo-500 to-blue-600 rounded-xl shadow-2xl md:flex-row md:items-center md:justify-between">
                    <h1 class="mb-2 text-2xl font-extrabold md:text-3xl md:mb-0">
                        Resep Klaim Terpisah (SIMGOS)
                    </h1>
                </div>

                <ErrorFlash :flash="{ error: errorMessage }" @clearFlash="errorMessage = ''" />
                <SuccessFlash :flash="{ success: successMessage }" @clearFlash="successMessage = ''" />

                <!-- Filter Form -->
                <div class="p-6 mb-6 bg-white rounded-xl shadow-lg">
                    <h2 class="mb-4 text-xl font-bold text-gray-800">Filter Resep</h2>

                    <form @submit.prevent="cariData" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                            <div>
                                <label for="PAWAL" class="block mb-1 text-sm font-medium text-gray-700">Periode
                                    Awal</label>
                                <input id="PAWAL" v-model="searchForm.PAWAL" type="date"
                                    class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    required />
                            </div>

                            <div>
                                <label for="PAKHIR" class="block mb-1 text-sm font-medium text-gray-700">Periode
                                    Akhir</label>
                                <input id="PAKHIR" v-model="searchForm.PAKHIR" type="date"
                                    class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    required />
                            </div>

                            <div>
                                <label for="JENIS_RESEP" class="block mb-1 text-sm font-medium text-gray-700">Jenis
                                    Resep</label>
                                <select id="JENIS_RESEP" v-model="searchForm.JENIS_RESEP"
                                    class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Semua</option>
                                    <option value="1">Kronis</option>
                                    <option value="2">Kemoterapi</option>
                                </select>
                            </div>

                            <div>
                                <label for="NORM" class="block mb-1 text-sm font-medium text-gray-700">No RM</label>
                                <input id="NORM" v-model="searchForm.NORM" type="text" placeholder="Masukkan No RM"
                                    class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                            </div>

                            <div class="flex gap-2">
                                <button type="submit" :disabled="isLoading"
                                    class="px-4 py-2 font-semibold text-white bg-green-600 rounded-lg transition duration-300 hover:bg-green-700 disabled:bg-green-400 w-full">
                                    <font-awesome-icon v-if="isLoading" icon="spinner" spin />
                                    {{ isLoading ? '' : 'Cari Data' }}
                                </button>

                                <button type="button" @click="resetForm"
                                    class="px-4 py-2 font-semibold text-white bg-gray-500 rounded-lg transition duration-300 hover:bg-gray-600 w-full">
                                    Reset
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Hasil -->
                <div class="p-6 bg-white rounded-xl shadow-lg">
                    <h3 class="mb-4 text-lg font-bold text-gray-800">
                        Hasil Resep (<span class="text-green-500">{{ resepList.length }} data</span>)
                    </h3>

                    <div class="w-full overflow-x-auto sm:overflow-visible">
                        <table class="min-w-full table-fixed bg-white rounded-lg border border-gray-200">
                            <thead class="bg-gray-100">
                                <tr class="text-center">
                                    <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">No</th>
                                    <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">Nomor</th>
                                    <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">Masuk</th>
                                    <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">Keluar</th>
                                    <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">Referensi SJP</th>
                                    <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">Norm</th>
                                    <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">Nama</th>
                                    <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">No Kartu</th>
                                    <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">Dokter</th>
                                    <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">Asal Resep</th>
                                    <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">Tgl Resep</th>
                                    <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">Jns Resep</th>
                                    <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="resepList.length > 0">
                                    <tr v-for="(item, index) in resepList" :key="index"
                                        class="text-center hover:bg-gray-50" :class="{
                                            'text-green-700 hover:bg-teal-100': item.STATUSKLAIM == 1,
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
                                        <td class="px-2 py-2 text-sm border-b">{{ getJenisResepText(item.JENISRESEP) }}
                                        </td>
                                        <td class="px-2 py-2 text-sm border-b">
                                            <Tooltip text="Kirim Resep ke BPJS" bgColor="bg-green-600">
                                                <button @click="openModalSimpan(item)"
                                                    class="text-lg rounded hover:text-green-600 text-rose-700">
                                                    <font-awesome-icon icon="notes-medical" />
                                                </button>
                                            </Tooltip>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="12" class="px-3 py-8 text-center text-gray-500">
                                        Tidak ada data resep
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </ApolLayout>
    <CreateResepModal :show="showModalSimpan" :selected-item="selectedResep" @close="showModalSimpan = false"
        @saved="handleResepSaved" />
</template>

<script setup>
import { Head } from "@inertiajs/vue3";
import { ref, reactive, onMounted } from "vue";
import dayjs from "dayjs";
import ErrorFlash from "@/Components/ErrorFlash.vue";
import SuccessFlash from "@/Components/SuccessFlash.vue";
import ApolLayout from "../Layout/ApolLayout.vue";
import CreateResepModal from "./Partials/CreateResepModal.vue";
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

const resetForm = () => {
    searchForm.PAWAL = "";
    searchForm.PAKHIR = "";
    resepList.value = [];
    errorMessage.value = "";
    successMessage.value = "";
};

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
const getJenisKunjungan = (item) => {
    return item.REFERENSI?.ASAL_RESEP?.JENIS_KUNJUNGAN || '-';
};

</script>