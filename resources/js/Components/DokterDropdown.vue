<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  dokterList: {
    type: Array,
    default: () => []
  },
  selectedDokter: {
    type: Object,
    default: null
  },
  isLoading: {
    type: Boolean,
    default: false
  },
  placeholder: {
    type: String,
    default: 'Pilih dokter'
  },
  emptyMessage: {
    type: String,
    default: 'Tidak ada dokter tersedia'
  },
  emptySubMessage: {
    type: String,
    default: 'Silakan pilih tanggal terlebih dahulu'
  }
});

const emit = defineEmits(['select', 'toggle']);

const isOpen = ref(false);

const toggleDropdown = () => {
  if (props.isLoading) return;
  isOpen.value = !isOpen.value;
  emit('toggle', isOpen.value);
};

const selectDokter = (dokter) => {
  emit('select', dokter);
  isOpen.value = false;
};

const handleClickOutside = (event) => {
  const dropdownContainer = event.target.closest('[data-dropdown="dokter"]');
  if (!dropdownContainer) {
    isOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <div class="relative" data-dropdown="dokter">
    <!-- Dropdown Button -->
    <div 
      @click="toggleDropdown"
      class="px-3 py-2 w-full bg-white rounded-lg border border-gray-300 transition-all duration-200 cursor-pointer hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      :class="{
        'border-blue-500 ring-2 ring-blue-500': isOpen,
        'bg-gray-100 cursor-not-allowed': isLoading
      }"
    >
      <div v-if="selectedDokter" class="flex justify-between items-center">
        <div>
          <div class="text-sm font-bold text-gray-900">{{ selectedDokter.namaDokter }}</div>
          <div class="text-xs text-gray-600">
            {{ selectedDokter.jadwalPraktek }} • Kapasitas: {{ selectedDokter.kapasitas }}
          </div>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
      </div>
      
      <div v-else class="flex justify-between items-center">
        <span class="text-gray-500">{{ placeholder }}</span>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
      </div>
    </div>

    <!-- Dropdown Options -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-show="isOpen" class="overflow-y-auto absolute z-50 mt-2 w-full max-h-60 bg-white rounded-lg border border-gray-200 shadow-lg">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex justify-center items-center py-8">
          <svg class="w-6 h-6 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span class="ml-2 text-gray-600">Memuat dokter...</span>
        </div>
        
        <!-- Doctor List -->
        <div v-else-if="dokterList.length > 0">
          <div 
            v-for="dokter in dokterList" 
            :key="dokter.kodeDokter"
            @click="selectDokter(dokter)"
            class="px-4 py-3 border-b border-gray-100 transition-colors duration-150 cursor-pointer last:border-b-0 hover:bg-blue-50"
            :class="{
              'bg-blue-50 border-blue-200': selectedDokter?.kodeDokter === dokter.kodeDokter
            }"
          >
            <div class="font-semibold text-gray-900">{{ dokter.namaDokter }}</div>
            <div class="mt-1 text-sm text-gray-600">
              <span class="inline-flex items-center">
                <svg class="mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ dokter.jadwalPraktek }}
              </span>
              <span class="inline-flex items-center ml-4">
                <svg class="mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Kapasitas: {{ dokter.kapasitas }}
              </span>
            </div>
          </div>
        </div>
        
        <!-- Empty State -->
        <div v-else class="flex flex-col justify-center items-center py-8 text-center">
          <svg class="mb-3 w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
          <p class="font-medium text-gray-600">{{ emptyMessage }}</p>
          <p class="mt-1 text-sm text-gray-500">{{ emptySubMessage }}</p>
        </div>
      </div>
    </Transition>
  </div>
</template>