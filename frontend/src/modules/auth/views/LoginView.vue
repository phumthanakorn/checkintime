<template>
  <div class="flex flex-col items-center">
    <div
      class="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-status-checkin text-white shadow-lg shadow-emerald-500/30"
    >
      <AppIcon name="clock" :size="34" />
    </div>
    <h1 class="text-2xl font-bold text-ink">เข้าสู่ระบบ</h1>
    <p class="mt-1 text-[13px] text-ink-muted">ระบบลงเวลาทำงานและจัดการกะงานพนักงาน</p>
  </div>

  <div class="mt-6 rounded-3xl bg-card p-5 shadow-sm">
    <LoginForm
      :loading="loading"
      :initial-username="rememberedUsername"
      @submit="handleLogin"
      @forgot="router.push({ name: 'forgot-password' })"
    />

    <div class="my-5 flex items-center gap-3 text-xs text-slate-400">
      <span class="h-px flex-1 bg-slate-200" />
      หรือเข้าสู่ระบบด้วย
      <span class="h-px flex-1 bg-slate-200" />
    </div>

    <div class="grid grid-cols-2 gap-3">
      <button class="alt-btn" type="button" @click="comingSoon">
        <span class="flex h-5 w-5 items-center justify-center rounded bg-[#06C755] text-[10px] font-bold text-white">L</span>
        LINE Official
      </button>
      <button class="alt-btn" type="button" @click="loginWithPin">
        <AppIcon name="fingerprint" :size="20" class="text-emerald-600" />
        PIN / Biometric
      </button>
    </div>
  </div>

  <div
    class="mt-6 flex items-center justify-center gap-2 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-2 text-center text-xs text-emerald-700"
  >
    <AppIcon name="shield-check" :size="16" />
    ระบบปลอดภัยตามมาตรฐานความปลอดภัยข้อมูลพนักงาน (PDPA)
  </div>

  <p class="mt-4 text-center text-xs text-slate-500">
    พบปัญหาการเข้าสู่ระบบ? ติดต่อฝ่ายบุคคล (HR)
    <span class="font-semibold text-slate-800">{{ HR_CONTACT_PHONE }}</span>
  </p>
  <p class="mt-4 text-center text-xs text-slate-400">{{ APP_NAME }} v{{ APP_VERSION }}</p>

  <p v-if="USE_MOCK" class="mt-4 text-center text-[11px] text-slate-400">
    โหมดทดลอง: EMP-2569001 / 123456
  </p>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import LoginForm from '../components/LoginForm.vue'
import { useAuth } from '@/composables/useAuth'
import { useNotification } from '@/composables/useNotification'
import { APP_NAME, APP_VERSION, HR_CONTACT_PHONE, STORAGE_KEYS, USE_MOCK } from '@/utils/constants'

const { login } = useAuth()
const notify = useNotification()
const router = useRouter()

const loading = ref(false)
const rememberedUsername = localStorage.getItem(STORAGE_KEYS.REMEMBER_USERNAME) || ''

async function handleLogin({ username, password, remember }) {
  loading.value = true
  try {
    await login({ username, password })
    if (remember) localStorage.setItem(STORAGE_KEYS.REMEMBER_USERNAME, username)
    else localStorage.removeItem(STORAGE_KEYS.REMEMBER_USERNAME)
  } catch (error) {
    notify.error(error.message)
  } finally {
    loading.value = false
  }
}

function comingSoon() {
  notify.info('ช่องทางนี้จะเปิดให้ใช้งานเร็ว ๆ นี้')
}

/** เข้าด้วย PIN ได้เมื่อเคยตั้ง PIN บนเครื่องนี้ไว้ */
function loginWithPin() {
  if (localStorage.getItem(STORAGE_KEYS.PIN_USER)) router.push({ name: 'pin-login' })
  else notify.info('ยังไม่ได้ตั้งค่า PIN บนเครื่องนี้ เข้าสู่ระบบด้วยรหัสผ่านแล้วตั้งค่าที่เมนู “ฉัน”')
}
</script>

<style scoped>
.alt-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  height: 2.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: #334155;
  transition: background 0.15s;
}

.alt-btn:hover {
  background: #f8fafc;
}
</style>
