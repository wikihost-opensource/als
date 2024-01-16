import './assets/base.css'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import { setupI18n } from './config/lang.js'
const app = createApp(App)
app.use(setupI18n())
app.use(createPinia())
app.mount('#app')
