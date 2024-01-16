<script setup>
import { useAppStore } from '@/stores/app'
import Copyable from './Copy.vue'
import Markdown from 'vue3-markdown-it'
import { useI18n } from 'vue-i18n'

const appStore = useAppStore()
const { t } = useI18n({ useScope: 'global' })
const configKeyMap = ref({
  location: 'server_location',
  my_ip: 'my_address',
  public_ipv4: 'ipv4_address',
  public_ipv6: 'ipv6_address'
})
</script>

<template>
  <n-card hoverable>
    <template #header> {{ $t('server_info') }} </template>
    <n-grid x-gap="12" cols="1 s:2" responsive="screen" v-if="appStore.config">
      <template v-for="(index, key) in configKeyMap">
        <template v-if="appStore.config[key]">
          <n-gi span="1" style="margin-bottom: 5px">
            <n-card>
              <template #header> {{ $t(index) }} </template>
              <Copyable text :value="appStore.config[key]">{{ appStore.config[key] }}</Copyable>
            </n-card>
          </n-gi>
        </template>
      </template>
    </n-grid>
  </n-card>
  <n-card v-if="appStore.config.sponsor_message.length > 0" hoverable style="margin-top: 10px">
    <template #header> {{ $t('sponsor_message') }} </template>
    <div class="sponsor">
      <Markdown :source="appStore.config.sponsor_message" />
    </div>
  </n-card>
</template>

<style>
.sponsor a {
  color: #70c0e8;
  text-decoration: none;
}
</style>
