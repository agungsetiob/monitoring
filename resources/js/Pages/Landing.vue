<script setup>
import { Head } from '@inertiajs/vue3';
import LandingSection from '@/Components/Landing/LandingSection.vue';
import FeatureCard from '@/Components/FeatureCard.vue';
</script>

<template>
  <Head title="RSUD dr. H. Abdurrahman Noor" />

  <LandingSection>
    <FeatureCard
      title="Monitoring Pasien IGD"
      :link="route('pasien-igd.index')"
      icon="bed-pulse"
      color="blue"
    >
      Monitor pasien yang sedang ditangani di IGD.
      <span class="font-semibold text-red-500">(Khusus petugas IGD)</span>
    </FeatureCard>

    <FeatureCard
      title="Antrean Farmasi"
      :link="route('antrean-farmasi.index')"
      icon="prescription-bottle-medical"
      color="teal"
    >
      Pantau status antrean resep obat di Depo Farmasi.
    </FeatureCard>

    <FeatureCard
      title="Laporan"
      :link="route('laporan.index')"
      icon="chart-bar"
      color="yellow"
    >
      Laporan monitoring pasien Gawat Darurat.
    </FeatureCard>
  </LandingSection>
</template>
