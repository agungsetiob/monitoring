<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import Chart from 'chart.js/auto';
import DateFilter from '@/Components/DateFilter.vue';

const props = defineProps({
    start_date: String,
    end_date: String,
    data_per_jam: Array,
    data_per_shift: Array
});

const chartRefJam = ref(null);
const chartRefShift = ref(null);
let chartJamInstance = null;
let chartShiftInstance = null;

const handleFilter = (dates) => {
    router.get(route('laporan.kepadatan-igd'), dates);
};

onMounted(() => {
    // Chart Per Jam
    if (chartJamInstance) chartJamInstance.destroy();
    const ctxJam = chartRefJam.value.getContext('2d');
    const labelsJam = props.data_per_jam.map(d => String(d.jam).padStart(2, '0') + ':00');
    const valuesJam = props.data_per_jam.map(d => d.jumlah);

    chartJamInstance = new Chart(ctxJam, {
        type: 'bar',
        data: {
            labels: labelsJam,
            datasets: [{
                label: 'Jumlah Pasien Masuk',
                data: valuesJam,
                backgroundColor: valuesJam.map(v => {
                    if (v > 11) return '#dc2626'; // merah
                    if (v > 5) return '#f97316';  // oranye
                    return '#16a34a';             // hijau
                }),
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => `${ctx.parsed.y} pasien`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Jumlah Pasien' }
                },
                x: {
                    title: { display: true, text: 'Jam Masuk' }
                }
            }
        }
    });

    // Chart Per Shift
    if (chartShiftInstance) chartShiftInstance.destroy();
    const ctxShift = chartRefShift.value.getContext('2d');
    const labelsShift = props.data_per_shift.map(d => d.shift);
    const valuesShift = props.data_per_shift.map(d => d.jumlah);

    chartShiftInstance = new Chart(ctxShift, {
        type: 'pie',
        data: {
            labels: labelsShift,
            datasets: [{
                data: valuesShift,
                backgroundColor: ['#3b82f6', '#f59e0b', '#10b981'] // biru, oranye, hijau
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: ctx => `${ctx.label}: ${ctx.parsed} pasien`
                    }
                }
            }
        }
    });
});

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

    <div class="min-h-screen bg-gradient-to-br from-green-100 to-blue-100 px-8 py-10 flex flex-col items-center">
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
                    <p class="text-sm text-gray-600">Pasien Tersibuk per Jam</p>
                    <p class="text-2xl font-bold text-red-800">
                        {{ props.summary.jumlah_tersibuk }}
                    </p>
                </div>
            </div>
            <!-- Chart Per Jam -->
            <div class="bg-white p-6 md:p-8 rounded-xl shadow-md border border-gray-200">
                <h2 class="text-xl font-bold mb-4 text-gray-700">📈 Grafik Pasien per Jam</h2>
                <div class="relative h-[400px]">
                    <canvas ref="chartRefJam"></canvas>
                </div>
                <!-- Legend -->
                <div class="mt-4 flex flex-wrap gap-4 text-sm text-gray-600">
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded bg-red-600"></span>
                        ≥ 12 pasien (padat)
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded bg-orange-500"></span>
                        6-11 pasien (sedang)
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded bg-green-600"></span>
                        ≤ 5 pasien (longgar)
                    </div>
                </div>
            </div>
            <!-- Chart Per Shift -->
            <div class="bg-white p-6 md:p-8 rounded-xl shadow-md border border-gray-200">
                <h2 class="text-xl font-bold mb-4 text-gray-700">🕒 Kepadatan Pasien per Shift</h2>
                <div class="relative h-[300px]">
                    <canvas ref="chartRefShift"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-80">
        <video src="/img/loading.webm" autoplay loop muted playsinline />
    </div>
</template>
