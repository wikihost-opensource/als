<script setup>
import 'xterm/css/xterm.css'
import { Terminal } from 'xterm'
import { FitAddon } from '@xterm/addon-fit'
import { onUnmounted } from 'vue'
import { useAppStore } from '@/stores/app'
const terminal = new Terminal()
const terminalRef = ref()
const fitAddon = new FitAddon()
const emit = defineEmits(['closed'])
let websocket
let buffer = []

const flushToTerminal = () => {
  if (buffer.length > 0) {
    terminal.write(new Uint8Array(buffer.shift()), () => {
      flushToTerminal()
    })
  }
}

const updateWindowSize = () => {
  fitAddon.fit()
  websocket.send(new TextEncoder().encode('2' + terminal.rows + ';' + terminal.cols))
}

let resizeTimer
const handleResize = () => {
  clearTimeout(resizeTimer)
  resizeTimer = setTimeout(() => {
    updateWindowSize()
  }, 100)
}

onMounted(() => {
  terminal.loadAddon(fitAddon)
  terminal.open(toRaw(terminalRef.value))
  fitAddon.fit()
  const url = new URL(location.href)
  const protocol = url.protocol == 'http:' ? 'ws:' : 'wss:'
  websocket = new WebSocket(
    protocol + '//' + url.host + url.pathname + 'session/' + useAppStore().sessionId + '/shell'
  )
  websocket.binaryType = 'arraybuffer'
  websocket.addEventListener('message', (event) => {
    buffer.push(event.data)
    flushToTerminal()
  })

  websocket.addEventListener('open', (event) => {
    window.addEventListener('resize', handleResize)

    handleResize()
    setTimeout(handleResize, 1000)
  })

  websocket.addEventListener('close', (event) => {
    console.log(event)
    emit('closed')
  })

  terminal.onData((data) => {
    websocket.send(new TextEncoder().encode('1' + data))
  })
  fitAddon.fit()
})

onUnmounted(() => {
  if (websocket) {
    websocket.close()
  }
})
</script>

<template>
  <div ref="terminalRef" class="terminal" style="flex-grow: 1; height: 100%" />
</template>

<style>
div:has(> div.terminal) {
  padding: 0px !important;
}
</style>
