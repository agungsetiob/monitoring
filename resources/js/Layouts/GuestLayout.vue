<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const isLoading = ref(false);
document.addEventListener('inertia:start', () => {
  isLoading.value = true;
});

document.addEventListener('inertia:finish', () => {
  isLoading.value = false;
});
const currentYear = computed(() => new Date().getFullYear());

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-pattern pt-6 sm:justify-center sm:pt-0"
    >
        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <div class="text-center">
                <Link href="/" class="inline-block">
                    <ApplicationLogo class="h-20 w-20 fill-current text-red-500" />
                </Link>
                <p class="font-bold">Monitoring dan Analisis Serapan Anggaran</p>
            </div>
            <slot />
            <footer class="text-gray-800 py-4 text-center w-full">
                <div class="max-w-7xl mx-auto px-4">
                    <p class="text-sm">&copy; {{ currentYear }} {{ appName }} Kabupaten Tanah Bumbu</p>
                </div>
            </footer>
        </div>
    </div>
</template>
