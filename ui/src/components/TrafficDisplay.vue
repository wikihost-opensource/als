<script setup>
import { useAppStore } from '@/stores/app'
import { onUnmounted } from 'vue'
import { formatBytes } from '@/helper/unit'
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
      updateSerieByInterface(localIface, new Date(point[0] * 1000))
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
  updateSerieByInterface(iface, new Date(time * 1000))
}

const createGraph = (interfaceName) => {
  interfaces.value[interfaceName] = {
    traffic: {
      receive: null,
      send: null
    },
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
  return
}

const updateSerieByInterface = (iface, date = null, pointname = null) => {
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

  let categories = iface.chartOptions.xaxis.categories
  let receiveDatas = iface.series[0].data
  let sendDatas = iface.series[1].data

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
  receiveDatas = receiveDatas.slice(-20)
  sendDatas = sendDatas.slice(-20)
  categories = categories.slice(-20)

  iface.chartOptions.value = {
    xaxis: { categories: categories }
  }
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
    <template #header> 服务器流量图 </template>
    <n-grid x-gap="12" cols="1 s:1 m:1 l:2 xl:2 2xl:2" responsive="screen">
      <n-gi v-for="(interfaceData, interfaceName) in interfaces">
        <n-card :title="interfaceName">
          <n-grid x-gap="12" :cols="2">
            <n-gi>
              <h3>已接收</h3>
              <span class="traffic-display">
                {{ formatBytes(interfaceData.traffic.receive, 2, true) }} /
                {{ formatBytes(interfaceData.receive) }}
              </span>
            </n-gi>
            <n-gi>
              <h3>已发送</h3>
              <span class="traffic-display">
                {{ formatBytes(interfaceData.traffic.send, 2, true) }} /
                {{ formatBytes(interfaceData.send) }}
              </span>
            </n-gi>
            <n-gi span="2">
              <apexchart
                type="area"
                :options="interfaceData.chartOptions"
                :series="interfaceData.series"
              >
              </apexchart>
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
