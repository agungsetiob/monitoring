<script setup>
import { ref, watch, onMounted } from 'vue';
import Chart from 'chart.js/auto';
import dayjs from 'dayjs';

const props = defineProps({
    data: Array
});

const chartRef = ref(null);
let chartInstance = null;

const renderChart = () => {
    if (!chartRef.value || !props.data) return;

    const labels = props.data.map(row => row.dokter);
    const values = props.data.map(row => row.total);

    if (chartInstance) chartInstance.destroy();

    chartInstance = new Chart(chartRef.value, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Jumlah Pasien',
                data: values,
                backgroundColor: '#FF6384',
                borderColor: '#FF6384',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } }
        }
    });
};

const exportChartAsImage = () => {
    if (!chartInstance) return;
    const link = document.createElement('a');
    link.href = chartInstance.toBase64Image();
    link.download = 'perdokter-' +  dayjs().format('YYYY-MM-DD') + '.png';
    link.click();
};

watch(() => props.data, renderChart);
onMounted(renderChart);
</script>

<template>
    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-800">👨‍⚕️ Pasien per Dokter</h2>
            <button @click="exportChartAsImage"
                class="p-2 rounded-lg bg-green-100 hover:bg-gray-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
