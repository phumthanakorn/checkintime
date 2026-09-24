<template>
  <!-- ช่อง OTP: ใช้ input จริงตัวเดียว (รองรับวางรหัส / เติมอัตโนมัติจาก SMS) แล้วแสดงเป็นกล่องทีละหลัก -->
  <label class="relative block cursor-text">
    <input
      ref="inputRef"
      :value="model"
      class="absolute inset-0 h-full w-full opacity-0"
      type="text"
      inputmode="numeric"
      autocomplete="one-time-code"
      :maxlength="length"
      aria-label="รหัส OTP"
      @input="onInput"
      @focus="focused = true"
      @blur="focused = false"
    />
    <span class="grid gap-2" :style="{ gridTemplateColumns: `repeat(${length}, minmax(0, 1fr))` }">
      <span
        v-for="i in length"
        :key="i"
        class="flex h-14 items-center justify-center rounded-2xl border-2 bg-card font-display text-2xl font-bold text-ink transition-colors"
        :class="
          error
            ? 'border-status-outside'
            : focused && i - 1 === Math.min(model.length, length - 1)
              ? 'border-status-checkin'
              : model[i - 1]
                ? 'border-slate-300'
                : 'border-slate-200'
        "
      >
        {{ model[i - 1] || '' }}
      </span>
    </span>
  </label>
</template>

<script setup>
import { onMounted, ref } from 'vue'

const model = defineModel({ type: String, default: '' })

const props = defineProps({
  length: { type: Number, default: 6 },
  error: { type: Boolean, default: false },
  autofocus: { type: Boolean, default: true },
})

const emit = defineEmits(['complete'])

const inputRef = ref()
const focused = ref(false)

function onInput(event) {
  const digits = event.target.value.replace(/\D/g, '').slice(0, props.length)
  event.target.value = digits
  model.value = digits
  if (digits.length === props.length) emit('complete', digits)
}

onMounted(() => {
  if (props.autofocus) inputRef.value?.focus()
})
</script>
