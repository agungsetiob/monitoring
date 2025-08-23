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
const successMessage = ref('');
const detailData = ref(null);

const updateForm = reactive({
  noSuratKontrol: '',
  tglRencanaKontrol: '',
  user: 'admin' // Default user
});

const resetMessages = () => {
  errorMessage.value = '';
  successMessage.value = '';
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
      console.log(response.data.data)
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
    console.error('Error saat mengambil detail:', error);
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
  
  try {
    const response = await axios.post('/rencana-kontrol/update', {
      noSuratKontrol: updateForm.noSuratKontrol,
      noSEP: detailData.value.sep?.noSep,
      kodeDokter: detailData.value.kodeDokter,
      poliKontrol: detailData.value.poliKontrol,
      tglRencanaKontrol: updateForm.tglRencanaKontrol,
      user: updateForm.user
    });
    
    if (response.data.success) {
      successMessage.value = 'Rencana kontrol berhasil diupdate';
      errorMessage.value = '';
      // Refresh detail data
      await getDetailRencanaKontrol();
    } else {
      errorMessage.value = response.data.message;
      successMessage.value = '';
    }
  } catch (error) {
    console.error('Error saat update:', error);
    errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan saat mengupdate data';
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
    <div class="mx-auto max-w-4xl">
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

      <!-- Success Message -->
      <div v-if="successMessage" class="px-4 py-3 mb-6 text-green-700 bg-green-100 rounded-lg border border-green-400">
        <div class="flex items-center">
          <svg class="mr-2 w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
          </svg>
          <span class="font-medium">{{ successMessage }}</span>
        </div>
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
            <p class="text-lg font-semibold text-gray-900">{{ detailData.namaDokter || '-' }}</p>
            <p class="text-sm text-gray-500">{{ detailData.kodeDokter || '-' }}</p>
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

<style scoped>
.bg-pattern {
  background-color: #f8fafc;
  background-image: 
    radial-gradient(circle at 25px 25px, rgba(255,255,255,.2) 2%, transparent 50%),
    radial-gradient(circle at 75px 75px, rgba(255,255,255,.2) 2%, transparent 50%);
  background-size: 100px 100px;
}
</style>