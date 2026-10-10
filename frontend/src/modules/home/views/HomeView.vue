<template>
  <div class="home-stagger space-y-5">
    <LocationPermissionBanner />

    <CheckInCard
      :state="clockState"
      :now="now"
      :loading="attendance.submitting"
      :nearest-location="geofence.nearest.value"
      @action="handleAction"
      @help="openOutOfArea"
      @location-click="locationMapOpen = true"
    />

    <TodayAttendanceCard :record="attendance.today" />

    <RequestStatusList :items="attendance.requestStatus" @select="handleSelectRequest" />
  </div>

  <!-- ถ่ายเซลฟี่ยืนยันตัวตนก่อนลงเวลา — แทนที่การกดค้างแล้วลงเวลาทันทีแบบเดิม (ทั้งเข้างาน/ออกงาน ใช้ชุดเดียวกัน
       ไม่แยก flow เพราะเป็นปุ่มเดียวกัน แค่ label/สีเปลี่ยนตามสถานะ) -->
  <CameraCaptureModal
    v-model="cameraOpen"
    :title="pendingAction === CLOCK_STATE.WORKING ? 'ถ่ายภาพยืนยันออกงาน' : 'ถ่ายภาพยืนยันเข้างาน'"
    @confirm="handlePhotoConfirmed"
  />

  <!-- Popup แผนที่สถานที่ลงเวลาที่ใกล้ที่สุด — เปิดตอนแตะป้ายชื่อสถานที่บนการ์ด ดูเฉยๆ ไม่เกี่ยวกับการบังคับ -->
  <LocationMapModal
    v-model="locationMapOpen"
    :location="geofence.nearest.value?.location"
    :user="geofence.position.value"
  />

  <OfflineSheet v-model="offlineOpen" />

  <ClockSuccessOverlay v-model="celebration.open" v-bind="celebration.props" />
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import CheckInCard from '../components/CheckInCard.vue'
import TodayAttendanceCard from '../components/TodayAttendanceCard.vue'
import RequestStatusList from '../components/RequestStatusList.vue'
import ClockSuccessOverlay from '../components/ClockSuccessOverlay.vue'
import LocationPermissionBanner from '../components/LocationPermissionBanner.vue'
import OfflineSheet from '../components/OfflineSheet.vue'
import CameraCaptureModal from '../components/CameraCaptureModal.vue'
import LocationMapModal from '../components/LocationMapModal.vue'
import { useOnline } from '@/composables/useOnline'
import { useAttendanceStore } from '@/store'
import { useNow } from '@/composables/useNow'
import { useGeofence } from '@/composables/useGeofence'
import { useNotification } from '@/composables/useNotification'
import { CLOCK_STATE, REQUEST_TYPES } from '@/utils/constants'

const attendance = useAttendanceStore()
const geofence = useGeofence()
const notify = useNotification()
const router = useRouter()
const now = useNow()

const cameraOpen = ref(false)
const pendingAction = ref(null) // CLOCK_STATE.READY | CLOCK_STATE.WORKING — รอถ่ายภาพยืนยันอยู่สำหรับ action ไหน
const locationMapOpen = ref(false)
const offlineOpen = ref(false)
const online = useOnline()

function openOutOfArea() {
  router.push({ name: 'out-of-area' })
}

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

const clockState = computed(() => {
  if (attendance.hasCheckedOut) return CLOCK_STATE.DONE
  if (!geofence.inside.value) return CLOCK_STATE.OUT_OF_AREA
  if (attendance.hasCheckedIn) return CLOCK_STATE.WORKING
  return CLOCK_STATE.READY
})

onMounted(async () => {
  geofence.refresh()
  try {
    await attendance.fetchHome()
  } catch (error) {
    notify.error(error.message)
  }
})

async function handleAction(state) {
  // ไม่มีอินเทอร์เน็ต -> อธิบายวิธีแก้
  if (!online.value) {
    offlineOpen.value = true
    return
  }

  // ขอพิกัดใหม่ทุกครั้งก่อนลงเวลา
  await geofence.refresh()
  if (!geofence.inside.value) {
    openOutOfArea()
    return
  }

  // กดค้างผ่านแล้ว (ยืนยันว่าตั้งใจกดจริง) ต่อด้วยถ่ายภาพยืนยันตัวตน — ยังไม่เรียก check-in/check-out จนกว่า
  // จะถ่ายภาพและกด "ยืนยัน" ใน CameraCaptureModal เสร็จ (ดู handlePhotoConfirmed)
  pendingAction.value = state
  cameraOpen.value = true
}

/** ถ่ายภาพยืนยันเสร็จแล้ว (จาก CameraCaptureModal) — ค่อยเรียก check-in/check-out จริงตามที่ค้างไว้ */
async function handlePhotoConfirmed(photo) {
  const action = pendingAction.value
  pendingAction.value = null
  if (action === CLOCK_STATE.READY) await doCheckIn(photo)
  else if (action === CLOCK_STATE.WORKING) await doCheckOut(photo)
}

/** ข้อมูลประกอบตอนลงเวลา: GPS, สถานที่ใกล้สุด, อุปกรณ์ และรูปถ่าย */
function buildCheckContext(photo) {
  return {
    location: geofence.position.value,
    nearestLocation: geofence.nearest.value,
    device: navigator.userAgent,
    photo,
  }
}

async function doCheckIn(photo) {
  try {
    celebrateRecord(await attendance.checkIn(buildCheckContext(photo)))
  } catch (error) {
    notify.error(error.message)
  }
}

async function doCheckOut(photo) {
  try {
    celebrateRecord(await attendance.checkOut(buildCheckContext(photo)))
  } catch (error) {
    notify.error(error.message)
  }
}

function handleSelectRequest(type) {
  // RequestStatusList กรองเหลือแค่ประเภทที่เปิดใช้งานจริงแล้ว (ดู FEATURES ใน utils/constants.js) เลยมีแค่
  // 2 เคสนี้ให้เลือกเท่านั้น
  if (type === REQUEST_TYPES.LEAVE) router.push({ name: 'leave' })
  else if (type === REQUEST_TYPES.TIME_FIX) router.push({ name: 'time-fix' })
}
</script>

<style scoped>
/* การ์ดแต่ละส่วนไล่ fade และเลื่อนขึ้นเมื่อเปิดหน้าหลัก */
.home-stagger > * {
  animation: home-fade-up 0.45s ease-out both;
}
.home-stagger > *:nth-child(1) { animation-delay: 0s; }
.home-stagger > *:nth-child(2) { animation-delay: 0.06s; }
.home-stagger > *:nth-child(3) { animation-delay: 0.12s; }
.home-stagger > *:nth-child(4) { animation-delay: 0.18s; }
.home-stagger > *:nth-child(5) { animation-delay: 0.24s; }

@keyframes home-fade-up {
  from {
    opacity: 0;
    transform: translateY(14px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .home-stagger > * {
    animation-duration: 0.01s !important;
    animation-delay: 0s !important;
  }
}
</style>
