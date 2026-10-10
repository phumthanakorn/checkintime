<template>
  <section class="overflow-hidden rounded-[28px] bg-card p-5 shadow-sm">
    <div class="mb-4 flex items-center justify-between gap-2">
      <div>
        <p class="text-[11px] text-ink-muted">วันนี้</p>
        <p class="text-sm font-bold text-ink">{{ formatThaiDate(today) }}</p>
      </div>
      <span
        v-if="statusLabel"
        class="flex items-center gap-1.5 rounded-full px-3 py-1.5 text-[11px] font-semibold"
        :class="statusClass"
      >
        <span class="h-1.5 w-1.5 rounded-full bg-current" />
        {{ statusLabel }}
      </span>
      <span v-else class="rounded-full bg-app-bg px-3 py-1.5 text-[11px] font-semibold text-ink-muted">
        ยังไม่ได้ลงเวลา
      </span>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div class="relative overflow-hidden rounded-2xl p-4" :class="record?.checkIn ? 'bg-status-checkin/10' : 'bg-app-bg'">
        <span
          class="flex items-center gap-1.5 text-xs font-semibold"
          :class="record?.checkIn ? 'text-status-checkin' : 'text-ink-muted'"
        >
          <AppIcon name="sign-in" :size="15" weight="bold" />
          เข้างาน
        </span>
        <p class="mt-2 font-display text-[28px] font-extrabold leading-none tabular-nums text-ink">
          {{ record?.checkIn ? formatClock(record.checkIn) : '--:--' }}
        </p>
        <!-- ถ้าเวลาที่โชว์มาจากการอนุมัติ (ไม่ใช่สแกนจริง) ต้องไม่โชว์สถานที่สแกน เพราะคนละช่วงเวลากัน
        (คำขอลงเวลาย้อนหลังไม่มีพิกัด GPS) — โชว์ป้ายบอกที่มาแทน -->
        <p v-if="record?.checkInSource === 'approved'" class="mt-2 flex items-center gap-1 text-[11px] leading-tight text-violet-500">
          <AppIcon name="clock-edit" :size="12" class="shrink-0" />
          <span>เวลาจากการขอลงเวลาย้อนหลัง</span>
        </p>
        <p v-else-if="record?.checkInSource === 'legacy'" class="mt-2 flex items-center gap-1 text-[11px] leading-tight text-metric-blue">
          <AppIcon name="clock" :size="12" class="shrink-0" />
          <span>เวลาจากระบบ DEMPC เดิม</span>
        </p>
        <p v-else-if="checkInLocationName" class="mt-2 flex items-center gap-1 text-[11px] leading-tight text-ink-muted">
          <AppIcon name="map-pin" :size="12" class="shrink-0" />
          <span class="min-w-0 truncate">{{ checkInLocationName }}</span>
        </p>
      </div>

      <div class="relative overflow-hidden rounded-2xl p-4" :class="record?.checkOut ? 'bg-action-checkout/10' : 'bg-app-bg'">
        <span
          class="flex items-center gap-1.5 text-xs font-semibold"
          :class="record?.checkOut ? 'text-action-checkout' : 'text-ink-muted'"
        >
          <AppIcon name="sign-out" :size="15" weight="bold" />
          ออกงาน
        </span>
        <p class="mt-2 font-display text-[28px] font-extrabold leading-none tabular-nums text-ink">
          {{ record?.checkOut ? formatClock(record.checkOut) : '--:--' }}
        </p>
        <p v-if="record?.checkOutSource === 'approved'" class="mt-2 flex items-center gap-1 text-[11px] leading-tight text-violet-500">
          <AppIcon name="clock-edit" :size="12" class="shrink-0" />
          <span>เวลาจากการขอลงเวลาย้อนหลัง</span>
        </p>
        <p v-else-if="record?.checkOutSource === 'legacy'" class="mt-2 flex items-center gap-1 text-[11px] leading-tight text-metric-blue">
          <AppIcon name="clock" :size="12" class="shrink-0" />
          <span>เวลาจากระบบ DEMPC เดิม</span>
        </p>
        <p v-else-if="checkOutLocationName" class="mt-2 flex items-center gap-1 text-[11px] leading-tight text-ink-muted">
          <AppIcon name="map-pin" :size="12" class="shrink-0" />
          <span class="min-w-0 truncate">{{ checkOutLocationName }}</span>
        </p>
      </div>
    </div>

    <!-- ระยะเวลาทำงานวันนี้ — มีเฉพาะตอนลงเวลาออกแล้วเท่านั้น (ข้อเท็จจริงเฉยๆ ไม่ใช่การตัดสินสาย/OT) -->
    <p v-if="record?.workMinutes != null" class="mt-3 flex items-center gap-1.5 text-xs text-ink-muted">
      <AppIcon name="timer" :size="14" />
      วันนี้ทำงาน {{ formatDuration(record.workMinutes) }}
    </p>
  </section>
</template>

<script setup>
/**
 * แทนที่ AttendanceStats.vue (การ์ดสถิติ 4 ช่อง วันลาคงเหลือ/มาสาย/OT) บนหน้าหลัก — เลิกใช้เพราะระบบนี้ไม่มี
 * การตัดสินสาย/OT แล้ว ตัวเลข 3 ใน 4 ช่องเลยเป็น 0 ตลอดไม่มีความหมาย ใช้การ์ด "วันนี้ลงเวลาอะไรไปแล้วบ้าง"
 * แทน ซึ่งมีข้อมูลจริงให้โชว์เสมอ (จาก attendance.today ที่ fetchHome() โหลดมาอยู่แล้ว ไม่ต้องยิง API เพิ่ม)
 */
import { computed } from 'vue'
import { formatClock, formatDuration, formatThaiDate, toDateKey } from '@/utils/formatters'

const props = defineProps({
  /** attendance.today จาก store — null ถ้ายังไม่ได้ลงเวลาเข้าเลยวันนี้ */
  record: { type: Object, default: null },
})

const today = toDateKey()

const statusLabel = computed(() => {
  if (!props.record?.checkIn) return null
  return props.record.checkOut ? 'ลงเวลาครบแล้ว' : 'กำลังทำงาน'
})
// action-done (เขียว) ให้ตรงกับสีการ์ดเวลาด้านบนตอนสถานะ "ครบแล้ว" (CheckInCard.vue) — ไม่ใช้ status-checkin
// (ส้ม ซ้ำกับ "เข้างาน") หรือ status-done (เทาเข้ม ใกล้เคียง action-checkout จนแยกยาก)
const statusClass = computed(() =>
  props.record?.checkOut ? 'bg-action-done/10 text-action-done' : 'bg-metric-blue/10 text-metric-blue',
)

const checkInLocationName = computed(() => props.record?.checkInContext?.nearestLocation?.location?.name ?? null)
const checkOutLocationName = computed(() => props.record?.checkOutContext?.nearestLocation?.location?.name ?? null)
</script>
