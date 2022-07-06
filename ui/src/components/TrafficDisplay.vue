<template>
    <div>
        <n-card title="Server Traffic" hoverable>
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
                                <apexchart type="line" :options="interfaceData.chartOptions"
                                    :series="interfaceData.series">
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
import { defineComponent, defineAsyncComponent } from 'vue'
export default defineComponent({
    components: {
        apexchart: defineAsyncComponent(() => import('vue3-apexcharts')),
    },
    props: {
        wsMessage: Array
    },
    data() {
        return {
            traffic: {
                receive: null,
                send: null
            },
            categories: [],
            refreshTimer: null,
            interfaces: {}
        }
    },
    methods: {
        updateSeries() {
            var nowPointName = (new Date()).getHours().toString().padStart(2, '0') + ':' +
                (new Date()).getMinutes().toString().padStart(2, '0') + ':' +
                (new Date()).getSeconds().toString().padStart(2, '0')
            for (let interfaceName in this.interfaces) {
                let categories = this.interfaces[interfaceName].chartOptions.xaxis.categories
                let receiveDatas = this.interfaces[interfaceName].series[0].data
                let sendDatas = this.interfaces[interfaceName].series[1].data
                let receive = this.interfaces[interfaceName].receive - this.interfaces[interfaceName].lastReceive
                let send = this.interfaces[interfaceName].send - this.interfaces[interfaceName].lastSend

                this.interfaces[interfaceName].lastReceive = this.interfaces[interfaceName].receive
                this.interfaces[interfaceName].lastSend = this.interfaces[interfaceName].send
                this.interfaces[interfaceName].traffic.receive = receive
                this.interfaces[interfaceName].traffic.send = send
                receiveDatas.push(receive)
                sendDatas.push(send)

                categories.push(nowPointName)
                this.interfaces[interfaceName].chartOptions.value = {
                    xaxis: { categories: categories }
                }
            }
        },
        formatBytes(bytes, decimals = 2, bandwidth = false) {
            if (bytes === 0) return '0 Bytes';

            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
            const bandwidthSizes = ['Bps', 'Kbps', 'Mbps', 'Gbps', 'Tbps', 'Pbs', 'Ebps', 'Zbps', 'Ybps'];

            const i = Math.floor(Math.log(bytes) / Math.log(k));

            if (bandwidth) {
                bytes = bytes * 10
                return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + bandwidthSizes[i];
            }
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
    },
    mounted() {
        setInterval(() => {
            this.updateSeries()
        }, 1000)
        this.$watch(() => this.wsMessage, () => {
            this.wsMessage.forEach((e, i) => {
                if (e[0] != 100) return false
                let interfaceName = e[1]
                let receiveTraffic = e[2]
                let sendTraffic = e[3]
                this.wsMessage.splice(i, 1)
                if (!this.interfaces.hasOwnProperty(interfaceName)) {
                    this.interfaces[interfaceName] = {
                        traffic: {
                            receive: null,
                            send: null
                        },
                        receive: receiveTraffic,
                        send: sendTraffic,
                        lastReceive: receiveTraffic,
                        lastSend: sendTraffic,
                        chartOptions: {
                            chart: {
                                id: "interface-" + interfaceName + "-chart",
                                foreColor: '#e8e8e8',
                                animations: {
                                    enabled: true,
                                    easing: 'linear',
                                    dynamicAnimation: {
                                        speed: 1000
                                    },
                                },
                                zoom: {
                                    enabled: false
                                },
                                toolbar: {
                                    show: false,
                                },
                                tooltip: {
                                    theme: 'dark'
                                },
                            },

                            xaxis: {
                                range: 10,
                                type: 'category',
                                categories: [''],
                            },
                            yaxis: {
                                labels: {
                                    formatter: (value) => {
                                        return this.formatBytes(value, 2, true)
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
                            },
                        },
                        series: [
                            {
                                type: 'line',
                                name: 'Receive',
                                data: []
                            },
                            {
                                type: 'line',
                                name: 'Send',
                                data: []
                            }
                        ]
                    }
                    return;
                }
                this.interfaces[interfaceName].receive = receiveTraffic
                this.interfaces[interfaceName].send = sendTraffic
            })
        }, { immediate: true, deep: true });
    }
})
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