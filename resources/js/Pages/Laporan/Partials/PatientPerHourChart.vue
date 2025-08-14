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

    const labels = props.data.map(d => String(d.jam).padStart(2, '0') + ':00');
    const values = props.data.map(d => d.jumlah);
    const colors = values.map(v => {
        if (v > 11) return '#dc2626'; // merah
        if (v > 5) return '#f97316';  // oranye
        return '#16a34a';            // hijau
    });

    if (chartInstance) chartInstance.destroy();

    chartInstance = new Chart(chartRef.value, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Jumlah Pasien Masuk',
                data: values,
                backgroundColor: colors,
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
};

const exportChart = () => {
    if (!chartRef.value) return;
    const link = document.createElement('a');
    link.href = chartRef.value.toDataURL('image/png');
    link.download = 'chart-per-jam' + dayjs().format('YYYY-MM-DD') + '.png';
    link.click();
};

watch(() => props.data, renderChart);
onMounted(renderChart);
</script>

<template>
    <div class="bg-white p-6 md:p-8 rounded-xl shadow-md border border-gray-200">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-700">📈 Pasien per Jam</h2>
            <button @click="exportChart"
                class="p-2 rounded-lg bg-green-100 hover:bg-gray-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 12v6m0 0l-3-3m3 3l3-3M12 4v8" />
                </svg>
            </button>
        </div>
        <div class="relative h-[400px]">
            <canvas ref="chartRef"></canvas>
        </div>
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
</template>
