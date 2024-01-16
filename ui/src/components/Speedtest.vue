<script setup>
import FileSpeedtest from '@/components/Speedtest/FileSpeedtest.vue'
import Librespeed from '@/components/Speedtest/Librespeed.vue'
import { useAppStore } from '@/stores/app'
const appStore = useAppStore()
</script>

<template>
  <n-card v-if="appStore.config.feature_librespeed || appStore.config.feature_filespeedtest">
    <template #header> {{ $t('server_speedtest') }} </template>
    <Librespeed v-if="appStore.config.feature_librespeed" />
    <n-divider v-if="appStore.config.feature_filespeedtest && appStore.config.feature_librespeed" />
    <FileSpeedtest v-if="appStore.config.feature_filespeedtest" />
  </n-card>
</template>
