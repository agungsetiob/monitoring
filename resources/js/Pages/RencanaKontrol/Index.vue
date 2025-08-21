<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import dayjs from 'dayjs';

const isLoading = ref(false);
const searchResult = ref(null);
const poliList = ref([]);
const dokterList = ref([]);
const showUpdateForm = ref(false);
const updateSuccess = ref(false);
const errorMessage = ref('');

const searchForm = reactive({
  no_kartu: '',
  tanggal_sep: ''
});

const updateForm = reactive({
  no_sep: '',
  no_kartu: '',
  tanggal_rencana: '',
  poli_kontrol: '',
  dokter: '',
  user: 'admin' // Default user, bisa disesuaikan dengan auth user
});

const resetForms = () => {
  searchForm.no_kartu = '';
  searchForm.tanggal_sep = '';
  updateForm.no_sep = '';
  updateForm.no_kartu = '';
  updateForm.tanggal_rencana = '';
  updateForm.poli_kontrol = '';
  updateForm.dokter = '';
  searchResult.value = null;
  showUpdateForm.value = false;
  updateSuccess.value = false;
  errorMessage.value = '';
};

const cariData = async () => {
  if (!searchForm.no_kartu || !searchForm.tanggal_sep) {
    errorMessage.value = 'Nomor kartu dan tanggal SEP harus diisi';
    return;
  }

  isLoading.value = true;
  errorMessage.value = '';
  
  try {
    const response = await axios.post('/rencana-kontrol/cari-data', searchForm);
    
    if (response.data.success) {
      searchResult.value = response.data.data;
      // Pre-fill update form
      updateForm.no_sep = response.data.data.no_sep;
      updateForm.no_kartu = response.data.data.no_kartu;
      showUpdateForm.value = true;
      await loadPoliList();
    } else {
      errorMessage.value = response.data.message;
      searchResult.value = null;
      showUpdateForm.value = false;
    }
  } catch (error) {
    console.error('Error saat mencari data:', error);
    errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan saat mencari data';
    searchResult.value = null;
    showUpdateForm.value = false;
  } finally {
    isLoading.value = false;
  }
};

const loadPoliList = async () => {
  try {
    const response = await axios.get('/rencana-kontrol/poli-list');
    if (response.data.success) {
      poliList.value = response.data.data;
    }
  } catch (error) {
    console.error('Error saat mengambil daftar poli:', error);
  }
};

const loadDokterList = async () => {
  if (!updateForm.poli_kontrol || !updateForm.tanggal_rencana) {
    return;
  }

  try {
    const response = await axios.get('/rencana-kontrol/dokter-list', {
      params: {
        kode_poli: updateForm.poli_kontrol,
        tanggal: updateForm.tanggal_rencana
      }
    });
    
    if (response.data.success) {
      dokterList.value = response.data.data;
    }
  } catch (error) {
    console.error('Error saat mengambil daftar dokter:', error);
  }
};

const updateRencanaKontrol = async () => {
  if (!updateForm.tanggal_rencana || !updateForm.poli_kontrol || !updateForm.dokter) {
    errorMessage.value = 'Semua field harus diisi';
    return;
  }

  isLoading.value = true;
  errorMessage.value = '';
  
  try {
    const response = await axios.post('/rencana-kontrol/update', updateForm);
    
    if (response.data.success) {
      updateSuccess.value = true;
      errorMessage.value = '';
    } else {
      errorMessage.value = response.data.message;
    }
  } catch (error) {
    console.error('Error saat mengupdate rencana kontrol:', error);
    errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan saat mengupdate rencana kontrol';
  } finally {
    isLoading.value = false;
  }
};

// Watch for changes in poli and tanggal to load dokter list
const onPoliOrTanggalChange = () => {
  updateForm.dokter = '';
  dokterList.value = [];
  loadDokterList();
};
</script>

<template>
  <Head title="Update Rencana Kontrol" />
  
  <div class="min-h-screen bg-pattern p-4 md:p-6">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="bg-gradient-to-r from-teal-500 to-blue-500 rounded-xl shadow-2xl px-6 py-4 mb-6 flex flex-col md:flex-row md:items-center md:justify-between text-white">
        <h1 class="text-2xl md:text-3xl font-extrabold mb-2 md:mb-0">
          Update Rencana Kontrol
        </h1>
        
        <button @click="router.visit('/')" 
          class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2 rounded-xl shadow-lg transition duration-300 ease-in-out transform hover:scale-105">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5"></path>
            <path d="M12 19l-7-7 7-7"></path>
          </svg>
          Kembali
        </button>
      </div>

      <!-- Success Message -->
      <div v-if="updateSuccess" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
        <div class="flex items-center">
          <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
          </svg>
          <span class="font-medium">Rencana kontrol berhasil diupdate!</span>
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
        <div class="flex items-center">
          <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
          </svg>
          <span class="font-medium">{{ errorMessage }}</span>
        </div>
      </div>

      <!-- Search Form -->
      <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Cari Data Rencana Kontrol</h2>
        
        <form @submit.prevent="cariData" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="no_kartu" class="block text-sm font-medium text-gray-700 mb-2">
                Nomor Kartu BPJS
              </label>
              <input 
                id="no_kartu"
                v-model="searchForm.no_kartu" 
                type="text" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Masukkan nomor kartu BPJS"
                required
              >
            </div>
            
            <div>
              <label for="tanggal_sep" class="block text-sm font-medium text-gray-700 mb-2">
                Tanggal SEP
              </label>
              <input 
                id="tanggal_sep"
                v-model="searchForm.tanggal_sep" 
                type="date" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required
              >
            </div>
          </div>
          
          <div class="flex gap-3">
            <button 
              type="submit" 
              :disabled="isLoading"
              class="bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-semibold px-6 py-2 rounded-lg transition duration-300 flex items-center gap-2"
            >
              <svg v-if="isLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ isLoading ? 'Mencari...' : 'Cari Data' }}
            </button>
            
            <button 
              type="button" 
              @click="resetForms"
              class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-2 rounded-lg transition duration-300"
            >
              Reset
            </button>
          </div>
        </form>
      </div>

      <!-- Search Result -->
      <div v-if="searchResult" class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Data Ditemukan</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div class="space-y-2">
            <div><span class="font-medium">Nomor Kartu:</span> {{ searchResult.no_kartu }}</div>
            <div><span class="font-medium">Nama Peserta:</span> {{ searchResult.nama_peserta }}</div>
            <div><span class="font-medium">Nomor SEP:</span> {{ searchResult.no_sep }}</div>
          </div>
          <div class="space-y-2">
            <div><span class="font-medium">Tanggal SEP:</span> {{ dayjs(searchResult.tanggal_sep).format('DD-MM-YYYY') }}</div>
            <div><span class="font-medium">Poli Asal:</span> {{ searchResult.poli_asal }}</div>
            <div><span class="font-medium">Diagnosa:</span> {{ searchResult.diagnosa }}</div>
          </div>
        </div>
      </div>

      <!-- Update Form -->
      <div v-if="showUpdateForm" class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Form Update Rencana Kontrol</h3>
        
        <form @submit.prevent="updateRencanaKontrol" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="tanggal_rencana" class="block text-sm font-medium text-gray-700 mb-2">
                Tanggal Rencana Kontrol
              </label>
              <input 
                id="tanggal_rencana"
                v-model="updateForm.tanggal_rencana" 
                type="date" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                @change="onPoliOrTanggalChange"
                required
              >
            </div>
            
            <div>
              <label for="poli_kontrol" class="block text-sm font-medium text-gray-700 mb-2">
                Poli Kontrol
              </label>
              <select 
                id="poli_kontrol"
                v-model="updateForm.poli_kontrol" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                @change="onPoliOrTanggalChange"
                required
              >
                <option value="">Pilih Poli</option>
                <option v-for="poli in poliList" :key="poli.kode" :value="poli.kode">
                  {{ poli.nama }}
                </option>
              </select>
            </div>
            
            <div class="md:col-span-2">
              <label for="dokter" class="block text-sm font-medium text-gray-700 mb-2">
                Dokter
              </label>
              <select 
                id="dokter"
                v-model="updateForm.dokter" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :disabled="dokterList.length === 0"
                required
              >
                <option value="">{{ dokterList.length === 0 ? 'Pilih poli dan tanggal terlebih dahulu' : 'Pilih Dokter' }}</option>
                <option v-for="dokter in dokterList" :key="dokter.kode" :value="dokter.kode">
                  {{ dokter.nama }}
                </option>
              </select>
            </div>
          </div>
          
          <div class="flex gap-3 pt-4">
            <button 
              type="submit" 
              :disabled="isLoading"
              class="bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white font-semibold px-6 py-2 rounded-lg transition duration-300 flex items-center gap-2"
            >
              <svg v-if="isLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ isLoading ? 'Mengupdate...' : 'Update Rencana Kontrol' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.bg-pattern {
  background-color: #f8fafc;
  background-image: 
    radial-gradient(circle at 25px 25px, rgba(255,255,255,.2) 2%, transparent 50%),
    radial-gradient(circle at 75px 75px, rgba(255,255,255,.2) 2%, transparent 50%);
  background-size: 100px 100px;
}
</style>