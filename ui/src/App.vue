<script setup>
import { darkTheme, useOsTheme } from 'naive-ui'
import { list as langList, setI18nLanguage, loadLocaleMessages, autoLang } from './config/lang.js'
import { useAppStore } from './stores/app'
import LoadingCard from '@/components/Loading.vue'
import InfoCard from '@/components/Information.vue'
import SpeedtestCard from '@/components/Speedtest.vue'
import UtilitiesCard from '@/components/Utilities.vue'
import TrafficCard from '@/components/TrafficDisplay.vue'
import { computed } from 'vue'

const lang = computed(() => {
  return currentLang.value.uiLang()
})

const dateLang = computed(() => {
  return currentLang.value.dateLang()
})

const currentLangCode = ref('en-US')
const currentLang = computed(() => {
  for (var i in langList) {
    const _lang = langList[i]
    if (_lang.value == currentLangCode.value) {
      return _lang
    }
  }
  return null
})

const langDropdown = computed(() => {
  return langList.map((item) => {
    return {
      label: item.label,
      value: item.value
    }
  })
})

const handleLangChange = async () => {
  await loadLocaleMessages(currentLangCode.value)
  setI18nLanguage(currentLangCode.value)
}

const osThemeRef = useOsTheme()
const theme = computed(() => (osThemeRef.value === 'dark' ? darkTheme : null))
const appStore = useAppStore()

onMounted(async () => {
  currentLangCode.value = await autoLang()
})
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
        <n-space justify="space-between">
          <div>
            <div style="margin-top: 10px">
              Powered by
              <n-button
                text
                tag="a"
                target="_blank"
                href="https://github.com/wikihost-opensource/als"
              >
                WIKIHOST Opensource - ALS (Github)
              </n-button>
            </div>
            <div>
              <p>{{ $t('memory_usage') }}: {{ appStore.memoryUsage }}</p>
            </div>
          </div>
          <div>
            <n-select
              v-model:value="currentLangCode"
              :options="langDropdown"
              style="min-width: 150px"
              @update:value="handleLangChange"
            />
          </div>
        </n-space>
      </n-space>
    </n-message-provider>
  </n-config-provider>
</template>
