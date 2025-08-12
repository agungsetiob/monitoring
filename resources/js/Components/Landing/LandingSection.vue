<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const { props } = usePage();
const isAuthenticated = props.auth?.user;
const isLoading = ref(false);

const logoutForm = useForm({});
const loginForm = useForm({});

const onLogout = () => logoutForm.post('/logout');
const onLogin = () => loginForm.get('/login');

document.addEventListener('inertia:start', () => {
  isLoading.value = true;
});
document.addEventListener('inertia:finish', () => {
  isLoading.value = false;
});
</script>

<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-blue-100 to-green-100 p-4">
    <div v-if="isAuthenticated" class="absolute top-4 right-4">
      <button @click="onLogout" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
        Logout
      </button>
    </div>
    <div v-else class="absolute top-4 right-4">
      <button @click="onLogin" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
        Login
      </button>
    </div>

    <div class="text-center mb-12">
      <img src="/img/sigap-obatin.png" alt="RSUD Logo" class="mx-auto h-32 w-auto mb-4">
      <h1 class="text-4xl md:text-6xl font-extrabold text-gray-800 leading-tight mb-4 drop-shadow-md">
        Selamat Datang di
      </h1>
      <p class="text-3xl md:text-5xl font-bold text-teal-600 drop-shadow-sm">
        RSUD dr. H. Andi Abdurrahman Noor
      </p>
      <p class="mt-4 text-xl text-gray-600 max-w-2xl mx-auto">
        Sistem Informasi Gawat Darurat Pasien dan Obat Terintegrasi Nasional
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 w-full max-w-7xl">
      <slot />
    </div>
  </div>

  <div v-if="isLoading" class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-white bg-opacity-80">
    <video src="/img/loading.webm" autoplay loop muted playsinline />
  </div>
</template>
