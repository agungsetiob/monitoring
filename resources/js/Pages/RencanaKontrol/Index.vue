<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import dayjs from 'dayjs';
import Tooltip from '@/Components/Tooltip.vue';
import ErrorFlash from '@/Components/ErrorFlash.vue';

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

const searchResult = ref([]);

const cariData = async () => {
  if (!searchForm.no_kartu || !searchForm.bulan || !searchForm.tahun) {
    errorMessage.value = 'Nomor kartu, bulan, dan tahun harus diisi';
    return;
  }

  isLoading.value = true;
  errorMessage.value = '';
  searchResult.value = [];

  try {
    const response = await axios.post('/rencana-kontrol/cari-data', searchForm);

    if (response.data.success) {
      searchResult.value = response.data.data.list;
      errorMessage.value = '';
    } else {
      searchResult.value = [];
      errorMessage.value = response.data.message;
    }
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan saat mencari data';
    searchResult.value = [];
  } finally {
    isLoading.value = false;
  }
};

const editRencanaKontrol = (item) => {
  router.visit(`/rencana-kontrol/update?noSuratKontrol=${item.noSuratKontrol}`);
};
const isBack = ref(false);
document.addEventListener('inertia:start', () => {
  isBack.value = true;
});
document.addEventListener('inertia:finish', () => {
  isBack.value = false;
});
</script>

<template>

  <Head title="Update Rencana Kontrol" />

  <div class="p-4 min-h-screen bg-gradient-to-br from-blue-100 to-green-100 md:p-6">
    <div class="mx-auto max-w-8xl">
      <!-- Header -->
      <div
        class="flex flex-col px-6 py-4 mb-6 text-white bg-gradient-to-r from-teal-500 to-blue-500 rounded-xl shadow-2xl md:flex-row md:items-center md:justify-between">
        <h1 class="mb-2 text-2xl font-extrabold md:text-3xl md:mb-0">
          Rencana Kontrol
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

      <!-- Search Form -->
      <div class="p-6 mb-6 bg-white rounded-xl shadow-lg">
        <h2 class="mb-4 text-xl font-bold text-gray-800">Cari Data Rencana Kontrol</h2>

        <form @submit.prevent="cariData" class="space-y-4">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
              <label for="no_kartu" class="block mb-2 text-sm font-medium text-gray-700">
                Nomor Kartu BPJS
              </label>
              <input id="no_kartu" v-model="searchForm.no_kartu" type="text"
                class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Masukkan nomor kartu BPJS" required>
            </div>

            <div>
              <label for="bulan" class="block mb-2 text-sm font-medium text-gray-700">
                Bulan
              </label>
              <select id="bulan" v-model="searchForm.bulan"
                class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required>
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
              <input id="tahun" v-model="searchForm.tahun" type="text" minlength="4" maxlength="4" pattern="[0-9]{4}"
                class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="YYYY" required>
            </div>

            <div>
              <label for="filter" class="block mb-2 text-sm font-medium text-gray-700">
                Filter
              </label>
              <select id="filter" v-model="searchForm.filter"
                class="px-3 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Pilih Filter</option>
                <option value="1">Tanggal Entri</option>
                <option value="2">Tanggal Rencana Kontrol</option>
              </select>
            </div>
          </div>

          <div class="flex gap-3">
            <button type="submit" :disabled="isLoading"
              class="flex gap-2 items-center px-6 py-2 font-semibold text-white bg-blue-600 rounded-lg transition duration-300 hover:bg-blue-700 disabled:bg-blue-400">
              <font-awesome-icon v-if="isLoading" icon="spinner" spin />
              {{ isLoading ? 'Mencari...' : 'Cari Data' }}
            </button>

            <button type="button" @click="resetForm"
              class="px-6 py-2 font-semibold text-white bg-gray-500 rounded-lg transition duration-300 hover:bg-gray-600">
              Reset
            </button>
          </div>
        </form>
      </div>

      <!-- Search Result -->
      <div class="p-6 mb-6 bg-white rounded-xl shadow-lg">
        <h3 class="mb-4 text-lg font-bold text-gray-800">Data Rencana Kontrol Ditemukan ({{ searchResult.length }} data)
        </h3>

        <div class="overflow-x-auto">
          <table class="min-w-full bg-white rounded-lg border border-gray-200">
            <thead class="bg-gray-100">
              <tr>
                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">No. Surat Kontrol</th>
                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">Jenis</th>
                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">Nama Peserta</th>
                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">Poli</th>
                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">Dokter</th>
                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">Tgl. Rencana</th>
                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">Tgl. Terbit</th>
                <th class="px-4 py-3 text-sm font-medium text-left text-gray-700 border-b">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <template v-if="searchResult && searchResult.length > 0">
                <tr v-for="(item, index) in searchResult" :key="index" class="hover:bg-gray-50">
                  <td class="px-4 py-3 text-sm text-gray-900 border-b">{{ item.noSuratKontrol || '-' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-900 border-b">{{ item.namaJnsKontrol || '-' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-900 border-b">{{ item.nama || '-' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-900 border-b">{{ item.namaPoliTujuan || '-' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-900 border-b">{{ item.namaDokter || '-' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-900 border-b">
                    {{ item.tglRencanaKontrol ? dayjs(item.tglRencanaKontrol).format('DD-MM-YYYY') : '-' }}
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-900 border-b">
                    {{ item.tglTerbitKontrol ? dayjs(item.tglTerbitKontrol).format('DD-MM-YYYY') : '-' }}
                  </td>
                  <td class="px-4 py-3 text-sm border-b">
                    <div class="flex gap-2">
                      <Tooltip text="Ubah rencana" bgColor="bg-blue-600">
                        <button @click="editRencanaKontrol(item)"
                          class="px-2 py-1 text-xs font-medium text-blue-600 border border-blue-600 rounded transition duration-300 hover:bg-blue-200">
                          <font-awesome-icon icon="edit" />
                        </button>
                      </Tooltip>
                    </div>
                  </td>
                </tr>
              </template>
              <tr v-else>
                <td colspan="8" class="px-4 py-3 text-center text-gray-500">Tidak ada data</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div v-if="isBack" class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-white bg-opacity-80">
    <video src="/img/loading.webm" autoplay loop muted playsinline />
  </div>
</template>
