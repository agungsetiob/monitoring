<script setup>
defineProps({
  item: {
    type: Object,
    required: true
  },
  statusType: String
})

</script>

<template>
  <div 
    class="p-4 rounded-lg border transition-all hover:shadow-md"
    :class="{
      'border-gray-300': statusType === 'waiting',
      'border-yellow-300': statusType === 'process',
      'border-green-300': statusType === 'complete',
      'border-2 border-red-400 bg-red-100': item.CITO === 1,
    }"
  >
    <div class="flex justify-between items-start">
      <div>
        <div class="flex items-center gap-2">
          <span class="font-bold text-gray-800">{{ item.NORM }}</span>
          <span class="text-sm text-gray-600">
            <font-awesome-icon :icon="item.JENIS_KELAMIN === 1 ? 'mars' : 'venus'" :class="item.JENIS_KELAMIN === 1 ? 'text-blue-500' : 'text-pink-500'" />
          </span>
        </div>
        <span class="text-gray-800">{{ item.NAMA }}</span>
      </div>
      <div class="flex gap-2">
        <span 
          v-if="item.RACIKAN === 1" 
          class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800"
        >
          Racikan
        </span>
        <span 
          v-if="item.CITO === 1" 
          class="px-2 py-1 text-xs rounded-full bg-red-600 text-white font-semibold"
        >
          Cito
        </span>
      </div>
    </div>

    <div v-if="item.ASAL_RUANGAN" class="text-sm text-gray-600 mt-1">
      {{ item.ASAL_RUANGAN }}
    </div>

    <div class="flex justify-between items-center mt-2">
      <span class="text-xs text-gray-500">
        {{ (item.TANGGAL) }}
      </span>
      <span class="text-xs font-medium text-gray-700">
        {{ item.NOPEN }}
      </span>
    </div>
  </div>
</template>