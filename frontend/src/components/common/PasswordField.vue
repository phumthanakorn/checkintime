<template>
  <div>
    <TextField
      v-model="model"
      :label="label"
      :type="visible ? 'text' : 'password'"
      :icon="icon"
      :placeholder="placeholder"
      :error="error"
      :required="required"
      :autocomplete="autocomplete"
    >
      <template #append>
        <button
          type="button"
          class="text-slate-400"
          :aria-label="visible ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน'"
          @click="visible = !visible"
        >
          <AppIcon :name="visible ? 'eye-slash' : 'eye'" :size="20" />
        </button>
      </template>
    </TextField>

    <!-- แถบความแข็งแรงของรหัสผ่าน -->
    <div v-if="showStrength && model" class="mt-2">
      <div class="grid grid-cols-4 gap-1">
        <span
          v-for="i in 4"
          :key="i"
          class="h-1.5 rounded-full transition-colors duration-200"
          :class="i <= strength.score ? strength.bar : 'bg-slate-200'"
        />
      </div>
      <p class="mt-1 text-[11px]" :class="strength.text">ความแข็งแรง: {{ strength.label }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import TextField from './TextField.vue'
import { passwordStrength } from '@/utils/validators'

const model = defineModel({ type: String, default: '' })

defineProps({
  label: { type: String, default: 'รหัสผ่าน' },
  icon: { type: String, default: 'lock' },
  placeholder: { type: String, default: '' },
  error: { type: String, default: '' },
  required: { type: Boolean, default: false },
  autocomplete: { type: String, default: 'current-password' },
  showStrength: { type: Boolean, default: false },
})

const visible = ref(false)

const LEVELS = [
  { label: 'อ่อนมาก', bar: 'bg-status-outside', text: 'text-status-outside' },
  { label: 'อ่อน', bar: 'bg-status-outside', text: 'text-status-outside' },
  { label: 'พอใช้', bar: 'bg-status-checkout', text: 'text-amber-600' },
  { label: 'ดี', bar: 'bg-status-checkin', text: 'text-status-checkin' },
  { label: 'แข็งแรงมาก', bar: 'bg-status-checkin', text: 'text-status-checkin' },
]

const strength = computed(() => {
  const score = passwordStrength(model.value)
  return { score, ...LEVELS[score] }
})
</script>
