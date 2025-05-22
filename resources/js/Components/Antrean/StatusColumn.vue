<script setup>
import AntreanCard from './AntreanCard.vue'

defineProps({
  title: String,
  items: {
    type: Array,
    default: () => []
  },
  statusType: {
    type: String,
    validator: (value) => ['waiting', 'process', 'complete'].includes(value)
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const bgColors = {
  waiting: 'bg-gray-500',
  process: 'bg-yellow-400',
  complete: 'bg-green-500'
}

const textColors = {
  waiting: 'text-white',
  process: 'text-gray-800',
  complete: 'text-white'
}

const scrollbarColors = {
  waiting: 'scroll-waiting',
  process: 'scroll-process',
  complete: 'scroll-complete'
}
</script>

<template>
  <div class="rounded-xl shadow-lg overflow-hidden h-full border border-gray-200 relative">
    <!-- Column Header -->
    <div 
      class="p-4 text-center text-xl font-bold"
      :class="[bgColors[statusType], textColors[statusType]]"
    >
      {{ title }}
    </div>
    
    <!-- List Antrian Resep -->
    <div 
      class="bg-white p-4 space-y-3 h-[50vh] overflow-y-auto relative custom-scrollbar"
      :class="scrollbarColors[statusType]"
    >
      <!-- Loading Overlay -->
      <div v-if="loading" class="absolute inset-0 bg-white bg-opacity-70 flex items-center justify-center z-10">
          <div class="animate-spin rounded-full h-12 w-12 border-t-4 border-b-4" 
              :class="{
                'border-gray-500': statusType === 'waiting',
                'border-yellow-400': statusType === 'process',
                'border-green-500': statusType === 'complete'
              }">
          </div>
      </div>
      <template v-if="!loading && items && items.length > 0">
        <AntreanCard 
          v-for="item in items" 
          :key="item.TANGGAL" 
          :item="item" 
          :status-type="statusType"
        />
      </template>
      <div v-if="!loading && (!items || items.length === 0)" class="text-center text-red-500 py-8">
        <font-awesome-icon icon="pills" class="text-4xl"/>
        <p class="mt-2">Tidak ada resep</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}

.scroll-waiting::-webkit-scrollbar-thumb {
  background-color: rgb(107 114 128);
}

.scroll-process::-webkit-scrollbar-thumb {
  background-color: rgb(250 204 21);
}

.scroll-complete::-webkit-scrollbar-thumb {
  background-color: rgb(34 197 94);
}
</style>
