

<template>
  <n-config-provider :theme="darkTheme">
    <n-message-provider>
      <Loading v-show="!isLoaded"></Loading>
      <div v-show="isLoaded">
        <n-space vertical>
          <h2>Looking Glass Server</h2>
          <Information v-model:wsMessage="wsMessage" v-model:componentConfig="componentConfig"></Information>
          <Utilities v-model:componentConfig="componentConfig" v-model:ws="ws" v-model:wsMessage="wsMessage">
          </Utilities>
          <Speedtest v-model:componentConfig="componentConfig" v-show="componentConfig.display_speedtest"></Speedtest>
          <TrafficDisplay v-show="componentConfig.display_traffic" v-model:wsMessage="wsMessage"></TrafficDisplay>
          <div>
            Powered by
            <n-button text tag="a" target="_blank" href="https://github.com/wikihost-opensource/als">
              WIKIHOST Opensource - ALS (Github)
            </n-button>
          </div>
        </n-space>
      </div>
    </n-message-provider>
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
      if (this.isLoaded || this.isConnecting) return;
      this.isConnecting = true
      this.ws = new WebSocket(location.protocol.replace('http', 'ws') + '//' + location.host + '/ws')
      this.ws.onopen = () => {
        this.isLoaded = true
        this.isConnecting = false
      }
      this.ws.onmessage = (message) => {
        this.wsMessage.push(message.data.split('|'))
      };

      let error_or_closed = () => {
        this.isLoaded = false
        this.isConnecting = false
        this.ws.close()
        this.ws = null
        this.initWebsocket()
      }

      this.ws.onclose = error_or_closed
      this.ws.onerror = error_or_closed
    }
  },
  data() {
    return {
      ws: false,
      wsMessage: reactive([]),
      isLoaded: false,
      isConnecting: false,
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
