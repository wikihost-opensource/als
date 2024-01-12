<script setup>
import { onMounted, onUnmounted } from 'vue'
import { useAppStore } from '@/stores/app'
import 'xterm/css/xterm.css'
import { Terminal } from 'xterm'
import { FitAddon } from '@xterm/addon-fit'
import Copy from '../Copy.vue'

const appStore = useAppStore()
const working = ref(false)
const port = ref(0)
const timeout = ref(0)
const timeoutPercentage = ref(0)
const timePass = ref(0)
const timeoutTimer = ref()

const terminal = new Terminal()
const terminalRef = ref()
const fitAddon = new FitAddon()

const handlePortChange = (e) => {
  port.value = e.data
}

const handleMessage = (e) => {
  fitAddon.fit()
  for (var line of e.data.split('\n')) {
    terminal.writeln(line)
  }
}

onMounted(() => {
  terminal.loadAddon(fitAddon)
  terminal.open(toRaw(terminalRef.value))
  fitAddon.fit()
})

let abortController = markRaw(new AbortController())
const startServer = async () => {
  working.value = true
  terminal.clear()
  port.value = null

  abortController = new AbortController()
  appStore.source.addEventListener('Iperf3', handlePortChange)
  appStore.source.addEventListener('Iperf3Stream', handleMessage)

  try {
    await appStore.requestMethod('iperf3/server', {}, abortController.signal)
  } catch (e) {}

  working.value = false
}
const stopServer = () => {
  appStore.source.removeEventListener('Iperf3', handlePortChange)
  appStore.source.removeEventListener('Iperf3Stream', handleMessage)
  abortController.abort('Unmounted')
  terminal.writeln('IPerf3 Server stoped')
}
onUnmounted(() => {
  stopServer()
})
</script>

<template>
  <n-flex vertical style="height: 100%">
    <n-button
      :block="true"
      :type="working ? 'error' : 'primary'"
      ghost
      @click="!working ? startServer() : stopServer()"
    >
      <span v-if="!working"> 启动 iPerf3 服务器 </span>
      <span v-else> 停止 iPerf3 服务器 </span>
    </n-button>
    <n-progress
      v-show="timeout != 0"
      style="transform: rotate(180deg)"
      type="line"
      :percentage="100 - timeoutPercentage"
      :show-indicator="false"
    />
    <n-alert v-if="working && port" title="您可以使用以下命令来连接 IPerf3 服务器" type="info">
      <n-space vertical>
        <template v-if="appStore.config.public_ipv4">
          // IPv4
          <Copy :value="'iperf3 -c ' + appStore.config.public_ipv4 + ' -p ' + port"
            >iperf3 -c {{ appStore.config.public_ipv4 }} -p {{ port }}</Copy
          >
        </template>
        <template v-if="appStore.config.public_ipv6">
          // IPv6
          <Copy :value="'iperf3 -c ' + appStore.config.public_ipv6 + ' -p ' + port"
            >iperf3 -c {{ appStore.config.public_ipv6 }} -p {{ port }}</Copy
          >
        </template>
      </n-space>
    </n-alert>
    <div ref="terminalRef" style="flex-grow: 1" />
  </n-flex>
</template>
