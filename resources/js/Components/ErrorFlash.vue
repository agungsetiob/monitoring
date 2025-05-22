<template>
    <Transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 translate-y-4"
        enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-4">
        <div v-if="flash.error" class="fixed inset-x-0 top-4 flex justify-center z-50 px-4">
            <div class="flex items-center justify-between w-full max-w-md bg-red-50 border-l-4 border-red-500 text-red-800 px-4 py-3 rounded-lg shadow-lg"
                role="alert" style="backdrop-filter: blur(10px);" ref="errorAlert">
                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-3 mt-0.5 text-red-600 flex-shrink-0" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="font-medium text-sm sm:text-base">{{ flash.error }}</p>
                    </div>
                </div>
                <button @click="closeAlert"
                    class="ml-4 p-1 rounded-full hover:bg-red-100 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-red-50"
                    aria-label="Close notification">
                    <svg class="w-5 h-5 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
    flash: Object
});

const emit = defineEmits(['clearFlash']);
const timeoutId = ref(null);
const errorAlert = ref(null);

const closeAlert = () => {
    clearTimeout(timeoutId.value);
    emit('clearFlash');
};

onMounted(() => {
    watch(() => props.flash.error, (newVal) => {
        if (newVal) {
            clearTimeout(timeoutId.value);
            timeoutId.value = setTimeout(() => {
                emit('clearFlash');
            }, 5000);
        }
    }, { immediate: true });
});
</script>