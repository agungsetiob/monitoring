<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
  <Head title="RSUD dr. H. Abdurrahman Noor" />

  <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-blue-100 to-green-100 p-4">
    <div class="text-center mb-12">
      <img src="/img/logo.png" alt="RSUD Logo" class="mx-auto h-32 w-auto mb-4">
      <h1 class="text-4xl md:text-6xl font-extrabold text-gray-800 leading-tight mb-4 drop-shadow-md">
        Selamat Datang di
      </h1>
      <p class="text-3xl md:text-5xl font-bold text-teal-600 drop-shadow-sm">
        RSUD dr. H. Andi Abdurrahman Noor
      </p>
      <p class="mt-4 text-xl text-gray-600 max-w-2xl mx-auto">
        Sistem Informasi Monitoring Pasien & Antrean Farmasi IGD
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-4xl">
      <Link :href="route('pasien-igd.index')"
        class="group flex flex-col items-center justify-center p-8 bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 ease-in-out transform hover:-translate-y-2 border-b-4 border-blue-500 hover:border-blue-700 cursor-pointer">
        <div class="text-6xl text-blue-600 group-hover:text-blue-800 transition-colors duration-300 mb-4">
          <font-awesome-icon icon="bed-pulse" class="h-24 w-24" />
        </div>
        <h2 class="text-3xl font-bold text-gray-800 mb-2 group-hover:text-blue-800 transition-colors duration-300">
          Monitoring Pasien IGD
        </h2>
        <p class="text-lg text-gray-600 text-center">
          Lihat daftar pasien yang sedang ditangani di Unit Gawat Darurat.
        </p>
      </Link>

      <Link :href="route('antrean-farmasi.index')"
        class="group flex flex-col items-center justify-center p-8 bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 ease-in-out transform hover:-translate-y-2 border-b-4 border-teal-500 hover:border-teal-700 cursor-pointer">
        <div class="text-6xl text-teal-600 group-hover:text-teal-800 transition-colors duration-300 mb-4">
          <font-awesome-icon icon="prescription-bottle-medical" class="h-24 w-24" />
        </div>
        <h2 class="text-3xl font-bold text-gray-800 mb-2 group-hover:text-teal-800 transition-colors duration-300">
          Antrean Farmasi
        </h2>
        <p class="text-lg text-gray-600 text-center">
          Pantau status antrean resep obat di Depo Farmasi.
        </p>
      </Link>
    </div>
  </div>
</template>