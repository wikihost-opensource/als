<template>
  <div>
    <n-card>
      <template #header> 网络工具 </template>
      <n-space>
        <n-button v-if="componentConfig.utilities_ping" @click="activate('ping')">Ping</n-button>
        <!-- <n-button v-show="componentConfig.utilities_traceroute" @click="activate('traceroute')">Traceroute</n-button> -->
        <n-button v-if="componentConfig.utilities_iperf3" @click="activate('iperf3')">iPerf3</n-button>
        <n-button v-if="componentConfig.utilities_speedtestdotnet"
          @click="activate('speedtest')">Speedtest.net</n-button>
        <n-button v-if="componentConfig.utilities_fakeshell" @click="activate('fakeshell')">Shell</n-button>
      </n-space>
    </n-card>
    <n-drawer v-model:show="componentSwitch.ping" :native-scrollbar="true" :width="drawWidth" placement="right">
      <n-drawer-content title="Ping" :closable="true">
        <ping v-if="componentSwitch.ping" v-model:ws="ws" v-model:wsMessage="wsMessage" />
      </n-drawer-content>
    </n-drawer>
    <!-- <n-drawer
      v-model:show="componentSwitch.traceroute"
      :native-scrollbar="true"
      :width="drawWidth"
      placement="right"
    >
      <n-drawer-content title="Traceroute" :closable="true">
        <traceroute v-model:ws="ws" v-model:wsMessage="wsMessage" />
      </n-drawer-content>
    </n-drawer> -->
    <n-drawer v-model:show="componentSwitch.iperf3" :native-scrollbar="true" :width="drawWidth" placement="right">
      <n-drawer-content title="iPerf3" :closable="true">
        <iperf3 v-if="componentSwitch.iperf3" v-model:ws="ws" v-model:wsMessage="wsMessage"
          v-model:componentConfig="componentConfig" />
      </n-drawer-content>
    </n-drawer>

    <n-drawer v-model:show="componentSwitch.speedtest" :native-scrollbar="true" :width="drawWidth" placement="right">
      <n-drawer-content title="Speedtest.net GUI" :closable="true">
        <speedtestdotnet v-if="componentSwitch.speedtest" v-model:ws="ws" v-model:wsMessage="wsMessage"
          v-model:componentConfig="componentConfig" />
      </n-drawer-content>
    </n-drawer>

    <n-drawer v-model:show="componentSwitch.fakeshell" :native-scrollbar="true" :width="drawWidth" placement="right">
      <n-drawer-content title="Shell" :closable="true">
        <shell v-if="componentSwitch.fakeshell" v-model:ws="ws" v-model:wsMessage="wsMessage"
          v-model:componentConfig="componentConfig" />
      </n-drawer-content>
    </n-drawer>
  </div>
</template>

<script>
import { defineComponent, defineAsyncComponent } from "vue";
export default defineComponent({
  components: {
    ping: defineAsyncComponent(() => import("./Utilities/Ping.vue")),
    // traceroute: defineAsyncComponent(() =>
    //   import("./Utilities/Traceroute.vue")
    // ),
    speedtestdotnet: defineAsyncComponent(() =>
      import("./Utilities/SpeedtestDotNet.vue")
    ),
    iperf3: defineAsyncComponent(() => import("./Utilities/iPerf3.vue")),
    shell: defineAsyncComponent(() => import("./Utilities/Shell.vue")),
  },
  props: {
    wsMessage: Array,
    ws: WebSocket,
    componentConfig: Object,
  },
  data() {
    return {
      drawWidth: 800,
      componentSwitch: {
        ping: false,
        traceroute: false,
        iperf3: false,
        speedtest: false,
        fakeshell: false,
      },
    };
  },
  mounted() {
    if (window.screen.width < 800) {
      this.drawWidth = window.screen.width;
    }
  },
  methods: {
    activate(args) {
      this.host = "";
      this.componentSwitch[args] = true;
    },
  },
});
</script>
