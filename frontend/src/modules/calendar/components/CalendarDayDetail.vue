<template>
  <section v-if="day" class="rounded-3xl bg-card p-4 shadow-sm">
    <div class="mb-3 flex items-center justify-between gap-2">
      <h2 class="text-base font-bold text-ink">{{ formatThaiDate(day.date) }}</h2>
      <span v-if="day.date === today" class="rounded-full bg-status-checkin/10 px-2.5 py-1 text-[11px] font-semibold text-status-checkin">
        วันนี้
      </span>
    </div>

    <!-- เวลาเข้า-ออกงาน: เข้า=ซ้าย ออก=ขวา — ตัดแถว "กะ" ออก เพราะยังไม่มีระบบกะงานจริง (ดูแค่เวลาที่ลงจริง
    จาก Employee_CheckinTime_Attendance_Log เท่านั้น) -->
    <div v-if="day.attendance" class="mb-3">
      <span class="mb-2 flex items-center gap-1.5 text-sm font-medium text-ink">
        <span class="h-2 w-2 rounded-full" :class="DAY_STATUS_META[day.attendance].dot" />
        {{ DAY_STATUS_META[day.attendance].label }}
      </span>
      <div class="grid grid-cols-2 gap-3">
        <button
          type="button"
          class="rounded-2xl bg-app-bg p-3 text-left transition active:scale-[0.98]"
          :disabled="!day.checkInDetail && !day.checkInAdjustment"
          @click="openDetail('checkIn')"
        >
          <span class="flex items-center gap-1.5 text-xs text-ink-muted">
            <AppIcon name="sign-in" :size="14" />
            เข้างาน
          </span>
          <p class="mt-1 font-display text-xl font-bold tabular-nums text-ink">
            {{ day.checkIn ? formatClock(day.checkIn) : '--:--' }}
          </p>
          <!-- ถ้าเวลาที่โชว์มาจากการอนุมัติ (ไม่ใช่สแกนจริง) ต้องไม่โชว์สถานที่ของการสแกน เพราะเป็นคนละช่วง
          เวลากัน (คำขอลงเวลาย้อนหลังไม่มีพิกัด GPS เลย) — โชว์ป้ายบอกที่มาแทน ไม่ให้เข้าใจผิดว่าสแกนที่นั่นตอนนั้น -->
          <p v-if="day.checkInSource === 'approved'" class="mt-1.5 flex items-start gap-1 text-[11px] leading-tight text-violet-500">
            <AppIcon name="clock-edit" :size="12" class="mt-0.5 shrink-0" />
            <span>เวลาจากการขอลงเวลาย้อนหลัง</span>
          </p>
          <!-- มาจากระบบ DEMPC เดิม (Excel import / แก้มือผ่านหน้าเดิม) ไม่ใช่สแกนผ่านแอปนี้ — ก็ไม่มีพิกัด GPS
          ให้โชว์เหมือนกัน (ดู CheckinAttendanceLogModel::legacyEffectiveTimes()) -->
          <p v-else-if="day.checkInSource === 'legacy'" class="mt-1.5 flex items-start gap-1 text-[11px] leading-tight text-metric-blue">
            <AppIcon name="clock" :size="12" class="mt-0.5 shrink-0" />
            <span>เวลาจากระบบ DEMPC เดิม</span>
          </p>
          <p v-else-if="day.checkInDetail?.locationName" class="mt-1.5 flex items-start gap-1 text-[11px] leading-tight text-ink-muted">
            <AppIcon name="map-pin" :size="12" class="mt-0.5 shrink-0" />
            <span>
              {{ day.checkInDetail.locationName }}
              <template v-if="day.checkInDetail.distanceMeters != null">
                ({{ Math.round(day.checkInDetail.distanceMeters) }} ม.)
              </template>
            </span>
          </p>
        </button>
        <button
          type="button"
          class="rounded-2xl bg-app-bg p-3 text-left transition active:scale-[0.98]"
          :disabled="!day.checkOutDetail && !day.checkOutAdjustment"
          @click="openDetail('checkOut')"
        >
          <span class="flex items-center gap-1.5 text-xs text-ink-muted">
            <AppIcon name="sign-out" :size="14" />
            ออกงาน
          </span>
          <p class="mt-1 font-display text-xl font-bold tabular-nums text-ink">
            {{ day.checkOut ? formatClock(day.checkOut) : '--:--' }}
          </p>
          <p v-if="day.checkOutSource === 'approved'" class="mt-1.5 flex items-start gap-1 text-[11px] leading-tight text-violet-500">
            <AppIcon name="clock-edit" :size="12" class="mt-0.5 shrink-0" />
            <span>เวลาจากการขอลงเวลาย้อนหลัง</span>
          </p>
          <p v-else-if="day.checkOutSource === 'legacy'" class="mt-1.5 flex items-start gap-1 text-[11px] leading-tight text-metric-blue">
            <AppIcon name="clock" :size="12" class="mt-0.5 shrink-0" />
            <span>เวลาจากระบบ DEMPC เดิม</span>
          </p>
          <p v-else-if="day.checkOutDetail?.locationName" class="mt-1.5 flex items-start gap-1 text-[11px] leading-tight text-ink-muted">
            <AppIcon name="map-pin" :size="12" class="mt-0.5 shrink-0" />
            <span>
              {{ day.checkOutDetail.locationName }}
              <template v-if="day.checkOutDetail.distanceMeters != null">
                ({{ Math.round(day.checkOutDetail.distanceMeters) }} ม.)
              </template>
            </span>
          </p>
        </button>
      </div>
    </div>

    <!-- วันที่ยังไม่มีอะไรให้แสดง (ไม่มีลงเวลา/วันหยุด/วันลา) — บอกเหตุผลสั้นๆ แทนปล่อยการ์ดว่างเปล่า -->
    <p v-else-if="emptyMessage" class="mb-3 rounded-2xl bg-app-bg px-3 py-4 text-center text-sm text-ink-muted">
      {{ emptyMessage }}
    </p>

    <ul class="list-none space-y-2.5">
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

    <!-- รายละเอียดการสแกนจริง (เข้า/ออก) — เปิดจากการกดการ์ดเวลาด้านบน เวลาที่โชว์ในนี้คือเวลาสแกนจริงเสมอ
    (ไม่ใช่ effective time ที่อาจถูกค่าอนุมัติทับ — ดู comment CalendarController::detail()) -->
    <AppBottomSheet v-model="sheetOpen" :title="sheetTitle">
      <div v-if="activeAdjustment" class="mb-4 space-y-3 rounded-2xl border border-violet-100 bg-violet-50 p-4">
        <div class="flex items-center justify-between gap-3">
          <p class="text-xs text-violet-600">เวลาที่อนุมัติ</p>
          <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs text-emerald-700">อนุมัติแล้ว</span>
        </div>
        <p class="font-display text-xl font-bold text-ink">{{ formatClock(activeAdjustment.time) }}</p>
        <p v-if="activeAdjustment.requestId" class="text-xs text-ink-muted">คำขอ #{{ activeAdjustment.requestId }}</p>
        <div v-if="activeAdjustment.reason"><p class="text-xs text-ink-muted">เหตุผลที่ขอปรับเวลา</p><p class="whitespace-pre-wrap text-sm text-ink">{{ activeAdjustment.reason }}</p></div>
        <p v-if="activeAdjustment.reviewedBy" class="text-xs text-ink-muted">รหัสผู้อนุมัติ: {{ activeAdjustment.reviewedBy }}</p>
        <p v-if="activeAdjustment.reviewedAt" class="text-xs text-ink-muted">อนุมัติ {{ formatThaiDate(activeAdjustment.reviewedAt) }} เวลา {{ formatClock(activeAdjustment.reviewedAt) }}</p>
        <p v-if="activeAdjustment.reviewNote" class="whitespace-pre-wrap text-sm text-ink">หมายเหตุ: {{ activeAdjustment.reviewNote }}</p>
        <TimeFixAttachments v-if="activeAdjustment.requestId" :request-id="activeAdjustment.requestId" :files="activeAdjustment.attachments" />
      </div>
      <h3 v-if="activeAdjustment && activeDetail" class="mb-3 border-t border-slate-100 pt-4 text-sm font-semibold text-ink">ข้อมูลการสแกนเดิม</h3>
      <p v-if="activeAdjustment && !activeDetail" class="text-sm text-ink-muted">ไม่มีข้อมูลการสแกนเดิม</p>
      <div v-if="activeDetail" class="space-y-4">
        <div class="flex items-center gap-3">
          <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-status-checkin/10 text-status-checkin">
            <AppIcon name="clock" :size="20" />
          </span>
          <div>
            <p class="text-xs text-ink-muted">เวลาที่สแกน</p>
            <p class="font-display text-lg font-bold tabular-nums text-ink">{{ formatClock(activeDetail.time) }}</p>
          </div>
        </div>

        <div v-if="activeDetail.locationName" class="flex items-start gap-3">
          <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-metric-blue/10 text-metric-blue">
            <AppIcon name="map-pin" :size="20" />
          </span>
          <div class="min-w-0">
            <p class="text-xs text-ink-muted">สถานที่ใกล้ที่สุด</p>
            <p class="text-sm font-semibold text-ink">
              {{ activeDetail.locationName }}
              <span v-if="activeDetail.distanceMeters != null" class="font-normal text-ink-muted">
                (ห่าง {{ Math.round(activeDetail.distanceMeters) }} ม.)
              </span>
            </p>
            <p v-if="activeDetail.lat != null" class="mt-0.5 font-display text-xs tabular-nums text-ink-muted">
              {{ activeDetail.lat.toFixed(5) }}, {{ activeDetail.lng.toFixed(5) }}
              <template v-if="activeDetail.accuracyMeters != null">· ±{{ activeDetail.accuracyMeters }} ม.</template>
            </p>
          </div>
        </div>

        <!-- แผนที่จุดที่สแกน — โหมดไม่มี office อ้างอิง (มีแค่พิกัดจุดสแกนเอง ไม่มีพิกัดของ "สถานที่ใกล้ที่สุด"
        เก็บไว้ในระบบ มีแต่ชื่อ+ระยะห่าง) ดู MapView.vue ที่ปรับให้ office เป็น optional แล้ว -->
        <div v-if="activeDetail.lat != null" class="h-40 overflow-hidden rounded-2xl">
          <MapView :user="{ lat: activeDetail.lat, lng: activeDetail.lng, accuracy: activeDetail.accuracyMeters }" />
        </div>

        <div v-if="activeDetail.device" class="flex items-start gap-3">
          <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-500">
            <AppIcon name="mobile" :size="20" />
          </span>
          <div class="min-w-0 flex-1">
            <p class="text-xs text-ink-muted">อุปกรณ์</p>
            <p class="text-sm text-ink">{{ formatDeviceLabel(activeDetail.device) }}</p>
          </div>
        </div>

        <!-- รูปถ่ายยืนยันตัวตน — โหลดแบบ lazy ตอนเปิด sheet เท่านั้น (ไม่ preload ทุกวันในปฏิทิน) ผ่าน
        axios เป็น blob เพราะ endpoint ต้องมี Authorization header แนบไปด้วย <img src> ธรรมดาทำไม่ได้
        (ดู photoUrl/loadPhoto ด้านล่าง) -->
        <div v-if="activeDetail.hasPhoto">
          <p class="mb-1.5 flex items-center gap-1.5 text-xs text-ink-muted">
            <AppIcon name="camera" :size="14" />
            รูปถ่ายยืนยันตัวตนตอนสแกน
          </p>
          <div class="flex min-h-[140px] items-center justify-center overflow-hidden rounded-2xl bg-app-bg">
            <img v-if="photoUrl" :src="photoUrl" alt="รูปถ่ายยืนยันตัวตนตอนสแกน" class="w-full object-cover" />
            <p v-else-if="photoError" class="px-4 text-center text-xs text-ink-muted">โหลดรูปไม่สำเร็จ</p>
            <LoadingDots v-else size="sm" />
          </div>
        </div>
      </div>
    </AppBottomSheet>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import axiosClient from '@/api/axiosClient'
import AppBottomSheet from '@/components/common/AppBottomSheet.vue'
import MapView from '@/components/common/MapView.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import LoadingDots from '@/components/feedback/LoadingDots.vue'
import TimeFixAttachments from '@/modules/time-fix/components/TimeFixAttachments.vue'
import { DAY_STATUS_META, LEAVE_PERIOD_LABELS, LEAVE_TYPE_META, REQUEST_STATUS_META } from '@/utils/constants'
import { formatClock, formatDeviceLabel, formatThaiDate, toDateKey } from '@/utils/formatters'

const props = defineProps({
  day: { type: Object, default: null },
})

const today = toDateKey()

// เปิด bottom sheet รายละเอียดการสแกน — เก็บแค่ว่าเปิดฝั่งไหน ('checkIn' | 'checkOut' | null) แล้ว derive
// รายละเอียดจาก props.day สดๆ ทุกครั้ง (ไม่ copy ข้อมูลมาเก็บเอง กัน sheet ค้างข้อมูลเก่าเวลา day เปลี่ยน)
const sheetSide = ref(null)
const sheetOpen = computed({
  get: () => sheetSide.value !== null,
  set: (value) => { if (!value) sheetSide.value = null },
})
const activeDetail = computed(() => (sheetSide.value ? props.day?.[`${sheetSide.value}Detail`] : null))
const activeAdjustment = computed(() => (sheetSide.value && props.day?.[`${sheetSide.value}Source`] === 'approved' ? props.day?.[`${sheetSide.value}Adjustment`] : null))
const sheetTitle = computed(() => (activeAdjustment.value ? (sheetSide.value === 'checkOut' ? 'รายละเอียดการปรับเวลาออกงาน' : 'รายละเอียดการปรับเวลาเข้างาน') : (sheetSide.value === 'checkOut' ? 'รายละเอียดการออกงาน' : 'รายละเอียดการเข้างาน')))

function openDetail(side) {
  if (!props.day?.[`${side}Detail`] && !props.day?.[`${side}Adjustment`]) return
  sheetSide.value = side
}

// รูปถ่าย — ดึงเป็น blob (ไม่ใช่ <img src> ตรงๆ) เพราะ endpoint /attendance/photo ต้องมี Authorization
// header แนบไปด้วยถึงจะผ่าน auth guard ได้ (ดู AbstractApiController) แปลงเป็น object URL ชั่วคราวไว้ใช้โชว์
// แล้ว revoke ทิ้งทุกครั้งที่เปลี่ยนรูป/ปิด sheet กัน memory leak สะสม
const photoUrl = ref(null)
const photoError = ref(false)

function clearPhoto() {
  if (photoUrl.value) URL.revokeObjectURL(photoUrl.value)
  photoUrl.value = null
  photoError.value = false
}

async function loadPhoto(day, side) {
  clearPhoto()
  try {
    const blob = await axiosClient.get('/attendance/photo', {
      params: { date: day, side },
      responseType: 'blob',
    })
    photoUrl.value = URL.createObjectURL(blob)
  } catch {
    photoError.value = true
  }
}

watch(
  () => [sheetSide.value, activeDetail.value?.hasPhoto, props.day?.date],
  ([side, hasPhoto]) => {
    if (side && hasPhoto) loadPhoto(props.day.date, side)
    else clearPhoto()
  },
)

onBeforeUnmount(clearPhoto)

const leaveType = computed(() => LEAVE_TYPE_META[props.day?.leave?.leaveType] ?? {})
const needsFix = computed(() => ['missing', 'incomplete'].includes(props.day?.attendance))

// ไม่มีทั้งการลงเวลา/วันหยุด/วันลา — บอกเหตุผลให้ชัดแทนปล่อยว่าง: วันอนาคตยังไม่ถึงวัน, วันนี้ยังไม่ได้ลงเวลา
const emptyMessage = computed(() => {
  const d = props.day
  if (!d || d.attendance || d.holiday || d.leave) return null
  return d.date > today ? 'ยังไม่ถึงวันนี้' : 'วันนี้ยังไม่ได้ลงเวลา'
})
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
