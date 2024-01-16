<script setup>
import { useAppStore } from '@/stores/app'
import { onMounted, toRaw } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
const appStore = useAppStore()

let workerInstance = null
let workerTimer = null
const working = ref(false)
const uploadText = ref('...')
const downloadText = ref('...')
const chartDownloadRef = ref()
const chartUploadRef = ref()
const baseChartOptions = {
  chart: {
    height: 200,
    foreColor: '#e8e8e8',
    animations: {
      enabled: true,
      easing: 'linear',
      dynamicAnimation: {
        speed: 300
      }
    },
    zoom: {
      enabled: false
    },
    toolbar: {
      show: false
    }
  },
  tooltip: {
    theme: 'dark'
  },
  xaxis: {
    type: 'category',
    categories: [''],
    labels: {
      show: false
    }
  },
  yaxis: {
    labels: {
      formatter: (value) => {
        return value + ' Mbps'
      }
    }
  },
  dataLabels: {
    enabled: false
  },
  markers: {
    size: 0
  },
  stroke: {
    curve: 'smooth'
  }
}
const baseSeries = {
  type: 'area',
  name: 'Receive',
  data: []
}
const charts = ref({
  download: {
    ref: null,
    options: { ...baseChartOptions },
    series: [{ ...baseSeries }],
    data: [],
    categories: []
  },
  upload: {
    ref: null,
    options: { ...baseChartOptions },
    series: [{ ...baseSeries }],
    data: [],
    categories: []
  }
})

for (var chartId in charts.value) {
  charts.value[chartId].options.chart.id =
    'chart-librespeed-' + (Math.random() + 1).toString(36).substring(2)
}

const startOrStopSpeedtest = (force = false) => {
  if (workerInstance != null) {
    workerInstance.postMessage('abort')
    clearInterval(workerTimer)

    workerInstance = null
    if (force) {
      uploadText.value = '...'
      downloadText.value = '...'
    }
    working.value = false
    return
  }
  working.value = true
  workerInstance = new Worker('./speedtest_worker.js')
  workerInstance.onmessage = (e) => {
    var nowPointName =
      new Date().getHours().toString().padStart(2, '0') +
      ':' +
      new Date().getMinutes().toString().padStart(2, '0') +
      ':' +
      new Date().getSeconds().toString().padStart(2, '0')

    var data = JSON.parse(e.data)
    console.log(data)
    var status = data.testState
    if (status >= 4) {
      return startOrStopSpeedtest(false)
    }

    if (status == 1 && data.dlStatus == 0) {
      downloadText.value = '...'
      uploadText.value = '...'
    }

    if (data.ulStatus) {
      uploadText.value = data.ulStatus
      charts.value.upload.data.push(data.ulStatus)
      charts.value.upload.categories.push(nowPointName)
      chartUploadRef.value.updateOptions({
        xaxis: {
          categories: toRaw(charts.value.upload.categories)
        }
      })
      chartUploadRef.value.updateSeries([
        {
          name: 'Receive',
          data: toRaw(charts.value.upload.data)
        }
      ])
      return
    }

    if (data.dlStatus) {
      downloadText.value = data.dlStatus
      charts.value.download.data.push(data.dlStatus)
      charts.value.download.categories.push(nowPointName)
      chartDownloadRef.value.updateOptions({
        xaxis: {
          categories: toRaw(charts.value.download.categories)
        }
      })
      chartDownloadRef.value.updateSeries([
        {
          name: 'Receive',
          data: toRaw(charts.value.download.data)
        }
      ])
    }
  }
  workerInstance.postMessage(
    'start ' +
      JSON.stringify({
        test_order: 'D_U',
        url_dl: './session/' + appStore.sessionId + '/speedtest/download',
        url_ul: './session/' + appStore.sessionId + '/speedtest/upload',
        url_ping: './session/' + appStore.sessionId + '/speedtest/upload'
      })
  )
  workerTimer = setInterval(() => {
    workerInstance.postMessage('status')
  }, 200)
}
</script>

<template>
  <div v-show="working || downloadText !== '...'">
    <n-grid x-gap="12" cols="1 s:1 m:1 l:2" responsive="screen">
      <n-gi span="1">
        <div>
          <h4>{{ $t('librespeed_download') }}</h4>
          <h1>{{ downloadText }} Mbps</h1>
        </div>
        <VueApexCharts
          type="area"
          ref="chartDownloadRef"
          :options="charts.download.options"
          height="200px"
          :series="charts.download.series"
        />
      </n-gi>
      <n-gi span="1">
        <div>
          <h4>{{ $t('librespeed_upload') }}</h4>
          <h1>{{ uploadText }} Mbps</h1>
        </div>
        <VueApexCharts
          type="area"
          ref="chartUploadRef"
          :options="charts.upload.options"
          height="200px"
          :series="charts.upload.series"
        />
      </n-gi>
    </n-grid>
  </div>
  <n-space justify="space-evenly">
    <n-button size="large" @click="startOrStopSpeedtest" style="margin-top: 10px">
      <template v-if="working">
        <n-spin size="small" style="margin-right: 10px" />{{ $t('librespeed_stop') }}
      </template>
      <template v-else>{{ $t('librespeed_begin') }}</template>
    </n-button>
  </n-space>
</template>
