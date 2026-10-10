<template>
  <section>
    <h2 class="mb-3 text-base font-bold text-ink">สถานะการส่งคำขอ</h2>
    <div class="space-y-3">
      <button
        v-for="item in rows"
        :key="item.type"
        type="button"
        class="flex w-full items-center gap-3 rounded-2xl bg-card p-4 text-left shadow-sm transition active:scale-[0.99]"
        @click="emit('select', item.type)"
      >
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl" :class="item.bg">
          <AppIcon :name="item.icon" :size="22" weight="duotone" :class="item.color" />
        </span>
        <span class="min-w-0 flex-1">
          <span class="block text-sm font-semibold text-ink">{{ item.label }}</span>
          <span class="block text-xs" :class="item.pendingCount ? 'text-amber-600' : 'text-ink-muted'">
            {{ item.subtitle }}
          </span>
        </span>
        <AppIcon name="caret-right" class="text-slate-300" />
      </button>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { REQUEST_TYPES } from '@/utils/constants'

const props = defineProps({
  /** [{ type: 'leave' | 'time_fix', pendingCount: number }] */
  items: { type: Array, default: () => [] },
})

const emit = defineEmits(['select'])

const META = {
  [REQUEST_TYPES.LEAVE]: {
    label: 'การลา',
    icon: 'calendar-check',
    bg: 'bg-status-checkin/10',
    color: 'text-status-checkin',
    available: true,
  },
  [REQUEST_TYPES.TIME_FIX]: {
    label: 'ขอลงเวลาย้อนหลัง',
    icon: 'clock-edit',
    bg: 'bg-violet-50',
    color: 'text-violet-500',
    available: true,
  },
}

const rows = computed(() =>
  Object.values(REQUEST_TYPES)
    .map((type) => {
      const meta = META[type]
      const pendingCount = props.items.find((i) => i.type === type)?.pendingCount ?? 0
      const subtitle = pendingCount ? `รออนุมัติ ${pendingCount} รายการ` : 'ไม่มีรายการ'
      return { type, pendingCount, subtitle, ...meta }
    }),
)
</script>
