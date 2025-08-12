<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import Chart from 'chart.js/auto';
import dayjs from 'dayjs';
import duration from 'dayjs/plugin/duration';
import DateFilter from '@/Components/DateFilter.vue';
dayjs.extend(duration);

const props = defineProps({
  report: Object,
  daily_trend: Array,
  percentages: Object,
  avg_los: Array,
  start_date: String,
  end_date: String
});

const isLoading = ref(false);
document.addEventListener('inertia:start', () => {
  isLoading.value = true;
});
document.addEventListener('inertia:finish', () => {
  isLoading.value = false;
});

const triageData = ref([
  { label: 'P1', count: props.report?.P1 ?? 0, color: '#f87171' },
  { label: 'P2', count: props.report?.P2 ?? 0, color: '#f87171' },
  { label: 'P3', count: props.report?.P3 ?? 0, color: '#facc15' },
  { label: 'P4', count: props.report?.P4 ?? 0, color: '#4ade80' },
  { label: 'P5', count: props.report?.P5 ?? 0, color: '#4ade80' },
  { label: 'DOA', count: props.report?.DOA ?? 0, color: '#9ca3af' }
]);
const dailyTrendData = ref(props.daily_trend);

let triageChartInstance = null;
let dailyTrendChartInstance = null;

const handleFilter = (dates) => {
  router.get(route('laporan.triage'), dates);
};

const renderTriageChart = () => {
  const ctx1 = document.getElementById('triageChart');
  if (!ctx1) return;

  if (triageChartInstance) triageChartInstance.destroy();

  triageChartInstance = new Chart(ctx1, {
    type: 'bar',
    data: {
      labels: triageData.value.map(t => t.label),
      datasets: [{
        label: 'Jumlah Pasien',
        data: triageData.value.map(t => t.count),
        backgroundColor: triageData.value.map(t => t.color)
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
    }
  });
};

const renderDailyTrendChart = () => {
  const ctx2 = document.getElementById('dailyTrendChart');
  if (!ctx2) return;

  if (dailyTrendChartInstance) dailyTrendChartInstance.destroy();

  dailyTrendChartInstance = new Chart(ctx2, {
    type: 'line',
    data: {
      labels: dailyTrendData.value.map(d => d.tanggal),
      datasets: ['P1', 'P2', 'P3', 'P4', 'P5', 'DOA'].map((cat, i) => ({
        label: cat,
        data: dailyTrendData.value.map(d => d[cat]),
        borderColor: triageData.value[i].color,
        fill: false,
        tension: 0.1
      }))
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
    }
  });
};

onMounted(() => {
  renderTriageChart();
  renderDailyTrendChart();
});

watch([() => props.report, () => props.daily_trend], () => {
  triageData.value = [
    { label: 'P1', count: props.report?.P1 ?? 0, color: '#f87171' },
    { label: 'P2', count: props.report?.P2 ?? 0, color: '#f87171' },
    { label: 'P3', count: props.report?.P3 ?? 0, color: '#facc15' },
    { label: 'P4', count: props.report?.P4 ?? 0, color: '#4ade80' },
    { label: 'P5', count: props.report?.P5 ?? 0, color: '#4ade80' },
    { label: 'DOA', count: props.report?.DOA ?? 0, color: '#9ca3af' }
  ];
  dailyTrendData.value = props.daily_trend;
  renderTriageChart();
  renderDailyTrendChart();
}, { deep: true });

const formatLos = (minutes) => {
  const dur = dayjs.duration(minutes, 'minutes');
  const jam = dur.hours();
  const menit = dur.minutes();
  return `${jam} jam ${menit} menit`;
};
</script>
<template>
  <Head title="Laporan Triage" />
  <div
    class="min-h-screen bg-gradient-to-br from-blue-100 to-green-100 flex flex-col items-center justify-center px-8 py-10">
    <div class="w-full bg-white rounded-2xl shadow-xl p-8 space-y-8 max-w-full xl:max-w-8xl">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h1 class="text-3xl font-extrabold text-gray-800 flex items-center gap-3">
          📊 Laporan Triage Pasien
        </h1>
        <button @click="router.visit('/laporan')"
          class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-3 rounded-xl transition duration-300 ease-in-out transform hover:scale-105">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5"></path>
            <path d="M12 19l-7-7 7-7"></path>
          </svg>
          Kembali
        </button>
      </div>
      <DateFilter
        :initial-start-date="props.start_date"
        :initial-end-date="props.end_date"
        @filter="handleFilter" />
      <div class="bg-white p-6 md:p-8 rounded-xl shadow-md border border-gray-200">
        <h2 class="text-xl font-bold mb-4 text-gray-700">📊 Grafik Triage</h2>
        <div class="relative h-[400px]">
          <canvas id="triageChart"></canvas>
        </div>
      </div>
      <div class="bg-white p-6 md:p-8 rounded-xl shadow-md border border-gray-200">
        <h2 class="text-xl font-bold mb-4 text-gray-700">📈 Tren Harian</h2>
        <div class="relative h-[400px]">
          <canvas id="dailyTrendChart"></canvas>
        </div>
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="bg-white p-6 md:p-8 rounded-xl shadow-md border border-gray-200">
          <h2 class="text-xl font-bold mb-4 text-gray-700">📊 Persentase Triage</h2>
          <table class="w-full table-auto border-collapse border border-gray-300 rounded-lg overflow-hidden">
            <thead class="bg-gray-100">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-800">Kategori</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-800">Persentase (%)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(value, key) in props.percentages" :key="key" class="border-b border-gray-200 last:border-b-0">
                <td class="px-4 py-3 text-gray-700">{{ key }}</td>
                <td class="px-4 py-3 text-gray-700">{{ value }}%</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="bg-white p-6 md:p-8 rounded-xl shadow-md border border-gray-200">
          <h2 class="text-xl font-bold mb-4 text-gray-700">🏥 Rata-rata LOS (Menit)</h2>
          <table class="w-full table-auto border-collapse border border-gray-300 rounded-lg overflow-hidden">
            <thead class="bg-gray-100">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-800">Kategori</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-800">LOS Rata-rata</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in props.avg_los" :key="row.kategori" class="border-b border-gray-200 last:border-b-0">
                <td class="px-4 py-3 text-gray-700">{{ row.kategori }}</td>
                <td class="px-4 py-3 text-gray-700">{{ formatLos(row.avg_los_minutes) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-80">
    <video src="/img/loading.webm" autoplay loop muted playsinline />
  </div>
</template>