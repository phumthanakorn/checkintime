<template>
  <button
    type="button"
    class="flex w-full items-center gap-3 rounded-2xl p-3 text-left shadow-sm transition active:scale-[0.99]"
    :class="needsFix ? 'bg-status-outside/5 ring-1 ring-status-outside/20' : 'bg-card'"
    @click="emit('select', record)"
  >
    <!-- วันที่ -->
    <span
      class="flex h-14 w-12 shrink-0 flex-col items-center justify-center rounded-xl"
      :class="isToday ? 'bg-status-checkin text-white' : needsFix ? 'bg-card text-ink' : 'bg-app-bg text-ink'"
    >
      <span class="text-[11px]" :class="isToday ? 'text-white/85' : 'text-ink-muted'">
        {{ formatWeekdayShort(record.date) }}
      </span>
      <span class="font-display text-xl font-bold leading-none">{{ toDate(record.date).getDate() }}</span>
    </span>

    <!-- วันที่ไม่มีการลงเวลาเลย -->
    <span v-if="record.missing" class="min-w-0 flex-1">
      <span class="block text-sm font-semibold text-ink">ไม่มีการลงเวลา</span>
      <span class="mt-0.5 flex items-center gap-1 text-xs" :class="hasPendingFix(record) ? 'text-metric-blue' : 'text-status-outside'">
        <v-icon :icon="hasPendingFix(record) ? 'mdi-timer-sand' : 'mdi-gesture-tap'" size="14" />
        {{ hasPendingFix(record) ? 'ส่งคำขอแล้ว รอหัวหน้าอนุมัติ' : 'แตะเพื่อขอลงเวลาย้อนหลัง' }}
      </span>
    </span>

    <!-- เวลาเข้า-ออก -->
    <span v-else class="min-w-0 flex-1">
      <span class="flex items-center gap-3">
        <span class="flex items-center gap-1">
          <v-icon icon="mdi-login" size="14" class="text-status-checkin" />
          <span class="font-display text-sm font-semibold text-ink">{{ formatClock(record.checkIn) }}</span>
        </span>
        <span class="h-3 w-px bg-slate-200" />
        <span class="flex items-center gap-1">
          <v-icon icon="mdi-logout" size="14" class="text-status-checkout" />
          <span class="font-display text-sm font-semibold text-ink">{{ formatClock(record.checkOut) }}</span>
        </span>
      </span>
      <span class="mt-1 block text-xs text-ink-muted">
        {{ record.workMinutes != null ? `ทำงาน ${formatDuration(record.workMinutes)}` : 'ยังไม่ลงเวลาออก' }}
        <span v-if="record.otMinutes" class="text-metric-cyan"> · OT {{ record.otMinutes }} นาที</span>
      </span>
    </span>

    <StatusBadge v-bind="status" />
  </button>
</template>

<script setup>
import { computed } from 'vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { getRecordStatus, hasPendingFix, isIncomplete } from '../recordStatus'
import { toDate } from '@/utils/dates'
import { formatClock, formatDuration, formatWeekdayShort, toDateKey } from '@/utils/formatters'

const props = defineProps({
  record: { type: Object, required: true },
})

const emit = defineEmits(['select'])

const status = computed(() => getRecordStatus(props.record))
const isToday = computed(() => props.record.date === toDateKey())
// ลงเวลาไม่ครบและยังไม่ได้ส่งคำขอ -> เน้นให้เห็น
const needsFix = computed(() => isIncomplete(props.record) && !hasPendingFix(props.record))
</script>
