<script setup>
import { ref } from 'vue';

const props = defineProps({
  initialStartDate: String,
  initialEndDate: String
});

const emit = defineEmits(['filter']);

const startDate = ref(props.initialStartDate);
const endDate = ref(props.initialEndDate);

const submitForm = () => {
  emit('filter', {
    start_date: startDate.value,
    end_date: endDate.value
  });
};
</script>

<template>
  <form @submit.prevent="submitForm"
    class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end bg-gray-50 p-6 rounded-xl border border-gray-200">
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Awal</label>
      <input type="date" v-model="startDate"
        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
        required>
    </div>
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Akhir</label>
      <input type="date" v-model="endDate"
        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
        required>
    </div>
    <div>
      <button type="submit"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg transition duration-200 ease-in-out transform hover:scale-105">
        🔍 Tampilkan Data
      </button>
    </div>
  </form>
</template>