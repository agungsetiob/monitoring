<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import dayjs from 'dayjs';

const props = defineProps({
  searchData: {
    type: Object,
    default: () => ({})
  }
});

const isLoading = ref(false);
const isLoadingDetail = ref(false);
const isUpdating = ref(false);
const errorMessage = ref('');
const detailData = ref(null);

const updateForm = reactive({
  noSuratKontrol: '',
  tglRencanaKontrol: '',
  user: 'admin' // Default user
});

const resetMessages = () => {
  errorMessage.value = '';
};

const getDetailRencanaKontrol = async () => {
  if (!updateForm.noSuratKontrol) {
    errorMessage.value = 'Nomor surat kontrol harus diisi';
    return;
  }

  isLoadingDetail.value = true;
  resetMessages();
  detailData.value = null;
  
  try {
    const response = await axios.post('/rencana-kontrol/detail', {
      noSuratKontrol: updateForm.noSuratKontrol
    });
    
    if (response.data.success) {
      detailData.value = response.data.data;
      // Set tanggal rencana kontrol saat ini ke form
      if (detailData.value.tglRencanaKontrol) {
        updateForm.tglRencanaKontrol = dayjs(detailData.value.tglRencanaKontrol).format('YYYY-MM-DD');
      }
      errorMessage.value = '';
    } else {
      detailData.value = null;
      errorMessage.value = response.data.message;
    }
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan saat mengambil detail data';
    detailData.value = null;
  } finally {
    isLoadingDetail.value = false;
  }
};

const updateRencanaKontrol = async () => {
  if (!updateForm.noSuratKontrol || !updateForm.tglRencanaKontrol) {
    errorMessage.value = 'Nomor surat kontrol dan tanggal rencana kontrol harus diisi';
    return;
  }

  if (!detailData.value) {
    errorMessage.value = 'Silakan ambil detail data terlebih dahulu';
    return;
  }

  isUpdating.value = true;
  resetMessages();
  
  const updateData = {
    noSuratKontrol: updateForm.noSuratKontrol,
    noSEP: detailData.value.sep?.noSep,
    kodeDokter: detailData.value.dokter?.kodeDokter || detailData.value.kodeDokter,
    poliKontrol: detailData.value.poliTujuan || detailData.value.poliKontrol,
    tglRencanaKontrol: updateForm.tglRencanaKontrol,
    user: updateForm.user
  };
  
  try {
    const response = await axios.post('/rencana-kontrol/update', updateData);
    
    if (response.data.success) {
      errorMessage.value = '';
      // Refresh detail data
      await getDetailRencanaKontrol();
    } else {
      errorMessage.value = response.data.message;
    }
  } catch (error) {
    console.error('Error saat update:', error);
    
    // Handle validation errors (422)
    if (error.response?.status === 422 && error.response?.data?.errors) {
      const validationErrors = Object.values(error.response.data.errors).flat();
      errorMessage.value = 'Validation Error: ' + validationErrors.join(', ');
    } else {
      errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan saat mengupdate data';
    }
    
    // Auto-hide error toast after 5 seconds
    setTimeout(() => {
      errorMessage.value = '';
    }, 5000);
    
    successMessage.value = '';
  } finally {
    isUpdating.value = false;
  }
};

// Get noSuratKontrol from URL params on mount
onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search);
  const noSuratKontrol = urlParams.get('noSuratKontrol');
  
  if (noSuratKontrol) {
    updateForm.noSuratKontrol = noSuratKontrol;
    // Auto load detail when noSuratKontrol is available
    getDetailRencanaKontrol();
  }
});
</script>

<template>
  <Head title="Update Tanggal Rencana Kontrol" />
  
  <div class="p-4 min-h-screen bg-pattern md:p-6">
    <div class="mx-auto max-w-8xl">
      <!-- Header -->
      <div class="flex flex-col px-6 py-4 mb-6 text-white bg-gradient-to-r from-teal-500 to-blue-500 rounded-xl shadow-2xl md:flex-row md:items-center md:justify-between">
        <h1 class="mb-2 text-2xl font-extrabold md:text-3xl md:mb-0">
          Update Tanggal Rencana Kontrol
        </h1>
        
        <button @click="router.visit('/rencana-kontrol')" 
          class="inline-flex gap-2 items-center px-5 py-2 font-semibold text-white bg-indigo-600 rounded-xl shadow-lg transition duration-300 ease-in-out transform hover:bg-indigo-700 hover:scale-105">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5"></path>
            <path d="M12 19l-7-7 7-7"></path>
          </svg>
          Kembali
        </button>
      </div>

      <!-- Error Toast Alert -->
      <Transition name="toast">
        <div v-if="errorMessage" class="fixed top-4 right-4 z-50 w-full max-w-sm">
          <div class="p-4 bg-red-50 rounded-lg border border-red-200 shadow-lg">
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
              </div>
              <div class="flex-1 ml-3">
                <h3 class="text-sm font-medium text-red-800">Error!</h3>
                <p class="mt-1 text-sm text-red-700">{{ errorMessage }}</p>
              </div>
              <div class="flex-shrink-0 ml-4">
                <button @click="errorMessage = ''" class="inline-flex text-red-400 hover:text-red-600 focus:outline-none">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Search Form -->
      <div class="p-6 mb-6 bg-white rounded-xl shadow-lg">
        <h2 class="mb-4 text-xl font-bold text-gray-800">Cari Data Rencana Kontrol</h2>
        
        <form @submit.prevent="getDetailRencanaKontrol" class="space-y-4">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label for="noSuratKontrol" class="block mb-2 text-sm font-medium text-gray-700">
                Nomor Surat Kontrol
              </label>
              <input 
                id="noSuratKontrol"
                v-model="updateForm.noSuratKontrol" 
                type="text" 
                class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Masukkan nomor surat kontrol"
                required
              >
            </div>
          </div>
          
          <div class="flex gap-3">
            <button 
              type="submit" 
              :disabled="isLoadingDetail"
              class="flex gap-2 items-center px-6 py-2 font-semibold text-white bg-blue-600 rounded-lg transition duration-300 hover:bg-blue-700 disabled:bg-blue-400"
            >
              <svg v-if="isLoadingDetail" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ isLoadingDetail ? 'Mencari...' : 'Cari Data' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Detail Data -->
      <div v-if="detailData" class="p-6 mb-6 bg-white rounded-xl shadow-lg">
        <h3 class="mb-4 text-lg font-bold text-gray-800">Detail Rencana Kontrol</h3>
        
        <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-2">
          <div class="p-4 bg-gray-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-600">Nomor Surat Kontrol</label>
            <p class="text-lg font-semibold text-gray-900">{{ detailData.noSuratKontrol || '-' }}</p>
          </div>
          
          <div class="p-4 bg-gray-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-600">Nama Peserta</label>
            <p class="text-lg font-semibold text-gray-900">{{ detailData.sep?.peserta?.nama || '-' }}</p>
          </div>
          
          <div class="p-4 bg-gray-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-600">Nomor Kartu</label>
            <p class="text-lg font-semibold text-gray-900">{{ detailData.sep?.peserta?.noKartu || '-' }}</p>
          </div>
          
          <div class="p-4 bg-gray-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-600">Nomor SEP</label>
            <p class="text-lg font-semibold text-gray-900">{{ detailData.sep?.noSep || '-' }}</p>
          </div>
          
          <div class="p-4 bg-gray-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-600">Poli Kontrol</label>
            <p class="text-lg font-semibold text-gray-900">{{ detailData.namaPoliTujuan || '-' }}</p>
            <p class="text-sm text-gray-500">{{ detailData.poliTujuan || '-' }}</p>
          </div>
          
          <div class="p-4 bg-gray-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-600">Dokter</label>
            <p class="text-lg font-semibold text-gray-900">{{ detailData.dokter?.namaDokter || detailData.namaDokter || '-' }}</p>
            <p class="text-sm text-gray-500">{{ detailData.dokter?.kodeDokter || detailData.kodeDokter || '-' }}</p>
          </div>
          
          <div class="p-4 bg-gray-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-600">Tanggal Rencana Kontrol Saat Ini</label>
            <p class="text-lg font-semibold text-gray-900">
              {{ detailData.tglRencanaKontrol ? dayjs(detailData.tglRencanaKontrol).format('DD-MM-YYYY') : '-' }}
            </p>
          </div>
          
          <div class="p-4 bg-gray-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-600">Tanggal Terbit</label>
            <p class="text-lg font-semibold text-gray-900">
              {{ detailData.tglTerbit ? dayjs(detailData.tglTerbit).format('DD-MM-YYYY') : '-' }}
            </p>
          </div>
          
          <div class="p-4 bg-gray-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-600">Jenis Kontrol</label>
            <p class="text-lg font-semibold text-gray-900">{{ detailData.namaJnsKontrol || '-' }}</p>
          </div>
          
          <div class="p-4 bg-gray-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-600">Tanggal Lahir</label>
            <p class="text-lg font-semibold text-gray-900">
              {{ detailData.sep?.peserta?.tglLahir ? dayjs(detailData.sep?.peserta?.tglLahir).format('DD-MM-YYYY') : '-' }}
            </p>
          </div>
          
          <div class="p-4 bg-gray-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-600">Jenis Kelamin</label>
            <p class="text-lg font-semibold text-gray-900">{{ detailData.sep?.peserta?.kelamin === 'L' ? 'Laki-laki' : detailData.sep?.peserta?.kelamin === 'P' ? 'Perempuan' : '-' }}</p>
          </div>
          
          <div class="p-4 bg-gray-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-600">Hak Kelas</label>
            <p class="text-lg font-semibold text-gray-900">Kelas {{ detailData.sep?.peserta?.hakKelas || '-' }}</p>
          </div>
        </div>

        <!-- Update Form -->
        <div class="pt-6 border-t border-gray-200">
          <h4 class="mb-4 text-lg font-bold text-gray-800">Update Tanggal Rencana Kontrol</h4>
          
          <form @submit.prevent="updateRencanaKontrol" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div>
                <label for="tglRencanaKontrol" class="block mb-2 text-sm font-medium text-gray-700">
                  Tanggal Rencana Kontrol Baru
                </label>
                <input 
                  id="tglRencanaKontrol"
                  v-model="updateForm.tglRencanaKontrol" 
                  type="date" 
                  class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  required
                >
              </div>
              
              <div>
                <label for="user" class="block mb-2 text-sm font-medium text-gray-700">
                  User
                </label>
                <input 
                  id="user"
                  v-model="updateForm.user" 
                  type="text" 
                  class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Masukkan nama user"
                  required
                >
              </div>
            </div>
            
            <div class="flex gap-3">
              <button 
                type="submit" 
                :disabled="isUpdating"
                class="flex gap-2 items-center px-6 py-2 font-semibold text-white bg-green-600 rounded-lg transition duration-300 hover:bg-green-700 disabled:bg-green-400"
              >
                <svg v-if="isUpdating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ isUpdating ? 'Mengupdate...' : 'Update Tanggal' }}
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</template>
