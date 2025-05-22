<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
    meta: Object
});
</script>

<template>
    <div class="flex items-center justify-between bg-white px-4 py-3 sm:px-6">
        <!-- Mobile version -->
        <div class="flex flex-1 justify-between sm:hidden">
            <component
                :is="links[0].url ? Link : 'span'"
                :href="links[0].url"
                preserve-scroll
                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                :class="{ 'opacity-50 cursor-not-allowed': !links[0].url }"
            >
                Previous
            </component>
            <component
                :is="links[links.length - 1].url ? Link : 'span'"
                :href="links[links.length - 1].url"
                preserve-scroll
                class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                :class="{ 'opacity-50 cursor-not-allowed': !links[links.length - 1].url }"
            >
                Next
            </component>
        </div>

        <!-- Desktop version -->
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Showing
                    <span class="font-medium">{{ meta?.from ?? 0 }}</span>
                    to
                    <span class="font-medium">{{ meta?.to ?? 0 }}</span>
                    of
                    <span class="font-medium">{{ meta?.total ?? 0 }}</span>
                    results
                </p>
            </div>
            <div>
                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                    <component
                        :is="links[0].url ? Link : 'span'"
                        :href="links[0].url"
                        preserve-scroll
                        class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-gray-300 ring-inset hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                        :class="{ 'opacity-50 cursor-not-allowed': !links[0].url }"
                    >
                        <span class="sr-only">Previous</span>
                        <span class="h-5 w-5" aria-hidden="true">&laquo;</span>
                    </component>

                    <template v-for="(link, index) in links.slice(1, -1)" :key="index">
                        <component
                            :is="link.url ? Link : 'span'"
                            :href="link.url"
                            preserve-scroll
                            class="relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20"
                            :class="[
                                link.active
                                    ? 'z-10 bg-indigo-600 text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'
                                    : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-offset-0',
                                index > 0 && index < links.length - 3 && !link.active
                                    ? 'hidden md:inline-flex'
                                    : ''
                            ]"
                        >
                            <span v-html="link.label"></span>
                        </component>
                    </template>

                    <component
                        :is="links[links.length - 1].url ? Link : 'span'"
                        :href="links[links.length - 1].url"
                        preserve-scroll
                        class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-gray-300 ring-inset hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                        :class="{ 'opacity-50 cursor-not-allowed': !links[links.length - 1].url }"
                    >
                        <span class="sr-only">Next</span>
                        <span class="h-5 w-5" aria-hidden="true">&raquo;</span>
                    </component>
                </nav>
            </div>
        </div>
    </div>
</template>