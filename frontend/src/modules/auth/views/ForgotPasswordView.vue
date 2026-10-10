<template>
  <div>
    <div class="flex items-center gap-3">
      <button
        type="button"
        class="flex h-10 w-10 items-center justify-center rounded-full bg-card text-ink shadow-sm"
        aria-label="ย้อนกลับ"
        @click="router.replace({ name: 'login' })"
      >
        <AppIcon name="caret-left" :size="22" />
      </button>
    </div>

    <div class="mt-8 text-center">
      <span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl" :class="sent ? 'bg-status-checkin/10' : 'bg-status-checkin/10'">
        <AppIcon :name="sent ? 'envelope' : 'key'" :size="32" weight="duotone" class="text-status-checkin" />
      </span>
      <h1 class="text-2xl font-bold text-ink">ลืมรหัสผ่าน</h1>
      <p class="mx-auto mt-1 max-w-[300px] text-[13px] text-ink-muted">
        {{ sent ? 'ส่งลิงก์รีเซ็ตรหัสผ่านไปที่อีเมลของคุณแล้ว' : 'กรอกอีเมลที่ลงทะเบียนไว้ ระบบจะส่งลิงก์ตั้งรหัสผ่านใหม่ไปให้' }}
      </p>
    </div>

    <div class="mt-6 rounded-3xl bg-card p-5 shadow-sm">
      <form v-if="!sent" class="space-y-4" novalidate @submit.prevent="submit">
        <TextField
          v-model="email"
          label="อีเมล"
          icon="envelope"
          placeholder="name@company.com"
          inputmode="email"
          autocomplete="username"
          :error="error"
        />
        <button
          type="submit"
          class="flex h-12 w-full items-center justify-center rounded-2xl bg-status-checkin font-semibold text-white shadow-lg shadow-status-checkin/30 disabled:opacity-50 disabled:shadow-none"
          :disabled="loading"
        >
          <LoadingDots v-if="loading" />
          <template v-else>ส่งลิงก์รีเซ็ตรหัสผ่าน</template>
        </button>
      </form>

      <div v-else class="space-y-4 text-center">
        <p class="text-sm text-ink-muted">
          ลิงก์มีอายุ 30 นาที ไม่เห็นอีเมล? ลองเช็คโฟลเดอร์ Junk/Spam หรือกดส่งอีกครั้ง
        </p>
        <button
          type="button"
          class="font-semibold text-status-checkin disabled:text-ink-muted"
          :disabled="countdown > 0"
          @click="submit"
        >
          {{ countdown ? `ส่งอีกครั้งใน ${countdown} วินาที` : 'ส่งลิงก์อีกครั้ง' }}
        </button>
      </div>
    </div>

    <p class="mt-6 text-center text-xs text-ink-muted">
      เข้าอีเมลไม่ได้? ติดต่อฝ่ายบุคคล (HR)
      <span class="font-semibold text-ink">{{ HR_CONTACT_PHONE }}</span>
    </p>
  </div>
</template>

<script setup>
import { onBeforeUnmount, ref } from 'vue'
import { useRouter } from 'vue-router'
import TextField from '@/components/common/TextField.vue'
import LoadingDots from '@/components/feedback/LoadingDots.vue'
import { authService } from '@/api/services/authService'
import { useNotification } from '@/composables/useNotification'
import { HR_CONTACT_PHONE } from '@/utils/constants'
import { isEmail } from '@/utils/validators'

const router = useRouter()
const notify = useNotification()

const email = ref('')
const error = ref('')
const loading = ref(false)
const sent = ref(false)
const countdown = ref(0)
let timer = null

function startCountdown() {
  countdown.value = 60
  clearInterval(timer)
  timer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) clearInterval(timer)
  }, 1000)
}
onBeforeUnmount(() => clearInterval(timer))

async function submit() {
  const value = email.value.trim()
  error.value = !value ? 'กรุณากรอกอีเมล' : !isEmail(value) ? 'รูปแบบอีเมลไม่ถูกต้อง' : ''
  if (error.value) return

  loading.value = true
  try {
    await authService.requestPasswordReset({ email: value })
    sent.value = true
    startCountdown()
  } catch (e) {
    notify.error(e.message)
  } finally {
    loading.value = false
  }
}
</script>
