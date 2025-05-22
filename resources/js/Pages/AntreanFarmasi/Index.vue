<template>
  <Head title="Antrean Farmasi" />
  <div class="min-h-screen bg-pattern p-4 md:p-4">
    <div class="text-center mb-6">
      <div class="bg-gradient-to-r from-teal-500 to-blue-500 rounded-xl shadow-2xl p-4 inline-block">
        <h1 class="text-2xl md:text-4xl font-extrabold mb-4 text-white">
          Antrean Farmasi RSUD dr. H. Andi Abdurrahman Noor
        </h1>
        <div class="flex flex-col md:flex-row items-center justify-center gap-4">
          <div class="relative w-full md:w-64">
            <select
              v-model="selectedDepo"
              :disabled="isSwitchingDepo"
              class="w-full px-4 py-2 border-2 border-teal-200 rounded-xl bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all appearance-none"
            >
              <option
                v-for="option in mappedDepoOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>
          <div class="text-lg md:text-xl font-semibold text-gray-700 bg-white px-4 py-2 rounded-xl shadow-sm">
            Last Update: {{ formattedLastUpdate }}
          </div>
        </div>
      </div>
    </div>

    <div v-if="error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6">
      <div class="flex items-center">
        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ error }}</span>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mb-6 transition-all duration-300">
      <StatusColumn
        title="Belum Diterima"
        :items="antrianData.belum_diterima"
        status-type="waiting"
        :loading="isSwitchingDepo" />
      <StatusColumn
        title="Sedang Dilayani"
        :items="antrianData.dilayani"
        status-type="process"
        :loading="isSwitchingDepo" />
      <StatusColumn
        title="Resep Selesai"
        :items="antrianData.selesai"
        status-type="complete"
        :loading="isSwitchingDepo" />
    </div>

    <div class="bg-white rounded-xl shadow-lg p-4 md:p-6 border border-teal-100 transition-all duration-300">
      <h2 class="text-xl font-bold text-center mb-4 text-gray-700 flex items-center justify-center">
        <span class="bg-purple-100 text-purple-800 px-4 py-2 rounded-full">Obat Diterima Pasien</span>
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <AntreanCard
          v-for="item in antrianData.final"
          :key="item.NOPEN"
          :item="item"
          status-type="complete"
          :loading="isSwitchingDepo"
          class="border-2 border-purple-300 hover:border-purple-500 transition-colors duration-200"
          v-if="!isSwitchingDepo" />
        <div v-if="antrianData.final.length === 0 && !isSwitchingDepo" class="text-center text-red-500 py-2 col-span-4">
          <font-awesome-icon icon="face-sad-cry" class="text-5xl"/>
          <p class="mt-2">Belum ada obat diterima pasien</p>
        </div>
        <div v-if="isSwitchingDepo" class="text-center text-red-500 py-4 col-span-4">
          <div class="animate-spin rounded-full h-12 w-12 border-t-4 border-b-4 border-purple-500 mx-auto mb-2"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
  import { Head, router } from '@inertiajs/vue3'
  import { ref, watch, computed, onMounted, onUnmounted } from 'vue'
  import StatusColumn from '@/Components/Antrean/StatusColumn.vue'
  import AntreanCard from '@/Components/Antrean/AntreanCard.vue'

  const props = defineProps({
    initialData: Object,
    depoOptions: Array,
    selectedDepo: String
  })

  const selectedDepo = ref(props.selectedDepo || (props.depoOptions.length > 0 ? props.depoOptions[0] : ''))
  const antrianData = ref({
    belum_diterima: [],
    dilayani: [],
    selesai: [],
    final: []
  })
  const isLoading = ref(false)
  const error = ref(null)
  const isSwitchingDepo = ref(false)
  const lastUpdate = ref(null)
  let pollingTimer = null

  const depoLabels = {
    rajal: 'Depo Rawat Jalan',
    ranap: 'Depo Rawat Inap',
    ok: 'Depo OK',
    igd: 'Depo IGD'
  }

  const mappedDepoOptions = computed(() => {
    return props.depoOptions.map(depo => ({
      value: depo,
      label: depoLabels[depo] || depo.toUpperCase()
    }))
  })

  const formattedLastUpdate = computed(() => {
    if (!lastUpdate.value) return 'Belum ada update'
    return new Date(lastUpdate.value).toLocaleString('id-ID', {
      dateStyle: 'long',
      timeStyle: 'medium'
    })
  })

  const fetchAntrian = async (depo, showLoading = true) => {
    try {
      if (showLoading) {
        isLoading.value = true
      }
      error.value = null

      const response = await fetch(`/api/antrean-farmasi/data?depo=${depo}`, {
        headers: {
          'Accept': 'application/json'
        }
      })

      const result = await response.json()

      if (!response.ok || !result.success) {
        throw new Error(result.message || 'Gagal memuat data')
      }

      antrianData.value = result.data
      lastUpdate.value = new Date()
    } catch (err) {
      console.error('Fetch error:', err)
      error.value = err.message
    } finally {
      if (showLoading) {
        isLoading.value = false
      }
    }
  }

  const startPolling = () => {
    pollingTimer = setInterval(() => {
      if (!isSwitchingDepo.value) {
        fetchAntrian(selectedDepo.value, false)
      }
    }, 19000)
  }
  const stopPolling = () => {
    clearInterval(pollingTimer)
    pollingTimer = null
  }

  let debounceTimer
  watch(selectedDepo, (newDepo) => {
    isSwitchingDepo.value = true
    fetchAntrian(newDepo, true).finally(() => {
      isSwitchingDepo.value = false
    })

    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
      router.get('/resep', { depo: newDepo }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
      })
      stopPolling()
      startPolling()
    }, 300)
  })

  onMounted(() => {
    if (props.initialData?.data) {
      antrianData.value = props.initialData.data
      lastUpdate.value = new Date()
      startPolling()
    } else if (selectedDepo.value) {
      fetchAntrian(selectedDepo.value, true).then(() => {
        startPolling()
      })
    }
  })

  onUnmounted(() => {
    stopPolling()
  })
</script>