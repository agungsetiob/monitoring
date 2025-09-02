<script setup>
import { ref, computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const page = usePage();

const user = page.props.auth.user;
const role = user.role;
//const currentRoute = page.component;

const menuItems = {
  admin: [
    { label: 'Dashboard', route: 'landing-page', icon: ['fas', 'bed-pulse'] },
    { label: 'Resep', route: 'apol.index', icon: ['fas', 'pills'] },
    { label: 'Pelayanan', route: 'resep-klaim-terpisah', icon: ['fas', 'notes-medical'] },
  ],
};

const menus = computed(() => menuItems[role] || []);

const isLoading = ref(false);
document.addEventListener('inertia:start', () => {
  isLoading.value = true;
});

document.addEventListener('inertia:finish', () => {
  isLoading.value = false;
});

const currentYear = computed(() => new Date().getFullYear());
const appName = import.meta.env.VITE_APP_NAME || 'MONALISA';
function isActive(routeName) {
  const current = route().current();
  return current.startsWith(routeName) || current.includes(routeName.split('.')[0]);
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-100 to-green-100 flex flex-col">
    <!-- Top Navigation -->
    <nav class="border-b border-gray-700 bg-blue-700 fixed top-0 left-0 right-0 z-50 h-16">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
          <div class="flex">
            <div class="flex shrink-0 items-center">
              <Link :href="route('landing-page')">
              <img src="/img/logo.png" alt="SIGAP Logo" class="block h-10 w-auto" />
              </Link>
            </div>
          </div>

          <div class="-me-2 flex items-center sm:hidden">
            <button @click="showingNavigationDropdown = !showingNavigationDropdown"
              class="inline-flex items-center justify-center p-2 rounded-md text-gray-300 hover:text-gray-200 hover:bg-blue-500 focus:outline-none focus:bg-blue-500 focus:text-gray-200 transition duration-150 ease-in-out">
              <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path v-if="!showingNavigationDropdown" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16" />
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>
      <!-- Bottom Navigation -->
      <header class="border bg-white shadow fixed bottom-0 left-0 right-0 z-40 h-12 sm:sticky sm:top-16 sm:h-auto">
        <div class="max-w-7xl mx-auto py-2 px-4 sm:px-6 lg:px-8 flex flex-col items-center justify-center">
          <nav class="flex overflow-x-auto whitespace-nowrap scrollbar-hide w-full lg:gap-8 gap-3 justify-center">
            <template v-for="menu in menus" :key="menu.route">
              <Link :href="route(menu.route)" class="flex flex-col items-center text-sm sm:text-base" :class="{
                'border-b-2 border-cyan-500': isActive(menu.route)
              }">
              <font-awesome-icon :icon="menu.icon" :class="[
                'text-lg sm:text-xl',
                isActive(menu.route) ? 'text-cyan-500' : 'text-gray-800'
              ]" />
              <span class="mt-2 font-medium text-gray-800 hover:text-blue-700">{{ menu.label }}</span>
              </Link>
            </template>
          </nav>
        </div>
      </header>
    </nav>

    <div class="flex-1 pt-16 pb-12 sm:pt-16 sm:pb-0 relative">
      <div v-if="isLoading" class="fixed inset-0 z-30 flex flex-col items-center justify-center bg-white bg-opacity-80">
        <video src="/img/loading.webm" autoplay loop muted playsinline />
      </div>
      <main class="h-full overflow-auto">
        <slot />
      </main>
    </div>
    <footer class="text-gray-800 py-4 text-center w-full">
      <div class="max-w-7xl mx-auto px-4">
        <p class="text-sm">&copy; {{ currentYear }} {{ appName }} Kabupaten Tanah Bumbu</p>
      </div>
    </footer>
  </div>
</template>