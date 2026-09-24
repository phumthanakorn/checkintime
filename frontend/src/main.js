import { createApp } from 'vue'
import App from './App.vue'
import pinia from './plugins/pinia'
import vuetify from './plugins/vuetify'
import router from './router'

import './assets/css/tailwind.css'
import './assets/css/main.css'

const app = createApp(App)

// ต้องลงทะเบียน pinia ก่อน router เพราะ route guard ใช้ authStore
app.use(pinia)
app.use(router)
app.use(vuetify)

app.mount('#app')
