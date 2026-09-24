<template>
  <div>
    <!-- หัวหน้า + ขั้นตอน -->
    <div class="flex items-center gap-3">
      <button
        v-if="step < 4"
        type="button"
        class="flex h-10 w-10 items-center justify-center rounded-full bg-card text-ink shadow-sm"
        aria-label="ย้อนกลับ"
        @click="back"
      >
        <AppIcon name="caret-left" :size="22" />
      </button>
      <div v-if="step < 4" class="flex flex-1 justify-center gap-1.5 pr-10">
        <span
          v-for="i in 3"
          :key="i"
          class="h-1.5 rounded-full transition-all duration-300"
          :class="i <= step ? 'w-8 bg-status-checkin' : 'w-4 bg-slate-200'"
        />
      </div>
    </div>

    <div class="mt-8 text-center">
      <span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl" :class="current.iconBg">
        <AppIcon :name="current.icon" :size="32" weight="duotone" :class="current.iconColor" />
      </span>
      <h1 class="text-2xl font-bold text-ink">{{ current.title }}</h1>
      <p class="mx-auto mt-1 max-w-[300px] text-[13px] text-ink-muted">{{ current.subtitle }}</p>
    </div>

    <div class="mt-6 rounded-3xl bg-card p-5 shadow-sm">
      <!-- 1) ระบุบัญชี -->
      <form v-if="step === 1" class="space-y-4" novalidate @submit.prevent="requestOtp">
        <TextField
          v-model="username"
          label="รหัสพนักงาน หรือ อีเมล"
          icon="user"
          placeholder="เช่น EMP-2569001"
          autocomplete="username"
          :error="error"
        />
        <SubmitButton :loading="loading">ส่งรหัส OTP</SubmitButton>
      </form>

      <!-- 2) OTP -->
      <div v-else-if="step === 2" class="space-y-4">
        <OtpInput v-model="otp" :error="!!error" @complete="verifyOtp" />
        <p v-if="error" class="text-center text-xs text-status-outside">{{ error }}</p>
        <p class="text-center font-display text-[11px] text-ink-muted">Ref: {{ refCode }}</p>
        <SubmitButton :loading="loading" :disabled="otp.length < 6" @click="verifyOtp">ยืนยันรหัส</SubmitButton>
        <p class="text-center text-xs text-ink-muted">
          ไม่ได้รับรหัส?
          <button
            type="button"
            class="font-semibold"
            :class="countdown ? 'text-ink-muted' : 'text-status-checkin'"
            :disabled="countdown > 0 || loading"
            @click="requestOtp"
          >
            {{ countdown ? `ส่งอีกครั้งใน ${countdown} วินาที` : 'ส่งรหัสอีกครั้ง' }}
          </button>
        </p>
        <p v-if="USE_MOCK" class="rounded-xl bg-app-bg py-2 text-center text-[11px] text-ink-muted">
          โหมดทดลอง: รหัส OTP คือ <span class="font-display font-semibold text-ink">123456</span>
        </p>
      </div>

      <!-- 3) ตั้งรหัสใหม่ -->
      <form v-else-if="step === 3" class="space-y-4" novalidate @submit.prevent="resetPassword">
        <PasswordField
          v-model="password"
          label="รหัสผ่านใหม่"
          icon="lock-key"
          required
          show-strength
          autocomplete="new-password"
          :error="passwordError"
        />
        <PasswordField
          v-model="confirm"
          label="ยืนยันรหัสผ่านใหม่"
          icon="lock-key"
          required
          autocomplete="new-password"
          :error="confirmError"
        />
        <p class="text-[11px] text-ink-muted">อย่างน้อย 8 ตัวอักษร และมีทั้งตัวอักษรกับตัวเลข</p>
        <SubmitButton :loading="loading">ตั้งรหัสผ่านใหม่</SubmitButton>
      </form>

      <!-- 4) สำเร็จ -->
      <div v-else class="space-y-4 text-center">
        <p class="text-sm text-ink-muted">ใช้รหัสผ่านใหม่เข้าสู่ระบบได้ทันที</p>
        <SubmitButton @click="router.replace({ name: 'login' })">กลับไปหน้าเข้าสู่ระบบ</SubmitButton>
      </div>
    </div>

    <p class="mt-6 text-center text-xs text-ink-muted">
      ไม่ได้ลงทะเบียนเบอร์โทร? ติดต่อฝ่ายบุคคล (HR)
      <span class="font-semibold text-ink">{{ HR_CONTACT_PHONE }}</span>
    </p>
  </div>
</template>

<script setup>
import { computed, h, onBeforeUnmount, ref } from 'vue'
import { useRouter } from 'vue-router'
import TextField from '@/components/common/TextField.vue'
import PasswordField from '@/components/common/PasswordField.vue'
import OtpInput from '@/components/common/OtpInput.vue'
import LoadingDots from '@/components/feedback/LoadingDots.vue'
import { authService } from '@/api/services/authService'
import { useNotification } from '@/composables/useNotification'
import { HR_CONTACT_PHONE, USE_MOCK } from '@/utils/constants'
import { validateNewPassword } from '@/utils/validators'

// ปุ่มหลักของหน้า (ใช้ซ้ำทุกขั้น)
const SubmitButton = (props, { slots, attrs }) =>
  h(
    'button',
    {
      type: attrs.onClick ? 'button' : 'submit',
      class:
        'flex h-12 w-full items-center justify-center rounded-2xl bg-status-checkin font-semibold text-white shadow-lg shadow-status-checkin/30 disabled:opacity-50 disabled:shadow-none',
      disabled: props.loading || props.disabled,
      ...attrs,
    },
    props.loading ? h(LoadingDots) : slots.default?.(),
  )
SubmitButton.props = ['loading', 'disabled']

const router = useRouter()
const notify = useNotification()

const step = ref(1)
const username = ref('')
const otp = ref('')
const password = ref('')
const confirm = ref('')
const error = ref('')
const passwordError = ref('')
const confirmError = ref('')
const loading = ref(false)
const maskedPhone = ref('')
const refCode = ref('')
const resetToken = ref('')
const countdown = ref(0)
let timer = null

const current = computed(
  () =>
    ({
      1: {
        title: 'ลืมรหัสผ่าน',
        subtitle: 'กรอกรหัสพนักงานหรืออีเมล ระบบจะส่งรหัส OTP ไปยังเบอร์โทรที่ลงทะเบียนไว้',
        icon: 'key',
        iconBg: 'bg-status-checkin/10',
        iconColor: 'text-status-checkin',
      },
      2: {
        title: 'กรอกรหัส OTP',
        subtitle: `ส่งรหัส 6 หลักไปที่ ${maskedPhone.value} แล้ว`,
        icon: 'mobile',
        iconBg: 'bg-metric-blue/10',
        iconColor: 'text-metric-blue',
      },
      3: {
        title: 'ตั้งรหัสผ่านใหม่',
        subtitle: 'ตั้งรหัสผ่านที่จำง่ายสำหรับคุณ แต่เดายากสำหรับคนอื่น',
        icon: 'lock-key',
        iconBg: 'bg-violet-50',
        iconColor: 'text-violet-500',
      },
      4: {
        title: 'เปลี่ยนรหัสผ่านสำเร็จ',
        subtitle: '',
        icon: 'seal-check',
        iconBg: 'bg-status-checkin/10',
        iconColor: 'text-status-checkin',
      },
    })[step.value],
)

function startCountdown() {
  countdown.value = 60
  clearInterval(timer)
  timer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) clearInterval(timer)
  }, 1000)
}
onBeforeUnmount(() => clearInterval(timer))

async function requestOtp() {
  error.value = username.value.trim() ? '' : 'กรุณากรอกรหัสพนักงานหรืออีเมล'
  if (error.value) return
  loading.value = true
  try {
    const result = await authService.requestPasswordReset({ username: username.value })
    maskedPhone.value = result.maskedPhone
    refCode.value = result.refCode
    otp.value = ''
    step.value = 2
    startCountdown()
    notify.success('ส่งรหัส OTP แล้ว')
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function verifyOtp() {
  if (otp.value.length < 6 || loading.value) return
  loading.value = true
  error.value = ''
  try {
    const result = await authService.verifyResetOtp({ username: username.value, otp: otp.value })
    resetToken.value = result.resetToken
    step.value = 3
  } catch (e) {
    error.value = e.message
    otp.value = ''
  } finally {
    loading.value = false
  }
}

async function resetPassword() {
  passwordError.value = validateNewPassword(password.value)
  confirmError.value = confirm.value === password.value ? '' : 'รหัสผ่านไม่ตรงกัน'
  if (passwordError.value || confirmError.value) return
  loading.value = true
  try {
    await authService.resetPassword({ resetToken: resetToken.value, newPassword: password.value })
    step.value = 4
  } catch (e) {
    notify.error(e.message)
  } finally {
    loading.value = false
  }
}

function back() {
  error.value = ''
  if (step.value === 1) router.replace({ name: 'login' })
  else step.value--
}
</script>
