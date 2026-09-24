<template>
  <div class="flex items-center justify-between rounded-2xl bg-card p-1.5 shadow-sm">
    <button
      type="button"
      class="flex h-9 w-9 items-center justify-center rounded-xl text-ink transition hover:bg-slate-50"
      aria-label="เดือนก่อนหน้า"
      @click="month = shiftMonth(month, -1)"
    >
      <AppIcon name="caret-left" :size="22" />
    </button>

    <span class="flex items-center gap-2 text-sm font-semibold text-ink">
      <AppIcon name="calendar" :size="18" class="text-ink-muted" />
      {{ formatMonth(month) }}
    </span>

    <button
      type="button"
      class="flex h-9 w-9 items-center justify-center rounded-xl text-ink transition hover:bg-slate-50 disabled:text-slate-300 disabled:hover:bg-transparent"
      aria-label="เดือนถัดไป"
      :disabled="isLatest"
      @click="month = shiftMonth(month, 1)"
    >
      <AppIcon name="caret-right" :size="22" />
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { shiftMonth } from '@/utils/dates'
import { formatMonth, toMonthKey } from '@/utils/formatters'

/** 'YYYY-MM' */
const month = defineModel({ type: String, required: true })

const props = defineProps({
  /** เดือนล่าสุดที่เลือกได้ (ค่าเริ่มต้น = เดือนปัจจุบัน) */
  max: { type: String, default: () => toMonthKey() },
})

const isLatest = computed(() => month.value >= props.max)
</script>
