<template>
  <form class="space-y-4" novalidate @submit.prevent="handleSubmit">
    <div>
      <label for="username" class="mb-2 block text-sm font-medium text-ink">อีเมล</label>
      <div class="field" :class="{ 'field--error': errors.username }">
        <AppIcon name="user" :size="20" class="text-slate-400" />
        <input
          id="username"
          v-model.trim="form.username"
          type="email"
          autocomplete="username"
          placeholder="อีเมลที่ลงทะเบียนไว้กับฝ่ายบุคคล"
        />
      </div>
      <p v-if="errors.username" class="mt-1 text-xs text-red-500">{{ errors.username }}</p>
    </div>

    <div>
      <PasswordField
        v-model="form.password"
        label="รหัสผ่าน"
        icon="lock-open"
        placeholder="กรอกรหัสผ่านของคุณ"
        :error="errors.password"
      />
    </div>

    <div class="flex items-center justify-between text-sm">
      <label class="flex cursor-pointer items-center gap-2 text-slate-600">
        <input v-model="form.remember" type="checkbox" class="h-4 w-4 accent-emerald-600" />
        จดจำการเข้าสู่ระบบ
      </label>
      <button type="button" class="font-medium text-status-checkin" @click="emit('forgot')">ลืมรหัสผ่าน?</button>
    </div>

    <button
      type="submit"
      :disabled="loading"
      class="login-submit flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-status-checkin font-semibold text-white shadow-lg shadow-status-checkin/30 transition hover:brightness-95 disabled:opacity-70"
    >
      <LoadingDots v-if="loading" />
      <template v-else>
        เข้าสู่ระบบ
        <AppIcon name="arrow-right" :size="20" />
      </template>
    </button>
  </form>
</template>

<script setup>
import { reactive } from 'vue'
import { email as emailRule, required } from '@/utils/validators'
import PasswordField from '@/components/common/PasswordField.vue'

const props = defineProps({
  loading: { type: Boolean, default: false },
  initialUsername: { type: String, default: '' },
})

const emit = defineEmits(['submit', 'forgot'])

const form = reactive({ username: props.initialUsername, password: '', remember: !!props.initialUsername })
const errors = reactive({ username: '', password: '' })

const requireUsername = required('กรุณากรอกอีเมล')
const validateEmailFormat = emailRule()
const rules = {
  password: required('กรุณากรอกรหัสผ่าน'),
}

function validate() {
  // username: เช็คว่ากรอกหรือยังก่อน แล้วค่อยเช็ครูปแบบอีเมล (ระบบบังคับ login ด้วยอีเมลอย่างเดียวแล้ว
  // ไม่รับรหัสพนักงานอีกต่อไป ดู AuthController::login())
  const requiredResult = requireUsername(form.username)
  const emailResult = requiredResult === true ? validateEmailFormat(form.username) : requiredResult
  errors.username = emailResult === true ? '' : emailResult

  for (const key of Object.keys(rules)) {
    const result = rules[key](form[key])
    errors[key] = result === true ? '' : result
  }
  return !errors.username && !errors.password
}

function handleSubmit() {
  if (validate()) emit('submit', { ...form })
}
</script>

<style scoped>
.login-submit { border:0; border-radius:0.75rem !important; }

.field {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  height: 3rem;
  padding: 0 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  background: #f8fafc;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.field:focus-within {
  border-color: #fd7e14;
  box-shadow: 0 0 0 3px rgb(253 126 20 / 0.15);
  background: #fff;
}

.field--error {
  border-color: #ef4444;
}

.field input {
  flex: 1;
  min-width: 0;
  outline: none;
  background: transparent;
  font-size: 0.875rem;
  color: #1e293b;
}

.field input::placeholder {
  color: #94a3b8;
}
</style>
