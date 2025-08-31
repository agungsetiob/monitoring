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
      Monitor pasien gawat darurat.
    </FeatureCard>

    <FeatureCard
      title="Antrean Farmasi"
      :link="route('antrean-farmasi.index')"
      icon="prescription-bottle-medical"
      color="teal"
    >
      Pantau status antrean resep Farmasi.
    </FeatureCard>

    <FeatureCard
      title="Laporan"
      :link="route('laporan.index')"
      icon="chart-bar"
      color="yellow"
    >
      Laporan monitoring pasien Gawat Darurat.
    </FeatureCard>

    <FeatureCard
      title="Rencana Kontrol"
      :link="route('rencana-kontrol.index')"
      icon="notes-medical"
      color="red"
    >
      Kelola dan pantau rencana kontrol pasien.
    </FeatureCard>
    <FeatureCard
      title="Apotek Online"
      :link="route('apol.index')"
      icon="pills"
      color="purple"
    >
      Kelola Klaim Resep pasien BPJS.
    </FeatureCard>
    <FeatureCard
      title="Halo Farmasi"
      :link="route('apol.index')"
      icon="user-doctor"
      color="cyan"
    >
      Konsultasi farmasi pasien kronis.
    </FeatureCard>
  </LandingSection>
</template>
