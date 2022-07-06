import { fileURLToPath, URL } from 'url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'

import Components from 'unplugin-vue-components/vite'
import { NaiveUiResolver } from 'unplugin-vue-components/resolvers'
// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueJsx(),
    Components({
      resolvers: [NaiveUiResolver()]
    })],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    }
  },
  server: {
    proxy: {
      '/ws': {
        target: 'ws://127.0.0.1:80',
        ws: true
      },
      '/speedtest-static': {
        target: 'http://127.0.0.1:80',
      },
      '/speedtest/': {
        target: 'http://127.0.0.1:80',
      }
    }
  }
})
