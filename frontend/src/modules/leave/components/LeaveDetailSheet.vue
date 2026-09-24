<template>
  <AppBottomSheet v-model="open" title="รายละเอียดคำขอลา">
    <template v-if="request">
      <div class="flex items-center gap-3">
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl" :class="meta.bg">
          <AppIcon :name="meta.icon" :size="24" weight="duotone" :class="meta.color" />
        </span>
        <div class="min-w-0 flex-1">
          <p class="text-base font-bold text-ink">{{ meta.label }}</p>
          <p class="text-xs text-ink-muted">ยื่นเมื่อ {{ formatDayMonth(request.createdAt, true) }}</p>
        </div>
        <StatusBadge :label="status.label" :tone="status.tone" />
      </div>

      <dl class="mt-4 divide-y divide-slate-100 rounded-2xl bg-app-bg px-4">
        <div v-for="row in rows" :key="row.label" class="flex justify-between gap-4 py-3 text-sm">
          <dt class="shrink-0 text-ink-muted">{{ row.label }}</dt>
          <dd class="text-right font-medium text-ink">{{ row.value }}</dd>
        </div>
      </dl>

      <AttachmentList :files="request.attachments" class="mt-4" />

      <div
        v-if="request.reviewNote"
        class="mt-3 flex gap-2 rounded-2xl bg-status-outside/10 p-3 text-xs text-status-outside"
      >
        <AppIcon name="chat" :size="16" />
        <span><b>หมายเหตุจากหัวหน้า:</b> {{ request.reviewNote }}</span>
      </div>
    </template>

    <template v-if="request?.status === LEAVE_STATUS.PENDING" #footer>
      <button
        type="button"
        class="flex h-12 w-full items-center justify-center gap-2 rounded-2xl border border-status-outside/30 text-sm font-semibold text-status-outside disabled:opacity-60"
        :disabled="loading"
        @click="emit('cancel', request)"
      >
        <LoadingDots v-if="loading" size="sm" />
        <template v-else>
          <AppIcon name="x-circle" :size="18" />
          ยกเลิกคำขอลา
        </template>
      </button>
    </template>
  </AppBottomSheet>
</template>

<script setup>
import { computed } from 'vue'
import AppBottomSheet from '@/components/common/AppBottomSheet.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import AttachmentList from '@/components/common/AttachmentList.vue'
import { LEAVE_PERIOD_LABELS, LEAVE_STATUS, LEAVE_STATUS_META, LEAVE_TYPE_META } from '@/utils/constants'
import { formatDateRange, formatDayMonth, formatLeaveDays } from '@/utils/formatters'

const open = defineModel({ type: Boolean, default: false })

const props = defineProps({
  request: { type: Object, default: null },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['cancel'])

const meta = computed(() => LEAVE_TYPE_META[props.request?.leaveType] ?? {})
const status = computed(() => LEAVE_STATUS_META[props.request?.status] ?? {})

const rows = computed(() => {
  const r = props.request
  if (!r) return []
  return [
    { label: 'วันที่ลา', value: formatDateRange(r.startDate, r.endDate) },
    { label: 'ช่วงเวลา', value: LEAVE_PERIOD_LABELS[r.period] },
    { label: 'จำนวน', value: formatLeaveDays(r.days) },
    { label: 'เหตุผล', value: r.reason },
  ]
})
</script>
