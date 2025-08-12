<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  title: String,
  link: String,
  label: String,
  labelColor: {
    type: String,
    default: 'text-gray-500'
  }
});
</script>

<template>
  <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 shadow hover:shadow-md transition space-y-4">
    <div class="flex items-center gap-2">
      <h2 class="text-lg font-semibold text-gray-700">{{ title }}</h2>
      <span v-if="label" :class="['text-sm font-semibold', labelColor]">{{ label }}</span>
    </div>
    <Link
      :href="link"
      class="inline-block bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition"
    >
      📥 Tampilkan
    </Link>
  </div>
</template>
