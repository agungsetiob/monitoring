<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import dayjs from 'dayjs';
import Tooltip from '@/Components/Tooltip.vue';
import SuccessFlash from '@/Components/SuccessFlash.vue'
import ErrorFlash from '@/Components/ErrorFlash.vue'
import HapusResepModal from './Partials/HapusResepModal.vue';

const isLoading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const page = usePage();

const searchForm = reactive({
    kdppk: '',
    KdJnsObat: '0',
    JnsTgl: 'TGLPELSJP',
    TglMulai: '',
    TglAkhir: ''
});

const showDeleteModal = ref(false);
const isDeleting = ref(false);
const selectedItem = ref(null);

const openDelete = (item) => {
    selectedItem.value = item;
    showDeleteModal.value = true;
};

const closeDelete = () => {
    showDeleteModal.value = false;
    isDeleting.value = false;
    selectedItem.value = null;
};

const handleResepDeleted = (nosjp) => {
    showMessage('Resep berhasil dihapus', 'success');
    // Hapus baris di tabel (berdasarkan nosjp)
    const nr = String(nosjp).toUpperCase();
    searchResult.value = searchResult.value.filter(
        r => String(r?.NOAPOTIK ?? r?.nosjp ?? '').toUpperCase() !== nr
    );

    closeDelete();
};

const searchResult = ref([]);

onMounted(async () => {
    if (!searchForm.kdppk) {
        searchForm.kdppk = page.props.defaultKdppk || '';
    }

    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    searchForm.TglMulai = dayjs(firstDay).format('YYYY-MM-DD');
    searchForm.TglAkhir = dayjs(lastDay).format('YYYY-MM-DD');
});

const showMessage = (message, type = 'error') => {
    if (type === 'error') {
        errorMessage.value = message;
        successMessage.value = '';
    } else {
        successMessage.value = message;
        errorMessage.value = '';
    }

    setTimeout(() => {
        errorMessage.value = '';
        successMessage.value = '';
    }, 5000);
};

const cariData = async () => {
    if (!searchForm.kdppk || !searchForm.TglMulai || !searchForm.TglAkhir) {
        showMessage('Kode PPK, tanggal mulai, dan tanggal akhir harus diisi');
        return;
    }

    isLoading.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    searchResult.value = [];

    try {
        const response = await axios.post('/apol/daftar-resep', searchForm);

        if (response.data.success) {
            searchResult.value = response.data.data || [];
            if (searchResult.value.length > 0) {
                showMessage(`Berhasil menemukan ${searchResult.value.length} data resep`, 'success');
            } else {
                showMessage('Tidak ada data resep ditemukan untuk kriteria yang diberikan');
            }
        } else {
            searchResult.value = [];
            showMessage(response.data.message);
        }
    } catch (error) {
        showMessage(error.response?.data?.message || 'Terjadi kesalahan saat mencari data');
        searchResult.value = [];
    } finally {
        isLoading.value = false;
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR'
    }).format(value || 0);
};

const formatTanggal = (tanggal) => {
    if (!tanggal) return '-';
    return dayjs(tanggal).format('DD-MM-YYYY');
};
</script>

<template>

    <Head title="Daftar Resep" />
    <div class="p-4 min-h-screen bg-gradient-to-br from-blue-100 to-green-100 md:p-6">
        <div class="mx-auto max-w-8xl">
            <ErrorFlash :flash="{ error: errorMessage }" @clearFlash="errorMessage = ''" />
            <SuccessFlash :flash="{ success: successMessage }" @clearFlash="successMessage = ''" />
            <!-- Search Form -->
            <div class="p-6 mb-6 bg-white rounded-xl shadow-lg">
                <h2 class="mb-4 text-xl font-bold text-gray-800">Filter Daftar Resep</h2>

                <form @submit.prevent="cariData" class="space-y-4">
                    <input type="hidden" v-model="searchForm.kdppk" />
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label for="JnsTgl" class="block mb-2 text-sm font-medium text-gray-700">
                                Jenis Tanggal
                            </label>
                            <select id="JnsTgl" v-model="searchForm.JnsTgl"
                                class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="TGLPELSJP">Tanggal Pelayanan SJP</option>
                                <option value="TGLRSP">Tanggal Resep</option>
                            </select>
                        </div>

                        <div>
                            <label for="TglMulai" class="block mb-2 text-sm font-medium text-gray-700">
                                Tanggal Mulai <span class="text-red-600">*</span>
                            </label>
                            <input id="TglMulai" v-model="searchForm.TglMulai" type="date"
                                class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                required>
                        </div>

                        <div>
                            <label for="TglAkhir" class="block mb-2 text-sm font-medium text-gray-700">
                                Tanggal Akhir <span class="text-red-600">*</span>
                            </label>
                            <input id="TglAkhir" v-model="searchForm.TglAkhir" type="date"
                                class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                required>
                        </div>

                        <div class="flex items-end">
                            <button type="submit" :disabled="isLoading"
                                class="flex gap-2 items-center px-6 py-2 font-semibold text-white bg-green-600 rounded-lg transition duration-300 hover:bg-green-700 disabled:bg-green-400">
                                <font-awesome-icon v-if="isLoading" icon="spinner" spin />
                                {{ isLoading ? 'Mencari...' : 'Cari Data' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Search Result -->
            <div class="p-6 mb-6 bg-white rounded-xl shadow-lg">
                <h3 class="mb-4 text-lg font-bold text-gray-800">
                    Daftar Klaim Resep (<span class="text-green-500">{{ searchResult.length }} data</span>)
                </h3>

                <div class="w-full overflow-x-auto sm:overflow-visible">
                    <table class="min-w-full table-fixed bg-white rounded-lg border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr class="text-center">
                                <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">No</th>
                                <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">No. Resep
                                </th>
                                <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">No. SJP
                                </th>
                                <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">No. SEP
                                    Kunjungan</th>
                                <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">No. Kartu
                                </th>
                                <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">Nama Peserta
                                </th>
                                <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">Tgl. Entry
                                </th>
                                <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">Tgl. Resep
                                </th>
                                <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">Biaya Tagih
                                </th>
                                <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">Biaya
                                    Verifikasi</th>
                                <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">Iterasi
                                </th>
                                <th class="px-2 py-2 text-sm font-medium text-gray-700 border-b">Aksi</th>

                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="searchResult && searchResult.length > 0">
                                <tr v-for="(item, index) in searchResult" :key="index"
                                    class="hover:bg-gray-50 text-center">
                                    <td class="px-2 py-2 text-sm text-gray-900 border-b">{{ index + 1 }}</td>
                                    <td class="px-2 py-2 text-sm text-gray-900 border-b font-medium">{{ item.NORESEP }}
                                    </td>
                                    <td class="px-2 py-2 text-sm text-gray-900 border-b">{{ item.NOAPOTIK || '-' }}
                                    </td>
                                    <td class="px-2 py-2 text-sm text-gray-900 border-b font-medium text-blue-600">
                                        {{ item.NOSEP_KUNJUNGAN || '-' }}</td>
                                    <td class="px-2 py-2 text-sm text-gray-900 border-b">{{ item.NOKARTU || '-' }}
                                    </td>
                                    <td class="px-2 py-2 text-sm text-gray-900 border-b font-medium">{{ item.NAMA
                                        ||'-'}}</td>
                                    <td class="px-2 py-2 text-sm text-gray-900 border-b">{{ formatTanggal(item.TGLENTRY)
                                        }}</td>
                                    <td class="px-2 py-2 text-sm text-gray-900 border-b">{{ formatTanggal(item.TGLRESEP)
                                        }}</td>
                                    <td class="px-2 py-2 text-sm text-gray-900 border-b font-medium">
                                        {{ formatCurrency(item.BYTAGRSP) }}
                                    </td>
                                    <td class="px-2 py-2 text-sm text-gray-900 border-b">
                                        {{ formatCurrency(item.BYVERRSP) }}
                                    </td>
                                    <td class="px-2 py-2 text-sm text-gray-900 border-b">
                                        <span class="px-2 py-1 text-xs rounded-full"
                                            :class="item.FLAGITER === 'True' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800'">
                                            {{ item.FLAGITER === 'True' ? 'Ya' : 'Tidak' }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 text-sm text-gray-900 border-b">
                                        <Tooltip text="Hapus Klaim" bgColor="bg-red-600">
                                            <button @click="openDelete(item)"
                                                class="px-2 py-1 text-xs font-medium text-red-600 rounded transition duration-300 hover:bg-red-200 hover:text-green-600">
                                                <font-awesome-icon icon="trash" />
                                            </button>
                                        </Tooltip>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="14" class="px-3 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 mb-2 text-gray-300" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                        <p class="text-lg font-medium">Tidak ada data resep</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <HapusResepModal :show="showDeleteModal" :selected-item="selectedItem" @close="closeDelete"
        @deleted="handleResepDeleted" />
</template>