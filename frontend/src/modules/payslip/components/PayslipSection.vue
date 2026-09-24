<template>
  <section class="rounded-3xl bg-card p-4 shadow-sm">
    <h2 class="mb-1 flex items-center gap-2 text-base font-bold text-ink">
      <span class="flex h-8 w-8 items-center justify-center rounded-full" :class="tone === 'plus' ? 'bg-status-checkin/10' : 'bg-status-outside/10'">
        <AppIcon :name="icon" :size="18" weight="duotone" :class="tone === 'plus' ? 'text-status-checkin' : 'text-status-outside'" />
      </span>
      {{ title }}
    </h2>

    <ul class="divide-y divide-slate-100">
      <li v-for="item in items" :key="item.label" class="flex items-start justify-between gap-4 py-3">
        <span class="min-w-0">
          <span class="block text-sm text-ink">{{ item.label }}</span>
          <span v-if="item.note" class="block text-[11px] text-ink-muted">{{ item.note }}</span>
        </span>
        <span class="shrink-0 font-display text-sm font-semibold tabular-nums text-ink">
          {{ hidden ? '•••' : `${tone === 'minus' ? '-' : ''}${formatMoney(item.amount)}` }}
        </span>
      </li>
    </ul>

    <div class="mt-1 flex items-center justify-between rounded-2xl bg-app-bg px-3 py-2.5">
      <span class="text-sm font-medium text-ink-muted">รวม{{ title }}</span>
      <span
        class="font-display text-base font-bold tabular-nums"
        :class="tone === 'plus' ? 'text-status-checkin' : 'text-status-outside'"
      >
        {{ hidden ? '•••' : `${tone === 'minus' ? '-' : ''}${formatMoney(total)}` }}
      </span>
    </div>
  </section>
</template>

<script setup>
import { formatMoney } from '@/utils/formatters'

defineProps({
  title: { type: String, required: true },
  icon: { type: String, required: true },
  /** plus = รายได้ (เขียว), minus = รายการหัก (แดง) */
  tone: { type: String, default: 'plus' },
  items: { type: Array, default: () => [] },
  total: { type: Number, default: 0 },
  hidden: { type: Boolean, default: false },
})
</script>
