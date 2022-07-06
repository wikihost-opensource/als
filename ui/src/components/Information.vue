<template>
    <div>
        <n-card title="Server Information" hoverable>
            <n-space vertical>
                <div v-show="location">
                    服务器地点: {{ location }}
                </div>
                <div v-show="publicIpv4">
                    公网 IPv4 地址: <n-tag>{{ publicIpv4 }}</n-tag>
                </div>
                <div v-show="publicIpv6">
                    公网 IPv6 地址: <n-tag> [{{ publicIpv6 }}]</n-tag>
                </div>
            </n-space>
            <!-- <n-progress type=" line" :percentage="100" :show-indicator="false" processing /> -->
        </n-card>
    </div>

</template>

<script>
import { defineComponent } from 'vue'
export default defineComponent({
    props: {
        wsMessage: Array,
        componentConfig: Object
    },
    data() {
        return {
            location: false,
            publicIpv4: false,
            publicIpv6: false,
        }
    },
    mounted() {
        let DataWatcher = this.$watch(() => this.wsMessage, () => {
            this.wsMessage.forEach((e, i) => {
                if (e[0] != 1000) return;
                let data = JSON.parse(e[1])
                this.wsMessage.splice(i, 1)
                this.location = data.location
                this.publicIpv4 = data.public_ipv4
                this.publicIpv6 = data.public_ipv6
                this.componentConfig.public_ipv4 = data.public_ipv4
                this.componentConfig.public_ipv6 = data.public_ipv6
                this.componentConfig.testFiles = data.testfiles
                this.componentConfig.display_traffic = data.display_traffic
                this.componentConfig.display_speedtest = data.display_speedtest
                DataWatcher()
            })
        }, { immediate: true, deep: true });
    }
})
</script>