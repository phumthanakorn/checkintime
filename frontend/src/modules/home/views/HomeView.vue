<template>
  <div class="space-y-5">
    <StatePreviewSwitcher v-if="SHOW_STATE_PREVIEW" v-model="previewState" @celebrate="previewCelebration" />

    <CheckInCard :state="displayState" :now="now" :loading="attendance.submitting" @action="handleAction" />

    <AttendanceStats :summary="displaySummary" />

    <RequestStatusList :items="attendance.requestStatus" @select="handleSelectRequest" />
  </div>

  <ConfirmModal
    v-model="confirmCheckOut"
    title="ยืนยันการออกงาน"
    :message="`ลงเวลาออกงานเวลา ${formatClock(now)} ใช่หรือไม่?`"
    confirm-text="ออกงาน"
    color="warning"
    :loading="attendance.submitting"
    @confirm="doCheckOut"
  />

  <ClockSuccessOverlay v-model="celebration.open" v-bind="celebration.props" />
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import CheckInCard from '../components/CheckInCard.vue'
import AttendanceStats from '../components/AttendanceStats.vue'
import RequestStatusList from '../components/RequestStatusList.vue'
import StatePreviewSwitcher from '../components/StatePreviewSwitcher.vue'
import ClockSuccessOverlay from '../components/ClockSuccessOverlay.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useAttendanceStore } from '@/store'
import { useNow } from '@/composables/useNow'
import { useGeofence } from '@/composables/useGeofence'
import { useNotification } from '@/composables/useNotification'
import { CLOCK_STATE, REQUEST_TYPES, USE_MOCK } from '@/utils/constants'
import { formatClock } from '@/utils/formatters'

// แสดงแถบดูตัวอย่างสถานะเฉพาะตอนพัฒนา หรือโหมด mock
const SHOW_STATE_PREVIEW = import.meta.env.DEV || USE_MOCK

// ตัวเลขสรุปตัวอย่างของแต่ละสถานะ (ตามแบบ UI)
const PREVIEW_SUMMARY = {
  [CLOCK_STATE.READY]: { leaveRemainingDays: 20, lateCount: 1, lateMinutes: 580, otMinutes: 25 },
  [CLOCK_STATE.WORKING]: { leaveRemainingDays: 20, lateCount: 1, lateMinutes: 580, otMinutes: 25 },
  [CLOCK_STATE.DONE]: { leaveRemainingDays: 20, lateCount: 0, lateMinutes: 0, otMinutes: 0 },
  [CLOCK_STATE.OUT_OF_AREA]: { leaveRemainingDays: 20, lateCount: 0, lateMinutes: 0, otMinutes: 0 },
}

const attendance = useAttendanceStore()
const geofence = useGeofence()
const notify = useNotification()
const router = useRouter()
const now = useNow()

const confirmCheckOut = ref(false)
const previewState = ref(null)

// หน้าจอฉลองหลังลงเวลาสำเร็จ
const celebration = reactive({ open: false, props: {} })

function celebrate(variant, props = {}) {
  celebration.props = { variant, time: new Date(), ...props }
  celebration.open = true
}

/** เลือกหน้าจอฉลองตามรายการลงเวลาที่เพิ่งบันทึก */
function celebrateRecord(record) {
  if (record.checkOut) {
    celebrate('checkout', { time: record.checkOut, workMinutes: record.workMinutes })
  } else if (record.lateMinutes > 0) {
    celebrate('checkin-late', { time: record.checkIn, lateMinutes: record.lateMinutes })
  } else {
    celebrate('checkin-ontime', { time: record.checkIn })
  }
}

// ตัวอย่างจากแถบ UI preview
function previewCelebration(variant) {
  celebrate(variant, { lateMinutes: 12, workMinutes: 545 })
}

const clockState = computed(() => {
  if (attendance.hasCheckedOut) return CLOCK_STATE.DONE
  if (!geofence.inside.value) return CLOCK_STATE.OUT_OF_AREA
  if (attendance.hasCheckedIn) return CLOCK_STATE.WORKING
  return CLOCK_STATE.READY
})

const displayState = computed(() => previewState.value ?? clockState.value)
const displaySummary = computed(() =>
  previewState.value ? PREVIEW_SUMMARY[previewState.value] : attendance.summary,
)

onMounted(async () => {
  geofence.refresh()
  try {
    await attendance.fetchHome()
  } catch (error) {
    notify.error(error.message)
  }
})

async function handleAction(state) {
  // โหมดดูตัวอย่าง: เปลี่ยนสถานะบนหน้าจออย่างเดียว ไม่เรียก API
  if (previewState.value) {
    if (state === CLOCK_STATE.READY) {
      previewState.value = CLOCK_STATE.WORKING
      celebrate('checkin-ontime')
    } else if (state === CLOCK_STATE.WORKING) {
      confirmCheckOut.value = true
    }
    return
  }

  // ขอพิกัดใหม่ทุกครั้งก่อนลงเวลา
  await geofence.refresh()
  if (!geofence.inside.value) {
    notify.warning('คุณอยู่นอกพื้นที่ที่อนุญาตให้ลงเวลา')
    return
  }

  if (state === CLOCK_STATE.READY) await doCheckIn()
  else if (state === CLOCK_STATE.WORKING) confirmCheckOut.value = true
}

async function doCheckIn() {
  try {
    celebrateRecord(await attendance.checkIn(geofence.position.value))
  } catch (error) {
    notify.error(error.message)
  }
}

async function doCheckOut() {
  if (previewState.value) {
    previewState.value = CLOCK_STATE.DONE
    confirmCheckOut.value = false
    celebrate('checkout', { workMinutes: 545 })
    return
  }

  try {
    const record = await attendance.checkOut(geofence.position.value)
    confirmCheckOut.value = false
    celebrateRecord(record)
  } catch (error) {
    notify.error(error.message)
  }
}

function handleSelectRequest(type) {
  if (type === REQUEST_TYPES.LEAVE) router.push({ name: 'leave' })
  else notify.info('เมนูการเบิกเงินจะเปิดให้ใช้งานเร็ว ๆ นี้')
}
</script>
