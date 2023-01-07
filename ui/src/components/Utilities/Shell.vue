<template>
  <div class="shell">
    <iframe v-if="load" :src="url"></iframe>
  </div>
  <n-collapse-transition :show="!load">
    <n-alert :show-icon="false" :bordered="false"> Loading...</n-alert>
  </n-collapse-transition>
</template>

<script>
import { defineComponent, defineAsyncComponent } from "vue";
export default defineComponent({
  data() {
    return {
      load: false,
      url: undefined,
    };
  },
  props: {
    wsMessage: Array,
    ws: WebSocket,
    componentConfig: Object,
  },
  mounted() {
    let DataWatcher = this.$watch(
      () => this.wsMessage,
      () => {
        this.wsMessage.forEach((e, i) => {
          if (e[0] != 6) return;
          if (e[1] != 1) return;
          this.url = "./shell/?arg=" + e[2];
          this.load = true;
          this.wsMessage.splice(i, 1);
          DataWatcher();
        });
      },
      { immediate: true, deep: true }
    );
    this.ws.send(6);
  },
});
</script>

<style scoped>
.shell {
  height: inherit;
}

.shell iframe {
  border: 0;
  width: 100%;
  height: 100%;
}
</style>
