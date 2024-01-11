<script setup>
import { darkTheme, useOsTheme, zhCN, dateZhCN } from 'naive-ui'
import { useAppStore } from './stores/app'
import LoadingCard from '@/components/Loading.vue'
import InfoCard from '@/components/Information.vue'
import SpeedtestCard from '@/components/Speedtest.vue'
import UtilitiesCard from '@/components/Utilities.vue'
import TrafficCard from '@/components/TrafficDisplay.vue'
import { computed } from 'vue'

const lang = computed(() => {
  return zhCN
})

const dateLang = computed(() => {
  return dateZhCN
})
const osThemeRef = useOsTheme()
const theme = computed(() => (osThemeRef.value === 'dark' ? darkTheme : null))
const appStore = useAppStore()
</script>

<template>
  <n-config-provider :locale="lang" :date-locale="dateLang" :theme="theme">
    <n-global-style />
    <n-message-provider>
      <n-space vertical>
        <h2>Looking Glass Server</h2>
        <LoadingCard v-if="appStore.connecting" />
        <template v-else>
          <InfoCard />
          <UtilitiesCard />
          <SpeedtestCard />
          <TrafficCard v-if="appStore.config.feature_iface_traffic" />
        </template>
        <div style="margin-top: 10px">
          Powered by
          <n-button text tag="a" target="_blank" href="https://github.com/wikihost-opensource/als">
            WIKIHOST Opensource - ALS (Github)
          </n-button>
        </div>
        <div>Memory usage: {{ appStore.memoryUsage }}</div>
      </n-space>
    </n-message-provider>
  </n-config-provider>
</template>
