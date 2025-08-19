<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import dayjs from 'dayjs';
import duration from 'dayjs/plugin/duration';
dayjs.extend(duration);
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);
import DateFilter from '@/Components/DateFilter.vue';
import LosChart from './Partials/LosChart.vue';

const props = defineProps({
    start_date: String,
    end_date: String
});

const startDate = ref(props.start_date);
const endDate = ref(props.end_date);
const averageLos = ref(0);
const categories = ref({});
const patients = ref([]);
const currentPage = ref(1);
const lastPage = ref(1);
const perPage = ref(20);
const totalPatients = ref(0);
const isLoading = ref(false);
const loadingStates = ref({
    chart: false
});

const loadReport = async () => {
    isLoading.value = true;
    loadingStates.value.chart = true;

    try {
        const res = await fetch(`/laporan-los/data?start_date=${startDate.value}&end_date=${endDate.value}&page=${currentPage.value}&per_page=${perPage.value}`);
        const data = await res.json();
        if (data.success) {
            patients.value = data.patients;
            averageLos.value = data.average_los;
            categories.value = data.categories;
            currentPage.value = data.pagination.page;
            perPage.value = data.pagination.per_page;
            lastPage.value = data.pagination.last_page;
            totalPatients.value = data.pagination.total;
        }
    } catch (err) {
        console.error('Gagal load data:', err);
    } finally {
        isLoading.value = false;
        loadingStates.value.chart = false;
    }
};

const goToPage = (p) => {
    if (p < 1 || p > lastPage.value) return;
    currentPage.value = p;
    loadReport();
};

onMounted(loadReport);

const formatLos = (minutes) => {
    const dur = dayjs.duration(minutes, 'minutes');
    return `${dur.hours()} jam ${dur.minutes()} menit`;
};

const handleFilter = (dates) => {
    router.get(route('laporan.los'), dates);
};

document.addEventListener('inertia:start', () => {
    isLoading.value = true;
});
document.addEventListener('inertia:finish', () => {
    isLoading.value = false;
});
function getLosCategoryClass(minutes) {
    if (minutes < 60) return 'bg-blue-100 text-blue-600';
    if (minutes < 180) return 'bg-emerald-100 text-emerald-600';
    if (minutes < 420) return 'bg-yellow-100 text-yellow-600';
    if (minutes < 480) return 'bg-red-100 text-red-600';
    return 'bg-gray-200 text-gray-700';
}
</script>

<template>

    <Head title="Laporan LOS IGD" />

    <div class="min-h-screen bg-gradient-to-br from-blue-100 to-green-100 px-8 py-10 flex flex-col items-center">
        <div class="w-full max-w-8xl bg-white rounded-2xl shadow-xl p-8 space-y-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <h1 class="text-3xl font-extrabold text-gray-800 flex items-center gap-3">
                    🏥 Laporan LOS IGD
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
            <DateFilter :initial-start-date="props.start_date" :initial-end-date="props.end_date"
                @filter="handleFilter" />
            <LosChart :categories="categories" :start-date="props.start_date" :end-date="props.end_date"
                :loading-state="loadingStates.chart" />

            <div class="bg-blue-50 p-6 rounded-xl shadow-inner relative">
                <h2 class="text-xl font-bold text-blue-700 mb-4">📊 Rangkuman</h2>
                <p class="text-lg text-gray-800"><strong>Rata-rata LOS:</strong> {{ averageLos }} jam</p>
                <ul class="list-disc pl-5 mt-2 text-gray-700">
                    <li v-for="(val, key) in categories" :key="key">{{ key }}: {{ val }} pasien</li>
                </ul>
            </div>
            <div class="overflow-x-auto relative">
                <table class="min-w-full border border-gray-300 rounded-lg overflow-hidden">
                    <thead class="bg-blue-600 text-white">
                        <tr>
                            <th class="px-4 py-2 text-left">No</th>
                            <th class="px-4 py-2 text-left">NORM</th>
                            <th class="px-4 py-2 text-left">Nama</th>
                            <th class="px-4 py-2 text-left">Masuk</th>
                            <th class="px-4 py-2 text-left">Keluar</th>
                            <th class="px-4 py-2 text-left">LOS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(p, i) in patients" :key="i" class="border-t"
                            :class="getLosCategoryClass(p.LOS_MINUTES)">
                            <td class="px-4 py-2">{{ (currentPage - 1) * perPage + i + 1 }}</td>
                            <td class="px-4 py-2">{{ p.NORM }}</td>
                            <td class="px-4 py-2">{{ p.NAMA }}</td>
                            <td class="px-4 py-2">{{ dayjs(p.MASUK).format('DD/MM/YYYY HH:mm') }}</td>
                            <td class="px-4 py-2">{{ dayjs(p.KELUAR).format('DD/MM/YYYY HH:mm') }}</td>
                            <td class="px-4 py-2 font-semibold">
                                {{ formatLos(p.LOS_MINUTES) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-between items-center mt-6">
                <div class="flex items-center gap-4">
                    <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
                        class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-lg disabled:opacity-40 disabled:cursor-not-allowed transition">
                        <font-awesome-icon icon="chevron-left" />
                        <span>Sebelumnya</span>
                    </button>
                    <span class="text-gray-700 font-medium">Halaman {{ currentPage }} dari {{ lastPage }}</span>
                    <button @click="goToPage(currentPage + 1)" :disabled="currentPage === lastPage"
                        class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-lg disabled:opacity-40 disabled:cursor-not-allowed transition">
                        <span>Berikutnya</span>
                        <font-awesome-icon icon="chevron-right" />
                    </button>
                </div>
                <div class="text-sm text-gray-700">
                    Menampilkan pasien {{ (currentPage - 1) * perPage + 1 }} - {{ Math.min(currentPage * perPage,
                        totalPatients) }}
                    dari total <strong class="text-red-600">{{ totalPatients }}</strong> pasien
                </div>
            </div>
        </div>
    </div>
    <div v-if="isLoading" class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-white bg-opacity-80">
        <video src="/img/loading.webm" autoplay loop muted playsinline />
    </div>
</template>
