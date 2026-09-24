import { reactive, watch } from 'vue'
import { STORAGE_KEYS } from '@/utils/constants'

const DEFAULTS = {
  notifications: true, // สวิตช์หลัก
  checkInReminder: true,
  checkInReminderTime: '08:45',
  checkOutReminder: true,
  checkOutReminderTime: '18:05',
  requestUpdates: true, // แจ้งผลคำขอลา / ลงเวลาย้อนหลัง
}

function read() {
  try {
    return { ...DEFAULTS, ...JSON.parse(localStorage.getItem(STORAGE_KEYS.SETTINGS)) }
  } catch {
    return { ...DEFAULTS }
  }
}

// ใช้ state เดียวกันทั้งแอป
const settings = reactive(read())

watch(
  settings,
  (value) => {
    try {
      localStorage.setItem(STORAGE_KEYS.SETTINGS, JSON.stringify(value))
    } catch {
      // บันทึกไม่ได้ (เช่น โหมดส่วนตัว) ใช้ค่าในหน่วยความจำต่อไป
    }
  },
  { deep: true },
)

/** การตั้งค่าของผู้ใช้บนเครื่องนี้ (บันทึกใน localStorage อัตโนมัติ) */
export function useSettings() {
  return settings
}
