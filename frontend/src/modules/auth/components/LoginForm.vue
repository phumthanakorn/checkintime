<template>
  <form class="space-y-4" novalidate @submit.prevent="handleSubmit">
    <div>
      <label for="username" class="mb-2 block text-sm font-medium text-ink">รหัสพนักงาน หรือ อีเมล</label>
      <div class="field" :class="{ 'field--error': errors.username }">
        <AppIcon name="user" :size="20" class="text-slate-400" />
        <input
          id="username"
          v-model.trim="form.username"
          type="text"
          autocomplete="username"
          placeholder="เช่น EMP-2569001 หรือชื่อผู้ใช้"
        />
      </div>
      <p v-if="errors.username" class="mt-1 text-xs text-red-500">{{ errors.username }}</p>
    </div>

    <div>
      <label for="password" class="mb-2 block text-sm font-medium text-ink">รหัสผ่าน</label>
      <div class="field" :class="{ 'field--error': errors.password }">
        <AppIcon name="lock-open" :size="20" class="text-slate-400" />
        <input
          id="password"
          v-model="form.password"
          :type="showPassword ? 'text' : 'password'"
          autocomplete="current-password"
          placeholder="กรอกรหัสผ่านของคุณ"
        />
        <button
          type="button"
          class="text-slate-400"
          :aria-label="showPassword ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน'"
          @click="showPassword = !showPassword"
        >
          <AppIcon :name="showPassword ? 'eye-slash' : 'eye'" :size="20" />
        </button>
      </div>
      <p v-if="errors.password" class="mt-1 text-xs text-red-500">{{ errors.password }}</p>
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
      class="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-status-checkin font-semibold text-white shadow-lg shadow-status-checkin/30 transition hover:brightness-95 disabled:opacity-70"
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
import { reactive, ref } from 'vue'
import { required } from '@/utils/validators'

const props = defineProps({
  loading: { type: Boolean, default: false },
  initialUsername: { type: String, default: '' },
})

const emit = defineEmits(['submit', 'forgot'])

const showPassword = ref(false)
const form = reactive({ username: props.initialUsername, password: '', remember: !!props.initialUsername })
const errors = reactive({ username: '', password: '' })

const rules = {
  username: required('กรุณากรอกรหัสพนักงานหรืออีเมล'),
  password: required('กรุณากรอกรหัสผ่าน'),
}

function validate() {
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
  border-color: #10b981;
  box-shadow: 0 0 0 3px rgb(16 185 129 / 0.15);
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
  color: #0f172a;
}

.field input::placeholder {
  color: #94a3b8;
}
</style>
