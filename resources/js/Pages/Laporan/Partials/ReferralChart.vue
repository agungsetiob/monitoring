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

    if (chartInstance) {
        chartInstance.destroy();
    }

    chartInstance = new Chart(chartRef.value, {
        type: 'bar',
        data: {
            labels: props.data.map(
                item =>
                    item.ASAL_RUJUKAN_NAMA ??
                    'Tidak Diketahui'
            ),
            datasets: [
                {
                    label: 'Jumlah Pasien',
                    data: props.data.map(
                        item => item.JUMLAH
                    )
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
};

watch(
    () => props.data,
    renderChart,
    { deep: true }
);

onMounted(renderChart);
</script>

<template>
    <div class="bg-white rounded-xl shadow border p-6">
        <h2 class="text-xl font-bold mb-4">
            🏥 Asal Rujukan
        </h2>

        <div class="h-[500px]">
            <canvas ref="chartRef"></canvas>
        </div>
    </div>
</template>