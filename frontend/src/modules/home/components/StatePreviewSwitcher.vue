<template>
  <!-- แถบสลับสถานะการ์ดลงเวลา สำหรับดู UI (แสดงเฉพาะตอน dev / mock) -->
  <section class="rounded-2xl border border-dashed border-slate-300 bg-white/70 p-3">
    <p class="mb-2 flex items-center gap-1 text-xs font-medium text-slate-500">
      <v-icon icon="mdi-eye-outline" size="16" />
      ดูตัวอย่างสถานะ (UI)
    </p>
    <div class="grid grid-cols-5 gap-1.5">
      <button
        v-for="option in options"
        :key="option.label"
        type="button"
        class="flex flex-col items-center gap-1 rounded-xl px-0.5 py-2 text-[10px] leading-tight whitespace-nowrap transition"
        :class="modelValue === option.value ? 'bg-ink text-white' : 'bg-slate-100 text-slate-600'"
        @click="emit('update:modelValue', option.value)"
      >
        <span class="h-3 w-3 rounded-full" :class="option.dot" />
        {{ option.label }}
      </button>
    </div>

    <p class="mb-2 mt-3 flex items-center gap-1 text-xs font-medium text-slate-500">
      <v-icon icon="mdi-party-popper" size="16" />
      ดูหน้าจอฉลอง
    </p>
    <div class="grid grid-cols-3 gap-1.5">
      <button
        v-for="option in celebrateOptions"
        :key="option.value"
        type="button"
        class="rounded-xl bg-slate-100 px-1 py-2 text-[11px] text-slate-600 transition active:scale-95"
        @click="emit('celebrate', option.value)"
      >
        {{ option.label }}
      </button>
    </div>
  </section>
</template>

<script setup>
import { CLOCK_STATE } from '@/utils/constants'

defineProps({
  /** null = ใช้ข้อมูลจริง */
  modelValue: { type: String, default: null },
})

const emit = defineEmits(['update:modelValue', 'celebrate'])

const options = [
  { value: null, label: 'ข้อมูลจริง', dot: 'bg-card ring-1 ring-slate-300' },
  { value: CLOCK_STATE.READY, label: 'เข้างาน', dot: 'bg-status-checkin' },
  { value: CLOCK_STATE.WORKING, label: 'ออกงาน', dot: 'bg-status-checkout' },
  { value: CLOCK_STATE.DONE, label: 'ครบแล้ว', dot: 'bg-status-done' },
  { value: CLOCK_STATE.OUT_OF_AREA, label: 'นอกพื้นที่', dot: 'bg-status-outside' },
]

const celebrateOptions = [
  { value: 'checkin-ontime', label: '🎉 ตรงเวลา' },
  { value: 'checkin-late', label: '⏰ มาสาย' },
  { value: 'checkout', label: '🌙 ออกงาน' },
]
</script>
