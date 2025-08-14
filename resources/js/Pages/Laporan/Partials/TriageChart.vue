<script setup>
import { onMounted, watch, ref } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
  data: Array
});

let chartInstance = null;

const renderChart = () => {
  const ctx = document.getElementById('triageChart');
  if (!ctx) return;
  if (chartInstance) chartInstance.destroy();

  chartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: props.data.map(t => t.label),
      datasets: [{
        label: 'Jumlah Pasien',
        data: props.data.map(t => t.count),
        backgroundColor: props.data.map(t => t.color)
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
    }
  });
};

onMounted(() => requestIdleCallback(renderChart));
watch(() => props.data, renderChart, { deep: true });
</script>

<template>
  <div class="relative h-[400px]">
    <canvas id="triageChart"></canvas>
  </div>
</template>
