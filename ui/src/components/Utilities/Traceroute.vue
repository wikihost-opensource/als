<template>
  <n-space vertical>
    <n-input-group>
      <n-input :disabled="working" v-model:value="host" :style="{ width: '90%' }" placeholder="IP Address Or Domain"
        @keyup.enter="traceroute" />
      <n-button :loading="working" type="primary" ghost @click="traceroute()">
        Traceroute
      </n-button>
    </n-input-group>
    <n-table v-show="records.length > 0" :bordered="false" :single-line="false">
      <thead>
        <tr>
          <th>Hop #</th>
          <th>Host</th>
          <th>#1</th>
          <th>#2</th>
          <th>#3</th>
        </tr>
      </thead>
      <tbody>
        <template v-for="(record, seq) in records">
          <tr v-if="record">
            <td>{{ seq }}</td>
            <td>
              <n-space vertical>
                <n-gradient-text v-show="record.host.length > 1" type="info">
                  ! 基于流的负载均衡已发现
                </n-gradient-text>
                <template v-for="pop in record.host">
                  <span>{{ pop.dns }} ({{ pop.host }}) | {{ pop.geo }}</span>
                </template>
              </n-space>
            </td>
            <td>
              <template v-if="record.latency[0]">
                {{ record.latency[0] }} ms
              </template>
              <template v-else> - </template>
            </td>
            <td>
              <template v-if="record.latency[1]">
                {{ record.latency[1] }} ms
              </template>
              <template v-else> - </template>
            </td>
            <td>
              <template v-if="record.latency[2]">
                {{ record.latency[2] }} ms
              </template>
              <template v-else> - </template>
            </td>
          </tr>
        </template>
      </tbody>
    </n-table>
  </n-space>
</template>

<script>
import { method } from "lodash";
import { defineComponent, defineAsyncComponent } from "vue";
export default defineComponent({
  props: {
    wsMessage: Array,
    ws: WebSocket,
  },
  data() {
    return {
      host: "",
      working: false,
      records: [],
    };
  },
  methods: {
    traceroute() {
      if (this.working) return false;
      this.records = [];
      this.working = true;
      this.ws.send("2|" + this.host);
      let ticket = "";
      let traceProcess = this.$watch(
        () => this.wsMessage,
        (e) => {
          this.wsMessage.forEach((e, i) => {
            if (e[0] != 2) return true;

            if (ticket.length == 0 && e[2] == this.host && e.length == 4) {
              ticket = e[3];
              this.wsMessage.splice(i, 1);
              return true;
            }

            if (ticket == e[1] && e[2] == "0") {
              this.working = false;
              traceProcess();
              this.wsMessage.splice(i, 1);
              return false;
            }

            if (ticket == e[1] && e[2] == "1") {
              if (this.records[e[3]] === undefined) {
                this.records[e[3]] = {
                  host: [
                    {
                      dns: e[4] == "0" ? "-" : e[4],
                      host: e[5] == "0" ? "-" : e[5],
                      geo: e[7],
                    },
                  ],
                  latency: [e[6]],
                };
              } else {
                this.records[e[3]].host.push({
                  dns: e[4] == "0" ? "-" : e[4],
                  host: e[5] == "0" ? "-" : e[5],
                  geo: e[7],
                });
                this.records[e[3]].latency.push(e[6]);
              }
              this.records[e[3]].host.forEach((v1, k1) => {
                this.records[e[3]].host.forEach((v2, k2) => {
                  if (k1 != k2 && v1.host == v2.host) {
                    this.records[e[3]].host.splice(k2, 1);
                  }
                });
                if (v1.dns == "-" && this.records[e[3]].host.length > 1) {
                  this.records[e[3]].host.splice(k1, 1);
                }
              });
              this.wsMessage.splice(i, 1);
              return true;
            }
          });
        },
        { immediate: true, deep: true }
      );
    },
  },
});
</script>
