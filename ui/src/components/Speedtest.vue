<template>
    <n-card>
        <template #header>
            服务器网络测速
        </template>
        <div v-show="h5Download !== '...'">
            <n-grid x-gap="12" cols="1 s:1 m:1 l:3" responsive="screen">
                <n-gi span="1 s:1 m:1 l:2">
                    <div>
                        <h4>下行</h4>
                        <h1>{{ h5Download }} Mbps</h1>
                        <apexchart type="line" :options="h5SpeedtestDownloadSpeedChart.chartOptions" height="200px"
                            :series="h5SpeedtestDownloadSpeedChart.series">
                        </apexchart>
                    </div>
                </n-gi>
                <n-gi span="1">
                    <div>
                        <h4>上行</h4>
                        <h1>{{ h5Upload }} Mbps</h1>
                        <apexchart type="line" :options="h5SpeedtestUploadSpeedChart.chartOptions" height="200px"
                            :series="h5SpeedtestUploadSpeedChart.series">
                        </apexchart>
                    </div>
                </n-gi>
            </n-grid>
        </div>
        <!-- <h3>下行:
                    <n-number-animation :to="h5Download" />
                </h3>
                <h3>上行: {{ h5Upload }}</h3> -->
        <n-space justify="space-evenly">
            <n-button size="large" @click="startOrStopSpeedtest" style="margin-top: 10px;">
                <n-spin size="small" v-show="h5SpeedWorker !== null" style="margin-right: 10px;" /> {{
                h5SpeedtestButtonText
                }}
            </n-button>
        </n-space>
        <n-divider v-show="componentConfig?.testFiles?.length > 0" dashed />
        <n-space v-show="componentConfig?.testFiles?.length > 0" justify="space-evenly">

            <div v-show="componentConfig.public_ipv4">
                <h3 style="text-align: center;">IPv4 下载测试</h3>
                <n-space>
                    <template v-for="i in componentConfig.testFiles">
                        <n-button strong secondary type="info" size="small" tag="a"
                            :href="`//${componentConfig.public_ipv4}/speedtest-static/${i}.test`" target="_blank">
                            {{ i }}
                        </n-button>
                    </template>
                </n-space>
            </div>

            <div v-show="componentConfig.public_ipv6">
                <h3 style="text-align: center;">IPv6 下载测试</h3>
                <n-space>
                    <template v-for="i in componentConfig.testFiles">
                        <n-button strong secondary type="info" size="small" tag="a"
                            :href="`//[${componentConfig.public_ipv6}]/speedtest-static/${i}.test`" target="_blank">
                            {{ i }}
                        </n-button>
                    </template>
                </n-space>
            </div>
        </n-space>
    </n-card>
</template>

<script>
const langMap = {
    beginSpeedtest: '开始测速',
}

import { defineComponent, defineAsyncComponent } from 'vue'
export default defineComponent({
    components: {
        apexchart: defineAsyncComponent(() => import('vue3-apexcharts')),
    },
    props: {
        componentConfig: Object
    },
    methods: {
        startOrStopSpeedtest(force = true) {
            if (this.h5SpeedWorker !== null) {
                this.h5SpeedWorker.postMessage('abort')
                clearInterval(this.h5SpeedWorkerTimer)
                this.h5SpeedtestButtonText = '开始测速'
                this.h5SpeedWorker = null
                if (force) {
                    this.h5Upload = '...'
                    this.h5Download = '...'
                }
                return;
            }
            this.h5Upload = '...'
            this.h5Download = '...'
            this.h5SpeedtestButtonText = '停止测速'
            this.h5SpeedWorker = new Worker('speedtest_worker.js')
            // this
            this.h5SpeedWorker.onmessage = (e) => {
                var nowPointName = (new Date()).getHours().toString().padStart(2, '0') + ':' +
                    (new Date()).getMinutes().toString().padStart(2, '0') + ':' +
                    (new Date()).getSeconds().toString().padStart(2, '0')

                var data = JSON.parse(e.data);
                var status = data.testState;
                if (status >= 4) {
                    return this.startOrStopSpeedtest(false)
                }

                if (status == 1 && data.dlStatus == 0) {
                    this.h5Download = '...'
                } else {
                    if (data.dlStatus) {
                        if (data.dlStatus != this.h5Download) {
                            this.h5Download = data.dlStatus
                            let ChartData = this.h5SpeedtestDownloadSpeedChart.series[0].data
                            let categories = this.h5SpeedtestDownloadSpeedChart.chartOptions.xaxis.categories
                            ChartData.push(this.h5Download)
                            categories.push(nowPointName)
                            this.h5SpeedtestDownloadSpeedChart.chartOptions.value = {
                                xaxis: { categories: categories }
                            }
                        }
                    }
                }

                if (status == 1 && data.ulStatus == 0) {
                    this.h5Upload = '...'
                } else {
                    if (data.ulStatus) {
                        if (data.ulStatus != this.h5Upload) {
                            this.h5Upload = data.ulStatus
                            let ChartData = this.h5SpeedtestUploadSpeedChart.series[0].data
                            let categories = this.h5SpeedtestUploadSpeedChart.chartOptions.xaxis.categories
                            ChartData.push(this.h5Upload)
                            categories.push(nowPointName)
                            this.h5SpeedtestUploadSpeedChart.chartOptions.value = {
                                xaxis: { categories: categories }
                            }
                        }
                    }
                }
            }
            this.h5SpeedWorker.postMessage('start ' + JSON.stringify({
                test_order: "D_U",
                url_dl: 'speedtest/download',
                url_ul: 'speedtest/upload',
                url_ping: 'speedtest/upload',
            }));
            this.h5SpeedWorkerTimer = setInterval(() => {
                this.h5SpeedWorker.postMessage('status')
            }, 200);
            // this.h5SpeedtestWorking = !this.h5SpeedtestWorking
        }
    },
    data() {
        return {
            h5SpeedWorker: null,
            h5SpeedWorkerTimer: null,
            h5SpeedtestWorking: false,
            h5SpeedtestButtonText: langMap.beginSpeedtest,
            h5Upload: '...',
            h5Download: '...',
            h5SpeedtestDownloadSpeedChart: {
                chartOptions: {
                    chart: {
                        id: "speedtest-download-chart",
                        height: 200,
                        foreColor: '#e8e8e8',
                        animations: {
                            enabled: true,
                            easing: 'linear',
                            dynamicAnimation: {
                                speed: 300
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
                    },
                },
                series: [
                    {
                        type: 'line',
                        name: 'Receive',
                        data: []
                    }
                ]
            },
            h5SpeedtestUploadSpeedChart: {
                chartOptions: {
                    chart: {
                        id: "speedtest-upload-chart",
                        height: 200,
                        foreColor: '#e8e8e8',
                        animations: {
                            enabled: true,
                            easing: 'linear',
                            dynamicAnimation: {
                                speed: 300
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
                    },
                },
                series: [
                    {
                        type: 'line',
                        name: 'Receive',
                        data: []
                    }
                ]
            },
        }
    },
    mounted() {

    }
})

</script>