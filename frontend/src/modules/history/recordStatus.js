import { REQUEST_STATUS, TIME_FIX_TYPES } from '@/utils/constants'
import { toDateKey } from '@/utils/formatters'

/** วันนี้ลงเวลาไม่ครบ (ไม่มีการลงเวลาเลย หรือไม่ได้ลงเวลาออก) และไม่ใช่วันนี้ */
export function isIncomplete(record) {
  return record.date !== toDateKey() && (record.missing || !record.checkOut)
}

/** มีคำขอแก้ไขเวลารออนุมัติอยู่ */
export const hasPendingFix = (record) => record.fixStatus === REQUEST_STATUS.PENDING

/** ประเภทคำขอที่ควรเลือกให้อัตโนมัติ เมื่อขอลงเวลาย้อนหลังจากรายการนี้ */
export function suggestedFixType(record) {
  if (record.missing) return TIME_FIX_TYPES.BOTH
  if (!record.checkOut) return TIME_FIX_TYPES.CHECK_OUT
  return TIME_FIX_TYPES.CHECK_IN
}

/** สถานะที่แสดงของรายการลงเวลา 1 วัน -> { label, tone } สำหรับ StatusBadge */
export function getRecordStatus(record) {
  if (hasPendingFix(record)) return { label: 'รออนุมัติแก้ไข', tone: 'info' }
  if (record.missing) return { label: 'ขาดลงเวลา', tone: 'danger' }
  if (!record.checkOut) {
    return record.date === toDateKey()
      ? { label: 'กำลังทำงาน', tone: 'info' }
      : { label: 'ไม่ได้ลงเวลาออก', tone: 'danger' }
  }
  if (record.lateMinutes > 0) return { label: `สาย ${record.lateMinutes} นาที`, tone: 'warning' }
  return { label: 'ตรงเวลา', tone: 'success' }
}
