<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import dayjs from 'dayjs';
import DateFilter from '@/Components/DateFilter.vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    patients: Object,
    summary: Object,
    start_date: String,
    end_date: String,
    per_page: Number
});

const startDate = ref(props.start_date);
const endDate = ref(props.end_date);
const currentPage = ref(props.patients.current_page);
const lastPage = ref(props.patients.last_page);
const perPage = ref(props.per_page ?? 20);
const totalPatients = ref(props.patients.total);
const patients = ref(props.patients.data);
const isLoading = ref(false);

const perHariChart = ref(null);
const perDokterChart = ref(null);

const goToPage = (page) => {
    if (page < 1 || page > lastPage.value) return;
    router.get(route('laporan.igd-ranap'), {
        start_date: startDate.value,
        end_date: endDate.value,
        page,
        per_page: perPage.value
    }, { preserveScroll: true });
};

const handleFilter = (dates) => {
    router.get(route('laporan.igd-ranap'), {
        ...dates,
        per_page: perPage.value
    });
};

onMounted(() => {
    // Chart jumlah pasien per hari
    const ctxHari = document.getElementById('chart-per-hari').getContext('2d');
    perHariChart.value = new Chart(ctxHari, {
        type: 'bar',
        data: {
            labels: props.summary.per_hari.map(row => dayjs(row.tanggal).format('DD/MM/YYYY')),
            datasets: [{
                label: 'Jumlah Pasien',
                data: props.summary.per_hari.map(row => row.total),
                backgroundColor: '#36A2EB', // solid blue khas Chart.js
                borderColor: '#36A2EB',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // non-aktifkan aspect ratio
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Chart jumlah pasien per dokter (horizontal bar chart)
    const ctxDokter = document.getElementById('chart-per-dokter').getContext('2d');
    perDokterChart.value = new Chart(ctxDokter, {
        type: 'bar',
        data: {
            labels: props.summary.per_dokter.map(row => row.dokter),
            datasets: [{
                label: 'Jumlah Pasien',
                data: props.summary.per_dokter.map(row => row.total),
                backgroundColor: '#FF6384', // solid pink khas Chart.js
                borderColor: '#FF6384',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y', // jadikan horizontal
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { beginAtZero: true }
            }
        }
    });
});
document.addEventListener('inertia:start', () => {
    isLoading.value = true;
});
document.addEventListener('inertia:finish', () => {
    isLoading.value = false;
});
</script>

<template>

    <Head title="Lanjut Rawat Inap" />

    <div class="min-h-screen bg-gradient-to-br from-blue-100 to-green-100 px-8 py-10 flex flex-col items-center">
        <div class="w-full max-w-8xl bg-white rounded-2xl shadow-xl p-8 space-y-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <h1 class="text-3xl font-extrabold text-gray-800 flex items-center gap-3">
                    🏥 Laporan IGD → Rawat Inap
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

            <DateFilter :initial-start-date="startDate" :initial-end-date="endDate" @filter="handleFilter" />
            <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 h-[500px]">
                <h2 class="text-xl font-bold text-gray-800 mb-4">📅 Lanjut Rawat Inap</h2>
                <canvas id="chart-per-hari"></canvas>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 h-[500px]">
                <h2 class="text-xl font-bold text-gray-800 mb-4">👨‍⚕️ Pasien per Dokter</h2>
                <canvas id="chart-per-dokter"></canvas>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-300 rounded-lg overflow-hidden">
                    <thead class="bg-blue-600 text-white">
                        <tr>
                            <th class="px-4 py-2 text-left">No</th>
                            <th class="px-4 py-2 text-left">NORM</th>
                            <th class="px-4 py-2 text-left">NO KUNJUNGAN</th>
                            <th class="px-4 py-2 text-left">Nama</th>
                            <th class="px-4 py-2 text-left">Masuk</th>
                            <th class="px-4 py-2 text-left">DPJP Ranap</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(p, i) in patients" :key="i" class="border-t">
                            <td class="px-4 py-2">{{ (currentPage - 1) * perPage + i + 1 }}</td>
                            <td class="px-4 py-2">{{ p.NORM }}</td>
                            <td class="px-4 py-2">{{ p.KUNJUNGAN }}</td>
                            <td class="px-4 py-2">{{ p.NAMA }}</td>
                            <td class="px-4 py-2">{{ dayjs(p.MASUK).format('DD/MM/YYYY HH:mm') }}</td>
                            <td class="px-4 py-2">{{ p.DPJP_RANAP }}</td>
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
                    Menampilkan pasien {{ (currentPage - 1) * perPage + 1 }} – {{ Math.min(currentPage * perPage,
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
