<template>
  <section class="grid grid-cols-3 gap-2.5">
    <div v-for="item in items" :key="item.type" class="rounded-2xl bg-card p-3 shadow-sm">
      <span class="flex h-8 w-8 items-center justify-center rounded-full" :class="item.meta.bg">
        <v-icon :icon="item.meta.icon" size="16" :class="item.meta.color" />
      </span>
      <p class="mt-2 text-xs text-ink-muted">{{ item.meta.label }}</p>
      <p class="flex items-baseline gap-1">
        <span class="font-display tabular-nums text-2xl font-bold text-ink">{{ item.remaining }}</span>
        <span class="text-[11px] text-ink-muted">/ {{ item.quota }} วัน</span>
      </p>
      <!-- แถบแสดงสัดส่วนที่ใช้ไป -->
      <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
        <div class="h-full rounded-full" :class="item.meta.bar" :style="{ width: `${item.usedPercent}%` }" />
      </div>
      <p class="mt-1 text-[10px] text-ink-muted">
        ใช้ไป {{ item.used }} วัน<span v-if="item.pending" class="text-amber-600"> · รอ {{ item.pending }}</span>
      </p>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { LEAVE_TYPE_META } from '@/utils/constants'

const props = defineProps({
  /** [{ type, quota, used, pending, remaining }] */
  balances: { type: Array, default: () => [] },
})

const items = computed(() =>
  props.balances.map((b) => ({
    ...b,
    meta: LEAVE_TYPE_META[b.type],
    usedPercent: b.quota ? Math.min(100, (b.used / b.quota) * 100) : 0,
  })),
)
</script>
