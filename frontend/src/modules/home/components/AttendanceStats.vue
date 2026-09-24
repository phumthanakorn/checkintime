<template>
  <section class="rounded-[28px] bg-card p-3 shadow-sm">
    <div class="grid grid-cols-2">
      <div
        v-for="(item, index) in items"
        :key="item.label"
        class="flex flex-col items-center gap-1 px-2 py-4"
        :class="[index % 2 === 0 && 'border-r border-slate-100', index < 2 && 'border-b border-slate-100']"
      >
        <span class="flex h-9 w-9 items-center justify-center rounded-full" :class="item.bg">
          <v-icon :icon="item.icon" size="18" :class="item.color" />
        </span>
        <span class="text-[13px] text-ink-muted">{{ item.label }}</span>
        <!-- Stat Display: ตัวเลข Plus Jakarta 24px / 700 + หน่วย Prompt -->
        <span class="flex items-baseline gap-1">
          <span class="font-display tabular-nums text-2xl font-bold text-ink">{{ item.value }}</span>
          <span class="text-xs text-ink-muted">{{ item.unit }}</span>
        </span>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  /** { leaveRemainingDays, lateCount, lateMinutes, otMinutes } */
  summary: { type: Object, required: true },
})

const items = computed(() => [
  {
    label: 'วันลาคงเหลือ',
    value: props.summary.leaveRemainingDays,
    unit: 'วัน',
    icon: 'mdi-calendar-blank',
    bg: 'bg-metric-blue/10',
    color: 'text-metric-blue',
  },
  {
    label: 'มาสาย (ครั้ง)',
    value: props.summary.lateCount,
    unit: 'ครั้ง',
    icon: 'mdi-alarm',
    bg: 'bg-rose-50',
    color: 'text-rose-500',
  },
  {
    label: 'มาสาย (รวม)',
    value: props.summary.lateMinutes,
    unit: 'นาที',
    icon: 'mdi-clock-outline',
    bg: 'bg-violet-50',
    color: 'text-violet-500',
  },
  {
    label: 'OT (เดือนนี้)',
    value: props.summary.otMinutes,
    unit: 'นาที',
    icon: 'mdi-timer-outline',
    bg: 'bg-metric-cyan/10',
    color: 'text-metric-cyan',
  },
])
</script>
