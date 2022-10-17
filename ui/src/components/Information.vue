<template>
    <div>

        <n-card hoverable>
            <template #header>
                服务器信息
            </template>
            <n-table style="max-width: 500px;border: none;">
                <tbody>
                    <template v-for="data in tableData">
                        <tr>
                            <td>{{ data.key }}</td>
                            <td>
                                <n-button text @click="copySomething(data.value)">{{ data.value }}</n-button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </n-table>
            <!-- <n-space vertical>
                <div v-show="location">
                    服务器地点: {{ location }}
                </div>
                <div v-show="publicIpv4">
                    公网 IPv4 地址: {{ publicIpv4 }}
                </div>
                <div v-show="publicIpv6">
                    公网 IPv6 地址: [{{ publicIpv6 }}]
                </div>
                <div v-show="clientIp">
                    您当前的 IP 地址: {{ clientIp }}
                </div>
            </n-space> -->
            <!-- <n-progress type=" line" :percentage="100" :show-indicator="false" processing /> -->
        </n-card>
    </div>

</template>

<script>
import { defineComponent } from 'vue'
import useClipboard from 'vue-clipboard3'
import { useMessage } from 'naive-ui'

const { toClipboard } = useClipboard()

export default defineComponent({
    props: {
        wsMessage: Array,
        componentConfig: Object
    },
    data() {
        return {
            tableData: []
        }
    },
    methods: {
        copySomething(String) {
            toClipboard(String, 'copy')
            window.$message.success('已复制')
        }
    },
    setup() {
        window.$message = useMessage()
    },
    mounted() {
        let DataWatcher = this.$watch(() => this.wsMessage, () => {
            this.wsMessage.forEach((e, i) => {
                if (e[0] != 1000) return;
                let data = JSON.parse(e[1])
                this.wsMessage.splice(i, 1)
                this.componentConfig.public_ipv4 = data.public_ipv4
                this.componentConfig.public_ipv6 = data.public_ipv6
                this.componentConfig.testFiles = data.testfiles
                this.componentConfig.display_traffic = data.display_traffic
                this.componentConfig.display_speedtest = data.display_speedtest
                this.componentConfig.utilities_ping = data.utilities_ping
                this.componentConfig.utilities_traceroute = data.utilities_traceroute
                this.componentConfig.utilities_iperf3 = data.utilities_iperf3

                this.tableData = []
                this.tableData.push({
                    key: '服务器位置',
                    value: data.location
                })

                if (data.public_ipv4) {
                    this.tableData.push({
                        key: 'IPv4 地址',
                        value: data.public_ipv4
                    })
                }

                if (data.public_ipv4) {
                    this.tableData.push({
                        key: 'IPv6 地址',
                        value: data.public_ipv6
                    })
                }

                this.tableData.push({
                    key: '您当前的 IP 地址',
                    value: data.client_ip
                })
                DataWatcher()
            })
        }, { immediate: true, deep: true });
    }
})
</script>