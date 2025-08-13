<template>

    <Head>
        <title>Login</title>
    </Head>

    <div class="min-h-screen bg-gradient-to-br from-blue-100 to-green-100 flex items-center justify-center p-4 sm:p-8">
        <div class="w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row">
            <div class="hidden md:flex md:w-1/2 bg-gradient-to-br from-indigo-300 to-blue-100 overflow-hidden">
                <div class="w-full h-full flex items-center justify-center relative">
                    <img src="/img/bupati.webp" alt="Foto Bupati"
                        class="w-full h-full md:h-auto md:object-contain object-cover object-center" />
                </div>
            </div>
            <div class="w-full md:w-1/2 p-6 sm:p-8 lg:p-10 space-y-6">
                <div class="flex justify-center gap-4 items-center mb-6">
                    <img src="/img/sigap-obatin.png" alt="Logo Sigap Obatin" class="h-12 sm:h-14 object-contain" />
                    <img src="/img/logo.png" alt="Ilustrasi Beraksi" class="h-12 sm:h-14 object-contain" />
                </div>

                <div class="text-center">
                    <h1 class="text-xl font-semibold">Sistem Informasi Gawat Darurat Pasien dan Obat Terintegrasi
                        Nasional</h1>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2" for="email">Email</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                    <path d="M22 6L12 13L2 6"></path>
                                </svg>
                            </span>
                            <input v-model="form.email" type="email" id="email" autocomplete="username"
                                placeholder="masukkan@email.com"
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition duration-200" />
                        </div>
                        <p v-if="form.errors.email" class="text-sm text-red-600 mt-2">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2" for="password">Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </span>
                            <input v-model="form.password" type="password" id="password" autocomplete="current-password"
                                placeholder="••••••••"
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition duration-200" />
                        </div>
                        <p v-if="form.errors.password" class="text-sm text-red-600 mt-2">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center text-sm text-gray-700">
                            <input type="checkbox" v-model="form.remember"
                                class="mr-2 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            Ingat saya
                        </label>
                    </div>

                    <button type="submit" :disabled="form.processing"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl transition duration-300 transform hover:scale-105 disabled:opacity-50 disabled:scale-100 flex items-center justify-center gap-2">
                        <svg v-if="form.processing" class="animate-spin h-5 w-5 text-white"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span v-else>Masuk</span>
                    </button>
                </form>

                <p class="text-xs text-center text-gray-500 mt-3">
                    &copy; {{ new Date().getFullYear() }} RSUD dr. H. Andi Abdurrahman Noor.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm, Head } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login');
};
</script>

<style>
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.6s ease-out forwards;
    opacity: 0;
}

.delay-150 {
    animation-delay: 0.15s;
}
</style>