<template>
  <div v-if="pinUser" class="flex min-h-[calc(100vh-5rem)] flex-col">
    <div class="flex flex-col items-center pt-4 text-center">
      <img
        v-if="pinUser.avatarUrl"
        :src="pinUser.avatarUrl"
        alt=""
        class="h-20 w-20 rounded-full object-cover ring-4 ring-card shadow-md"
      />
      <span
        v-else
        class="flex h-20 w-20 items-center justify-center rounded-full bg-status-checkin/10 text-3xl font-bold text-status-checkin ring-4 ring-card shadow-md"
      >
        {{ getInitials(pinUser.name) }}
      </span>
      <p class="mt-4 text-[13px] text-ink-muted">ยินดีต้อนรับกลับ</p>
      <h1 class="text-xl font-bold text-ink">{{ pinUser.name }}</h1>
      <p class="font-display text-xs text-ink-muted">{{ pinUser.employeeCode }}</p>
    </div>

    <div class="mt-8">
      <p class="mb-4 text-center text-sm font-medium text-ink">กรอก PIN 6 หลัก</p>
      <PinPad
        v-model="pin"
        :error="error"
        :disabled="loading || locked"
        :hint="locked ? '' : attemptsLeft < MAX_ATTEMPTS ? `ลองได้อีก ${attemptsLeft} ครั้ง` : ''"
        @complete="submit"
      />
      <div class="mt-3 flex h-6 justify-center">
        <LoadingDots v-if="loading" class="text-status-checkin" />
      </div>
    </div>

    <div class="mt-auto space-y-2 pb-2 pt-6 text-center">
      <router-link :to="{ name: 'login' }" class="block text-sm font-semibold text-status-checkin no-underline">
        เข้าสู่ระบบด้วยรหัสผ่าน
      </router-link>
      <button type="button" class="text-xs text-ink-muted" @click="forget">ไม่ใช่บัญชีของคุณ? ลบออกจากเครื่องนี้</button>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import PinPad from '@/components/common/PinPad.vue'
import { useAuth } from '@/composables/useAuth'
import { useNotification } from '@/composables/useNotification'
import { STORAGE_KEYS } from '@/utils/constants'
import { getInitials } from '@/utils/formatters'

const MAX_ATTEMPTS = 5

const { loginWithPin } = useAuth()
const notify = useNotification()
const router = useRouter()

const pinUser = ref(readPinUser())
const pin = ref('')
const error = ref('')
const loading = ref(false)
const attempts = ref(0)

const attemptsLeft = computed(() => MAX_ATTEMPTS - attempts.value)
const locked = computed(() => attemptsLeft.value <= 0)

function readPinUser() {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEYS.PIN_USER))
  } catch {
    return null
  }
}

onMounted(() => {
  if (!pinUser.value) {
    notify.info('ยังไม่ได้ตั้งค่า PIN บนเครื่องนี้ เข้าสู่ระบบด้วยรหัสผ่านแล้วตั้งค่าที่เมนู “ฉัน”')
    router.replace({ name: 'login' })
  }
})

async function submit(value) {
  loading.value = true
  error.value = ''
  try {
    await loginWithPin({ employeeCode: pinUser.value.employeeCode, pin: value })
  } catch (e) {
    attempts.value++
    error.value = locked.value ? 'ใส่ PIN ผิดหลายครั้ง กรุณาเข้าสู่ระบบด้วยรหัสผ่าน' : e.message
    pin.value = ''
  } finally {
    loading.value = false
  }
}

function forget() {
  localStorage.removeItem(STORAGE_KEYS.PIN_USER)
  router.replace({ name: 'login' })
}
</script>
