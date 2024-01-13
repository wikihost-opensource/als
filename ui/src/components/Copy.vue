<script setup>
import { Copy as IconCopy } from '@vicons/carbon'

import { Clipboard } from 'v-clipboard'
import { useMessage } from 'naive-ui'

const props = defineProps({
  value: String,
  text: Boolean,
  hideMessage: Boolean
})
const isClicked = ref(false)
const message = useMessage()
const copy = async (value) => {
  try {
    await navigator.clipboard.writeText(value)
  } catch (error) {
    const textarea = document.createElement('textarea')
    document.body.appendChild(textarea)
    textarea.textContent = value
    textarea.select()
    document?.execCommand('copy')
    textarea.remove()
  }
  isClicked.value = true
  if (!props['hideMessage']) {
    message.info('已复制到剪贴板')
  }
}

const handleUpdateShow = (show) => {
  if (!show) {
    setTimeout(() => (isClicked.value = false), 150)
  }
}
</script>

<template>
  <n-tooltip v-if="!props.text" placement="bottom" trigger="hover" @update:show="handleUpdateShow">
    <template #trigger>
      <div @click="copy(props.value)">
        <slot>
          <n-icon style="margin-left: 5px">
            <IconCopy />
          </n-icon>
        </slot>
      </div>
    </template>
    <span v-if="!isClicked">点击复制</span>
    <span v-if="isClicked">内容已复制 !</span>
  </n-tooltip>
  <n-button v-else text @click="copy(props.value)">
    <slot></slot>
  </n-button>
</template>
