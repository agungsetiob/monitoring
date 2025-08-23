<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import dayjs from 'dayjs';

const isLoading = ref(false);
const errorMessage = ref('');

const searchForm = reactive({
  no_kartu: '',
  bulan: String(new Date().getMonth() + 1).padStart(2, '0'),
  tahun: String(new Date().getFullYear()),
  filter: '2'
});

const resetForm = () => {
  searchForm.no_kartu = '';
  searchForm.bulan = String(new Date().getMonth() + 1).padStart(2, '0');
  searchForm.tahun = String(new Date().getFullYear());
  searchForm.filter = '2';
  errorMessage.value = '';
};

const searchResult = ref(null);

const cariData = async () => {
  if (!searchForm.no_kartu || !searchForm.bulan || !searchForm.tahun) {
    errorMessage.value = 'Nomor kartu, bulan, dan tahun harus diisi';
    return;
  }

  isLoading.value = true;
  errorMessage.value = '';
  searchResult.value = null;
  
  try {
    const response = await axios.post('/rencana-kontrol/cari-data', searchForm);
    
    if (response.data.success) {
      searchResult.value = response.data.data;
      errorMessage.value = '';
    } else {
      errorMessage.value = response.data.message;
      searchResult.value = null;
    }
  } catch (error) {
    console.error('Error saat mencari data:', error);
    errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan saat mencari data';
    searchResult.value = null;
  } finally {
    isLoading.value = false;
  }
};

const editRencanaKontrol = (item) => {
  // Redirect to update page with selected item data
  const searchData = encodeURIComponent(JSON.stringify(item));
  router.visit(`/rencana-kontrol/update?data=${searchData}`);
};

const printSuratKontrol = (item) => {
  // Implementasi print surat kontrol
  if (item.noSuratKontrol) {
    // Bisa redirect ke halaman print atau buka window baru
    window.open(`/rencana-kontrol/print/${item.noSuratKontrol}`, '_blank');
  } else {
    errorMessage.value = 'Nomor surat kontrol tidak tersedia';
  }
};
</script>

<template>
  <Head title="Update Rencana Kontrol" />
  
  <div class="p-4 min-h-screen bg-pattern md:p-6">
    <div class="mx-auto max-w-4xl">
      <!-- Header -->
      <div class="flex flex-col px-6 py-4 mb-6 text-white bg-gradient-to-r from-teal-500 to-blue-500 rounded-xl shadow-2xl md:flex-row md:items-center md:justify-between">
        <h1 class="mb-2 text-2xl font-extrabold md:text-3xl md:mb-0">
          Update Rencana Kontrol
        </h1>
        
        <button @click="router.visit('/')" 
          class="inline-flex gap-2 items-center px-5 py-2 font-semibold text-white bg-indigo-600 rounded-xl shadow-lg transition duration-300 ease-in-out transform hover:bg-indigo-700 hover:scale-105">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5"></path>
            <path d="M12 19l-7-7 7-7"></path>
          </svg>
          Kembali
        </button>
      </div>

      <!-- Error Message -->
      <div v-if="errorMessage" class="px-4 py-3 mb-6 text-red-700 bg-red-100 rounded-lg border border-red-400">
        <div class="flex items-center">
          <svg class="mr-2 w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
          </svg>
          <span class="font-medium">{{ errorMessage }}</span>
        </div>
      </div>

      <!-- Search Form -->
      <div class="p-6 mb-6 bg-white rounded-xl shadow-lg">
        <h2 class="mb-4 text-xl font-bold text-gray-800">Cari Data Rencana Kontrol</h2>
        
        <form @submit.prevent="cariData" class="space-y-4">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label for="no_kartu" class="block mb-2 text-sm font-medium text-gray-700">
                Nomor Kartu BPJS
              </label>
              <input 
                id="no_kartu"
                v-model="searchForm.no_kartu" 
                type="text" 
                class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Masukkan nomor kartu BPJS"
                required
              >
            </div>
            
            <div>
              <label for="bulan" class="block mb-2 text-sm font-medium text-gray-700">
                Bulan
              </label>
              <select 
                id="bulan"
                v-model="searchForm.bulan" 
                class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required
              >
                <option value="01">Januari</option>
                <option value="02">Februari</option>
                <option value="03">Maret</option>
                <option value="04">April</option>
                <option value="05">Mei</option>
                <option value="06">Juni</option>
                <option value="07">Juli</option>
                <option value="08">Agustus</option>
                <option value="09">September</option>
                <option value="10">Oktober</option>
                <option value="11">November</option>
                <option value="12">Desember</option>
              </select>
            </div>
            
            <div>
              <label for="tahun" class="block mb-2 text-sm font-medium text-gray-700">
                Tahun
              </label>
              <input 
                id="tahun"
                v-model="searchForm.tahun" 
                type="text" 
                minlength="4"
                maxlength="4"
                pattern="[0-9]{4}"
                class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="YYYY"
                required
              >
            </div>
          </div>
          
          <div>
            <label for="filter" class="block mb-2 text-sm font-medium text-gray-700">
              Filter
            </label>
            <select 
              id="filter"
              v-model="searchForm.filter" 
              class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Pilih Filter</option>
              <option value="1">Tanggal Entri</option>
              <option value="2">Tanggal Rencana Kontrol</option>
            </select>
          </div>
          
          <div class="flex gap-3">
            <button 
              type="submit" 
              :disabled="isLoading"
              class="flex gap-2 items-center px-6 py-2 font-semibold text-white bg-blue-600 rounded-lg transition duration-300 hover:bg-blue-700 disabled:bg-blue-400"
            >
              <svg v-if="isLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ isLoading ? 'Mencari...' : 'Cari Data' }}
            </button>
            
            <button 
              type="button" 
              @click="resetForm"
              class="px-6 py-2 font-semibold text-white bg-gray-500 rounded-lg transition duration-300 hover:bg-gray-600"
            >
              Reset
            </button>
          </div>
        </form>
      </div>

      <!-- Search Result -->
      <div v-if="searchResult && searchResult.length > 0" class="p-6 mb-6 bg-white rounded-xl shadow-lg">
        <h3 class="mb-4 text-lg font-bold text-gray-800">Data Rencana Kontrol Ditemukan ({{ searchResult.length }} data)</h3>
        
        <div class="space-y-4">
          <div v-for="(item, index) in searchResult" :key="index" class="p-4 rounded-lg border border-gray-200">
            <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
              <div class="space-y-2">
                <div><span class="font-medium">No. Surat Kontrol:</span> {{ item.noSuratKontrol || '-' }}</div>
                <div><span class="font-medium">Nama Peserta:</span> {{ item.nama || '-' }}</div>
                <div><span class="font-medium">No. Kartu:</span> {{ item.noKartu || '-' }}</div>
                <div><span class="font-medium">Kode Poli:</span> {{ item.kodePoli || '-' }}</div>
              </div>
              <div class="space-y-2">
                <div><span class="font-medium">Nama Poli:</span> {{ item.namaPoli || '-' }}</div>
                <div><span class="font-medium">Nama Dokter:</span> {{ item.namaDokter || '-' }}</div>
                <div><span class="font-medium">Tgl. Rencana:</span> {{ item.tglRencanaKontrol ? dayjs(item.tglRencanaKontrol).format('DD-MM-YYYY') : '-' }}</div>
                <div><span class="font-medium">Tgl. Terbit:</span> {{ item.tglTerbitKontrol ? dayjs(item.tglTerbitKontrol).format('DD-MM-YYYY') : '-' }}</div>
              </div>
            </div>
            
            <div class="flex gap-2 mt-4">
              <button 
                @click="editRencanaKontrol(item)"
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg transition duration-300 hover:bg-blue-700"
              >
                Edit
              </button>
              <button 
                @click="printSuratKontrol(item)"
                class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg transition duration-300 hover:bg-green-700"
              >
                Print Surat
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- No Data Found -->
      <div v-else-if="searchResult && searchResult.length === 0" class="p-6 mb-6 bg-yellow-50 rounded-xl border border-yellow-200">
        <div class="flex items-center">
          <svg class="mr-2 w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
          </svg>
          <span class="font-medium text-yellow-800">Tidak ada data rencana kontrol ditemukan untuk kriteria pencarian tersebut.</span>
        </div>
      </div>


    </div>
  </div>
</template>