<script setup>
import { ref, watch, onMounted } from 'vue';
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

const props = defineProps({
    categories: Object,
    startDate: String,
    endDate: String,
    loadingState: Boolean
});

const chartRef = ref(null);
let chartInstance = null;

const chartColors = {
    '<1h': '#3B82F6',
    '1-3h': '#10B981',
    '3-6h': '#FACC15',
    '6-8h': '#EF4444',
    '>8h': '#111827'
};

const renderChart = () => {
    if (!chartRef.value || !props.categories) return;

    const labels = Object.keys(props.categories);
    const dataValues = Object.values(props.categories);
    const colors = labels.map(label => chartColors[label] ?? '#9CA3AF');

    if (chartInstance) chartInstance.destroy();

    chartInstance = new Chart(chartRef.value, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Jumlah Pasien',
                data: dataValues,
                backgroundColor: colors,
                borderColor: colors,
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
};

watch(() => props.categories, renderChart);
onMounted(renderChart);

const exportChartAsImage = () => {
    if (!chartInstance) return;
    const link = document.createElement('a');
    link.href = chartInstance.toBase64Image();
    link.download = 'chart-los-' + props.startDate + '-' + props.endDate + '.png';
    link.click();
};
</script>

<template>
    <div class="bg-white p-6 md:p-8 rounded-xl shadow-md border border-gray-200 relative">
        <div v-if="props.loadingState"
            class="absolute inset-0 z-10 bg-white bg-opacity-70 flex items-center justify-center rounded-xl">
            <div class="animate-pulse flex flex-col items-center">
                <div class="h-8 w-8 bg-blue-400 rounded-full mb-2"></div>
            </div>
        </div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-800">📈 Grafik LOS</h2>
            <button @click="exportChartAsImage" class="p-2 rounded-lg bg-green-100 hover:bg-gray-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 12v6m0 0l-3-3m3 3l3-3M12 4v8" />
                </svg>
            </button>
        </div>
        <div class="relative h-[500px]">
            <canvas ref="chartRef"></canvas>
        </div>
    </div>
</template>
