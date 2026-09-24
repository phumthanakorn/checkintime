<template>
  <AppBottomSheet v-model="open" title="รายละเอียดคำขอ">
    <template v-if="request">
      <div class="flex items-center gap-3">
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-50">
          <v-icon icon="mdi-clock-edit-outline" size="24" class="text-violet-500" />
        </span>
        <div class="min-w-0 flex-1">
          <p class="text-base font-bold text-ink">{{ TIME_FIX_TYPE_LABELS[request.fixType] }}</p>
          <p class="text-xs text-ink-muted">ยื่นเมื่อ {{ formatDayMonth(request.createdAt, true) }}</p>
        </div>
        <StatusBadge :label="status.label" :tone="status.tone" />
      </div>

      <dl class="mt-4 divide-y divide-slate-100 rounded-2xl bg-app-bg px-4">
        <div v-for="row in rows" :key="row.label" class="flex justify-between gap-4 py-3 text-sm">
          <dt class="shrink-0 text-ink-muted">{{ row.label }}</dt>
          <dd class="text-right font-medium text-ink" :class="row.display && 'font-display'">{{ row.value }}</dd>
        </div>
      </dl>

      <AttachmentList :files="request.attachments" class="mt-4" />

      <div
        v-if="request.reviewNote"
        class="mt-3 flex gap-2 rounded-2xl bg-status-outside/10 p-3 text-xs text-status-outside"
      >
        <v-icon icon="mdi-message-reply-text-outline" size="16" />
        <span><b>หมายเหตุจากหัวหน้า:</b> {{ request.reviewNote }}</span>
      </div>
    </template>

    <template v-if="request?.status === REQUEST_STATUS.PENDING" #footer>
      <button
        type="button"
        class="flex h-12 w-full items-center justify-center gap-2 rounded-2xl border border-status-outside/30 text-sm font-semibold text-status-outside disabled:opacity-60"
        :disabled="loading"
        @click="emit('cancel', request)"
      >
        <v-progress-circular v-if="loading" indeterminate size="18" width="2" />
        <template v-else>
          <v-icon icon="mdi-close-circle-outline" size="18" />
          ยกเลิกคำขอ
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
import { REQUEST_STATUS, REQUEST_STATUS_META, TIME_FIX_TYPE_LABELS } from '@/utils/constants'
import { formatDayMonth, formatThaiDate } from '@/utils/formatters'

const open = defineModel({ type: Boolean, default: false })

const props = defineProps({
  request: { type: Object, default: null },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['cancel'])

const status = computed(() => REQUEST_STATUS_META[props.request?.status] ?? {})

const rows = computed(() => {
  const r = props.request
  if (!r) return []
  return [
    { label: 'วันที่', value: formatThaiDate(r.date) },
    r.checkIn && { label: 'เวลาเข้างาน', value: `${r.checkIn} น.`, display: true },
    r.checkOut && { label: 'เวลาออกงาน', value: `${r.checkOut} น.`, display: true },
    { label: 'เหตุผล', value: r.reason },
  ].filter(Boolean)
})
</script>
