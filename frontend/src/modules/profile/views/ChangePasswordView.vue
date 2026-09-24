<template>
  <div class="space-y-5">
    <PageHeader title="เปลี่ยนรหัสผ่าน" :back-to="{ name: 'profile' }" />

    <form class="space-y-4 rounded-3xl bg-card p-4 shadow-sm" novalidate @submit.prevent="submit">
      <PasswordField
        v-model="form.current"
        label="รหัสผ่านปัจจุบัน"
        required
        autocomplete="current-password"
        :error="errors.current"
      />
      <PasswordField
        v-model="form.next"
        label="รหัสผ่านใหม่"
        icon="lock-key"
        required
        show-strength
        autocomplete="new-password"
        :error="errors.next"
      />
      <PasswordField
        v-model="form.confirm"
        label="ยืนยันรหัสผ่านใหม่"
        icon="lock-key"
        required
        autocomplete="new-password"
        :error="errors.confirm"
      />

      <!-- เงื่อนไขรหัสผ่าน -->
      <ul class="space-y-1.5 rounded-2xl bg-app-bg p-3">
        <li v-for="rule in rules" :key="rule.label" class="flex items-center gap-2 text-xs">
          <AppIcon
            :name="rule.ok ? 'check' : 'x'"
            :size="14"
            weight="bold"
            :class="rule.ok ? 'text-status-checkin' : 'text-slate-300'"
          />
          <span :class="rule.ok ? 'text-ink' : 'text-ink-muted'">{{ rule.label }}</span>
        </li>
      </ul>

      <button
        type="submit"
        class="flex h-12 w-full items-center justify-center rounded-2xl bg-status-checkin font-semibold text-white shadow-lg shadow-status-checkin/30 disabled:opacity-60"
        :disabled="saving"
      >
        <LoadingDots v-if="saving" />
        <template v-else>เปลี่ยนรหัสผ่าน</template>
      </button>
    </form>

    <p class="px-2 text-center text-xs text-ink-muted">
      ลืมรหัสผ่านปัจจุบัน? ออกจากระบบแล้วเลือก “ลืมรหัสผ่าน?” ที่หน้าเข้าสู่ระบบ
    </p>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/layout/PageHeader.vue'
import PasswordField from '@/components/common/PasswordField.vue'
import { authService } from '@/api/services/authService'
import { useNotification } from '@/composables/useNotification'
import { validateNewPassword } from '@/utils/validators'

const notify = useNotification()
const router = useRouter()

const form = reactive({ current: '', next: '', confirm: '' })
const errors = reactive({ current: '', next: '', confirm: '' })
const saving = ref(false)

const rules = computed(() => [
  { label: 'อย่างน้อย 8 ตัวอักษร', ok: form.next.length >= 8 },
  { label: 'มีตัวอักษรภาษาอังกฤษ', ok: /[A-Za-z]/.test(form.next) },
  { label: 'มีตัวเลข', ok: /\d/.test(form.next) },
  { label: 'รหัสผ่านใหม่ตรงกันทั้งสองช่อง', ok: !!form.next && form.next === form.confirm },
])

function validate() {
  errors.current = form.current ? '' : 'กรุณากรอกรหัสผ่านปัจจุบัน'
  errors.next = validateNewPassword(form.next)
  errors.confirm = form.confirm === form.next ? '' : 'รหัสผ่านใหม่ไม่ตรงกัน'
  return !errors.current && !errors.next && !errors.confirm
}

async function submit() {
  if (!validate()) return
  saving.value = true
  try {
    await authService.changePassword({ currentPassword: form.current, newPassword: form.next })
    notify.success('เปลี่ยนรหัสผ่านเรียบร้อย')
    router.replace({ name: 'profile' })
  } catch (error) {
    if (/ปัจจุบัน/.test(error.message)) errors.current = error.message
    else notify.error(error.message)
  } finally {
    saving.value = false
  }
}
</script>
