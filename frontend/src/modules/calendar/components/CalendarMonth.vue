<template>
  <section class="rounded-[28px] bg-card p-4 shadow-sm">
    <!-- สรุปเดือน -->
    <div class="mb-4 grid grid-cols-4 gap-2">
      <div v-for="stat in summary" :key="stat.label" class="rounded-2xl px-1 py-2 text-center" :class="stat.bg">
        <p class="font-display text-lg font-bold leading-tight" :class="stat.color">{{ stat.value }}</p>
        <p class="text-[10px] text-ink-muted">{{ stat.label }}</p>
      </div>
    </div>

    <!-- หัววัน -->
    <div class="grid grid-cols-7">
      <span
        v-for="(label, index) in WEEKDAYS"
        :key="label"
        class="pb-2 text-center text-[11px] font-semibold"
        :class="index >= 5 ? 'text-rose-400' : 'text-ink-muted'"
      >
        {{ label }}
      </span>
    </div>

    <!-- วันที่ -->
    <div class="grid grid-cols-7 gap-y-1" role="grid">
      <span v-for="i in offset" :key="`blank-${i}`" />
      <button
        v-for="day in days"
        :key="day.date"
        type="button"
        role="gridcell"
        class="flex flex-col items-center gap-1 py-1"
        :aria-selected="day.date === selected"
        :aria-label="ariaLabel(day)"
        @click="emit('select', day.date)"
      >
        <span
          class="flex h-9 w-9 items-center justify-center rounded-full font-display text-sm transition-all duration-150"
          :class="circleClass(day)"
        >
          {{ Number(day.date.slice(8)) }}
        </span>
        <!-- จุดสถานะการลงเวลา (จองพื้นที่ไว้เสมอ ให้แถวเท่ากัน) -->
        <span class="h-1.5 w-1.5 rounded-full" :class="day.attendance ? DAY_STATUS_META[day.attendance].dot : ''" />
      </button>
    </div>

    <!-- คำอธิบายสัญลักษณ์ -->
    <div class="mt-3 space-y-2 border-t border-slate-100 pt-3">
      <div class="flex flex-wrap gap-x-3 gap-y-1.5">
        <span v-for="item in DAY_LEGEND" :key="item.label" class="flex items-center gap-1.5 text-[11px] text-ink-muted">
          <span v-if="item.text" class="font-display text-[11px] font-bold leading-none text-status-outside">{{ item.text }}</span>
          <span v-else class="h-3.5 w-3.5 rounded-full" :class="item.swatch" />
          {{ item.label }}
        </span>
      </div>
      <div class="flex flex-wrap gap-x-3 gap-y-1.5">
        <span v-for="item in STATUS_LEGEND" :key="item.label" class="flex items-center gap-1.5 text-[11px] text-ink-muted">
          <span class="h-1.5 w-1.5 rounded-full" :class="item.dot" />
          {{ item.label }}
        </span>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { DAY_STATUS_META, LEAVE_STATUS, LEAVE_TYPES } from '@/utils/constants'
import { formatThaiDate, toDateKey } from '@/utils/formatters'
import { toDate } from '@/utils/dates'

const props = defineProps({
  /** จาก API /calendar */
  days: { type: Array, default: () => [] },
  selected: { type: String, default: '' },
})

const emit = defineEmits(['select'])

const WEEKDAYS = ['จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส', 'อา']
const today = toDateKey()

// ช่องว่างก่อนวันที่ 1 (สัปดาห์เริ่มวันจันทร์)
const offset = computed(() => (props.days.length ? (toDate(props.days[0].date).getDay() + 6) % 7 : 0))

/** สีวงกลมของวันลาแต่ละประเภท: ทึบอ่อน = อนุมัติแล้ว, เส้นประ = รออนุมัติ */
const LEAVE_COLORS = {
  [LEAVE_TYPES.VACATION]: { approved: 'bg-metric-blue/15 text-metric-blue', pending: 'border-metric-blue text-metric-blue' },
  [LEAVE_TYPES.PERSONAL]: { approved: 'bg-violet-100 text-violet-600', pending: 'border-violet-400 text-violet-600' },
  [LEAVE_TYPES.SICK]: { approved: 'bg-rose-100 text-rose-600', pending: 'border-rose-400 text-rose-600' },
}

const DAY_LEGEND = [
  { label: 'วันหยุด', text: '31' },
  { label: 'ลาพักร้อน', swatch: 'bg-metric-blue/20' },
  { label: 'ลากิจ', swatch: 'bg-violet-100' },
  { label: 'ลาป่วย', swatch: 'bg-rose-100' },
  { label: 'รออนุมัติ', swatch: 'border-[1.5px] border-dashed border-slate-400' },
]

const STATUS_LEGEND = ['on_time', 'late', 'missing'].map((key) => ({
  label: key === 'missing' ? 'ขาด / ลงเวลาไม่ครบ' : DAY_STATUS_META[key].label,
  dot: DAY_STATUS_META[key].dot,
}))

function circleClass(day) {
  const isToday = day.date === today
  const isSelected = day.date === props.selected
  const classes = []

  if (isToday) {
    classes.push('bg-status-checkin font-bold text-white shadow-md shadow-status-checkin/30')
  } else if (day.leave) {
    const colors = LEAVE_COLORS[day.leave.leaveType]
    classes.push(
      'font-semibold',
      day.leave.status === LEAVE_STATUS.PENDING ? `border-[1.5px] border-dashed ${colors.pending}` : colors.approved,
    )
  } else if (day.holiday) {
    // วันหยุด: ตัวเลขแดงตัวหนา ไม่มีพื้น (สีพื้นใช้แยกประเภทวันลาเท่านั้น)
    classes.push('font-bold text-status-outside')
  } else if (!day.shift) {
    classes.push('text-slate-400')
  } else {
    classes.push('text-ink')
  }

  if (isSelected) classes.push(isToday ? 'ring-2 ring-status-checkin ring-offset-2' : 'ring-2 ring-ink ring-offset-1')
  else if (!isToday) classes.push('hover:bg-slate-100')
  return classes
}

// สรุปเดือน
const summary = computed(() => {
  const count = (fn) => props.days.filter(fn).length
  return [
    {
      label: 'มาทำงาน',
      value: count((d) => ['on_time', 'late', 'incomplete', 'working'].includes(d.attendance)),
      bg: 'bg-status-checkin/10',
      color: 'text-status-checkin',
    },
    {
      label: 'วันลา',
      value: count((d) => d.leave?.status === LEAVE_STATUS.APPROVED),
      bg: 'bg-metric-blue/10',
      color: 'text-metric-blue',
    },
    { label: 'มาสาย', value: count((d) => d.attendance === 'late'), bg: 'bg-status-checkout/10', color: 'text-status-checkout' },
    {
      label: 'ขาด/ไม่ครบ',
      value: count((d) => ['missing', 'incomplete'].includes(d.attendance)),
      bg: 'bg-status-outside/10',
      color: 'text-status-outside',
    },
  ]
})

function ariaLabel(day) {
  const parts = [formatThaiDate(day.date)]
  if (day.holiday) parts.push(day.holiday.name)
  if (day.leave) parts.push(day.leave.status === LEAVE_STATUS.PENDING ? 'วันลา (รออนุมัติ)' : 'วันลา')
  if (day.attendance) parts.push(DAY_STATUS_META[day.attendance].label)
  return parts.join(' · ')
}
</script>
