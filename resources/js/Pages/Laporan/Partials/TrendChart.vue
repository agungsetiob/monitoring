<script setup>
import { onMounted, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
  data: Array,
  colors: Array
});

let chartInstance = null;

const renderChart = () => {
  const ctx = document.getElementById('dailyTrendChart');
  if (!ctx) return;
  if (chartInstance) chartInstance.destroy();

  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: props.data.map(d => d.tanggal),
      datasets: ['P1','P2','P3','P4','P5','DOA'].map((cat, i) => ({
        label: cat,
        data: props.data.map(d => d[cat]),
        borderColor: props.colors[i],
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

onMounted(() => requestIdleCallback(renderChart));
watch(() => props.data, renderChart, { deep: true });
const exportChartAsImage = () => {
  if (!chartInstance) return;
  return chartInstance.toBase64Image();
};

defineExpose({ exportChartAsImage });
</script>

<template>
  <div class="relative h-[400px]">
    <canvas id="dailyTrendChart"></canvas>
  </div>
</template>
