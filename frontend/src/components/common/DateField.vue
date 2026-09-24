<template>
  <!--
    แสดงวันที่แบบไทย (พ.ศ.) ทับบน input type="date" ที่โปร่งใส
    กดตรงไหนของช่องก็เปิดตัวเลือกวันที่ของระบบ (มือถือใช้ date picker ของเครื่อง)
  -->
  <label class="relative flex h-12 cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-app-bg px-3.5 transition focus-within:border-status-checkin focus-within:bg-card">
    <v-icon icon="mdi-calendar-blank-outline" size="18" class="text-ink-muted" />
    <span class="truncate text-sm" :class="model ? 'text-ink' : 'text-slate-400'">
      {{ model ? formatDayMonth(model, true) : placeholder }}
    </span>
    <input
      v-model="model"
      type="date"
      :min="min"
      :max="max"
      :aria-label="ariaLabel"
      class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
      @click="openPicker"
    />
  </label>
</template>

<script setup>
import { formatDayMonth } from '@/utils/formatters'

/** 'YYYY-MM-DD' */
const model = defineModel({ type: String, default: '' })

defineProps({
  min: { type: String, default: undefined },
  max: { type: String, default: undefined },
  placeholder: { type: String, default: 'เลือกวันที่' },
  ariaLabel: { type: String, default: undefined },
})

function openPicker(event) {
  try {
    event.target.showPicker?.()
  } catch {
    // บางเบราว์เซอร์ไม่อนุญาต showPicker จะใช้การเปิดแบบปกติแทน
  }
}
</script>
