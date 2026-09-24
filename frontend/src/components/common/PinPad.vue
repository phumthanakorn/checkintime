<template>
  <div class="flex flex-col items-center">
    <!-- จุดแสดงจำนวนหลักที่กดแล้ว -->
    <div class="flex gap-3.5" :class="shaking && 'shake'" role="status" :aria-label="`กรอกแล้ว ${pin.length} จาก ${length} หลัก`">
      <span
        v-for="i in length"
        :key="i"
        class="h-3.5 w-3.5 rounded-full transition-all duration-150"
        :class="
          error ? 'bg-status-outside' : i <= pin.length ? 'scale-110 bg-status-checkin' : 'bg-slate-200'
        "
      />
    </div>
    <p class="mt-3 h-5 text-xs" :class="error ? 'text-status-outside' : 'text-ink-muted'">{{ error || hint }}</p>

    <!-- แป้นตัวเลข -->
    <div class="mt-4 grid w-full max-w-[280px] grid-cols-3 gap-3">
      <button
        v-for="key in KEYS"
        :key="key"
        type="button"
        class="flex h-16 items-center justify-center rounded-2xl font-display text-2xl font-semibold text-ink transition active:scale-95"
        :class="key === '' ? 'pointer-events-none' : key === 'del' ? 'text-ink-muted active:bg-slate-100' : 'bg-card shadow-sm active:bg-status-checkin/10'"
        :disabled="disabled || key === ''"
        :aria-label="key === 'del' ? 'ลบ' : key || undefined"
        @click="press(key)"
      >
        <AppIcon v-if="key === 'del'" name="backspace" :size="26" />
        <template v-else>{{ key }}</template>
      </button>
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

const pin = defineModel({ type: String, default: '' })

const props = defineProps({
  length: { type: Number, default: 6 },
  hint: { type: String, default: '' },
  error: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['complete'])

const KEYS = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '', '0', 'del']
const shaking = ref(false)

function press(key) {
  if (props.disabled) return
  if (key === 'del') {
    pin.value = pin.value.slice(0, -1)
    return
  }
  if (pin.value.length >= props.length) return
  // ใช้ค่าที่คำนวณเอง: ค่า v-model จากหน้าแม่ยังไม่อัปเดตจนกว่าจะ re-render
  const next = pin.value + key
  pin.value = next
  if (next.length === props.length) emit('complete', next)
}

// สั่นเมื่อมี error ใหม่
watch(
  () => props.error,
  (value) => {
    if (!value) return
    shaking.value = true
    try {
      navigator.vibrate?.([40, 40, 40])
    } catch {
      // ไม่รองรับก็ข้ามไป
    }
    setTimeout(() => (shaking.value = false), 450)
  },
)

// รองรับคีย์บอร์ด (ตัวเลข / Backspace)
function onKey(event) {
  if (/^\d$/.test(event.key)) press(event.key)
  else if (event.key === 'Backspace') press('del')
}
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))
</script>

<style scoped>
.shake {
  animation: shake 0.4s ease-in-out;
}

@keyframes shake {
  0%,
  100% {
    transform: translateX(0);
  }
  20%,
  60% {
    transform: translateX(-8px);
  }
  40%,
  80% {
    transform: translateX(8px);
  }
}
</style>
