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
  yellow: 'border-yellow-500 hover:border-yellow-700'
}[props.color];

const iconColor = {
  blue: 'text-blue-600 group-hover:text-blue-800',
  teal: 'text-teal-600 group-hover:text-teal-800',
  yellow: 'text-yellow-500 group-hover:text-yellow-700'
}[props.color];

</script>

<template>
  <Link
    :href="link"
    class="group flex flex-col items-center justify-center p-8 bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 ease-in-out transform hover:-translate-y-2 border-b-4"
    :class="borderColor"
  >
    <div :class="iconColor" class="text-6xl transition-colors duration-300 mb-4">
      <font-awesome-icon :icon="icon" class="h-24 w-24" />
    </div>
    <h2 class="text-3xl font-bold text-gray-800 mb-2 group-hover:text-opacity-80 transition-colors duration-300">
      {{ title }}
    </h2>
    <p class="text-lg text-gray-600 text-center">
      <slot />
    </p>
  </Link>
</template>
