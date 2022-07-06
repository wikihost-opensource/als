

<template>
  <n-config-provider :theme="darkTheme">
    <Loading v-show="!isLoaded"></Loading>
    <div v-show="isLoaded">
      <n-space vertical>
        <h2>Looking Glass Server</h2>
        <Information v-model:wsMessage="wsMessage" v-model:componentConfig="componentConfig"></Information>
        <Utilities v-model:componentConfig="componentConfig" v-model:ws="ws" v-model:wsMessage="wsMessage"></Utilities>
        <Speedtest v-model:componentConfig="componentConfig" v-show="componentConfig.display_speedtest"></Speedtest>
        <TrafficDisplay v-show="componentConfig.display_traffic" v-model:wsMessage="wsMessage"></TrafficDisplay>
      </n-space>
    </div>
  </n-config-provider>
</template>
<script>
import Loading from './components/Loading.vue'
import { defineComponent, defineAsyncComponent, reactive } from 'vue'
import { darkTheme } from 'naive-ui'

export default defineComponent({
  components: {
    Information: defineAsyncComponent(() => import('./components/Information.vue')),
    TrafficDisplay: defineAsyncComponent(() => import('./components/TrafficDisplay.vue')),
    Speedtest: defineAsyncComponent(() => import('./components/Speedtest.vue')),
    Utilities: defineAsyncComponent(() => import('./components/Utilities.vue')),
  },
  created() {
    this.initWebsocket();
  },
  methods: {
    initWebsocket() {
      this.ws = new WebSocket(location.protocol.replace('http', 'ws') + '//' + location.host + '/ws')
      this.ws.onopen = () => {
        this.isLoaded = true
      }
      this.ws.onmessage = (message) => {
        this.wsMessage.push(message.data.split('|'))
      };
      this.ws.onclose = () => {
        this.isLoaded = false
        setTimeout(() => {
          this.initWebsocket()
        }, 1000)
      }
      this.ws.onerror = () => {
        this.isLoaded = false
        setTimeout(() => {
          this.initWebsocket()
        }, 1000)
      }
    }
  },
  data() {
    return {
      ws: false,
      wsMessage: reactive([]),
      isLoaded: false,
      componentConfig: reactive({})
    }
  },
  setup() {
    return {
      darkTheme
    }
  }
})
</script>
<style>
@import './assets/base.css';

#app {
  max-width: 1280px;
  margin: 0 auto;
  padding: 2rem;
  font-weight: normal;
}

/* @media (min-width: 1024px) {
  body {
    display: flex;
    place-items: center;
  }

  #app {
    display: grid;
    grid-template-columns: 0fr 0fr;
    padding: 0 2rem;
  }

  .loading-screen {
    min-width: 500px;
  }
} */
</style>
