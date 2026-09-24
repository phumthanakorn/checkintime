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
          <v-icon :icon="item.icon" size="22" :class="item.color" />
        </span>
        <span class="min-w-0 flex-1">
          <span class="block text-sm font-semibold text-ink">{{ item.label }}</span>
          <span class="block text-xs" :class="item.pendingCount ? 'text-amber-600' : 'text-ink-muted'">
            {{ item.pendingCount ? `รออนุมัติ ${item.pendingCount} รายการ` : 'ไม่มีรายการ' }}
          </span>
        </span>
        <v-icon icon="mdi-chevron-right" class="text-slate-300" />
      </button>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { REQUEST_TYPES } from '@/utils/constants'

const props = defineProps({
  /** [{ type: 'leave' | 'advance', pendingCount: number }] */
  items: { type: Array, default: () => [] },
})

const emit = defineEmits(['select'])

const META = {
  [REQUEST_TYPES.LEAVE]: { label: 'การลา', icon: 'mdi-calendar-check', bg: 'bg-status-checkin/10', color: 'text-status-checkin' },
  [REQUEST_TYPES.ADVANCE]: { label: 'การเบิกเงิน', icon: 'mdi-wallet', bg: 'bg-metric-blue/10', color: 'text-metric-blue' },
}

// แสดงทุกประเภทเสมอ แม้ API ยังไม่ส่งข้อมูลมา
const rows = computed(() =>
  Object.values(REQUEST_TYPES).map((type) => ({
    type,
    pendingCount: props.items.find((i) => i.type === type)?.pendingCount ?? 0,
    ...META[type],
  })),
)
</script>
