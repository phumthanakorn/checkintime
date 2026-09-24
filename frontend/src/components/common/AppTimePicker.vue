<template>
  <!-- ตัวเลือกเวลาแบบวงล้อ (ชั่วโมง : นาที) เลื่อนนิ้วหรือแตะตัวเลขก็ได้ -->
  <div class="relative h-[200px] select-none">
    <!-- แถบไฮไลต์ตรงกลาง -->
    <span class="pointer-events-none absolute inset-x-0 top-1/2 h-10 -translate-y-1/2 rounded-xl bg-status-checkin/10" />
    <span
      class="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 font-display text-xl font-bold text-status-checkin"
    >
      :
    </span>

    <div class="wheel-mask grid h-full grid-cols-2">
      <div
        v-for="column in columns"
        :key="column.name"
        :ref="(el) => (columnRefs[column.name] = el)"
        class="wheel h-full overflow-y-auto"
        :aria-label="column.label"
        role="listbox"
        @scroll="onScroll(column.name)"
      >
        <div :style="{ height: `${PADDING}px` }" />
        <button
          v-for="(value, index) in column.values"
          :key="value"
          type="button"
          role="option"
          :aria-selected="selected[column.name] === index"
          class="wheel-item flex w-full items-center justify-center font-display tabular-nums transition-all duration-150"
          :class="
            selected[column.name] === index ? 'text-2xl font-bold text-ink' : 'text-lg text-slate-400'
          "
          :style="{ height: `${ITEM_HEIGHT}px` }"
          @click="scrollTo(column.name, index, true)"
        >
          {{ value }}
        </button>
        <div :style="{ height: `${PADDING}px` }" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { nextTick, onMounted, reactive, watch } from 'vue'

/** 'HH:mm' */
const model = defineModel({ type: String, default: '09:00' })

const props = defineProps({
  /** ช่วงนาทีที่เลือกได้ (1 = ทุกนาที, 5 = ทีละ 5 นาที) */
  minuteStep: { type: Number, default: 1 },
})

const ITEM_HEIGHT = 40
const PADDING = (200 - ITEM_HEIGHT) / 2 // ให้ตัวเลขแรก/สุดท้ายเลื่อนมาอยู่กลางได้

const pad = (n) => String(n).padStart(2, '0')
const columns = [
  { name: 'hour', label: 'ชั่วโมง', values: Array.from({ length: 24 }, (_, i) => pad(i)) },
  {
    name: 'minute',
    label: 'นาที',
    values: Array.from({ length: Math.ceil(60 / props.minuteStep) }, (_, i) => pad(i * props.minuteStep)),
  },
]

const columnRefs = {}
const selected = reactive({ hour: 9, minute: 0 })
const timers = {}

function parse(value) {
  const [h, m] = (value || '09:00').split(':').map(Number)
  return { hour: h, minute: Math.round(m / props.minuteStep) }
}

function scrollTo(name, index, smooth = false) {
  selected[name] = index
  columnRefs[name]?.scrollTo({ top: index * ITEM_HEIGHT, behavior: smooth ? 'smooth' : 'auto' })
  commit()
}

function onScroll(name) {
  const el = columnRefs[name]
  const max = columns.find((c) => c.name === name).values.length - 1
  selected[name] = Math.min(max, Math.max(0, Math.round(el.scrollTop / ITEM_HEIGHT)))
  // อัปเดตค่าเมื่อหยุดเลื่อน
  clearTimeout(timers[name])
  timers[name] = setTimeout(commit, 120)
}

function commit() {
  const minute = columns[1].values[selected.minute]
  model.value = `${pad(selected.hour)}:${minute}`
}

const currentValue = () => `${pad(selected.hour)}:${columns[1].values[selected.minute]}`

/** จัดวงล้อให้ตรงกับค่าปัจจุบัน (เรียกหลังแสดงผล เช่น เมื่อเปิด bottom sheet) */
async function sync(smooth = false) {
  await nextTick()
  const { hour, minute } = parse(model.value)
  requestAnimationFrame(() => {
    selected.hour = hour
    selected.minute = minute
    const behavior = smooth ? 'smooth' : 'auto'
    columnRefs.hour?.scrollTo({ top: hour * ITEM_HEIGHT, behavior })
    columnRefs.minute?.scrollTo({ top: minute * ITEM_HEIGHT, behavior })
  })
}

// ค่าถูกเปลี่ยนจากภายนอก (เช่น ปุ่มลัด) -> เลื่อนวงล้อตาม
watch(model, (value) => {
  if (value && value !== currentValue()) sync(true)
})

onMounted(() => sync())

defineExpose({ sync })
</script>

<style scoped>
.wheel {
  scroll-snap-type: y mandatory;
  scrollbar-width: none;
  overscroll-behavior: contain;
}

.wheel::-webkit-scrollbar {
  display: none;
}

.wheel-item {
  scroll-snap-align: center;
}

/* จางบน-ล่าง ให้ดูเป็นวงล้อ */
.wheel-mask {
  mask-image: linear-gradient(to bottom, transparent, #000 30%, #000 70%, transparent);
  -webkit-mask-image: linear-gradient(to bottom, transparent, #000 30%, #000 70%, transparent);
}
</style>
