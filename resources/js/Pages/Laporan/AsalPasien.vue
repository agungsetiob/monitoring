<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import DateFilter from '@/Components/DateFilter.vue';
import ArrivalTypeChart from './Partials/ArrivalTypeChart.vue';
import ReferralChart from './Partials/ReferralChart.vue';

const props = defineProps({
    start_date: String,
    end_date: String,
    patients: Array,
    stats: Object,
    pagination: Object
});

// State untuk filter
const startDate = ref(props.start_date);
const endDate = ref(props.end_date);

// State pagination (ambil dari props)
const currentPage = ref(props.pagination.current_page);
const lastPage = ref(props.pagination.last_page);
const perPage = ref(props.pagination.per_page);
const totalPatients = ref(props.pagination.total);
const patients = ref(props.patients);

const isLoading = ref(false);

// Fungsi pindah halaman
const goToPage = (page) => {
    if (page < 1 || page > lastPage.value) return;
    router.get(route('laporan.asal-pasien'), {
        start_date: startDate.value,
        end_date: endDate.value,
        page: page,
        per_page: perPage.value
    }, { preserveScroll: true });
};

// Fungsi filter (reset ke halaman 1)
const handleFilter = (dates) => {
    startDate.value = dates.start_date;
    endDate.value = dates.end_date;
    router.get(route('laporan.asal-pasien'), {
        ...dates,
        per_page: perPage.value,
        page: 1 // kembali ke halaman pertama
    });
};

// Update state ketika props berubah (karena Inertia memperbarui komponen)
watch(() => props.pagination, (newPagination) => {
    currentPage.value = newPagination.current_page;
    lastPage.value = newPagination.last_page;
    perPage.value = newPagination.per_page;
    totalPatients.value = newPagination.total;
    patients.value = props.patients;
});

// Loading indicator
document.addEventListener('inertia:start', () => {
    isLoading.value = true;
});
document.addEventListener('inertia:finish', () => {
    isLoading.value = false;
});
</script>

<template>

    <Head title="Laporan Kedatangan Pasien" />

    <div class="min-h-screen bg-gradient-to-br from-blue-100 to-green-100 px-8 py-10 flex flex-col items-center">
        <div class="w-full max-w-8xl bg-white rounded-2xl shadow-xl p-8 space-y-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <h1 class="text-3xl font-extrabold text-gray-800 flex items-center gap-3">
                    🏥 Laporan Asal Pasien IGD
                </h1>
                <button @click="router.visit('/laporan')"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-3 rounded-xl transition duration-300 ease-in-out transform hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5"></path>
                        <path d="M12 19l-7-7 7-7"></path>
                    </svg>
                    Kembali
                </button>
            </div>

            <DateFilter :initial-start-date="start_date" :initial-end-date="end_date" @filter="handleFilter" />

            <!-- Summary -->

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <div class="bg-blue-100 p-4 rounded-xl text-center">
                    <p class="text-sm text-gray-600">Total Kunjungan</p>
                    <p class="text-3xl font-bold">
                        {{ pagination.total }}
                    </p>
                </div>

                <div class="bg-green-100 p-4 rounded-xl text-center">
                    <p class="text-sm text-gray-600">
                        Jenis Kedatangan
                    </p>
                    <p class="text-3xl font-bold">
                        {{ stats?.per_jenis?.length || 0 }}
                    </p>
                </div>

                <div class="bg-orange-100 p-4 rounded-xl text-center">
                    <p class="text-sm text-gray-600">
                        Asal Rujukan
                    </p>
                    <p class="text-3xl font-bold">
                        {{ stats?.per_rujukan?.length || 0 }}
                    </p>
                </div>

            </div>

            <ArrivalTypeChart :data="stats.per_jenis" />

            <ReferralChart :data="stats.per_rujukan" />

            <!-- Detail Table -->
            <div class="bg-white rounded-xl border shadow">
                <div class="p-4 border-b">
                    <h2 class="font-bold text-lg">Detail Kedatangan Pasien</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left">#</th>
                                <th class="px-4 py-3 text-left">No RM</th>
                                <th class="px-4 py-3 text-left">Nama</th>
                                <th class="px-4 py-3 text-left">Jenis</th>
                                <th class="px-4 py-3 text-left">Tanggal Kedatangan</th>
                                <th class="px-4 py-3 text-left">Transportasi</th>
                                <th class="px-4 py-3 text-left">Asal Rujukan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in patients" :key="item.KUNJUNGAN" class="border-t">
                                <td class="px-4 py-3">{{ (currentPage - 1) * perPage + index + 1 }}</td>
                                <td class="px-4 py-3">{{ item.NORM }}</td>
                                <td class="px-4 py-3">{{ item.NAMA }}</td>
                                <td class="px-4 py-3">{{ item.JENIS_KEDATANGAN }}</td>
                                <td class="px-4 py-3">{{ item.TANGGAL_KEDATANGAN }}</td>
                                <td class="px-4 py-3">{{ item.ALAT_TRANSPORTASI }}</td>
                                <td class="px-4 py-3">{{ item.ASAL_RUJUKAN_NAMA ?? '-' }}</td>
                            </tr>
                            <tr v-if="patients.length === 0">
                                <td colspan="7" class="text-center py-6 text-gray-500">Tidak ada data</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex flex-wrap items-center justify-between gap-4 px-4 py-3 border-t">
                    <div class="flex items-center gap-4">
                        <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
                            class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-lg disabled:opacity-40 disabled:cursor-not-allowed transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 18l-6-6 6-6" />
                            </svg>
                            <span>Sebelumnya</span>
                        </button>
                        <span class="text-gray-700 font-medium">
                            Halaman {{ currentPage }} dari {{ lastPage }}
                        </span>
                        <button @click="goToPage(currentPage + 1)" :disabled="currentPage === lastPage"
                            class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-lg disabled:opacity-40 disabled:cursor-not-allowed transition">
                            <span>Berikutnya</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 18l6-6-6-6" />
                            </svg>
                        </button>
                    </div>
                    <div class="text-sm text-gray-700">
                        Menampilkan pasien
                        <span class="font-semibold">{{ (currentPage - 1) * perPage + 1 }}</span>
                        –
                        <span class="font-semibold">{{ Math.min(currentPage * perPage, totalPatients) }}</span>
                        dari total <strong class="text-red-600">{{ totalPatients }}</strong> pasien
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-80">
        <video src="/img/loading.webm" autoplay loop muted playsinline />
    </div>
</template>