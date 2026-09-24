<template>
  <section class="grid grid-cols-4 rounded-[28px] bg-card px-2 py-4 shadow-sm">
    <div
      v-for="(item, index) in items"
      :key="item.label"
      class="flex flex-col items-center gap-0.5 px-1 text-center"
      :class="index > 0 && 'border-l border-slate-100'"
    >
      <span class="flex items-baseline gap-0.5">
        <span class="font-display tabular-nums text-xl font-bold" :class="item.color">{{ item.value }}</span>
        <span class="text-[11px] text-ink-muted">{{ item.unit }}</span>
      </span>
      <span class="text-[11px] leading-tight text-ink-muted">{{ item.label }}</span>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  records: { type: Array, default: () => [] },
})

const items = computed(() => {
  const late = props.records.filter((r) => r.lateMinutes > 0).length
  const otMinutes = props.records.reduce((total, r) => total + (r.otMinutes || 0), 0)
  return [
    { label: 'มาทำงาน', value: props.records.length, unit: 'วัน', color: 'text-ink' },
    { label: 'ตรงเวลา', value: props.records.length - late, unit: 'วัน', color: 'text-status-checkin' },
    { label: 'มาสาย', value: late, unit: 'ครั้ง', color: 'text-status-checkout' },
    { label: 'OT', value: +(otMinutes / 60).toFixed(1), unit: 'ชม.', color: 'text-metric-cyan' },
  ]
})
</script>
