<script setup>
import { useAppStore } from '@/stores/app'
const appStore = useAppStore()

const url = ref(new URL(location.href))
</script>

<template>
  <template
    v-if="
      (appStore.config.public_ipv4 == '' && appStore.config.public_ipv6 == '') ||
      (appStore.config.filetest_follow_domain && appStore.config.filetest_follow_domain != '')
    "
  >
    <n-space vertical align="center">
      <h3>{{ $t('file_speedtest') }}</h3>
      <n-space>
        <n-button
          strong
          secondary
          size="small"
          type="info"
          tag="a"
          target="blank"
          :href="'./session/' + appStore.sessionId + '/speedtest/file/' + '/' + fileSize + '.test'"
          v-for="fileSize in appStore.config.speedtest_files"
          >{{ fileSize }}</n-button
        >
      </n-space>
    </n-space>
  </template>
  <template v-else>
    <n-space justify="space-evenly">
      <div v-if="appStore.config.public_ipv4">
        <h3 style="text-align: center">{{ $t('file_ipv4_speedtest') }}</h3>
        <n-space>
          <n-button
            strong
            secondary
            size="small"
            type="info"
            tag="a"
            target="blank"
            :href="
              url.protocol +
              '//' +
              appStore.config.public_ipv4 +
              ':' +
              url.port +
              '/session/' +
              appStore.sessionId +
              '/speedtest/file/' +
              fileSize +
              '.test'
            "
            v-for="fileSize in appStore.config.speedtest_files"
            >{{ fileSize }}</n-button
          >
        </n-space>
      </div>

      <div v-if="appStore.config.public_ipv6">
        <h3 style="text-align: center">{{ $t('file_ipv6_speedtest') }}</h3>
        <n-space>
          <n-button
            strong
            secondary
            size="small"
            type="info"
            tag="a"
            target="blank"
            :href="
              url.protocol +
              '//[' +
              appStore.config.public_ipv6 +
              ']:' +
              url.port +
              '/session/' +
              appStore.sessionId +
              '/speedtest/file/' +
              fileSize +
              '.test'
            "
            v-for="fileSize in appStore.config.speedtest_files"
            >{{ fileSize }}</n-button
          >
        </n-space>
      </div>
    </n-space>
  </template>
</template>
