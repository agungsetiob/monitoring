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
  <div class="flex flex-col justify-center items-center p-4 min-h-screen bg-gradient-to-br from-blue-100 to-green-100">
    <div v-if="isAuthenticated" class="absolute top-4 right-4">
      <button @click="onLogout" class="px-4 py-2 text-white bg-red-600 rounded-md transition hover:bg-red-700">
        Logout
      </button>
    </div>
    <div v-else class="absolute top-4 right-4">
      <button @click="onLogin" class="px-4 py-2 text-white bg-green-600 rounded-md transition hover:bg-green-700">
        Login
      </button>
    </div>

    <div class="mb-12 text-center">
      <img src="/img/sigap-obatin.png" alt="RSUD Logo" class="mx-auto mb-4 w-auto h-32">
      <h1 class="mb-4 text-4xl font-extrabold leading-tight text-gray-800 drop-shadow-md md:text-6xl">
        Selamat Datang di
      </h1>
      <p class="text-3xl font-bold text-teal-600 drop-shadow-sm md:text-5xl">
        RSUD dr. H. Andi Abdurrahman Noor
      </p>
      <p class="mx-auto mt-4 max-w-2xl text-xl text-gray-600">
        Sistem Informasi Gawat Darurat Pasien dan Obat Terintegrasi Nasional
      </p>
    </div>

    <div class="grid grid-cols-1 gap-8 w-full max-w-7xl md:grid-cols-4">
      <slot />
    </div>
  </div>

  <div v-if="isLoading" class="flex fixed inset-0 z-50 flex-col justify-center items-center bg-white bg-opacity-80">
    <video src="/img/loading.webm" autoplay loop muted playsinline />
  </div>
</template>
