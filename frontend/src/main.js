import { createApp } from 'vue'
import App from './App.vue'
import pinia from './plugins/pinia'
import vuetify from './plugins/vuetify'
import router from './router'
import AppIcon from './components/common/AppIcon.vue'
import LoadingDots from './components/feedback/LoadingDots.vue'

import './assets/css/tailwind.css'
import './assets/css/main.css'

const app = createApp(App)

// ไอคอนใช้บ่อยมาก ลงทะเบียนให้ใช้ได้ทุก component โดยไม่ต้อง import
app.component('AppIcon', AppIcon)
app.component('LoadingDots', LoadingDots)

// ต้องลงทะเบียน pinia ก่อน router เพราะ route guard ใช้ authStore
app.use(pinia)
app.use(router)
app.use(vuetify)

app.mount('#app')
