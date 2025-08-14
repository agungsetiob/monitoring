<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import DateFilter from '@/Components/DateFilter.vue';
import PatientPerHourChart from './Partials/PatientPerHourChart.vue';
import PatientPerShiftChart from './Partials/PatientPerShiftChart.vue';

const props = defineProps({
    start_date: String,
    end_date: String,
    data_per_jam: Array,
    data_per_shift: Array,
    summary: Object
});

const chartRefJam = ref(null);
const chartRefShift = ref(null);

const handleFilter = (dates) => {
    router.get(route('laporan.kepadatan-igd'), dates);
};

const isLoading = ref(false);
document.addEventListener('inertia:start', () => {
    isLoading.value = true;
});
document.addEventListener('inertia:finish', () => {
    isLoading.value = false;
});
</script>

<template>

    <Head title="Laporan Kepadatan IGD" />

    <div class="min-h-screen bg-gradient-to-br from-blue-100 to-green-100 px-8 py-10 flex flex-col items-center">
        <div class="w-full max-w-8xl bg-white rounded-2xl shadow-xl p-8 space-y-8">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <h1 class="text-3xl font-extrabold text-gray-800 flex items-center gap-3">
                    📊 Laporan Kepadatan IGD
                </h1>
                <button @click="router.visit('/laporan')"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-3 rounded-xl transition duration-300 ease-in-out transform hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5"></path>
                        <path d="M12 19l-7-7 7-7"></path>
                    </svg>
                    Kembali
                </button>
            </div>
            <DateFilter :initial-start-date="props.start_date" :initial-end-date="props.end_date"
                @filter="handleFilter" />
            <!-- Summary -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6">
                <div class="bg-green-100 p-4 rounded-xl text-center">
                    <p class="text-sm text-gray-600">Total Pasien</p>
                    <p class="text-2xl font-bold text-green-800">
                        {{ props.summary.total_pasien }}
                    </p>
                </div>
                <div class="bg-orange-100 p-4 rounded-xl text-center">
                    <p class="text-sm text-gray-600">Jam Tersibuk</p>
                    <p class="text-2xl font-bold text-orange-800">
                        {{ props.summary.jam_tersibuk }}:00
                    </p>
                </div>
                <div class="bg-red-100 p-4 rounded-xl text-center">
                    <p class="text-sm text-gray-600">Pasien Jam Tersibuk</p>
                    <p class="text-2xl font-bold text-red-800">
                        {{ props.summary.jumlah_tersibuk }}
                    </p>
                </div>
            </div>
            <PatientPerHourChart :data="props.data_per_jam" />
            <PatientPerShiftChart :data="props.data_per_shift" />
        </div>
    </div>
    <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-80">
        <video src="/img/loading.webm" autoplay loop muted playsinline />
    </div>
</template>
