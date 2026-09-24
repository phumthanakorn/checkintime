import { toDateKey } from '@/utils/formatters'

/** สถานะที่แสดงของรายการลงเวลา 1 วัน -> { label, tone } สำหรับ StatusBadge */
export function getRecordStatus(record) {
  if (!record.checkOut) {
    return record.date === toDateKey()
      ? { label: 'กำลังทำงาน', tone: 'info' }
      : { label: 'ไม่ได้ลงเวลาออก', tone: 'danger' }
  }
  if (record.lateMinutes > 0) return { label: `สาย ${record.lateMinutes} นาที`, tone: 'warning' }
  return { label: 'ตรงเวลา', tone: 'success' }
}
