<template>
  <button
    type="button"
    class="flex w-full items-start gap-3 rounded-2xl bg-card p-4 text-left shadow-sm transition active:scale-[0.99]"
    @click="emit('select', request)"
  >
    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl" :class="meta.bg">
      <v-icon :icon="meta.icon" size="22" :class="meta.color" />
    </span>

    <span class="min-w-0 flex-1">
      <span class="flex items-start justify-between gap-2">
        <span class="text-sm font-semibold text-ink">
          {{ meta.label }}
          <span class="font-normal text-ink-muted">· {{ formatLeaveDays(request.days) }}</span>
        </span>
        <StatusBadge :label="status.label" :tone="status.tone" />
      </span>
      <span class="mt-0.5 flex items-center gap-1 text-xs text-ink">
        <v-icon icon="mdi-calendar-range" size="14" class="text-ink-muted" />
        {{ formatDateRange(request.startDate, request.endDate) }}
        <span v-if="request.period !== LEAVE_PERIODS.FULL" class="text-ink-muted">
          ({{ LEAVE_PERIOD_LABELS[request.period] }})
        </span>
      </span>
      <span class="mt-1 block truncate text-xs text-ink-muted">{{ request.reason }}</span>
    </span>
  </button>
</template>

<script setup>
import { computed } from 'vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { LEAVE_PERIOD_LABELS, LEAVE_PERIODS, LEAVE_STATUS_META, LEAVE_TYPE_META } from '@/utils/constants'
import { formatDateRange, formatLeaveDays } from '@/utils/formatters'

const props = defineProps({
  request: { type: Object, required: true },
})

const emit = defineEmits(['select'])

const meta = computed(() => LEAVE_TYPE_META[props.request.leaveType])
const status = computed(() => LEAVE_STATUS_META[props.request.status])
</script>
