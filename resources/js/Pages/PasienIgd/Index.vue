<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import 'dayjs/locale/id';

dayjs.extend(relativeTime);
dayjs.locale('id');

const patients = ref([]);

const fetchPatients = async () => {
  try {
    const response = await axios.get('/api/patient-igd');
    patients.value = response.data.data;
  } catch (error) {
    console.error("Gagal mengambil data pasien:", error);
  }
};

onMounted(() => {
  fetchPatients();
  setInterval(fetchPatients, 19000);
});

const getPatientCardClasses = (masuk) => {
  if (!masuk) return 'bg-gray-100 border-gray-300 text-gray-700';

  const now = dayjs();
  const masukTime = dayjs(masuk);
  const diffHours = now.diff(masukTime, 'hour', true);

  if (diffHours < 1) return 'bg-blue-600 border-blue-300 text-white';
  if (diffHours >= 1 && diffHours < 3) return 'bg-green-600 border-green-300 text-white';
  if (diffHours >= 3 && diffHours < 6) return 'bg-yellow-500 border-yellow-200 text-white';
  if (diffHours >= 6 && diffHours < 7) return 'bg-red-600 border-red-300 text-white';
  return 'bg-gray-800 border-gray-400 text-white'; // 7+ hours
};

const getTbakPillColor = (masuk, tanggal_tbak) => {
  if (!masuk || !tanggal_tbak) return 'bg-gray-400 text-white';

  const masukTime = dayjs(masuk);
  const tbakTime = dayjs(tanggal_tbak);
  const diffHours = tbakTime.diff(masukTime, 'hour', true);

  if (diffHours < 1) return 'bg-blue-200 text-blue-800';
  if (diffHours >= 1 && diffHours < 3) return 'bg-green-200 text-green-800';
  if (diffHours >= 3 && diffHours < 6) return 'bg-yellow-200 text-yellow-800';
  if (diffHours >= 6 && diffHours < 7) return 'bg-red-200 text-red-800';
  return 'bg-gray-400 text-gray-800'; // 7+ hours
};

const getTimeDiff = (from) => {
  const now = dayjs();
  const start = dayjs(from);
  const diffMinutes = now.diff(start, 'minute');
  const hours = Math.floor(diffMinutes / 60);
  const minutes = diffMinutes % 60;

  let result = '';
  if (hours > 0) result += `${hours} jam `;
  if (minutes > 0 || hours === 0) result += `${minutes} menit`;

  return result + ' lalu';
};

const getTriagePillColor = (status) => {
  switch (status) {
    case 'P1': return 'bg-red-200 text-red-800';
    case 'P2': return 'bg-red-200 text-red-800';
    case 'P3': return 'bg-yellow-200 text-yellow-800';
    case 'P4': return 'bg-green-200 text-green-800';
    case 'P5': return 'bg-green-200 text-green-800';
    case 'DOA': return 'bg-gray-400 text-gray-800';
    default: return 'bg-gray-400 text-gray-800';
  }
};
</script>

<template>

  <Head title="Pasien IGD" />

  <div class="min-h-screen bg-pattern p-4 md:p-4">
    <div class="text-center mb-4">
      <div class="bg-gradient-to-r from-teal-500 to-blue-500 rounded-xl shadow-2xl p-4 inline-block">
        <h1 class="text-2xl md:text-4xl font-extrabold mb-2 text-white">
          Pasien IGD RSUD dr. H. Andi Abdurrahman Noor
        </h1>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 md:gap-4 mb-4">
      <div v-for="patient in patients" :key="patient.KUNJUNGAN_ID" :class="getPatientCardClasses(patient.MASUK)"
        class="relative p-3 rounded-lg border-l-8 border shadow-md hover:shadow-lg transition-all duration-300 ease-in-out flex flex-col justify-between">
        <!-- Patient Info -->
        <div class="flex justify-between items-start mb-2">
          <h2 class="text-xl font-bold leading-tight pr-4 flex items-center">
            {{ patient.NAMA }} <font-awesome-icon :icon="patient.JENIS_KELAMIN === 1 ? 'mars' : 'venus'"
              :class="patient.JENIS_KELAMIN === 1 ? 'text-blue-300' : 'text-pink-400'" class="ml-1" />
          </h2>
          <span class="text-lg font-semibold">
            {{ patient.NORM }}
          </span>
        </div>

        <!-- Status Indicators -->
        <div class="flex flex-col gap-2 mb-2">
          <div class="flex justify-between items-center space-x-1">
            <span class="text-lg mr-1 font-semibold">🏥 {{ patient.RUANGAN }}</span>
            <span v-if="patient.STATUS_TBAK === 1" :class="getTbakPillColor(patient.MASUK, patient.TANGGAL_TBAK)"
              class="text-sm font-semibold px-2 py-1 rounded-full">
              Sudah Konsul
            </span>
            <span v-if="patient.TRIAGE_STATUS !== 'unclassified'"
              :class="['text-sm font-bold px-3 py-1 rounded-full', getTriagePillColor(patient.TRIAGE_STATUS)]">
              Triage: {{ patient.TRIAGE_STATUS }}
            </span>
            <span v-else class="text-sm font-semibold px-3 py-1 rounded-full bg-gray-200 text-gray-700">
              Belum Triage
            </span>
          </div>

          <div v-if="patient.DPJP_RANAP"
            class="flex items-center justify-center bg-purple-100 text-purple-800 rounded-full p-1">
            <span class="text-sm font-semibold text-center">Siap Ranap DPJP: {{ patient.DPJP_RANAP }}</span>
          </div>

          <div v-if="patient.NOMOR_REFERENSI"
            class="flex items-center justify-center bg-blue-100 text-blue-800 rounded-full p-1">
            <span class="text-sm font-semibold">No. Ref: {{ patient.NOMOR_REFERENSI }}</span>
          </div>
        </div>

        <div class="flex justify-between items-end text-sm">
          <div class="flex items-center">
            <span class="mr-1">⏰</span>
            <p class="text-lg font-semibold">{{ patient.TANGGAL }}</p>
          </div>
          <div class="text-right flex items-center">
            <span class="mr-1">⏳</span>
            <p class="text-lg font-semibold">{{ getTimeDiff(patient.MASUK) }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>