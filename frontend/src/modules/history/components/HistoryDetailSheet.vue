<template>
  <AppBottomSheet v-model="open" title="รายละเอียดการลงเวลา">
    <template v-if="record">
      <div class="flex items-center justify-between">
        <p class="text-sm font-semibold text-ink">{{ formatThaiDate(record.date) }}</p>
        <StatusBadge v-bind="getRecordStatus(record)" />
      </div>

      <!-- ไทม์ไลน์เข้า-ออก -->
      <ol class="mt-4 space-y-0">
        <li v-for="(step, index) in steps" :key="step.label" class="flex gap-3">
          <span class="flex flex-col items-center">
            <span class="flex h-9 w-9 items-center justify-center rounded-full" :class="step.bg">
              <v-icon :icon="step.icon" size="18" :class="step.color" />
            </span>
            <span v-if="index < steps.length - 1" class="my-1 w-px flex-1 bg-slate-200" />
          </span>
          <span class="pb-4">
            <span class="block text-xs text-ink-muted">{{ step.label }}</span>
            <span class="block font-display text-lg font-bold text-ink">{{ formatClock(step.time) }}</span>
            <span class="flex items-center gap-1 text-xs text-ink-muted">
              <v-icon icon="mdi-map-marker-outline" size="14" />
              {{ formatLocation(step.location) }}
            </span>
          </span>
        </li>
      </ol>

      <div class="grid grid-cols-3 gap-2">
        <div v-for="stat in stats" :key="stat.label" class="rounded-2xl bg-app-bg p-3 text-center">
          <p class="text-[11px] text-ink-muted">{{ stat.label }}</p>
          <p class="mt-0.5 text-sm font-semibold" :class="stat.color">{{ stat.value }}</p>
        </div>
      </div>
    </template>

    <template #footer>
      <button
        type="button"
        class="flex h-12 w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 text-sm font-semibold text-ink"
        @click="emit('request-fix', record)"
      >
        <v-icon icon="mdi-pencil-outline" size="18" />
        แจ้งแก้ไขเวลา
      </button>
    </template>
  </AppBottomSheet>
</template>

<script setup>
import { computed } from 'vue'
import AppBottomSheet from '@/components/common/AppBottomSheet.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { getRecordStatus } from '../recordStatus'
import { formatClock, formatDuration, formatThaiDate } from '@/utils/formatters'

const open = defineModel({ type: Boolean, default: false })

const props = defineProps({
  record: { type: Object, default: null },
})

const emit = defineEmits(['request-fix'])

const steps = computed(() => [
  {
    label: 'เข้างาน',
    time: props.record?.checkIn,
    location: props.record?.checkInLocation,
    icon: 'mdi-login',
    bg: 'bg-status-checkin/10',
    color: 'text-status-checkin',
  },
  {
    label: 'ออกงาน',
    time: props.record?.checkOut,
    location: props.record?.checkOutLocation,
    icon: 'mdi-logout',
    bg: 'bg-status-checkout/10',
    color: 'text-status-checkout',
  },
])

const stats = computed(() => [
  { label: 'เวลาทำงาน', value: formatDuration(props.record?.workMinutes), color: 'text-ink' },
  {
    label: 'มาสาย',
    value: props.record?.lateMinutes ? `${props.record.lateMinutes} นาที` : '-',
    color: props.record?.lateMinutes ? 'text-status-checkout' : 'text-ink',
  },
  {
    label: 'OT',
    value: props.record?.otMinutes ? `${props.record.otMinutes} นาที` : '-',
    color: props.record?.otMinutes ? 'text-metric-cyan' : 'text-ink',
  },
])

function formatLocation(location) {
  if (!location) return 'ไม่มีข้อมูลตำแหน่ง'
  return `${location.lat.toFixed(4)}, ${location.lng.toFixed(4)} (±${location.accuracy} ม.)`
}
</script>
