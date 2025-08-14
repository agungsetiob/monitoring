<script setup>
import { ref, watch, onMounted } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    data: Array
});

const chartRef = ref(null);
let chartInstance = null;

const renderChart = () => {
    if (!chartRef.value || !props.data) return;

    const labels = props.data.map(d => d.shift);
    const values = props.data.map(d => d.jumlah);

    if (chartInstance) chartInstance.destroy();

    chartInstance = new Chart(chartRef.value, {
        type: 'pie',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: ['#3b82f6', '#f59e0b', '#10b981']
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
};

const exportChart = () => {
    if (!chartRef.value) return;
    const link = document.createElement('a');
    link.href = chartRef.value.toDataURL('image/png');
    link.download = 'chart-per-shift.png';
    link.click();
};

watch(() => props.data, renderChart);
onMounted(renderChart);
</script>

<template>
    <div class="bg-white p-6 md:p-8 rounded-xl shadow-md border border-gray-200">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-700">🕒 Pasien per Shift</h2>
            <button @click="exportChart"
                class="p-2 rounded-lg bg-green-100 hover:bg-gray-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 12v6m0 0l-3-3m3 3l3-3M12 4v8" />
                </svg>
            </button>
        </div>
        <div class="relative h-[300px]">
            <canvas ref="chartRef"></canvas>
        </div>
    </div>
</template>
