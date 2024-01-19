// vite.config.ts
import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import AutoImport from 'unplugin-auto-import/vite'
import Components from 'unplugin-vue-components/vite'
import { NaiveUiResolver } from 'unplugin-vue-components/resolvers'
import fs from 'node:fs'
import path from 'node:path'

// https://vitejs.dev/config/
export default defineConfig(({ command }) => {
  return {
    base: './',
    server: {
      proxy: {
        '/session': {
          target: 'http://127.0.0.1:8080',
          ws: true
        },
        '/method': {
          target: 'http://127.0.0.1:8080',
          ws: true
        }
      }
    },
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url))
      }
    },
    plugins: [
      vue(),
      AutoImport({
        imports: [
          'vue',
          {
            'naive-ui': ['useDialog', 'useMessage', 'useNotification', 'useLoadingBar']
          }
        ]
      }),
      {
        name: 'build-script',
        buildStart(options) {
          if (command === 'build') {
            const dirPath = path.join(__dirname, 'public');
            const fileBuildRequired = {
              "speedtest_worker.js": "../speedtest/speedtest_worker.js"
            };

            for (var dest in fileBuildRequired) {
              const source = fileBuildRequired[dest]
              if (fs.existsSync(dirPath + "/" + dest)) {
                fs.unlinkSync(dirPath + "/" + dest)
              }
              fs.copyFileSync(dirPath + "/" + source, dirPath + "/" + dest)
            }
          }
        },
      },
      Components({
        resolvers: [NaiveUiResolver()]
      })
    ]
  }
})
