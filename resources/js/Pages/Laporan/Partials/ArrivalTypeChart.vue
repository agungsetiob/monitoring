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
        type: 'doughnut',
        data: {
            labels: props.data.map(
                item => item.JENIS_KEDATANGAN
            ),
            datasets: [
                {
                    data: props.data.map(
                        item => item.JUMLAH
                    )
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
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
            🚑 Jenis Kedatangan
        </h2>

        <div class="h-[350px]">
            <canvas ref="chartRef"></canvas>
        </div>
    </div>
</template>