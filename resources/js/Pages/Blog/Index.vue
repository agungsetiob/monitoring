<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import api from '@/axios';

const posts = ref([]);

const fetchPosts = async () => {
  try {
    const response = await api.get('/api/posts');
    posts.value = response.data.data;
  } catch (error) {
    console.error("Gagal mengambil data post:", error);
  }
};

onMounted(() => {
  fetchPosts();
});
</script>

<template>
  <Head title="Daftar Postingan" />
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Daftar Postingan Blog (API dari Project A)</h1>

    <div v-if="posts.length">
      <ul>
        <li v-for="post in posts" :key="post.id" class="mb-4 p-4 border rounded shadow">
          <h2 class="text-xl font-bold">{{ post.title }}</h2>
          <p v-html="post.excerpt" class="text-gray-600"></p>
          <p class="text-sm text-gray-400">{{ post.created_at }}</p>
        </li>
      </ul>
    </div>

    <div v-else>
      <p class="text-gray-500">Tidak ada postingan.</p>
    </div>
  </div>
</template>
