<template>
  <div>
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
                <apexchart type="line" :options="interfaceData.chartOptions" :series="interfaceData.series">
                </apexchart>
              </n-gi>
            </n-grid>
          </n-card>
        </n-gi>
      </n-grid>
    </n-card>
  </div>
</template>

<script>
import { defineComponent, defineAsyncComponent } from "vue";
export default defineComponent({
  components: {
    apexchart: defineAsyncComponent(() => import("vue3-apexcharts")),
  },
  props: {
    wsMessage: Array,
  },
  data() {
    return {
      traffic: {
        receive: null,
        send: null,
      },
      categories: [],
      refreshTimer: null,
      interfaces: {},
    };
  },
  methods: {
    createGraph(interfaceName) {
      this.interfaces[interfaceName] = {
        traffic: {
          receive: null,
          send: null,
        },
        receive: 0,
        send: 0,
        lastReceive: 0,
        lastSend: 0,
        chartOptions: {
          chart: {
            id: "interface-" + interfaceName + "-chart",
            foreColor: "#e8e8e8",
            animations: {
              enabled: true,
              easing: "linear",
              dynamicAnimation: {
                speed: 1000,
              },
            },
            zoom: {
              enabled: false,
            },
            toolbar: {
              show: false,
            },
            tooltip: {
              theme: "dark",
            },
          },

          xaxis: {
            range: 10,
            type: "category",
            categories: [""],
          },
          yaxis: {
            labels: {
              formatter: (value) => {
                return this.formatBytes(value, 2, true);
              },
            },
          },
          tooltip: {
            x: {
              format: "dd MMM yyyy",
            },
          },
          dataLabels: {
            enabled: false,
          },
          markers: {
            size: 0,
          },
          stroke: {
            curve: "smooth",
          },
        },
        series: [
          {
            type: "line",
            name: "Receive",
            data: [],
          },
          {
            type: "line",
            name: "Send",
            data: [],
          },
        ],
      };
      return;
    },
    updateSeries(date = null) {
      if (date === null) {
        date = new Date();
      }
      var nowPointName =
        date.getHours().toString().padStart(2, "0") +
        ":" +
        date.getMinutes().toString().padStart(2, "0") +
        ":" +
        date.getSeconds().toString().padStart(2, "0");
      for (let interfaceName in this.interfaces) {
        let categories =
          this.interfaces[interfaceName].chartOptions.xaxis.categories;
        let receiveDatas = this.interfaces[interfaceName].series[0].data;
        let sendDatas = this.interfaces[interfaceName].series[1].data;
        let receive =
          this.interfaces[interfaceName].receive -
          this.interfaces[interfaceName].lastReceive;
        let send =
          this.interfaces[interfaceName].send -
          this.interfaces[interfaceName].lastSend;

        this.interfaces[interfaceName].lastReceive =
          this.interfaces[interfaceName].receive;
        this.interfaces[interfaceName].lastSend =
          this.interfaces[interfaceName].send;
        this.interfaces[interfaceName].traffic.receive = receive;
        this.interfaces[interfaceName].traffic.send = send;
        receiveDatas.push(receive);
        sendDatas.push(send);

        categories.push(nowPointName);
        receiveDatas = receiveDatas.slice(-20);
        sendDatas = sendDatas.slice(-20);
        categories = categories.slice(-20);

        this.interfaces[interfaceName].chartOptions.value = {
          xaxis: { categories: categories },
        };
      }
    },
    formatBytes(bytes, decimals = 2, bandwidth = false) {
      if (bytes === 0) return "0 Bytes";

      const k = 1024;
      const dm = decimals < 0 ? 0 : decimals;
      const sizes = ["Bytes", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"];
      const bandwidthSizes = [
        "Bps",
        "Kbps",
        "Mbps",
        "Gbps",
        "Tbps",
        "Pbs",
        "Ebps",
        "Zbps",
        "Ybps",
      ];

      const i = Math.floor(Math.log(bytes) / Math.log(k));

      if (bandwidth) {
        bytes = bytes * 10;
        return (
          parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) +
          " " +
          bandwidthSizes[i]
        );
      }
      return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + " " + sizes[i];
    },
    loadCache(data) {
      data = JSON.parse(data[1]);
      data.forEach((e, i) => {
        let pointTime = new Date(parseInt(e.time.toString() + '000'))
        // console.log(e.data)
        for (let entry in e.data) {
          let interfaceName = e.data[entry]['name']
          if (!this.interfaces.hasOwnProperty(interfaceName)) {
            this.createGraph(interfaceName)
            this.interfaces[interfaceName].lastReceive = e.data[entry]['recv']
            this.interfaces[interfaceName].lastSend = e.data[entry]['send']
          }

          this.interfaces[interfaceName].receive = e.data[entry]['recv'];
          this.interfaces[interfaceName].send = e.data[entry]['send'];
        }
        this.updateSeries(pointTime)
      })
    }
  },
  mounted() {
    setInterval(() => {
      this.updateSeries();
    }, 1000);
    this.$watch(
      () => this.wsMessage,
      () => {
        this.wsMessage.forEach((e, i) => {
          if (e[0] == 101) {
            this.loadCache(e);
            this.wsMessage.splice(i, 1);
            return;
          }
          if (e[0] != 100) return false;
          let interfaceName = e[1];
          let receiveTraffic = e[2];
          let sendTraffic = e[3];
          this.wsMessage.splice(i, 1);
          if (!this.interfaces.hasOwnProperty(interfaceName)) {
            this.createGraph(interfaceName)
            this.interfaces[interfaceName].lastReceive = receiveTraffic
            this.interfaces[interfaceName].lastSend = sendTraffic
          }
          this.interfaces[interfaceName].receive = receiveTraffic;
          this.interfaces[interfaceName].send = sendTraffic;
        });
      },
      { immediate: true, deep: true }
    );
  },
});
</script>

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
