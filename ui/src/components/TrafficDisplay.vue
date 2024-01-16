<script setup>
import { useAppStore } from '@/stores/app'
import { onUnmounted, toRaw } from 'vue'
import { formatBytes } from '@/helper/unit'
import VueApexCharts from 'vue3-apexcharts'

const appStore = useAppStore()
const interfaces = ref({})
const handleCache = (e) => {
  const data = JSON.parse(e.data)
  for (var ifaceIndex in data) {
    const iface = data[ifaceIndex]
    const ifaceName = iface.InterfaceName
    if (!interfaces.value.hasOwnProperty(ifaceName)) {
      createGraph(ifaceName)
    }
    let localIface = interfaces.value[ifaceName]
    for (var point of data[ifaceIndex].Caches) {
      localIface.receive = point[1]
      localIface.send = point[2]
      updateSerieByInterface(ifaceName, localIface, new Date(point[0] * 1000))
    }
  }
}

const handleTrafficUpdate = (e) => {
  const data = e.data.split(',')
  const ifaceName = data[0]
  const time = data[1]

  if (!interfaces.value.hasOwnProperty(ifaceName)) {
    createGraph(ifaceName)
  }

  let iface = interfaces.value[ifaceName]
  iface.receive = data[2]
  iface.send = data[3]
  updateSerieByInterface(ifaceName, iface, new Date(time * 1000))
}

const createGraph = (interfaceName) => {
  const theRef = ref()
  interfaces.value[interfaceName] = {
    ref: null,
    theCompoent: null,
    traffic: {
      receive: null,
      send: null
    },
    lines: [[], []],
    categories: [],
    receive: 0,
    send: 0,
    lastReceive: 0,
    lastSend: 0,
    chartOptions: {
      chart: {
        id: 'interface-' + interfaceName + '-chart',
        foreColor: '#e8e8e8',
        animations: {
          enabled: true,
          easing: 'linear',
          dynamicAnimation: {
            speed: 1000
          },
          animateGradually: {
            enabled: true,
            delay: 300
          }
        },
        zoom: {
          enabled: false
        },
        toolbar: {
          show: false
        },
        tooltip: {
          theme: 'dark'
        }
      },

      xaxis: {
        range: 10,
        type: 'category',
        categories: ['']
      },
      yaxis: {
        labels: {
          formatter: (value) => {
            return formatBytes(value, 2, true)
          }
        }
      },
      tooltip: {
        x: {
          format: 'dd MMM yyyy'
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
    },
    series: [
      {
        type: 'area',
        name: 'Receive',
        data: []
      },
      {
        type: 'area',
        name: 'Send',
        data: []
      }
    ]
  }
  interfaces.value[interfaceName].theCompoent = () =>
    h(VueApexCharts, {
      ref: theRef,
      type: 'area',
      options: interfaces.value[interfaceName].chartOptions,
      series: interfaces.value[interfaceName].series
    })
  interfaces.value[interfaceName].ref = theRef
  return
}

const updateSerieByInterface = (interfaceName, iface, date = null, pointname = null) => {
  let nowPointName
  if (date === null) {
    date = new Date()
  }

  if (pointname !== null) {
    nowPointName = pointname
  } else {
    nowPointName =
      date.getHours().toString().padStart(2, '0') +
      ':' +
      date.getMinutes().toString().padStart(2, '0') +
      ':' +
      date.getSeconds().toString().padStart(2, '0')
  }

  let categories = iface.categories
  let receiveDatas = iface.lines[0]
  let sendDatas = iface.lines[1]

  if (iface.lastReceive == 0) {
    iface.lastReceive = iface.receive
  }

  if (iface.lastSend == 0) {
    iface.lastSend = iface.send
  }

  let receive = iface.receive - iface.lastReceive
  let send = iface.send - iface.lastSend
  iface.lastReceive = iface.receive
  iface.lastSend = iface.send
  iface.traffic.receive = receive
  iface.traffic.send = send
  receiveDatas.push(receive)
  sendDatas.push(send)

  categories.push(nowPointName)
  if (receiveDatas.length > 30) {
    interfaces.value[interfaceName].categories = categories.slice(-10)
    interfaces.value[interfaceName].lines[0] = receiveDatas.slice(-10)
    interfaces.value[interfaceName].lines[1] = sendDatas.slice(-10)
  }
  categories = categories.slice(0)
  receiveDatas = receiveDatas.slice(0)
  sendDatas = sendDatas.slice(0)
  if (!iface.ref) return
  iface.ref.updateOptions({
    xaxis: {
      categories: toRaw(categories)
    }
  })

  iface.ref.updateSeries([
    {
      name: 'Receive',
      data: toRaw(receiveDatas)
    },
    {
      name: 'Send',
      data: toRaw(sendDatas)
    }
  ])
}
onMounted(() => {
  appStore.source.addEventListener('InterfaceCache', handleCache)
  appStore.source.addEventListener('InterfaceTraffic', handleTrafficUpdate)
})

onUnmounted(() => {
  appStore.source.removeEventListener('InterfaceCache', handleCache)
  appStore.source.removeEventListener('InterfaceTraffic', handleTrafficUpdate)
})
</script>

<template>
  <n-card hoverable>
    <template #header> {{ $t('server_bandwidth_graph') }} </template>
    <n-grid x-gap="12" cols="1 s:1 m:1 l:2 xl:2 2xl:2" responsive="screen">
      <n-gi v-for="(interfaceData, interfaceName) in interfaces">
        <n-card :title="interfaceName">
          <n-grid x-gap="12" :cols="2">
            <n-gi>
              <h3>{{ $t('server_bandwidth_graph_receive') }}</h3>
              <span class="traffic-display">
                {{ formatBytes(interfaceData.traffic.receive, 2, true) }} /
                {{ formatBytes(interfaceData.receive) }}
              </span>
            </n-gi>
            <n-gi>
              <h3>{{ $t('server_bandwidth_graph_sended') }}</h3>
              <span class="traffic-display">
                {{ formatBytes(interfaceData.traffic.send, 2, true) }} /
                {{ formatBytes(interfaceData.send) }}
              </span>
            </n-gi>
            <n-gi span="2">
              <component :is="interfaceData.theCompoent" />
            </n-gi>
          </n-grid>
        </n-card>
      </n-gi>
    </n-grid>
  </n-card>
</template>

<style scoped>
h3 {
  text-align: center;
}

.traffic-display {
  text-align: center;
  display: block;
}
</style>

<style>
.apexcharts-tooltip-title,
.apexcharts-tooltip-text {
  color: #181818;
}
</style>
