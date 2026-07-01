<script setup>
import { ref, watch, onMounted } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    data: Array
});

const chartRef = ref(null);
let chartInstance = null;

const renderChart = () => {
    if (!chartRef.value || !props.data?.length) return;

    if (chartInstance) chartInstance.destroy();

    chartInstance = new Chart(chartRef.value, {
        type: 'doughnut',
        data: {
            labels: props.data.map(item => item.JENIS_KEDATANGAN),
            datasets: [{
                data: props.data.map(item => item.JUMLAH),
                backgroundColor: [
                    '#2563eb', '#16a34a', '#dc2626', '#f59e0b', '#9333ea'
                ],
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
};

const exportChartAsImage = () => {
    if (!chartInstance) return;
    const link = document.createElement('a');
    link.href = chartInstance.toBase64Image();
    link.download = 'jenis-kedatangan.png';
    link.click();
};

watch(() => props.data, renderChart, { deep: true });
onMounted(renderChart);
</script>
<template>
    <div class="bg-white rounded-xl shadow border p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">🚑 Jenis Kedatangan</h2>
            <button @click="exportChartAsImage"
                class="p-2 rounded-lg bg-green-100 hover:bg-gray-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 12v6m0 0l-3-3m3 3l3-3M12 4v8" />
                </svg>
            </button>
        </div>
        <div class="h-[350px]">
            <canvas ref="chartRef"></canvas>
        </div>
    </div>
</template>
