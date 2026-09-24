<template>
  <section>
    <h2 class="mb-3 text-base font-bold text-ink">สถานะการส่งคำขอ</h2>
    <div class="space-y-3">
      <button
        v-for="item in rows"
        :key="item.type"
        type="button"
        class="flex w-full items-center gap-3 rounded-2xl bg-card p-4 text-left shadow-sm transition"
        :class="item.available ? 'active:scale-[0.99]' : 'cursor-default'"
        :aria-disabled="!item.available"
        @click="emit('select', item.type)"
      >
        <span
          class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
          :class="item.available ? item.bg : 'bg-slate-100'"
        >
          <v-icon :icon="item.icon" size="22" :class="item.available ? item.color : 'text-slate-400'" />
        </span>
        <span class="min-w-0 flex-1">
          <span class="block text-sm font-semibold" :class="item.available ? 'text-ink' : 'text-ink-muted'">
            {{ item.label }}
          </span>
          <span class="block text-xs" :class="item.pendingCount ? 'text-amber-600' : 'text-ink-muted'">
            {{ item.subtitle }}
          </span>
        </span>
        <span
          v-if="!item.available"
          class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-ink-muted"
        >
          เร็ว ๆ นี้
        </span>
        <v-icon v-else icon="mdi-chevron-right" class="text-slate-300" />
      </button>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { FEATURES, REQUEST_TYPES } from '@/utils/constants'

const props = defineProps({
  /** [{ type: 'leave' | 'advance', pendingCount: number }] */
  items: { type: Array, default: () => [] },
})

const emit = defineEmits(['select'])

const META = {
  [REQUEST_TYPES.LEAVE]: {
    label: 'การลา',
    icon: 'mdi-calendar-check',
    bg: 'bg-status-checkin/10',
    color: 'text-status-checkin',
    available: true,
  },
  [REQUEST_TYPES.TIME_FIX]: {
    label: 'ขอลงเวลาย้อนหลัง',
    icon: 'mdi-clock-edit-outline',
    bg: 'bg-violet-50',
    color: 'text-violet-500',
    available: true,
  },
  [REQUEST_TYPES.ADVANCE]: {
    label: 'การเบิกเงิน',
    icon: 'mdi-wallet',
    bg: 'bg-metric-blue/10',
    color: 'text-metric-blue',
    available: FEATURES.ADVANCE_REQUEST,
  },
}

// แสดงทุกประเภทเสมอ แม้ API ยังไม่ส่งข้อมูลมา
const rows = computed(() =>
  Object.values(REQUEST_TYPES).map((type) => {
    const meta = META[type]
    const pendingCount = meta.available ? (props.items.find((i) => i.type === type)?.pendingCount ?? 0) : 0
    let subtitle = 'ไม่มีรายการ'
    if (!meta.available) subtitle = 'ยังไม่เปิดให้บริการ'
    else if (pendingCount) subtitle = `รออนุมัติ ${pendingCount} รายการ`
    return { type, pendingCount, subtitle, ...meta }
  }),
)
</script>
