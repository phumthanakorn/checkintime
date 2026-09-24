import axiosClient from '@/api/axiosClient'
import { mockAttendance } from '@/api/mock/mockServer'
import { USE_MOCK } from '@/utils/constants'

const httpAttendance = {
  /** รายการลงเวลาของวันนี้ของผู้ใช้ปัจจุบัน (null ถ้ายังไม่ลงเวลา) */
  getToday: () => axiosClient.get('/attendance/today'),
  /** @param {{ location: { lat: number, lng: number, accuracy: number } | null }} payload */
  checkIn: (payload) => axiosClient.post('/attendance/check-in', payload),
  checkOut: (payload) => axiosClient.post('/attendance/check-out', payload),
  /**
   * @param {{ month: string }} params  month = 'YYYY-MM'
   * @returns {Promise<{ leaveRemainingDays: number, lateCount: number, lateMinutes: number, otMinutes: number }>}
   */
  getSummary: (params) => axiosClient.get('/attendance/summary', { params }),
  getHistory: (params) => axiosClient.get('/attendance/history', { params }),
}

export const attendanceService = USE_MOCK ? mockAttendance : httpAttendance
