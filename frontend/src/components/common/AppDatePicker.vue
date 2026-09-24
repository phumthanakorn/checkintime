<template>
  <div class="select-none">
    <!-- หัวปฏิทิน: ชื่อเดือน (แตะเพื่อเลือกเดือน/ปี) + ปุ่มเลื่อน -->
    <div class="flex items-center justify-between pb-3">
      <button
        type="button"
        class="flex items-center gap-1 rounded-xl px-2 py-1.5 text-base font-bold text-ink transition hover:bg-slate-50"
        :aria-label="view === 'days' ? 'เลือกเดือนและปี' : 'กลับไปเลือกวัน'"
        @click="view = view === 'days' ? 'months' : 'days'"
      >
        {{ view === 'days' ? formatMonth(cursor) : `พ.ศ. ${cursorYear + 543}` }}
        <AppIcon :name="view === 'days' ? 'caret-down' : 'caret-up'" :size="20" class="text-ink-muted" />
      </button>

      <div class="flex gap-1">
        <button type="button" class="nav-btn" :aria-label="view === 'days' ? 'เดือนก่อนหน้า' : 'ปีก่อนหน้า'" @click="step(-1)">
          <AppIcon name="caret-left" :size="22" />
        </button>
        <button type="button" class="nav-btn" :aria-label="view === 'days' ? 'เดือนถัดไป' : 'ปีถัดไป'" @click="step(1)">
          <AppIcon name="caret-right" :size="22" />
        </button>
      </div>
    </div>

    <!-- มุมมองวัน -->
    <div v-if="view === 'days'">
      <div class="grid grid-cols-7 pb-1">
        <span
          v-for="(label, index) in WEEKDAY_LABELS"
          :key="label"
          class="py-1 text-center text-xs font-medium"
          :class="index >= 5 ? 'text-rose-400' : 'text-ink-muted'"
        >
          {{ label }}
        </span>
      </div>

      <div class="grid grid-cols-7 gap-y-1" role="grid">
        <div v-for="cell in cells" :key="cell.key" class="relative flex h-11 items-center justify-center">
          <template v-if="cell.day">
            <!-- แถบช่วงวัน (โหมด range) -->
            <span
              v-if="cell.inRange || cell.rangeEdge"
              class="absolute inset-y-1 bg-status-checkin/10"
              :class="[
                cell.isStart && !cell.isEnd && 'left-1/2 right-0',
                cell.isEnd && !cell.isStart && 'left-0 right-1/2',
                cell.inRange && 'inset-x-0',
                cell.isStart && cell.isEnd && 'hidden',
              ]"
            />
            <button
              type="button"
              role="gridcell"
              class="relative flex h-10 w-10 items-center justify-center rounded-full font-display text-sm transition"
              :class="dayClass(cell)"
              :disabled="cell.disabled"
              :aria-selected="cell.selected"
              :aria-label="formatThaiDate(cell.key)"
              @click="pick(cell.key)"
            >
              {{ cell.day }}
              <span
                v-if="cell.isToday && !cell.selected"
                class="absolute bottom-1 h-1 w-1 rounded-full bg-status-checkin"
              />
            </button>
          </template>
        </div>
      </div>
    </div>

    <!-- มุมมองเดือน -->
    <div v-else class="grid grid-cols-3 gap-2 py-2">
      <button
        v-for="(label, index) in MONTH_LABELS"
        :key="label"
        type="button"
        class="rounded-2xl py-3.5 text-sm font-medium transition"
        :class="
          index === cursorMonth
            ? 'bg-status-checkin font-semibold text-white'
            : index === todayMonth && cursorYear === todayYear
              ? 'bg-status-checkin/10 text-status-checkin'
              : 'bg-app-bg text-ink hover:bg-slate-100'
        "
        @click="chooseMonth(index)"
      >
        {{ label }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { isWeekend, shiftMonth, toDate } from '@/utils/dates'
import { formatMonth, formatThaiDate, toDateKey, toMonthKey } from '@/utils/formatters'

/**
 * ปฏิทินกลางของแอป (ปี พ.ศ., สัปดาห์เริ่มวันจันทร์)
 *  - mode 'single': v-model = 'YYYY-MM-DD'
 *  - mode 'range' : v-model = ['YYYY-MM-DD', 'YYYY-MM-DD' | null]
 */
const model = defineModel({ type: [String, Array], default: null })

const props = defineProps({
  mode: { type: String, default: 'single', validator: (v) => ['single', 'range'].includes(v) },
  min: { type: String, default: null },
  max: { type: String, default: null },
})

const WEEKDAY_LABELS = ['จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส', 'อา']
const MONTH_LABELS = Array.from({ length: 12 }, (_, i) =>
  new Date(2000, i, 1).toLocaleDateString('th-TH', { month: 'short' }),
)

const todayKey = toDateKey()
const todayYear = new Date().getFullYear()
const todayMonth = new Date().getMonth()

const view = ref('days')
const cursor = ref(toMonthKey(firstSelected() || todayKey)) // เดือนที่แสดง 'YYYY-MM'

const cursorYear = computed(() => Number(cursor.value.slice(0, 4)))
const cursorMonth = computed(() => Number(cursor.value.slice(5, 7)) - 1)

const start = computed(() => (props.mode === 'range' ? model.value?.[0] : model.value) || null)
const end = computed(() => (props.mode === 'range' ? model.value?.[1] : model.value) || null)

function firstSelected() {
  return Array.isArray(model.value) ? model.value[0] : model.value
}

// เลื่อนปฏิทินไปเดือนของค่าที่ถูกตั้งจากภายนอก
watch(
  () => firstSelected(),
  (value) => {
    if (value) cursor.value = toMonthKey(toDate(value))
  },
)

const cells = computed(() => {
  const first = new Date(cursorYear.value, cursorMonth.value, 1)
  const offset = (first.getDay() + 6) % 7 // จันทร์ = 0
  const daysInMonth = new Date(cursorYear.value, cursorMonth.value + 1, 0).getDate()
  const list = Array.from({ length: offset }, (_, i) => ({ key: `blank-${i}` }))

  for (let day = 1; day <= daysInMonth; day++) {
    const key = toDateKey(new Date(cursorYear.value, cursorMonth.value, day))
    const isStart = key === start.value
    const isEnd = key === end.value
    const hasRange = props.mode === 'range' && start.value && end.value && start.value !== end.value
    list.push({
      key,
      day,
      isToday: key === todayKey,
      weekend: isWeekend(key),
      disabled: (props.min && key < props.min) || (props.max && key > props.max),
      selected: isStart || isEnd,
      isStart,
      isEnd,
      rangeEdge: hasRange && (isStart || isEnd),
      inRange: hasRange && key > start.value && key < end.value,
    })
  }
  return list
})

function dayClass(cell) {
  if (cell.selected) return 'bg-status-checkin font-bold text-white shadow-md shadow-status-checkin/30'
  if (cell.disabled) return 'cursor-not-allowed text-slate-300'
  if (cell.inRange) return 'font-medium text-status-checkin'
  if (cell.isToday) return 'font-bold text-status-checkin ring-1 ring-status-checkin/40'
  if (cell.weekend) return 'text-rose-400 hover:bg-slate-50'
  return 'text-ink hover:bg-slate-50'
}

function pick(key) {
  if (props.mode === 'single') {
    model.value = key
    return
  }
  // range: แตะครั้งแรก = วันเริ่ม, ครั้งที่สอง = วันสิ้นสุด (ถ้าแตะก่อนวันเริ่ม ให้เริ่มใหม่)
  if (!start.value || end.value || key < start.value) model.value = [key, null]
  else model.value = [start.value, key]
}

function step(delta) {
  cursor.value = shiftMonth(cursor.value, view.value === 'days' ? delta : delta * 12)
}

function chooseMonth(index) {
  cursor.value = `${cursorYear.value}-${String(index + 1).padStart(2, '0')}`
  view.value = 'days'
}

/** เลื่อนปฏิทินไปเดือนปัจจุบัน */
function goToday() {
  cursor.value = toMonthKey()
  view.value = 'days'
}

defineExpose({ goToday })
</script>

<style scoped>
.nav-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 9999px;
  background: #f8faff;
  color: #0f172a;
  transition: background 0.15s;
}

.nav-btn:hover {
  background: #f1f5f9;
}
</style>
