<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
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

// --- Hapus Resep state ---
const showDeleteModal = ref(false);
const isDeleting = ref(false);
const selectedItem = ref(null);
const deleteForm = reactive({
    nosjp: '',       // <- ini dipakai sebagai no SEP Apotek/nosepapotek
    refasalsjp: '',
    noresep: ''
});

// --- Obat list & purge-first toggle ---
const isLoadingObatList = ref(false);
const obatList = ref([]); // [{kodeobat, namaobat, tipeobat, ...}]
const purgeObatFirst = ref(true); // default: hapus semua obat dulu
const deleteStats = reactive({
    total: 0,
    done: 0,
    failed: 0,
    failures: [] // [{kodeobat, message}]
});

const openDelete = (item) => {
    selectedItem.value = item;
    showDeleteModal.value = true;
};

// Replace the closeDelete function with:
const closeDelete = () => {
    showDeleteModal.value = false;
    isDeleting.value = false;
    selectedItem.value = null;
};

// Add this new function to handle successful deletion:
const handleResepDeleted = (noresep) => {
    showMessage('Resep berhasil dihapus', 'success');

    // Hapus baris di tabel secara lokal (berdasarkan NORESEP)
    const nr = String(noresep).toUpperCase();
    searchResult.value = searchResult.value.filter(
        r => String(r?.NORESEP ?? r?.noresep ?? '').toUpperCase() !== nr
    );

    closeDelete();
};

const resetDeleteProgress = () => {
    deleteStats.total = 0;
    deleteStats.done = 0;
    deleteStats.failed = 0;
    deleteStats.failures = [];
};

const deleteDisabled = computed(
    () => !deleteForm.nosjp || !deleteForm.refasalsjp || !deleteForm.noresep
);

// --- Helpers ---
const normalizeListObat = (list) => {
    if (!list) return [];
    if (Array.isArray(list)) return list;
    if (typeof list === 'object' && list.kodeobat) return [list];
    return [];
};

const loadObatList = async (nosepApotek) => {
    obatList.value = [];
    if (!nosepApotek) return;

    isLoadingObatList.value = true;
    try {
        // prefer RESTful: /apol/pelayanan/obat/daftar/{nosep}
        let res;
        try {
            res = await axios.get(`/apol/pelayanan/obat/daftar/${encodeURIComponent(nosepApotek)}`);
        } catch {
            // fallback query param: /apol/pelayanan/obat/daftar?nosep=...
            res = await axios.get('/apol/pelayanan/obat/daftar', { params: { nosep: nosepApotek } });
        }

        const payload = res?.data || {};

        // Cari listobat di berbagai bentuk payload
        let list = [];
        const candidates = [
            payload?.listobat,
            payload?.detailsep?.listobat,
            payload?.data?.listobat,
            payload?.data?.detailsep?.listobat,
            payload?.response?.listobat,
            payload?.response?.detailsep?.listobat,
        ];
        for (const cand of candidates) {
            const norm = normalizeListObat(cand);
            if (norm.length) { list = norm; break; }
        }

        obatList.value = list.map(o => ({
            kodeobat: String(o.kodeobat ?? o.kdobat ?? '').trim(),
            namaobat: o.namaobat ?? o.nmobat ?? '-',
            tipeobat: (o.tipeobat ?? o.tipeObat ?? 'N').toString().toUpperCase(),
            signa1: o.signa1 ?? o.signa ?? null,
            signa2: o.signa2 ?? null,
            jumlah: o.jumlah ?? o.qty ?? null,
            hari: o.hari ?? null,
            harga: o.harga ?? null,
        }));
    } catch (e) {
        showMessage(e?.response?.data?.message || 'Gagal memuat daftar obat');
    } finally {
        isLoadingObatList.value = false;
    }
};


// Hapus semua obat dulu (progress)
const deleteAllObatBeforeResep = async () => {
    resetDeleteProgress();
    deleteStats.total = obatList.value.length;
    if (deleteStats.total === 0) return;

    for (const ob of obatList.value) {
        try {
            await axios.post('/apol/hapus-obat', {
                nosepapotek: String(deleteForm.nosjp).trim(),     // penting: nama field backend
                noresep: String(deleteForm.noresep).trim(),
                kodeobat: String(ob.kodeobat).trim(),
                tipeobat: ob.tipeobat || 'N',
                verify: true // minta controller verifikasi before/after
            });
            deleteStats.done += 1;
        } catch (err) {
            deleteStats.failed += 1;
            deleteStats.failures.push({
                kodeobat: ob.kodeobat,
                message: err?.response?.data?.message || err.message || 'Gagal hapus obat'
            });
        }
    }

    // refresh list untuk bukti final
    await loadObatList(deleteForm.nosjp);
};

const submitDelete = async () => {
    if (deleteDisabled.value) {
        showMessage('Lengkapi No SJP/SEP Apotek, Ref Asal SJP, dan No Resep terlebih dahulu');
        return;
    }

    isDeleting.value = true;
    try {
        // 1) Opsional: hapus semua obat dulu
        if (purgeObatFirst.value) {
            await deleteAllObatBeforeResep();

            // kalau masih ada obat sisa, jangan lanjut hapus resep
            if ((obatList.value?.length ?? 0) > 0) {
                showMessage(
                    `Masih ada ${obatList.value.length} obat di resep ini. Resep belum dihapus.`,
                );
                return;
            }
        }

        // 2) Hapus resep
        const { data } = await axios.post('/apol/hapus-resep', {
            nosjp: String(deleteForm.nosjp).trim(),
            refasalsjp: String(deleteForm.refasalsjp).trim(),
            noresep: String(deleteForm.noresep).trim()
        });

        if (data.success) {
            showMessage('Resep berhasil dihapus', 'success');

            // Hapus baris di tabel secara lokal (berdasarkan NORESEP)
            const nr = String(deleteForm.noresep).toUpperCase();
            searchResult.value = searchResult.value.filter(
                r => String(r?.NORESEP ?? r?.noresep ?? '').toUpperCase() !== nr
            );

            closeDelete();
        } else {
            // bisa jadi backend mengembalikan 409 jika obat belum terhapus
            showMessage(data.message || 'Gagal menghapus resep');
            // refresh obat untuk bukti
            await loadObatList(deleteForm.nosjp);
        }
    } catch (e) {
        showMessage(e?.response?.data?.message || e.message || 'Terjadi kesalahan saat menghapus resep');
    } finally {
        isDeleting.value = false;
    }
};

const resetForm = () => {
    searchForm.kdppk = page.props.defaultKdppk || '';
    searchForm.KdJnsObat = '0';
    searchForm.JnsTgl = 'TGLPELSJP';
    searchForm.TglMulai = '';
    searchForm.TglAkhir = '';
    errorMessage.value = '';
    successMessage.value = '';
    searchResult.value = [];
};

const searchResult = ref([]);

// Set default dates (current month)
onMounted(async () => {
    // set kdppk dari props (ENV APOTEK_PPK)
    if (!searchForm.kdppk) {
        searchForm.kdppk = page.props.defaultKdppk || '';
    }

    // set tanggal default (format untuk input datetime-local)
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    searchForm.TglMulai = dayjs(firstDay).format('YYYY-MM-DDTHH:mm');
    searchForm.TglAkhir = dayjs(lastDay).format('YYYY-MM-DDTHH:mm');

    // auto panggil cariData kalau form lengkap
    if (searchForm.kdppk && searchForm.TglMulai && searchForm.TglAkhir) {
        await cariData();
    }
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
        const response = await axios.post('/apol/ajax/daftar-resep', searchForm);

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

const getSummary = async () => {
    if (!searchForm.kdppk || !searchForm.TglMulai || !searchForm.TglAkhir) {
        showMessage('Lengkapi form pencarian terlebih dahulu');
        return;
    }

    try {
        const response = await axios.post('/apol/summary-resep', searchForm);

        if (response.data.success) {
            const summary = response.data.summary;
            showMessage(`Summary: Total ${summary.total_resep} resep ditemukan`, 'success');
        }
    } catch (error) {
        showMessage(error.response?.data?.message || 'Terjadi kesalahan saat mengambil summary');
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

const getJenisObatText = (kode) => {
    switch (String(kode)) {
        case '0': return 'Semua';
        case '1': return 'Obat PRB';
        case '2': return 'Obat Kronis Belum Stabil';
        case '3': return 'Obat Kemoterapi';
        default: return kode ?? '-';
    }
};

const getJenisObatClass = (kode) => {
    switch (String(kode)) {
        case '1': return 'bg-green-100 text-green-800';   // PRB
        case '2': return 'bg-amber-100 text-amber-800';   // Kronis blm stabil
        case '3': return 'bg-purple-100 text-purple-800'; // Kemoterapi
        case '0': return 'bg-gray-100 text-gray-800';     // Semua
        default: return 'bg-gray-100 text-gray-800';
    }
};
</script>


<template>

    <Head title="APOL - Daftar Resep" />

    <div class="p-4 min-h-screen bg-gradient-to-br from-blue-100 to-green-100 md:p-6">
        <div class="mx-auto max-w-8xl">
            <!-- Header -->
            <div
                class="flex flex-col px-6 py-4 mb-6 text-white bg-gradient-to-r from-teal-500 to-blue-500 rounded-xl shadow-2xl md:flex-row md:items-center md:justify-between">
                <h1 class="mb-2 text-2xl font-extrabold md:text-3xl md:mb-0">
                    APOL - Daftar Resep
                </h1>

                <button @click="router.visit('/')"
                    class="inline-flex gap-2 items-center px-5 py-2 font-semibold text-white bg-indigo-600 rounded-xl shadow-lg transition duration-300 ease-in-out transform hover:bg-indigo-700 hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5"></path>
                        <path d="M12 19l-7-7 7-7"></path>
                    </svg>
                    Kembali
                </button>
            </div>
            <!-- Error Toast Alert -->
            <ErrorFlash :flash="{ error: errorMessage }" @clearFlash="errorMessage = ''" />
            <!-- Success Toast Alert -->
            <SuccessFlash :flash="{ success: successMessage }" @clearFlash="successMessage = ''" />
            <!-- Search Form -->
            <div class="p-6 mb-6 bg-white rounded-xl shadow-lg">
                <h2 class="mb-4 text-xl font-bold text-gray-800">Filter Daftar Resep</h2>

                <form @submit.prevent="cariData" class="space-y-4">
                    <input type="hidden" v-model="searchForm.kdppk" />
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">


                        <div>
                            <label for="KdJnsObat" class="block mb-2 text-sm font-medium text-gray-700">
                                Kode Jenis Obat
                            </label>
                            <select id="KdJnsObat" v-model="searchForm.KdJnsObat"
                                class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="0">Semua</option>
                                <option value="1">Obat PRB</option>
                                <option value="2">Obat Kronis Belum Stabil</option>
                                <option value="3">Obat Kemoterapi</option>
                            </select>
                        </div>
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
                                Tanggal Mulai *
                            </label>
                            <input id="TglMulai" v-model="searchForm.TglMulai" type="datetime-local"
                                class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                required>
                        </div>

                        <div>
                            <label for="TglAkhir" class="block mb-2 text-sm font-medium text-gray-700">
                                Tanggal Akhir *
                            </label>
                            <input id="TglAkhir" v-model="searchForm.TglAkhir" type="datetime-local"
                                class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                required>
                        </div>
                    </div>

                    <div class="flex gap-3 flex-wrap">
                        <button type="submit" :disabled="isLoading"
                            class="flex gap-2 items-center px-6 py-2 font-semibold text-white bg-green-600 rounded-lg transition duration-300 hover:bg-green-700 disabled:bg-green-400">
                            <svg v-if="isLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            {{ isLoading ? 'Mencari...' : 'Cari Data' }}
                        </button>

                        <button type="button" @click="resetForm"
                            class="px-6 py-2 font-semibold text-white bg-gray-500 rounded-lg transition duration-300 hover:bg-gray-600">
                            Reset
                        </button>

                        <button type="button" @click="getSummary" :disabled="isLoading"
                            class="px-6 py-2 font-semibold text-white bg-blue-500 rounded-lg transition duration-300 hover:bg-blue-600 disabled:bg-blue-400">
                            Summary
                        </button>
                    </div>
                </form>
            </div>

            <!-- Search Result -->
            <div class="p-6 mb-6 bg-white rounded-xl shadow-lg">
                <h3 class="mb-4 text-lg font-bold text-gray-800">
                    Daftar Resep Ditemukan ({{ searchResult.length }} data)
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-lg border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr class="text-center">
                                <th class="px-3 py-3 text-sm font-medium text-gray-700 border-b">No</th>
                                <th class="px-3 py-3 text-sm font-medium text-gray-700 border-b">No. Resep
                                </th>
                                <th class="px-3 py-3 text-sm font-medium text-gray-700 border-b">No. Apotek
                                </th>
                                <th class="px-3 py-3 text-sm font-medium text-gray-700 border-b">No. SEP
                                    Kunjungan</th>
                                <th class="px-3 py-3 text-sm font-medium text-gray-700 border-b">No. Kartu
                                </th>
                                <th class="px-3 py-3 text-sm font-medium text-gray-700 border-b">Nama Peserta
                                </th>
                                <th class="px-3 py-3 text-sm font-medium text-gray-700 border-b">Tgl. Entry
                                </th>
                                <th class="px-3 py-3 text-sm font-medium text-gray-700 border-b">Tgl. Resep
                                </th>
                                <th class="px-3 py-3 text-sm font-medium text-gray-700 border-b">Biaya Tagih
                                </th>
                                <th class="px-3 py-3 text-sm font-medium text-gray-700 border-b">Biaya
                                    Verifikasi</th>
                                <!-- <th class="px-3 py-3 text-sm font-medium text-gray-700 border-b">Jenis Obat
                                </th> -->
                                <th class="px-3 py-3 text-sm font-medium text-gray-700 border-b">Flag Iter
                                </th>
                                <th class="px-3 py-3 text-sm font-medium text-gray-700 border-b">Aksi</th>

                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="searchResult && searchResult.length > 0">
                                <tr v-for="(item, index) in searchResult" :key="index" class="hover:bg-gray-50 text-center">
                                    <td class="px-3 py-3 text-sm text-gray-900 border-b">{{ index + 1 }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 border-b font-medium">{{ item.NORESEP ||
                                        '-' }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 border-b">{{ item.NOAPOTIK || '-' }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 border-b font-medium text-blue-600">{{
                                        item.NOSEP_KUNJUNGAN || '-' }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 border-b">{{ item.NOKARTU || '-' }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 border-b font-medium">{{ item.NAMA || '-'
                                        }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 border-b">{{ formatTanggal(item.TGLENTRY)
                                        }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 border-b">{{ formatTanggal(item.TGLRESEP)
                                        }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 border-b text-right font-medium">
                                        {{ formatCurrency(item.BYTAGRSP) }}
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-900 border-b text-right">
                                        {{ formatCurrency(item.BYVERRSP) }}
                                    </td>
                                    <!-- <td class="px-3 py-3 text-sm text-gray-900 border-b">
                                        <span class="px-2 py-1 text-xs rounded-full"
                                            :class="getJenisObatClass(item.KDJNSOBAT)">
                                            {{ getJenisObatText(item.KDJNSOBAT) }}
                                        </span>
                                    </td> -->
                                    <td class="px-3 py-3 text-sm text-gray-900 border-b">
                                        <span class="px-2 py-1 text-xs rounded-full"
                                            :class="item.FLAGITER === 'True' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800'">
                                            {{ item.FLAGITER === 'True' ? 'Ya' : 'Tidak' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-900 border-b">
                                        <Tooltip text="Hapus Klaim" bgColor="bg-red-600">
                                            <button @click="openDelete(item)"
                                                class="px-2 py-1 text-xs font-medium text-red-600 border border-red-600 rounded transition duration-300 hover:bg-red-200">
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
                                        <p class="text-sm">Silakan lakukan pencarian untuk menampilkan data</p>
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