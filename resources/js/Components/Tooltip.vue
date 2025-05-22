<template>
    <div class="relative inline-block">
      <div class="group inline-flex w-full h-full">
        <slot />
        <div
          class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs text-white rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none z-50"
          :class="bgColor"
        >
          {{ text }}
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  defineProps({
    text: { type: String, required: true },
    bgColor: { type: String, default: 'bg-gray-800' },
  });
  </script>
  