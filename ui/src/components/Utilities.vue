<script setup>
import { useAppStore } from '@/stores/app'
const appStore = useAppStore()
import { shallowRef, computed, defineAsyncComponent, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
const { config } = storeToRefs(appStore)
const _v = (loader) => {
  return defineAsyncComponent({
    loader: loader,
    delay: 200
  })
}

const tools = ref([
  {
    label: 'Ping',
    show: false,
    enable: false,
    componentNode: _v(() => import('./Utilities/Ping.vue'))
  },
  {
    label: 'IPerf3',
    show: false,
    enable: false,
    componentNode: _v(() => import('./Utilities/IPerf3.vue'))
  },
  {
    label: 'Speedtest.net',
    show: false,
    enable: false,
    componentNode: _v(() => import('./Utilities/SpeedtestNet.vue'))
  },
  {
    label: 'Shell',
    show: false,
    enable: false,
    componentNode: _v(() => import('./Utilities/Shell.vue'))
  }
])

onMounted(() => {
  for (var tool of tools.value) {
    const configKey = 'feature_' + tool.label.toLowerCase().replace('.', '_dot_')
    console.log(configKey, config.value[configKey])
    tool.enable = config.value[configKey] ?? false
  }
})

const toolComponent = shallowRef(null)

const toolComponentShow = computed({
  get() {
    for (const tool of tools.value) {
      if (tool.show) {
        toolComponent.value = toRaw(tool.componentNode)
        return true
      }
    }
    return false
  },
  set(newValue) {
    if (newValue) return
    for (const tool of tools.value) {
      if (tool.show) {
        tool.show = false
        return
      }
    }
  }
})

const hasToolEnable = computed(() => {
  for (const tool of tools.value) {
    if (tool.enable) {
      return true
    }
  }
  return false
})

const toolComponentLabel = computed(() => {
  for (const tool of tools.value) {
    if (tool.show) {
      return tool.label
    }
  }
  return ''
})

const handleDrawClosed = () => {
  toolComponent.value = null
}
</script>

<template>
  <n-card v-if="hasToolEnable">
    <template #header> {{ $t('network_tools') }} </template>
    <n-space>
      <template v-for="tool in tools">
        <n-button v-if="tool.enable" @click="tool.show = true">{{ tool.label }}</n-button>
      </template>
    </n-space>
  </n-card>
  <n-drawer
    v-model:show="toolComponentShow"
    :width="appStore.drawerWidth"
    placement="right"
    @after-leave="handleDrawClosed"
  >
    <n-drawer-content :title="toolComponentLabel" closable>
      <component :is="toolComponent" @closed="toolComponentShow = false" />
    </n-drawer-content>
  </n-drawer>
</template>
