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
const poliList = ref([]);
const dokterList = ref([]);
const updateSuccess = ref(false);
const errorMessage = ref('');
const updateResult = ref(null);

const updateForm = reactive({
  no_sep: props.searchData?.no_sep || '',
  no_kartu: props.searchData?.no_kartu || '',
  tanggal_rencana: '',
  poli_kontrol: '',
  dokter: '',
  user: 'admin' // Default user, bisa disesuaikan dengan auth user
});

const resetForm = () => {
  updateForm.tanggal_rencana = '';
  updateForm.poli_kontrol = '';
  updateForm.dokter = '';
  updateSuccess.value = false;
  errorMessage.value = '';
  updateResult.value = null;
  dokterList.value = [];
};

const loadPoliList = async () => {
  try {
    const response = await axios.get('/rencana-kontrol/poli-list');
    if (response.data.success) {
      poliList.value = response.data.data;
    }
  } catch (error) {
    console.error('Error saat mengambil daftar poli:', error);
    errorMessage.value = 'Gagal mengambil daftar poli';
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
    errorMessage.value = 'Gagal mengambil daftar dokter';
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
      updateResult.value = response.data.data;
      errorMessage.value = '';
    } else {
      errorMessage.value = response.data.message;
      updateSuccess.value = false;
    }
  } catch (error) {
    console.error('Error saat mengupdate rencana kontrol:', error);
    errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan saat mengupdate rencana kontrol';
    updateSuccess.value = false;
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

const goBack = () => {
  router.visit('/rencana-kontrol');
};

const printSuratKontrol = () => {
  if (updateResult.value) {
    // Implementasi print atau download surat kontrol
    window.print();
  }
};

onMounted(() => {
  loadPoliList();
});
</script>

<template>
  <Head title="Update Rencana Kontrol" />
  
  <div class="min-h-screen bg-pattern p-4 md:p-6">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="bg-gradient-to-r from-teal-500 to-blue-500 rounded-xl shadow-2xl px-6 py-4 mb-6 flex flex-col md:flex-row md:items-center md:justify-between text-white">
        <h1 class="text-2xl md:text-3xl font-extrabold mb-2 md:mb-0">
          Form Update Rencana Kontrol
        </h1>
        
        <button @click="goBack" 
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
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span class="font-medium">Rencana kontrol berhasil diupdate!</span>
          </div>
          <button 
            @click="printSuratKontrol"
            class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm font-medium transition duration-300"
          >
            Print Surat
          </button>
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

      <!-- Patient Info -->
      <div v-if="props.searchData" class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Informasi Pasien</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div class="space-y-2">
            <div><span class="font-medium">Nomor Kartu:</span> {{ props.searchData.no_kartu }}</div>
            <div><span class="font-medium">Nama Peserta:</span> {{ props.searchData.nama_peserta }}</div>
            <div><span class="font-medium">Nomor SEP:</span> {{ props.searchData.no_sep }}</div>
          </div>
          <div class="space-y-2">
            <div><span class="font-medium">Tanggal SEP:</span> {{ dayjs(props.searchData.tanggal_sep).format('DD-MM-YYYY') }}</div>
            <div><span class="font-medium">Poli Asal:</span> {{ props.searchData.poli_asal }}</div>
            <div><span class="font-medium">Diagnosa:</span> {{ props.searchData.diagnosa }}</div>
          </div>
        </div>
      </div>

      <!-- Update Result -->
      <div v-if="updateResult" class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-6">
        <h3 class="text-lg font-bold text-blue-800 mb-4">Hasil Update Rencana Kontrol</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div class="space-y-2">
            <div><span class="font-medium">No. Surat Kontrol:</span> <span class="text-blue-600 font-mono">{{ updateResult.no_surat_kontrol }}</span></div>
            <div><span class="font-medium">Tanggal Terbit:</span> {{ dayjs(updateResult.tanggal_terbit).format('DD-MM-YYYY') }}</div>
          </div>
          <div class="space-y-2">
            <div><span class="font-medium">Tanggal Rencana:</span> {{ dayjs(updateResult.tanggal_rencana).format('DD-MM-YYYY') }}</div>
            <div><span class="font-medium">Poli Kontrol:</span> {{ updateResult.poli_kontrol }}</div>
            <div><span class="font-medium">Dokter:</span> {{ updateResult.dokter }}</div>
          </div>
        </div>
      </div>

      <!-- Update Form -->
      <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Form Update Rencana Kontrol</h3>
        
        <form @submit.prevent="updateRencanaKontrol" class="space-y-6">
          <!-- Hidden fields -->
          <input type="hidden" v-model="updateForm.no_sep">
          <input type="hidden" v-model="updateForm.no_kartu">
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="tanggal_rencana" class="block text-sm font-medium text-gray-700 mb-2">
                Tanggal Rencana Kontrol <span class="text-red-500">*</span>
              </label>
              <input 
                id="tanggal_rencana"
                v-model="updateForm.tanggal_rencana" 
                type="date" 
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-300"
                @change="onPoliOrTanggalChange"
                :min="dayjs().format('YYYY-MM-DD')"
                required
              >
              <p class="text-xs text-gray-500 mt-1">Pilih tanggal untuk rencana kontrol</p>
            </div>
            
            <div>
              <label for="poli_kontrol" class="block text-sm font-medium text-gray-700 mb-2">
                Poli Kontrol <span class="text-red-500">*</span>
              </label>
              <select 
                id="poli_kontrol"
                v-model="updateForm.poli_kontrol" 
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-300"
                @change="onPoliOrTanggalChange"
                required
              >
                <option value="">Pilih Poli Kontrol</option>
                <option v-for="poli in poliList" :key="poli.kode" :value="poli.kode">
                  {{ poli.nama }}
                </option>
              </select>
              <p class="text-xs text-gray-500 mt-1">Pilih poli untuk kontrol selanjutnya</p>
            </div>
            
            <div class="md:col-span-2">
              <label for="dokter" class="block text-sm font-medium text-gray-700 mb-2">
                Dokter <span class="text-red-500">*</span>
              </label>
              <select 
                id="dokter"
                v-model="updateForm.dokter" 
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-300"
                :disabled="dokterList.length === 0"
                required
              >
                <option value="">
                  {{ dokterList.length === 0 ? 'Pilih poli dan tanggal terlebih dahulu' : 'Pilih Dokter' }}
                </option>
                <option v-for="dokter in dokterList" :key="dokter.kode" :value="dokter.kode">
                  {{ dokter.nama }}
                </option>
              </select>
              <p class="text-xs text-gray-500 mt-1">Dokter akan muncul setelah memilih poli dan tanggal</p>
            </div>
          </div>
          
          <div class="flex gap-3 pt-6 border-t border-gray-200">
            <button 
              type="submit" 
              :disabled="isLoading || !updateForm.tanggal_rencana || !updateForm.poli_kontrol || !updateForm.dokter"
              class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-semibold px-8 py-3 rounded-lg transition duration-300 flex items-center gap-2 shadow-lg"
            >
              <svg v-if="isLoading" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
              </svg>
              {{ isLoading ? 'Mengupdate...' : 'Update Rencana Kontrol' }}
            </button>
            
            <button 
              type="button" 
              @click="resetForm"
              class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-3 rounded-lg transition duration-300 shadow-lg"
            >
              Reset Form
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

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>