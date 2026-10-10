import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { attendanceService } from '@/api/services/attendanceService'
import { requestService } from '@/api/services/requestService'
import { toMonthKey } from '@/utils/formatters'

const emptySummary = () => ({ leaveRemainingDays: 0, lateCount: 0, lateMinutes: 0, otMinutes: 0 })

export const useAttendanceStore = defineStore('attendance', () => {
  const today = ref(null)
  const summary = ref(emptySummary())
  const requestStatus = ref([])
  const history = ref([])

  const loading = ref(false)
  const submitting = ref(false)
  const loadingHistory = ref(false)

  const hasCheckedIn = computed(() => !!today.value?.checkIn)
  const hasCheckedOut = computed(() => !!today.value?.checkOut)

  /** โหลดข้อมูลทั้งหมดที่หน้าหลักใช้ */
  async function fetchHome() {
    loading.value = true
    try {
      const [todayRecord, monthSummary, requests] = await Promise.all([
        attendanceService.getToday(),
        attendanceService.getSummary({ month: toMonthKey() }),
        requestService.getStatusSummary(),
      ])
      today.value = todayRecord
      summary.value = monthSummary
      requestStatus.value = requests
    } finally {
      loading.value = false
    }
  }

  async function refreshSummary() {
    summary.value = await attendanceService.getSummary({ month: toMonthKey() })
  }

  /**
   * payload:
   *  - location: { lat, lng, accuracy } | null — GPS จริงของเครื่อง ณ ตอนลงเวลา
   *  - photo: dataURL ของภาพเซลฟี่ยืนยันตัวตน (จาก CameraCaptureModal)
   *  - nearestLocation: { location, distance } | null — สถานที่ลงเวลาที่ระบบเดาว่าใกล้ที่สุด (จาก
   *    useGeofence().nearest) คนละเรื่องกับ location ข้างบน (นึงคือ "เดาว่าใกล้ที่ไหน" อีกนึงคือพิกัดจริง)
   *  - device: string — navigator.userAgent ของเครื่องที่ลงเวลา
   * ฝั่งนี้แค่ส่งผ่านทั้งหมดไปให้ backend ตัดสินใจเองว่าจะเก็บ/ตรวจสอบยังไง
   */
  async function checkIn(payload = {}) {
    submitting.value = true
    try {
      today.value = await attendanceService.checkIn(payload)
      await refreshSummary()
      return today.value
    } finally {
      submitting.value = false
    }
  }

  async function checkOut(payload = {}) {
    submitting.value = true
    try {
      today.value = await attendanceService.checkOut(payload)
      await refreshSummary()
      return today.value
    } finally {
      submitting.value = false
    }
  }

  /** ประวัติการลงเวลาของเดือน ('YYYY-MM') */
  async function fetchHistory(month) {
    loadingHistory.value = true
    try {
      history.value = await attendanceService.getHistory({ month })
    } finally {
      loadingHistory.value = false
    }
  }

  function reset() {
    today.value = null
    summary.value = emptySummary()
    requestStatus.value = []
    history.value = []
  }

  return {
    today,
    summary,
    requestStatus,
    history,
    loading,
    loadingHistory,
    submitting,
    hasCheckedIn,
    hasCheckedOut,
    fetchHome,
    checkIn,
    checkOut,
    fetchHistory,
    reset,
  }
})
