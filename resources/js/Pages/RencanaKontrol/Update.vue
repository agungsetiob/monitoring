<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, reactive, computed, onMounted, watch } from 'vue';
import dayjs from 'dayjs';
import DokterDropdown from '@/Components/DokterDropdown.vue';
import ErrorFlash from '@/Components/ErrorFlash.vue';
import SuccessFlash from '@/Components/SuccessFlash.vue';

const props = defineProps({
  searchData: {
    type: Object,
    default: () => ({})
  }
});

const isLoadingDetail = ref(false);
const isUpdating = ref(false);
const isLoadingDokter = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const detailData = ref(null);
const dokterList = ref([]);
const updateForm = reactive({
  noSuratKontrol: '',
  tglRencanaKontrol: '',
  kodeDokter: '',
  user: 'admin' // Default user
});

// Computed property untuk dokter yang dipilih
const selectedDokter = computed(() => {
  return dokterList.value.find(dokter => dokter.kodeDokter === updateForm.kodeDokter) || null;
});

// Method untuk handle select dokter dari component
const handleSelectDokter = (dokter) => {
  updateForm.kodeDokter = dokter.kodeDokter;
};

const resetMessages = () => {
  errorMessage.value = '';
  successMessage.value = '';
};

const getDetailRencanaKontrol = async (preserveSuccessMessage = false) => {
  if (!updateForm.noSuratKontrol) {
    errorMessage.value = 'Nomor surat kontrol harus diisi';
    return;
  }

  isLoadingDetail.value = true;
  if (!preserveSuccessMessage) {
    resetMessages();
  } else {
    errorMessage.value = ''; // Only reset error message
  }
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
      // Set kode dokter saat ini ke form
      if (detailData.value.dokter?.kodeDokter || detailData.value.kodeDokter) {
        updateForm.kodeDokter = detailData.value.dokter?.kodeDokter || detailData.value.kodeDokter;
      }
      errorMessage.value = '';

      // Load jadwal praktek dokter setelah mendapat detail
      await getJadwalPraktekDokter();
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

const getJadwalPraktekDokter = async () => {
  if (!detailData.value || !updateForm.tglRencanaKontrol) {
    return;
  }

  isLoadingDokter.value = true;
  dokterList.value = [];

  try {
    const response = await axios.post('/rencana-kontrol/jadwal-praktek-dokter', {
      jnsKontrol: detailData.value.jnsKontrol,
      kodePoli: detailData.value.poliTujuan || detailData.value.poliKontrol,
      tglRencanaKontrol: updateForm.tglRencanaKontrol
    });

    if (response.data.success && response.data.data) {
      dokterList.value = response.data.data.list;
      errorMessage.value = '';
    } else {
      dokterList.value = [];
      errorMessage.value = response.data.message || 'Tidak ada jadwal praktek dokter tersedia';
    }
  } catch (error) {
    console.error('Error saat mengambil jadwal praktek dokter:', error);
    dokterList.value = [];
    errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan saat mengambil jadwal praktek dokter';
  } finally {
    isLoadingDokter.value = false;
  }
};

const updateRencanaKontrol = async () => {
  if (!updateForm.noSuratKontrol || !updateForm.tglRencanaKontrol) {
    errorMessage.value = 'Nomor surat kontrol dan tanggal rencana kontrol harus diisi';
    return;
  }

  if (!updateForm.kodeDokter) {
    errorMessage.value = 'Silakan pilih dokter terlebih dahulu';
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
    kodeDokter: updateForm.kodeDokter,
    poliKontrol: detailData.value.poliTujuan || detailData.value.poliKontrol,
    tglRencanaKontrol: updateForm.tglRencanaKontrol,
    user: updateForm.user
  };

  try {
    const response = await axios.post('/rencana-kontrol/update', updateData);

    if (response.data.success) {
      errorMessage.value = '';
      successMessage.value = 'Data rencana kontrol berhasil diupdate!';

      // Refresh detail data after showing success message
      setTimeout(async () => {
        await getDetailRencanaKontrol(true); // Preserve success message
      }, 1000); // Delay 1 second to let user see the success message

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
  } finally {
    isUpdating.value = false;
  }
};

// Watch for changes in tglRencanaKontrol to reload dokter list
watch(() => updateForm.tglRencanaKontrol, async (newDate) => {
  if (newDate && detailData.value) {
    await getJadwalPraktekDokter();
  }
});

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
const isBack = ref(false);
document.addEventListener('inertia:start', () => {
  isBack.value = true;
});
document.addEventListener('inertia:finish', () => {
  isBack.value = false;
});
</script>

<template>

  <Head title="Update Tanggal Rencana Kontrol" />

  <div class="p-4 min-h-screen bg-gradient-to-br from-blue-100 to-green-100 md:p-6">
    <div class="mx-auto max-w-8xl">
      <div
        class="flex flex-col px-6 py-4 mb-6 text-white bg-gradient-to-r from-teal-500 to-blue-500 rounded-xl shadow-2xl md:flex-row md:items-center md:justify-between">
        <h1 class="mb-2 text-2xl font-extrabold md:text-3xl md:mb-0">
          Update Tanggal Rencana Kontrol
        </h1>

        <Link :href="route('landing-page')"
          class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-3 rounded-xl transition duration-300 ease-in-out transform hover:scale-105">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 12H5"></path>
          <path d="M12 19l-7-7 7-7"></path>
        </svg>
        Kembali
        </Link>
      </div>
      <ErrorFlash :flash="{ error: errorMessage }" @clearFlash="errorMessage = ''" />
      <SuccessFlash :flash="{ success: successMessage }" @clearFlash="successMessage = ''" />

      <!-- Search Form -->
      <div class="p-6 mb-6 bg-white rounded-xl shadow-lg">
        <h2 class="mb-4 text-xl font-bold text-gray-800">Cari Data Rencana Kontrol</h2>

        <form @submit.prevent="getDetailRencanaKontrol" class="space-y-4">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label for="noSuratKontrol" class="block mb-2 text-sm font-medium text-gray-700">
                Nomor Surat Kontrol
              </label>
              <input id="noSuratKontrol" v-model="updateForm.noSuratKontrol" type="text"
                class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Masukkan nomor surat kontrol" required>
            </div>
          </div>

          <div class="flex gap-3">
            <button type="submit" :disabled="isLoadingDetail"
              class="flex gap-2 items-center px-6 py-2 font-semibold text-white bg-blue-600 rounded-lg transition duration-300 hover:bg-blue-700 disabled:bg-blue-400">
              <font-awesome-icon v-if="isLoadingDetail" icon="spinner" spin />
              {{ isLoadingDetail ? 'Mencari...' : 'Cari Data' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Detail Data -->
      <div v-if="detailData" class="p-6 mb-6 bg-white rounded-xl shadow-lg">
        <h3 class="mb-4 text-lg font-bold text-gray-800">Detail Rencana Kontrol</h3>

        <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-4">
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
            <p class="text-lg font-semibold text-gray-900">{{ detailData.dokter?.namaDokter || detailData.namaDokter ||
              '-' }}</p>
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
              {{ detailData.sep?.peserta?.tglLahir ? dayjs(detailData.sep?.peserta?.tglLahir).format('DD-MM-YYYY') : '-'
              }}
            </p>
          </div>

          <div class="p-4 bg-gray-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-600">Jenis Kelamin</label>
            <p class="text-lg font-semibold text-gray-900">{{ detailData.sep?.peserta?.kelamin === 'L' ? 'Laki-laki' :
              detailData.sep?.peserta?.kelamin === 'P' ? 'Perempuan' : '-' }}</p>
          </div>

          <div class="p-4 bg-gray-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-600">Hak Kelas</label>
            <p class="text-lg font-semibold text-gray-900">Kelas {{ detailData.sep?.peserta?.hakKelas || '-' }}</p>
          </div>
        </div>

        <!-- Update Form -->
        <div class="pt-6 border-t border-gray-200">
          <h4 class="mb-4 text-lg font-bold text-gray-800">Ubah Rencana Kontrol</h4>

          <form @submit.prevent="updateRencanaKontrol" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div>
                <label for="tglRencanaKontrol" class="block mb-2 text-sm font-medium text-gray-700">
                  Tanggal Rencana Kontrol Baru
                </label>
                <input id="tglRencanaKontrol" v-model="updateForm.tglRencanaKontrol" type="date"
                  class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  required>
              </div>

              <div>
                <label class="block mb-2 text-sm font-medium text-gray-700">
                  Pilih Dokter
                  <span v-if="isLoadingDokter" class="text-sm text-blue-600">(Memuat...)</span>
                </label>

                <DokterDropdown :dokter-list="dokterList" :selected-dokter="selectedDokter"
                  :is-loading="isLoadingDokter" placeholder="Pilih dokter" empty-message="Tidak ada dokter tersedia"
                  empty-sub-message="Silakan pilih tanggal terlebih dahulu" @select="handleSelectDokter" />

                <p v-if="dokterList.length === 0 && !isLoadingDokter && updateForm.tglRencanaKontrol"
                  class="mt-2 text-sm text-gray-500">
                  Tidak ada jadwal praktek dokter untuk tanggal yang dipilih
                </p>
              </div>
            </div>
            <div class="flex gap-3">
              <button type="submit" :disabled="isUpdating"
                class="flex gap-2 items-center px-6 py-2 font-semibold text-white bg-green-600 rounded-lg transition duration-300 hover:bg-green-700 disabled:bg-green-400">
                <svg v-if="isUpdating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                  </path>
                </svg>
                {{ isUpdating ? 'Mengupdate...' : 'Update' }}
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
  <div v-if="isBack" class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-white bg-opacity-80">
    <video src="/img/loading.webm" autoplay loop muted playsinline />
  </div>
</template>
