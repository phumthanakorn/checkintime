<template>
  <section v-if="day" class="rounded-3xl bg-card p-4 shadow-sm">
    <div class="mb-3 flex items-center justify-between gap-2">
      <h2 class="text-base font-bold text-ink">{{ formatThaiDate(day.date) }}</h2>
      <span v-if="day.date === today" class="rounded-full bg-status-checkin/10 px-2.5 py-1 text-[11px] font-semibold text-status-checkin">
        วันนี้
      </span>
    </div>

    <ul class="list-none space-y-2.5">
      <!-- กะ / วันหยุด -->
      <li class="flex items-center gap-3">
        <span class="icon" :class="day.shift ? 'bg-status-checkin/10' : 'bg-slate-100'">
          <AppIcon name="clock" :size="18" weight="duotone" :class="day.shift ? 'text-status-checkin' : 'text-slate-400'" />
        </span>
        <span class="min-w-0 flex-1 text-sm">
          <template v-if="day.shift">
            <span class="font-medium text-ink">{{ day.shift.name }}</span>
            <span class="ml-1 font-display text-ink-muted">{{ day.shift.start }}–{{ day.shift.end }} น.</span>
          </template>
          <span v-else class="text-ink-muted">ไม่มีกะทำงาน (วันหยุด)</span>
        </span>
      </li>

      <li v-if="day.holiday" class="flex items-center gap-3">
        <span class="icon bg-status-outside/10">
          <AppIcon name="calendar-check" :size="18" weight="duotone" class="text-status-outside" />
        </span>
        <span class="text-sm font-medium text-status-outside">{{ day.holiday.name }}</span>
      </li>

      <li v-if="day.leave">
        <router-link :to="{ name: 'leave' }" class="flex items-center gap-3 no-underline">
          <span class="icon" :class="leaveType.bg">
            <AppIcon :name="leaveType.icon" :size="18" weight="duotone" :class="leaveType.color" />
          </span>
          <span class="min-w-0 flex-1 text-sm text-ink">
            {{ leaveType.label }}
            <span class="text-ink-muted">({{ LEAVE_PERIOD_LABELS[day.leave.period] }})</span>
          </span>
          <StatusBadge v-bind="REQUEST_STATUS_META[day.leave.status]" />
        </router-link>
      </li>

      <li v-if="day.attendance" class="flex items-center gap-3">
        <span class="icon bg-app-bg">
          <span class="h-2.5 w-2.5 rounded-full" :class="DAY_STATUS_META[day.attendance].dot" />
        </span>
        <span class="min-w-0 flex-1 text-sm">
          <span class="font-medium text-ink">{{ DAY_STATUS_META[day.attendance].label }}</span>
          <span v-if="day.checkIn" class="ml-1 font-display text-ink-muted">
            {{ formatClock(day.checkIn) }} – {{ formatClock(day.checkOut) }}
          </span>
        </span>
      </li>
    </ul>

    <!-- สิ่งที่ทำต่อได้ -->
    <router-link
      v-if="needsFix"
      :to="{ name: 'time-fix', query: { date: day.date, type: day.attendance === 'missing' ? 'both' : 'check_out' } }"
      class="mt-4 flex h-11 items-center justify-center gap-2 rounded-2xl bg-status-checkin text-sm font-semibold text-white no-underline shadow-md shadow-status-checkin/30"
    >
      <AppIcon name="clock-edit" :size="18" />
      ขอลงเวลาย้อนหลัง
    </router-link>
    <router-link
      v-else-if="canLeave"
      :to="{ name: 'leave' }"
      class="mt-4 flex h-11 items-center justify-center gap-2 rounded-2xl bg-app-bg text-sm font-semibold text-ink no-underline"
    >
      <AppIcon name="plus" :size="18" class="text-status-checkin" />
      ยื่นใบลา
    </router-link>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { DAY_STATUS_META, LEAVE_PERIOD_LABELS, LEAVE_TYPE_META, REQUEST_STATUS_META } from '@/utils/constants'
import { formatClock, formatThaiDate, toDateKey } from '@/utils/formatters'

const props = defineProps({
  day: { type: Object, default: null },
})

const today = toDateKey()

const leaveType = computed(() => LEAVE_TYPE_META[props.day?.leave?.leaveType] ?? {})
const needsFix = computed(() => ['missing', 'incomplete'].includes(props.day?.attendance))
const canLeave = computed(() => props.day?.shift && !props.day.leave && props.day.date >= today)
</script>

<style scoped>
.icon {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 0.75rem;
}
</style>
