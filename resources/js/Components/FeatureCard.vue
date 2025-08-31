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
  red: 'border-red-500 hover:border-red-700',
  purple: 'border-purple-500 hover:border-purple-700',
  cyan: 'border-cyan-500 hover:border-cyan-700',
}[props.color];

const iconColor = {
  blue: 'text-blue-600 group-hover:text-blue-800',
  teal: 'text-teal-600 group-hover:text-teal-800',
  yellow: 'text-yellow-500 group-hover:text-yellow-700',
  red: 'text-red-600 group-hover:text-red-800',
  purple: 'text-purple-600 group-hover:text-purple-800',
  cyan: 'text-cyan-600 group-hover:text-cyan-800',
}[props.color];

</script>

<template>
  <Link
    :href="link"
    class="flex flex-col justify-center items-center p-8 bg-white rounded-2xl border-b-4 shadow-xl transition-all duration-300 ease-in-out transform group hover:shadow-2xl hover:-translate-y-2"
    :class="borderColor"
  >
    <div :class="iconColor" class="mb-4 transition-colors duration-300">
      <font-awesome-icon :icon="icon" class="w-16 h-16" />
    </div>
    <h2 class="mb-2 text-center text-3xl font-bold text-gray-800 transition-colors duration-300 group-hover:text-opacity-80">
      {{ title }}
    </h2>
    <p class="text-lg text-center text-gray-600">
      <slot />
    </p>
  </Link>
</template>
