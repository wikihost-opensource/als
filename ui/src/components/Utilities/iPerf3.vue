<template>
    <n-space vertical>
        <n-button :block="true" :loading="working" type="primary" ghost @click="startServer()">
            {{ btnText }}
        </n-button>
        <n-progress v-show="timeout != 0" style="transform: rotate(180deg)" type="line"
            :percentage="100 - TimeoutPercentage" :show-indicator="false" />
        <n-alert v-show="working && port != 0" type="default" :show-icon="false">
            iPerf3 command:
            <p v-show="componentConfig.public_ipv4">
                IPv4:<br />
                iperf3 -c {{ componentConfig.public_ipv4 }} -p {{ port }}
            </p>
            <p v-show="componentConfig.public_ipv6">
                IPv6:<br />
                iperf3 -c {{ componentConfig.public_ipv6 }} -p {{ port }}
            </p>
        </n-alert>
        <n-input autosize style="font-family: monospace;" v-show="log.length > 0" row="30" type="textarea" :value="log"
            placeholder="iPerf3 Server log" disabled />
    </n-space>
</template>


<script>
import { defineComponent, defineAsyncComponent } from 'vue'
export default defineComponent({
    props: {
        wsMessage: Array,
        ws: WebSocket,
        componentConfig: Object
    },
    data() {
        return {
            btnText: 'Start iPerf3 Server',
            working: false,
            log: '',
            port: 0,
            timeout: 0,
            timePass: 0,
            TimeoutPercentage: 0,
            timeoutTimer: null,
        }
    },
    methods: {
        startServer() {
            if (this.working) return false;
            this.btnText = 'iPerf3 Server starting...'
            this.log = ''
            this.working = true
            this.ws.send('4')
            let ticket = ''
            let iperfProcess = this.$watch(() => this.wsMessage, (e) => {
                this.wsMessage.forEach((e, i) => {
                    if (e[0] != 4) return true
                    if (ticket.length == 0 && e[1] == '1' && e.length == 3) {
                        ticket = e[2]
                        this.wsMessage.splice(i, 1)
                        return true;
                    }

                    if (ticket == e[1] && e[2] == '0') {
                        this.working = false
                        this.port = 0
                        this.timeout = 0
                        clearInterval(this.timeoutTimer)
                        this.timePass = 0
                        iperfProcess()
                        this.btnText = 'Start iPerf3 Server'
                        this.wsMessage.splice(i, 1)
                        return false;
                    }

                    if (ticket == e[1] && e[2] == '1') {
                        this.port = e[3]
                        this.timeout = e[4]
                        this.timeoutTimer = setInterval(() => {
                            this.btnText = 'iPerf3 Server started (' + (this.timeout - this.timePass) + 's left)'
                            this.timePass++
                            this.TimeoutPercentage = Math.floor((this.timePass / this.timeout) * 100)
                        }, 1000)
                        this.wsMessage.splice(i, 1)
                        return false;
                    }

                    if (ticket == e[1] && e[2] == '2') {
                        console.log(this.log)
                        if (e[3].length > 0) {
                            this.log = this.log + e[3]
                        }
                        this.wsMessage.splice(i, 1)
                        return false;
                    }
                })

            }, { immediate: true, deep: true })
        }
    }


})
</script>