<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import dayjs from 'dayjs';
import Tooltip from '@/Components/Tooltip.vue';
import HapusResepSimgosModal from './Partials/HapusResepSimgosModal.vue';
import ErrorFlashSimgos from '@/Components/ErrorFlashSimgos.vue';
import SuccessFlashSimgos from '@/Components/SuccessFlashSimgos.vue';

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
    <div class="text-white text-sm px-4 py-2 bg-[#3282ba]">
        Daftar Resep
    </div>
    <div class="p-4 min-h-screen md:p-3">
        <div class="mx-auto max-w-8xl">
            <ErrorFlashSimgos :flash="{ error: errorMessage }" @clearFlash="errorMessage = ''" />
            <SuccessFlashSimgos :flash="{ success: successMessage }" @clearFlash="successMessage = ''" />
            <!-- Search Form -->
            <div class="p-1 mb-1 bg-white border border-gray-300">
                <form @submit.prevent="cariData">
                    <input type="hidden" v-model="searchForm.kdppk" />
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                        <div class="col-span-5">
                            <select id="JnsTgl" v-model="searchForm.JnsTgl"
                                class="px-3 py-1.5 text-sm w-full border border-gray-300 focus:outline-none focus:ring-1 focus:ring-cyan-600 focus:border-transparent">
                                <option value="TGLPELSJP">Berdasarkan Tanggal Pelayanan</option>
                                <option value="TGLRSP">Berdasarkan Tanggal Resep</option>
                            </select>
                        </div>

                        <div class="col-span-3">
                            <input id="TglMulai" v-model="searchForm.TglMulai" type="date"
                                class="px-3 py-1.5 text-sm w-full border border-gray-300 focus:outline-none focus:ring-1 focus:ring-cyan-600 focus:border-transparent"
                                required>
                        </div>

                        <div class="col-span-3">
                            <input id="TglAkhir" v-model="searchForm.TglAkhir" type="date"
                                class="px-3 py-1.5 text-sm w-full border border-gray-300 focus:outline-none focus:ring-1 focus:ring-cyan-600 focus:border-transparent"
                                required>
                        </div>

                        <div class="flex col-span-1">
                            <button type="submit" :disabled="isLoading"
                                class="flex justify-center items-center px-5 py-1 font-semibold text-white bg-[#3282ba] hover:bg-[#133248] transition duration-300 disabled:bg-cyan-600 w-full relative">
                                <div class="flex items-center justify-center space-x-1">
                                    <font-awesome-icon v-if="!isLoading" icon="search" />
                                    <font-awesome-icon v-if="isLoading" icon="spinner" spin />
                                    <span>{{ isLoading ? '' : 'Filter' }}</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Search Result -->
            <div class="mb-2">
                <div class="w-full sm:overflow-visible border border-teal-300">
                    <table class="min-w-full table-fixed bg-white overflow-x-auto">
                        <thead style="background-color: #19c5bf;">
                            <tr class="text-center">
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">No</th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">No. Resep
                                </th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">No. Apotek
                                </th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">No. SEP</th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">No. Kartu
                                </th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">Nama
                                </th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">Tgl. Entry
                                </th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">Tgl. Resep
                                </th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">Tagihan
                                </th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">Verifikasi</th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b">Iterasi
                                </th>
                                <th class="px-2 py-2 text-sm font-medium text-white border-b"></th>

                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="searchResult && searchResult.length > 0">
                                <tr v-for="(item, index) in searchResult" :key="index"
                                    class="hover:bg-gray-50 text-center">
                                    <td class="px-2 py-2 text-sm border-b">{{ index + 1 }}</td>
                                    <td class="px-2 py-2 text-sm border-b">{{ item.NORESEP }}
                                    </td>
                                    <td class="px-2 py-2 text-sm border-b">{{ item.NOAPOTIK || '-' }}
                                    </td>
                                    <td class="px-2 py-2 text-sm border-b">
                                        {{ item.NOSEP_KUNJUNGAN || '-' }}</td>
                                    <td class="px-2 py-2 text-sm border-b">{{ item.NOKARTU || '-' }}
                                    </td>
                                    <td class="px-2 py-2 text-sm border-b font-medium">{{ item.NAMA
                                        || '-' }}</td>
                                    <td class="px-2 py-2 text-sm border-b">{{ formatTanggal(item.TGLENTRY)
                                    }}</td>
                                    <td class="px-2 py-2 text-sm border-b">{{ formatTanggal(item.TGLRESEP)
                                    }}</td>
                                    <td class="px-2 py-2 text-sm text-yellow-600 border-b">
                                        {{ formatCurrency(item.BYTAGRSP) }}
                                    </td>
                                    <td class="px-2 py-2 text-sm text-green-700 border-b">
                                        {{ formatCurrency(item.BYVERRSP) }}
                                    </td>
                                    <td class="px-2 py-2 text-sm border-b text-center">
                                        <span class="text-lg inline-flex items-center justify-center"
                                            :class="item.FLAGITER === 'True' ? 'text-green-600' : 'text-red-600'">
                                            <font-awesome-icon :icon="item.FLAGITER === 'True' ? 'check' : 'times'" />
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 text-sm border-b">
                                        <Tooltip text="Detail Klaim" bgColor="bg-red-600">
                                            <button @click="openDelete(item)"
                                                class="px-2 py-1 text-sm font-medium text-red-600 transition duration-300 hover:bg-red-200 hover:text-green-600">
                                                <font-awesome-icon icon="list" />
                                            </button>
                                        </Tooltip>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="14" class="px-3 py-8">
                                    <div class="flex flex-col items-center">
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <HapusResepSimgosModal :show="showDeleteModal" :selected-item="selectedItem" @close="closeDelete"
        @deleted="handleResepDeleted" />
</template>