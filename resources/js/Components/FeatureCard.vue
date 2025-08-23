<script setup>
import { Link } from '@inertiajs/vue3';
const props = defineProps({
  title: String,
  link: String,
  icon: [String, Array],
  color: {
    type: String,
    default: 'blue'
  }
});
const borderColor = {
  blue: 'border-blue-500 hover:border-blue-700',
  teal: 'border-teal-500 hover:border-teal-700',
  yellow: 'border-yellow-500 hover:border-yellow-700',
  green: 'border-green-500 hover:border-green-700'
}[props.color];

const iconColor = {
  blue: 'text-blue-600 group-hover:text-blue-800',
  teal: 'text-teal-600 group-hover:text-teal-800',
  yellow: 'text-yellow-500 group-hover:text-yellow-700',
  green: 'text-green-600 group-hover:text-green-800'
}[props.color];

</script>

<template>
  <Link
    :href="link"
    class="flex flex-col justify-center items-center p-8 bg-white rounded-2xl border-b-4 shadow-xl transition-all duration-300 ease-in-out transform group hover:shadow-2xl hover:-translate-y-2"
    :class="borderColor"
  >
    <div :class="iconColor" class="mb-4 text-6xl transition-colors duration-300">
      <font-awesome-icon :icon="icon" class="w-24 h-24" />
    </div>
    <h2 class="mb-2 text-3xl font-bold text-gray-800 transition-colors duration-300 group-hover:text-opacity-80">
      {{ title }}
    </h2>
    <p class="text-lg text-center text-gray-600">
      <slot />
    </p>
  </Link>
</template>
