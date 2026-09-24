const DATE_KEY_PATTERN = /^\d{4}-\d{2}-\d{2}$/

/** แปลงค่าเป็น Date โดย 'YYYY-MM-DD' จะถูกตีความเป็นเวลาท้องถิ่น (ไม่ใช่ UTC) */
export function toDate(value) {
  if (typeof value === 'string' && DATE_KEY_PATTERN.test(value)) {
    const [y, m, d] = value.split('-').map(Number)
    return new Date(y, m - 1, d)
  }
  return new Date(value)
}

/** เลื่อนเดือนแบบ 'YYYY-MM' เช่น shiftMonth('2026-01', -1) -> '2025-12' */
export function shiftMonth(monthKey, delta) {
  const [y, m] = monthKey.split('-').map(Number)
  const d = new Date(y, m - 1 + delta, 1)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

export const isWeekend = (date) => [0, 6].includes(toDate(date).getDay())

/** จำนวนวันทำงาน (จันทร์–ศุกร์) ระหว่าง start ถึง end รวมทั้งสองวัน */
export function countWeekdays(start, end) {
  const cursor = toDate(start)
  const last = toDate(end)
  let count = 0
  while (cursor <= last) {
    if (!isWeekend(cursor)) count++
    cursor.setDate(cursor.getDate() + 1)
  }
  return count
}
