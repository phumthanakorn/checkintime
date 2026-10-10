<template>
  <div>
    <div class="mt-4 text-center">
      <span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-status-checkin/10">
        <AppIcon name="lock-key" :size="32" weight="duotone" class="text-status-checkin" />
      </span>
      <h1 class="text-2xl font-bold text-ink">ตั้งรหัสผ่านใหม่</h1>
      <p class="mx-auto mt-1 max-w-[300px] text-[13px] text-ink-muted">
        ตั้งรหัสผ่านที่จำง่ายสำหรับคุณ แต่เดายากสำหรับคนอื่น
      </p>
    </div>

    <div class="mt-6 rounded-3xl bg-card p-5 shadow-sm">
      <form v-if="token" class="space-y-4" novalidate @submit.prevent="submit">
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
        <button
          type="submit"
          class="flex h-12 w-full items-center justify-center rounded-2xl bg-status-checkin font-semibold text-white shadow-lg shadow-status-checkin/30 disabled:opacity-50 disabled:shadow-none"
          :disabled="loading"
        >
          <LoadingDots v-if="loading" />
          <template v-else>ตั้งรหัสผ่านใหม่</template>
        </button>
      </form>

      <div v-else class="space-y-4 text-center">
        <p class="text-sm text-status-outside">ลิงก์นี้ไม่ถูกต้อง หรือหมดอายุแล้ว</p>
        <button
          type="button"
          class="flex h-12 w-full items-center justify-center rounded-2xl bg-status-checkin font-semibold text-white shadow-lg shadow-status-checkin/30"
          @click="router.replace({ name: 'forgot-password' })"
        >
          ขอลิงก์ใหม่
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PasswordField from '@/components/common/PasswordField.vue'
import LoadingDots from '@/components/feedback/LoadingDots.vue'
import { useAuthStore } from '@/store'
import { useNotification } from '@/composables/useNotification'
import { validateNewPassword } from '@/utils/validators'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const notify = useNotification()

const token = typeof route.query.token === 'string' ? route.query.token : ''
const password = ref('')
const confirm = ref('')
const passwordError = ref('')
const confirmError = ref('')
const loading = ref(false)

async function submit() {
  passwordError.value = validateNewPassword(password.value)
  confirmError.value = confirm.value === password.value ? '' : 'รหัสผ่านไม่ตรงกัน'
  if (passwordError.value || confirmError.value) return

  loading.value = true
  try {
    await auth.resetPassword({ token, newPassword: password.value })
    notify.success('ตั้งรหัสผ่านใหม่สำเร็จ')
    router.replace({ name: 'home' })
  } catch (e) {
    notify.error(e.message)
  } finally {
    loading.value = false
  }
}
</script>
