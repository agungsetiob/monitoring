<template>

  <Head title="Laporan" />
  <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-blue-100 to-green-100 p-8">
    <div class="w-full max-w-7xl bg-white rounded-2xl shadow-2xl p-10 space-y-12">
      <div class="flex flex-col sm:flex-row items-center sm:justify-between space-y-4 sm:space-y-0">
        <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight flex items-center gap-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-indigo-500" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
            <path d="M12 10v4m-2-2h4"></path>
          </svg>
          Laporan IGD
        </h1>
        <button @click="router.visit('/')"
          class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-3 rounded-xl transition duration-300 ease-in-out transform hover:scale-105">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5"></path>
            <path d="M12 19l-7-7 7-7"></path>
          </svg>
          Kembali
        </button>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        <ReportCard title="Rekap Triage" :link="route('laporan.triage')" />
        <ReportCard title="LOS IGD" :link="route('laporan.los')" />
        <ReportCard title="Lanjut Rawat Inap" labelColor="text-red-600" :link="route('laporan.igd-ranap')" />
        <ReportCard title="Kepadatan IGD" labelColor="text-red-600" :link="route('laporan.kepadatan-igd')" />
      </div>
    </div>
  </div>

  <div v-if="isLoading" class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-white bg-opacity-80">
    <video src="/img/loading.webm" autoplay loop muted playsinline />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ReportCard from './Partials/ReportCard.vue';
const isLoading = ref(false);
document.addEventListener('inertia:start', () => {
  isLoading.value = true;
});
document.addEventListener('inertia:finish', () => {
  isLoading.value = false;
});
</script>