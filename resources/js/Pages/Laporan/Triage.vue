<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import dayjs from 'dayjs';
import duration from 'dayjs/plugin/duration';
import axios from 'axios';
import DateFilter from '@/Components/DateFilter.vue';
import TriageChart from './Partials/TriageChart.vue';
import TrendChart from './Partials/TrendChart.vue';

dayjs.extend(duration);

const props = defineProps({
  start_date: String,
  end_date: String
});

const isLoading = ref(false);

// Section loading states (for data fetching)
const loadingStates = ref({
  summary: false,
  trend: false,
  los: false
});

// State data dari API
const triageData = ref([]);
const dailyTrend = ref([]);
const percentages = ref({});
const avgLos = ref([]);

const fetchSummaryData = async (startDate, endDate) => {
  loadingStates.value.summary = true;
  try {
    const { data } = await axios.get(route('laporan.triage.summary'), {
      params: { start_date: startDate, end_date: endDate }
    });
    triageData.value = [
      { label: 'P1', count: data.report?.P1 ?? 0, color: '#f87171' },
      { label: 'P2', count: data.report?.P2 ?? 0, color: '#f87171' },
      { label: 'P3', count: data.report?.P3 ?? 0, color: '#facc15' },
      { label: 'P4', count: data.report?.P4 ?? 0, color: '#4ade80' },
      { label: 'P5', count: data.report?.P5 ?? 0, color: '#4ade80' },
      { label: 'DOA', count: data.report?.DOA ?? 0, color: '#9ca3af' }
    ];
    percentages.value = data.percentages;
  } finally {
    loadingStates.value.summary = false;
  }
};

const fetchDailyTrendData = async (startDate, endDate) => {
  loadingStates.value.trend = true;
  try {
    const { data } = await axios.get(route('laporan.triage.daily-trend'), {
      params: { start_date: startDate, end_date: endDate }
    });
    dailyTrend.value = data.daily_trend;
  } finally {
    loadingStates.value.trend = false;
  }
};

const fetchAverageLosData = async (startDate, endDate) => {
  loadingStates.value.los = true;
  try {
    const { data } = await axios.get(route('laporan.triage.average-los'), {
      params: { start_date: startDate, end_date: endDate }
    });
    avgLos.value = data.avg_los;
  } finally {
    loadingStates.value.los = false;
  }
};

const fetchData = async (startDate, endDate) => {
  try {
    await Promise.all([
      fetchSummaryData(startDate, endDate),
      fetchDailyTrendData(startDate, endDate),
      fetchAverageLosData(startDate, endDate)
    ]);
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

const handleFilter = ({ start_date, end_date }) => {
  fetchData(start_date, end_date);
};

const formatLos = (minutes) => {
  const dur = dayjs.duration(minutes, 'minutes');
  return `${dur.hours()} jam ${dur.minutes()} menit`;
};

onMounted(() => {
  fetchData(props.start_date, props.end_date);
});

document.addEventListener('inertia:start', () => {
  isLoading.value = true;
});
document.addEventListener('inertia:finish', () => {
  isLoading.value = false;
});
const triageChartRef = ref(null);

const exportChartAsImage = () => {
  const image = triageChartRef.value?.exportChartAsImage?.();
  if (!image) return;
  const link = document.createElement('a');
  link.href = image;
  link.download = `triage-${props.start_date}-${props.end_date}.png`;
  link.click();
};
const exportChartAsImage2 = () => {
  const image = triageChartRef.value?.exportChartAsImage?.();
  if (!image) return;
  const link = document.createElement('a');
  link.href = image;
  link.download = `triage-harian-${props.start_date}-${props.end_date}.png`;
  link.click();
};
</script>

<template>
  <Head title="Laporan Triage" />
  
  <div v-if="isLoading" class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-white bg-opacity-80">
    <video src="/img/loading.webm" autoplay loop muted playsinline />
  </div>

  <div class="min-h-screen bg-gradient-to-br from-blue-100 to-green-100 flex flex-col items-center justify-center px-8 py-10">
    <div class="w-full bg-white rounded-2xl shadow-xl p-8 space-y-8 max-w-full xl:max-w-8xl">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h1 class="text-3xl font-extrabold text-gray-800 flex items-center gap-3">📊 Laporan Triage Pasien</h1>
        <button @click="$inertia.visit('/laporan')"
          class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-3 rounded-xl transition duration-300 ease-in-out transform hover:scale-105">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5"></path>
            <path d="M12 19l-7-7 7-7"></path>
          </svg>
          Kembali
        </button>
      </div>

      <DateFilter :initial-start-date="props.start_date" :initial-end-date="props.end_date" @filter="handleFilter" />

      <!-- Grafik Triage Section -->
      <div class="bg-white p-6 md:p-8 rounded-xl shadow-md border border-gray-200 relative">
        <div v-if="loadingStates.summary"
          class="absolute inset-0 z-10 bg-white bg-opacity-70 flex items-center justify-center rounded-xl">
          <div class="animate-pulse flex flex-col items-center">
            <div class="h-8 w-8 bg-blue-400 rounded-full mb-2"></div>
          </div>
        </div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-800">📈 Grafik Triage</h2>
            <button @click="exportChartAsImage"
                class="p-2 rounded-lg bg-green-100 hover:bg-gray-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 12v6m0 0l-3-3m3 3l3-3M12 4v8" />
                </svg>
            </button>
        </div>
        <TriageChart :data="triageData" ref="triageChartRef"/>
      </div>

      <!-- Tren Harian Section -->
      <div class="bg-white p-6 md:p-8 rounded-xl shadow-md border border-gray-200 relative">
        <div v-if="loadingStates.trend"
          class="absolute inset-0 z-10 bg-white bg-opacity-70 flex items-center justify-center rounded-xl">
          <div class="animate-pulse flex flex-col items-center">
            <div class="h-8 w-8 bg-blue-400 rounded-full mb-2"></div>
          </div>
        </div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-800">📈 Tren Harian</h2>
            <button @click="exportChartAsImage2"
                class="p-2 rounded-lg bg-green-100 hover:bg-gray-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 12v6m0 0l-3-3m3 3l3-3M12 4v8" />
                </svg>
            </button>
        </div>
        <TrendChart :data="dailyTrend" :colors="triageData.map(t => t.color)" ref="triageChartRef"/>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Persentase Triage Section -->
        <div class="bg-white p-6 md:p-8 rounded-xl shadow-md border border-gray-200 relative">
          <div v-if="loadingStates.summary"
            class="absolute inset-0 z-10 bg-white bg-opacity-70 flex items-center justify-center rounded-xl">
            <div class="animate-pulse flex flex-col items-center">
              <div class="h-8 w-8 bg-blue-400 rounded-full mb-2"></div>
            </div>
          </div>
          <h2 class="text-xl font-bold mb-4 text-gray-700">📊 Persentase Triage</h2>
          <table class="w-full table-auto border-collapse border border-gray-300 rounded-lg overflow-hidden">
            <thead class="bg-gray-100">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-800">Kategori</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-800">Persentase (%)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(value, key) in percentages" :key="key" class="border-b border-gray-200 last:border-b-0">
                <td class="px-4 py-3 text-gray-700">{{ key }}</td>
                <td class="px-4 py-3 text-gray-700">{{ value }}%</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Rata-rata LOS Section -->
        <div class="bg-white p-6 md:p-8 rounded-xl shadow-md border border-gray-200 relative">
          <div v-if="loadingStates.los"
            class="absolute inset-0 z-10 bg-white bg-opacity-70 flex items-center justify-center rounded-xl">
            <div class="animate-pulse flex flex-col items-center">
              <div class="h-8 w-8 bg-blue-400 rounded-full mb-2"></div>
            </div>
          </div>
          <h2 class="text-xl font-bold mb-4 text-gray-700">🏥 Rata-rata LOS</h2>
          <table class="w-full table-auto border-collapse border border-gray-300 rounded-lg overflow-hidden">
            <thead class="bg-gray-100">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-800">Kategori</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-800">LOS Rata-rata</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in avgLos" :key="row.kategori" class="border-b border-gray-200 last:border-b-0">
                <td class="px-4 py-3 text-gray-700">{{ row.kategori }}</td>
                <td class="px-4 py-3 text-gray-700">{{ formatLos(row.avg_los_minutes) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>