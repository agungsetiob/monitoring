<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
import dayjs from 'dayjs';
import Tooltip from '@/Components/Tooltip.vue';
import Modal from '@/Components/Modal.vue'
import SuccessFlash from '@/Components/SuccessFlash.vue'
import ErrorFlash from '@/Components/ErrorFlash.vue'

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

// Prefill field dari row + load daftar obat
const openDelete = async (item) => {
    selectedItem.value = item;

    // noresep biasanya ada
    deleteForm.noresep = item.NORESEP ?? item.noresep ?? '';

    // no SEP Apotek (nosepapotek) biasanya = NOAPOTIK, beberapa data bisa NOSJP
    deleteForm.nosjp =
        item.NOSJP ?? item.nosjp ?? item.NOAPOTIK ?? item.noapotik ?? '';

    // ref asal sjp: fallback
    deleteForm.refasalsjp =
        item.REFASALSJP ??
        item.refasalsjp ??
        item.NOSEP_KUNJUNGAN ??
        item.ref_asal_sjp ??
        '';

    // reset audit/progress & load obat
    resetDeleteProgress();
    await loadObatList(deleteForm.nosjp);

    showDeleteModal.value = true;
};

const closeDelete = () => {
    showDeleteModal.value = false;
    isDeleting.value = false;
    selectedItem.value = null;
    deleteForm.nosjp = '';
    deleteForm.refasalsjp = '';
    deleteForm.noresep = '';
    obatList.value = [];
    resetDeleteProgress();
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
const isExporting = ref(false);

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

const exportData = async (format) => {
    if (searchResult.value.length === 0) {
        showMessage('Tidak ada data untuk di-export. Lakukan pencarian terlebih dahulu.');
        return;
    }

    isExporting.value = true;

    try {
        const exportPayload = {
            ...searchForm,
            format: format
        };

        const response = await axios.post('/apol/export-resep', exportPayload, {
            responseType: format === 'excel' ? 'blob' : 'json'
        });

        if (format === 'excel') {
            const blob = new Blob([response.data], {
                type: 'application/vnd.ms-excel'
            });

            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `daftar_resep_${dayjs().format('YYYYMMDD_HHmmss')}.xls`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);

            showMessage(`Data berhasil di-export dalam format Excel (.xls)`, 'success');
        } else {
            if (response.data.success) {
                const csvContent = convertToCSV(response.data.data);
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });

                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `daftar_resep_${dayjs().format('YYYYMMDD_HHmmss')}.csv`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);

                showMessage(`Data berhasil di-export dalam format CSV`, 'success');
            } else {
                showMessage(response.data.message || 'Export gagal');
            }
        }
    } catch (error) {
        showMessage(error.response?.data?.message || 'Terjadi kesalahan saat export data');
    } finally {
        isExporting.value = false;
    }
};

const convertToCSV = (data) => {
    if (!data || data.length === 0) return '';

    const headers = [
        'No',
        'No. Resep',
        'No. Apotek',
        'No. SEP Kunjungan',
        'No. Kartu',
        'Nama Peserta',
        'Tgl. Entry',
        'Tgl. Resep',
        'Tgl. Pelayanan',
        'Biaya Tagih',
        'Biaya Verifikasi',
        'Jenis Obat',
        'Faskes Asal',
        'Flag Iter'
    ];

    const csvContent = [headers.join(',')];

    data.forEach((item, index) => {
        const row = [
            index + 1,
            `"${item.NORESEP || ''}"`,
            `"${item.NOAPOTIK || ''}"`,
            `"${item.NOSEP_KUNJUNGAN || ''}"`,
            `"${item.NOKARTU || ''}"`,
            `"${item.NAMA || ''}"`,
            `"${formatTanggal(item.TGLENTRY)}"`,
            `"${formatTanggal(item.TGLRESEP)}"`,
            `"${formatTanggal(item.TGLPELRSP)}"`,
            `"${item.BYTAGRSP || '0'}"`,
            `"${item.BYVERRSP || '0'}"`,
            `"${getJenisObatText(item.KDJNSOBAT)}"`,
            `"${item.FASKESASAL || ''}"`,
            `"${item.FLAGITER === 'True' ? 'Ya' : 'Tidak'}"`
        ];
        csvContent.push(row.join(','));
    });

    return csvContent.join('\n');
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

    <div class="p-4 min-h-screen bg-pattern md:p-6">
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
            <ErrorFlash   :flash="{ error:   errorMessage   }" @clearFlash="errorMessage   = ''" />
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

            <!-- Export Actions -->
            <div v-if="searchResult.length > 0" class="p-4 mb-6 bg-white rounded-xl shadow-lg">
                <h3 class="mb-3 text-lg font-bold text-gray-800">Export Data</h3>
                <div class="flex gap-3">
                    <button @click="exportData('excel')" :disabled="isExporting"
                        class="flex gap-2 items-center px-4 py-2 font-semibold text-white bg-emerald-600 rounded-lg transition duration-300 hover:bg-emerald-700 disabled:bg-emerald-400">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"
                                clip-rule="evenodd"></path>
                        </svg>
                        {{ isExporting ? 'Exporting...' : 'Excel' }}
                    </button>

                    <button @click="exportData('csv')" :disabled="isExporting"
                        class="flex gap-2 items-center px-4 py-2 font-semibold text-white bg-orange-600 rounded-lg transition duration-300 hover:bg-orange-700 disabled:bg-orange-400">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"
                                clip-rule="evenodd"></path>
                        </svg>
                        {{ isExporting ? 'Exporting...' : 'CSV' }}
                    </button>
                </div>
            </div>

            <!-- Search Result -->
            <div class="p-6 mb-6 bg-white rounded-xl shadow-lg">
                <h3 class="mb-4 text-lg font-bold text-gray-800">
                    Daftar Resep Ditemukan ({{ searchResult.length }} data)
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-lg border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">No</th>
                                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">No. Resep
                                </th>
                                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">No. Apotek
                                </th>
                                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">No. SEP
                                    Kunjungan</th>
                                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">No. Kartu
                                </th>
                                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">Nama Peserta
                                </th>
                                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">Tgl. Entry
                                </th>
                                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">Tgl. Resep
                                </th>
                                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">Tgl.
                                    Pelayanan</th>
                                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">Biaya Tagih
                                </th>
                                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">Biaya
                                    Verifikasi</th>
                                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">Jenis Obat
                                </th>
                                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">Faskes Asal
                                </th>
                                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">Flag Iter
                                </th>
                                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">Aksi</th>

                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="searchResult && searchResult.length > 0">
                                <tr v-for="(item, index) in searchResult" :key="index" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900 border-b">{{ index + 1 }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 border-b font-medium">{{ item.NORESEP ||
                                        '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 border-b">{{ item.NOAPOTIK || '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 border-b font-medium text-blue-600">{{
                                        item.NOSEP_KUNJUNGAN || '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 border-b">{{ item.NOKARTU || '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 border-b font-medium">{{ item.NAMA || '-'
                                        }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 border-b">{{ formatTanggal(item.TGLENTRY)
                                        }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 border-b">{{ formatTanggal(item.TGLRESEP)
                                        }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 border-b">{{
                                        formatTanggal(item.TGLPELRSP) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 border-b text-right font-medium">
                                        {{ formatCurrency(item.BYTAGRSP) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 border-b text-right">
                                        {{ formatCurrency(item.BYVERRSP) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 border-b">
                                        <span class="px-2 py-1 text-xs rounded-full"
                                            :class="getJenisObatClass(item.KDJNSOBAT)">
                                            {{ getJenisObatText(item.KDJNSOBAT) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 border-b">{{ item.FASKESASAL || '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 border-b">
                                        <span class="px-2 py-1 text-xs rounded-full"
                                            :class="item.FLAGITER === 'True' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800'">
                                            {{ item.FLAGITER === 'True' ? 'Ya' : 'Tidak' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 border-b">
                                        <button type="button" @click="openDelete(item)"
                                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md bg-rose-600 text-white hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500/40"
                                            title="Hapus resep ini dari APOL">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                                <path d="M10 11v6"></path>
                                                <path d="M14 11v6"></path>
                                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                                            </svg>
                                            Hapus
                                        </button>
                                    </td>

                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="14" class="px-4 py-8 text-center text-gray-500">
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
                <!-- Modal Hapus Resep -->
                <Modal :show="showDeleteModal" max-width="2xl" @close="closeDelete">
                    <div class="flex items-center justify-between px-5 py-4 border-b">
                        <h3 class="text-lg font-semibold">Hapus Resep</h3>
                        <button @click="closeDelete" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="px-5 py-4 space-y-4">
                        <div class="px-5 py-4 space-y-4">
                            <p class="text-sm text-gray-600">
                                Pastikan <b>No Resep</b>, <b>No SEP Apotek/SJP</b>, dan <b>Ref Asal SJP</b> sesuai.
                                Centang opsi di bawah untuk menghapus <b>semua obat</b> lebih dulu (disarankan).
                            </p>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div class="md:col-span-1">
                                    <label class="block mb-1 text-sm font-medium text-gray-700">No Resep</label>
                                    <input v-model="deleteForm.noresep" type="text"
                                        class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent"
                                        placeholder="Contoh: 0SI44" />
                                </div>

                                <div class="md:col-span-1">
                                    <label class="block mb-1 text-sm font-medium text-gray-700">No SEP
                                        Apotek/SJP</label>
                                    <input v-model="deleteForm.nosjp" type="text"
                                        class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent"
                                        placeholder="Contoh: 1801A00104190000001" />
                                </div>

                                <div class="md:col-span-1">
                                    <label class="block mb-1 text-sm font-medium text-gray-700">Ref Asal SJP</label>
                                    <input v-model="deleteForm.refasalsjp" type="text"
                                        class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent"
                                        placeholder="Contoh: 1801R0010419V000001" />
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
                                        Daftar Obat ({{ isLoadingObatList ? 'memuat...' : obatList.length + ' item'
                                        }})
                                    </h4>
                                    <button class="text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50"
                                        @click="loadObatList(deleteForm.nosjp)" :disabled="isLoadingObatList">
                                        Muat Ulang
                                    </button>
                                </div>

                                <div v-if="isLoadingObatList" class="text-xs text-gray-500 mt-2">Mengambil data...
                                </div>
                                <div v-else-if="obatList.length === 0" class="text-xs text-gray-500 mt-2">Tidak ada
                                    obat.</div>
                                <ul v-else class="mt-2 space-y-1 max-h-48 overflow-auto pr-1">
                                    <li v-for="(o, idx) in obatList" :key="idx"
                                        class="text-sm flex items-center justify-between border-b last:border-0 py-1">
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
                                    <div class="mb-1">Menghapus obat: {{ deleteStats.done }}/{{ deleteStats.total }}
                                        selesai,
                                        gagal: {{ deleteStats.failed }}</div>
                                    <div class="w-full bg-gray-200 rounded h-2 overflow-hidden">
                                        <div class="h-2 bg-rose-600"
                                            :style="{ width: (deleteStats.total ? (deleteStats.done / deleteStats.total * 100) : 0) + '%' }">
                                        </div>
                                    </div>
                                    <div v-if="deleteStats.failed > 0" class="mt-2 text-rose-600">
                                        Beberapa obat gagal dihapus. Resep tidak akan dihapus sampai semua obat
                                        terhapus.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 px-5 py-4 border-t">
                        <button @click="closeDelete"
                            class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                            Batal
                        </button>
                        <button :disabled="isDeleting || deleteDisabled" @click="submitDelete"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-lg text-white bg-rose-600 hover:bg-rose-700 disabled:bg-rose-400">
                            <svg v-if="isDeleting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            {{ isDeleting ? 'Memproses...' : 'Hapus Resep' }}
                        </button>
                    </div>
                </Modal>
            </div>
        </div>
    </div>
</template>

<style scoped>
.bg-pattern {
    background-color: #ffffff;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100%25'%3E%3Cdefs%3E%3ClinearGradient id='a' gradientUnits='userSpaceOnUse' x1='0' x2='0' y1='0' y2='100%25' gradientTransform='rotate(240)'%3E%3Cstop offset='0' stop-color='%23ffffff'/%3E%3Cstop offset='1' stop-color='%234FE'/%3E%3C/linearGradient%3E%3Cpattern patternUnits='userSpaceOnUse' id='b' width='540' height='450' x='0' y='0' viewBox='0 0 1080 900'%3E%3Cg fill-opacity='0.1'%3E%3Cpolygon fill='%23444' points='90 150 0 300 180 300'/%3E%3Cpolygon points='90 150 180 0 0 0'/%3E%3Cpolygon fill='%23AAA' points='270 150 360 0 180 0'/%3E%3Cpolygon fill='%23DDD' points='450 150 360 300 540 300'/%3E%3Cpolygon fill='%23999' points='450 150 540 0 360 0'/%3E%3Cpolygon points='630 150 540 300 720 300'/%3E%3Cpolygon fill='%23DDD' points='630 150 720 0 540 0'/%3E%3Cpolygon fill='%23444' points='810 150 720 300 900 300'/%3E%3Cpolygon fill='%23FFF' points='810 150 900 0 720 0'/%3E%3Cpolygon fill='%23DDD' points='990 150 900 300 1080 300'/%3E%3Cpolygon fill='%23444' points='990 150 1080 0 900 0'/%3E%3Cpolygon fill='%23DDD' points='90 450 0 600 180 600'/%3E%3Cpolygon points='90 450 180 300 0 300'/%3E%3Cpolygon fill='%23666' points='270 450 180 600 360 600'/%3E%3Cpolygon fill='%23AAA' points='270 450 360 300 180 300'/%3E%3Cpolygon fill='%23DDD' points='450 450 360 600 540 600'/%3E%3Cpolygon fill='%23999' points='450 450 540 300 360 300'/%3E%3Cpolygon fill='%23999' points='630 450 540 600 720 600'/%3E%3Cpolygon fill='%23FFF' points='630 450 720 300 540 300'/%3E%3Cpolygon points='810 450 720 600 900 600'/%3E%3Cpolygon fill='%23DDD' points='810 450 900 300 720 300'/%3E%3Cpolygon fill='%23AAA' points='990 450 900 600 1080 600'/%3E%3Cpolygon fill='%23444' points='990 450 1080 300 900 300'/%3E%3Cpolygon fill='%23222' points='90 750 0 900 180 900'/%3E%3Cpolygon points='270 750 180 900 360 900'/%3E%3Cpolygon fill='%23DDD' points='270 750 360 600 180 600'/%3E%3Cpolygon points='450 750 540 600 360 600'/%3E%3Cpolygon points='630 750 540 900 720 900'/%3E%3Cpolygon fill='%23444' points='630 750 720 600 540 600'/%3E%3Cpolygon fill='%23AAA' points='810 750 720 900 900 900'/%3E%3Cpolygon fill='%23666' points='810 750 900 600 720 600'/%3E%3Cpolygon fill='%23999' points='990 750 900 900 1080 900'/%3E%3Cpolygon fill='%23999' points='180 0 90 150 270 150'/%3E%3Cpolygon fill='%23444' points='360 0 270 150 450 150'/%3E%3Cpolygon fill='%23FFF' points='540 0 450 150 630 150'/%3E%3Cpolygon points='900 0 810 150 990 150'/%3E%3Cpolygon fill='%23222' points='0 300 -90 450 90 450'/%3E%3Cpolygon fill='%23FFF' points='0 300 90 150 -90 150'/%3E%3Cpolygon fill='%23FFF' points='180 300 90 450 270 450'/%3E%3Cpolygon fill='%23666' points='180 300 270 150 90 150'/%3E%3Cpolygon fill='%23222' points='360 300 270 450 450 450'/%3E%3Cpolygon fill='%23FFF' points='360 300 450 150 270 150'/%3E%3Cpolygon fill='%23444' points='540 300 450 450 630 450'/%3E%3Cpolygon fill='%23222' points='540 300 630 150 450 150'/%3E%3Cpolygon fill='%23AAA' points='720 300 630 450 810 450'/%3E%3Cpolygon fill='%23666' points='720 300 810 150 630 150'/%3E%3Cpolygon fill='%23FFF' points='900 300 810 450 990 450'/%3E%3Cpolygon fill='%23999' points='900 300 990 150 810 150'/%3E%3Cpolygon points='0 600 -90 750 90 750'/%3E%3Cpolygon fill='%23666' points='0 600 90 450 -90 450'/%3E%3Cpolygon fill='%23AAA' points='180 600 90 750 270 750'/%3E%3Cpolygon fill='%23444' points='180 600 270 450 90 450'/%3E%3Cpolygon fill='%23444' points='360 600 270 750 450 750'/%3E%3Cpolygon fill='%23999' points='360 600 450 450 270 450'/%3E%3Cpolygon fill='%23666' points='540 600 630 450 450 450'/%3E%3Cpolygon fill='%23222' points='720 600 630 750 810 750'/%3E%3Cpolygon fill='%23FFF' points='900 600 810 750 990 750'/%3E%3Cpolygon fill='%23222' points='900 600 990 450 810 450'/%3E%3Cpolygon fill='%23DDD' points='0 900 90 750 -90 750'/%3E%3Cpolygon fill='%23444' points='180 900 270 750 90 750'/%3E%3Cpolygon fill='%23FFF' points='360 900 450 750 270 750'/%3E%3Cpolygon fill='%23AAA' points='540 900 630 750 450 750'/%3E%3Cpolygon fill='%23FFF' points='720 900 810 750 630 750'/%3E%3Cpolygon fill='%23222' points='900 900 990 750 810 750'/%3E%3Cpolygon fill='%23222' points='1080 300 990 450 1170 450'/%3E%3Cpolygon fill='%23FFF' points='1080 300 1170 150 990 150'/%3E%3Cpolygon points='1080 600 990 750 1170 750'/%3E%3Cpolygon fill='%23666' points='1080 600 1170 450 990 450'/%3E%3Cpolygon fill='%23DDD' points='1080 900 1170 750 990 750'/%3E%3C/g%3E%3C/pattern%3E%3C/defs%3E%3Crect x='0' y='0' fill='url(%23a)' width='100%25' height='100%25'/%3E%3Crect x='0' y='0' fill='url(%23b)' width='100%25' height='100%25'/%3E%3C/svg%3E");
    background-attachment: fixed;
    background-size: cover;
}
</style>