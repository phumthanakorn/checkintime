import { toDate } from './dates'

const LOCALE = 'th-TH'

const pad = (n) => String(n).padStart(2, '0')

/** แปลง Date เป็น 'YYYY-MM-DD' ตามเวลาท้องถิ่น */
export function toDateKey(date = new Date()) {
  const d = new Date(date)
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

/** แปลง Date เป็น 'YYYY-MM' ตามเวลาท้องถิ่น */
export function toMonthKey(date = new Date()) {
  const d = new Date(date)
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}`
}

/** เวลาแบบ HH:mm หรือ '--:--' ถ้าไม่มีค่า */
export function formatClock(date) {
  if (!date) return '--:--'
  const d = new Date(date)
  return `${pad(d.getHours())}:${pad(d.getMinutes())}`
}

/** 'ศุกร์ 17 ก.ค. 2569' */
export function formatThaiDate(date = new Date()) {
  const d = toDate(date)
  const weekday = d.toLocaleDateString(LOCALE, { weekday: 'long' }).replace(/^วัน/, '')
  const month = d.toLocaleDateString(LOCALE, { month: 'short' })
  return `${weekday} ${d.getDate()} ${month} ${d.getFullYear() + 543}`
}

export function diffMinutes(start, end = new Date()) {
  if (!start) return null
  return Math.max(0, Math.floor((new Date(end) - new Date(start)) / 60000))
}

/** 125 -> '2 ชม. 5 นาที' */
export function formatDuration(minutes) {
  if (minutes == null) return '-'
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  if (h === 0) return `${m} นาที`
  return `${h} ชม. ${m} นาที`
}

export function getInitials(name = '') {
  const parts = name
    .replace(/^(นางสาว|นาง|นาย|Mr\.?|Mrs\.?|Ms\.?)\s*/i, '')
    .trim()
    .split(/\s+/)
  // ชื่อไทยใช้อักษรแรกของชื่อตัวเดียว (อักษรแรกของนามสกุลมักเป็นสระ เช่น 'ใ' ดูแปลก)
  if (/[฀-๿]/.test(parts[0] || '')) return parts[0].charAt(0)
  return parts
    .slice(0, 2)
    .map((part) => part.charAt(0))
    .join('')
    .toUpperCase()
}

/** อายุงานนับจากวันเริ่มงาน เช่น '2 ปี 6 เดือน' */
export function formatTenure(startDate, now = new Date()) {
  if (!startDate) return '-'
  const start = new Date(startDate)
  let months = (now.getFullYear() - start.getFullYear()) * 12 + (now.getMonth() - start.getMonth())
  if (now.getDate() < start.getDate()) months -= 1
  if (months < 1) return 'น้อยกว่า 1 เดือน'
  const years = Math.floor(months / 12)
  const rest = months % 12
  return [years && `${years} ปี`, rest && `${rest} เดือน`].filter(Boolean).join(' ')
}

/** 'YYYY-MM' -> 'กันยายน 2569' */
export function formatMonth(monthKey) {
  const [y, m] = monthKey.split('-').map(Number)
  return new Date(y, m - 1, 1).toLocaleDateString(LOCALE, { month: 'long', year: 'numeric' })
}

const WEEKDAY_SHORT = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.']

/** 'จ.' */
export function formatWeekdayShort(date) {
  return WEEKDAY_SHORT[toDate(date).getDay()]
}

/** '15 ก.ย.' หรือ '15 ก.ย. 2569' เมื่อ withYear = true */
export function formatDayMonth(date, withYear = false) {
  const d = toDate(date)
  const month = d.toLocaleDateString(LOCALE, { month: 'short' })
  return withYear ? `${d.getDate()} ${month} ${d.getFullYear() + 543}` : `${d.getDate()} ${month}`
}

/** ช่วงวันที่แบบย่อ: '15 ก.ย. 2569', '15–16 ก.ย. 2569', '30 ก.ย. – 2 ต.ค. 2569' */
export function formatDateRange(start, end) {
  const s = toDate(start)
  const e = toDate(end || start)
  if (toDateKey(s) === toDateKey(e)) return formatDayMonth(s, true)
  if (s.getFullYear() === e.getFullYear() && s.getMonth() === e.getMonth()) {
    return `${s.getDate()}–${formatDayMonth(e, true)}`
  }
  return `${formatDayMonth(s)} – ${formatDayMonth(e, true)}`
}

/** จำนวนวันลา: 1 -> '1 วัน', 0.5 -> 'ครึ่งวัน', 1.5 -> '1.5 วัน' */
export function formatLeaveDays(days) {
  if (days === 0.5) return 'ครึ่งวัน'
  return `${days} วัน`
}
