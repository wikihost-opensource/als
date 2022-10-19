<template>
  <n-space vertical>
    <n-input-group>
      <n-input
        :disabled="working"
        v-model:value="host"
        placeholder="IP Address Or Domain"
        @keyup.enter="ping"
      />
      <n-button :loading="working" type="primary" ghost @click="ping()">
        Ping
      </n-button>
    </n-input-group>
    <n-table v-show="records.length > 0" :bordered="false" :single-line="false">
      <thead>
        <tr>
          <th>#</th>
          <th>Host</th>
          <th>TTL</th>
          <th>Latency</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="record in records">
          <td>{{ record.seq }}</td>
          <td>{{ record.host }}</td>
          <td>{{ record.ttl }}</td>
          <td>{{ record.latency }} ms</td>
        </tr>
      </tbody>
    </n-table>
  </n-space>
</template>

<script>
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
    ping() {
      if (this.working) return false;
      this.records = [];
      this.working = true;
      this.ws.send("1|" + this.host);
      let ticket = "";
      let pingProcess = this.$watch(
        () => this.wsMessage,
        (e) => {
          this.wsMessage.forEach((e, i) => {
            if (e[0] != 1) return true;

            if (ticket.length == 0 && e[2] == this.host && e.length == 4) {
              ticket = e[3];
              this.wsMessage.splice(i, 1);
              return true;
            }

            if (ticket == e[1] && e[2] == "0") {
              this.working = false;
              pingProcess();
              this.wsMessage.splice(i, 1);
              return false;
            }

            if (ticket == e[1] && e[2] == "1") {
              if (e.length == 7) {
                this.records.push({
                  host: e[3],
                  seq: e[4],
                  ttl: e[5],
                  latency: e[6],
                });
              } else {
                this.records.push({
                  host: "-",
                  seq: this.records.length + 1,
                  ttl: "-",
                  latency: "-",
                });
              }
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
