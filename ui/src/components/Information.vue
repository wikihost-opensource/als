<script setup>
import { useAppStore } from '@/stores/app'
import Copyable from './Copy.vue'
import Markdown from 'vue3-markdown-it'

const appStore = useAppStore()
const configKeyMap = {
  location: '服务器位置',
  my_ip: '您当前的 IP 地址',
  public_ipv4: 'IPv4 地址',
  public_ipv6: 'IPv6 地址'
}
</script>

<template>
  <n-card hoverable>
    <template #header> 服务器信息 </template>
    <n-grid x-gap="12" cols="1 s:2" responsive="screen" v-if="appStore.config">
      <template v-for="(index, key) in configKeyMap">
        <template v-if="appStore.config[key]">
          <n-gi span="1" style="margin-bottom: 5px">
            <n-card>
              <template #header> {{ index }} </template>
              <Copyable text :value="appStore.config[key]">{{ appStore.config[key] }}</Copyable>
            </n-card>
          </n-gi>
        </template>
      </template>
    </n-grid>
  </n-card>
  <n-card v-if="appStore.config.sponsor_message.length > 0" hoverable style="margin-top: 10px">
    <template #header> 节点赞助商消息 </template>
    <div class="sponsor">
      <Markdown :source="appStore.config.sponsor_message" />
    </div>
  </n-card>
</template>
