export const APP_NAME = 'CheckInTime'
export const APP_VERSION = '1.0.0'
export const HR_CONTACT_PHONE = '02-xxx-xxxx'

export const USE_MOCK = import.meta.env.VITE_USE_MOCK === 'true'

export const STORAGE_KEYS = {
  TOKEN: 'cit_token',
  USER: 'cit_user',
  REMEMBER_USERNAME: 'cit_remember_username',
  NOTIFICATIONS: 'cit_notifications',
  HIDE_AMOUNTS: 'cit_hide_amounts',
  PIN_USER: 'cit_pin_user', // บัญชีที่ตั้ง PIN ไว้บนเครื่องนี้
  SETTINGS: 'cit_settings',
  MOCK_DB: 'cit_mock_db',
}

// เวลาทำงาน
export const WORK_START_TIME = '09:00'
export const WORK_END_TIME = '18:00'
export const LATE_GRACE_MINUTES = 0
// ออกงานหลังเวลาเลิกงานอย่างน้อยกี่นาทีจึงนับเป็น OT
export const OT_MIN_MINUTES = 30

// พื้นที่ที่อนุญาตให้ลงเวลา (geofence)
export const OFFICE_LOCATION = {
  lat: Number(import.meta.env.VITE_OFFICE_LAT) || 13.7563,
  lng: Number(import.meta.env.VITE_OFFICE_LNG) || 100.5018,
  radiusMeters: Number(import.meta.env.VITE_OFFICE_RADIUS) || 200,
}
export const ENFORCE_GEOFENCE = import.meta.env.VITE_ENFORCE_GEOFENCE === 'true'

/** สถานะของการ์ดลงเวลาในหน้าหลัก */
export const CLOCK_STATE = {
  READY: 'ready', // ยังไม่เข้างาน -> กดเข้างาน
  WORKING: 'working', // เข้างานแล้ว -> กดออกงาน
  DONE: 'done', // ลงเวลาครบแล้ววันนี้
  OUT_OF_AREA: 'out_of_area', // อยู่นอกพื้นที่ ลงเวลาไม่ได้
}

export const REQUEST_TYPES = {
  LEAVE: 'leave',
  TIME_FIX: 'time_fix',
  ADVANCE: 'advance',
}

// ---------- การลา ----------

export const LEAVE_TYPES = {
  VACATION: 'vacation',
  PERSONAL: 'personal',
  SICK: 'sick',
}

export const LEAVE_TYPE_META = {
  [LEAVE_TYPES.VACATION]: {
    label: 'ลาพักร้อน',
    icon: 'island',
    bg: 'bg-metric-blue/10',
    color: 'text-metric-blue',
    bar: 'bg-metric-blue',
  },
  [LEAVE_TYPES.PERSONAL]: {
    label: 'ลากิจ',
    icon: 'briefcase',
    bg: 'bg-violet-50',
    color: 'text-violet-500',
    bar: 'bg-violet-500',
  },
  [LEAVE_TYPES.SICK]: {
    label: 'ลาป่วย',
    icon: 'first-aid',
    bg: 'bg-rose-50',
    color: 'text-rose-500',
    bar: 'bg-rose-500',
  },
}

export const LEAVE_STATUS = {
  PENDING: 'pending',
  APPROVED: 'approved',
  REJECTED: 'rejected',
  CANCELLED: 'cancelled',
}

/** tone ใช้กับ StatusBadge */
export const LEAVE_STATUS_META = {
  [LEAVE_STATUS.PENDING]: { label: 'รออนุมัติ', tone: 'warning' },
  [LEAVE_STATUS.APPROVED]: { label: 'อนุมัติแล้ว', tone: 'success' },
  [LEAVE_STATUS.REJECTED]: { label: 'ไม่อนุมัติ', tone: 'danger' },
  [LEAVE_STATUS.CANCELLED]: { label: 'ยกเลิกแล้ว', tone: 'neutral' },
}

export const LEAVE_PERIODS = {
  FULL: 'full',
  MORNING: 'morning',
  AFTERNOON: 'afternoon',
}

export const LEAVE_PERIOD_LABELS = {
  [LEAVE_PERIODS.FULL]: 'เต็มวัน',
  [LEAVE_PERIODS.MORNING]: 'ครึ่งเช้า',
  [LEAVE_PERIODS.AFTERNOON]: 'ครึ่งบ่าย',
}

// ---------- เปิด/ปิดฟีเจอร์ ----------

/** ฟีเจอร์ที่ยังไม่เปิดให้บริการจะแสดงเมนูไว้ แต่มีป้าย "เร็ว ๆ นี้" และกดใช้งานไม่ได้ */
export const FEATURES = {
  ADVANCE_REQUEST: false, // เบิกเงิน: บริษัทยังไม่เปิดให้บริการ
}

// ---------- คำขอลงเวลาย้อนหลัง / แก้ไขเวลา ----------

/** สถานะคำขอใช้ชุดเดียวกับการลา */
export const REQUEST_STATUS = LEAVE_STATUS
export const REQUEST_STATUS_META = LEAVE_STATUS_META

export const TIME_FIX_TYPES = {
  CHECK_IN: 'check_in',
  CHECK_OUT: 'check_out',
  BOTH: 'both',
}

export const TIME_FIX_TYPE_LABELS = {
  [TIME_FIX_TYPES.CHECK_IN]: 'ลืมเข้างาน',
  [TIME_FIX_TYPES.CHECK_OUT]: 'ลืมออกงาน',
  [TIME_FIX_TYPES.BOTH]: 'ลืมทั้งคู่',
}

/** ขอลงเวลาย้อนหลังได้ไม่เกินกี่วัน */
export const TIME_FIX_MAX_DAYS_BACK = 30

export const TIME_FIX_REASONS = ['ลืมกดลงเวลา', 'โทรศัพท์แบตหมด', 'ออกไปพบลูกค้า', 'ระบบขัดข้อง']

// ---------- การแจ้งเตือน ----------

export const NOTIFICATION_TYPES = {
  LEAVE_APPROVED: 'leave_approved',
  LEAVE_REJECTED: 'leave_rejected',
  TIME_FIX_APPROVED: 'time_fix_approved',
  TIME_FIX_REJECTED: 'time_fix_rejected',
  REMINDER: 'reminder',
  PAYSLIP: 'payslip',
  ANNOUNCEMENT: 'announcement',
}

/** ไอคอนและสีของแต่ละประเภท (ไอคอนแสดงในวงกลมสีอ่อน) */
export const NOTIFICATION_META = {
  [NOTIFICATION_TYPES.LEAVE_APPROVED]: { icon: 'calendar-check', bg: 'bg-status-checkin/10', color: 'text-status-checkin' },
  [NOTIFICATION_TYPES.LEAVE_REJECTED]: { icon: 'x-circle', bg: 'bg-status-outside/10', color: 'text-status-outside' },
  [NOTIFICATION_TYPES.TIME_FIX_APPROVED]: { icon: 'clock-edit', bg: 'bg-violet-50', color: 'text-violet-500' },
  [NOTIFICATION_TYPES.TIME_FIX_REJECTED]: { icon: 'x-circle', bg: 'bg-status-outside/10', color: 'text-status-outside' },
  [NOTIFICATION_TYPES.REMINDER]: { icon: 'alarm', bg: 'bg-status-checkout/10', color: 'text-status-checkout' },
  [NOTIFICATION_TYPES.PAYSLIP]: { icon: 'file-text', bg: 'bg-metric-blue/10', color: 'text-metric-blue' },
  [NOTIFICATION_TYPES.ANNOUNCEMENT]: { icon: 'megaphone', bg: 'bg-metric-cyan/10', color: 'text-metric-cyan' },
}

// ---------- สลิปเงินเดือน ----------

/** วันที่จ่ายเงินเดือน (สลิปของเดือนจะออกวันนี้) */
export const PAYDAY = 25

// ---------- PDPA ----------

/** เวอร์ชันนโยบายความเป็นส่วนตัว: เปลี่ยนค่านี้เมื่อแก้นโยบาย ผู้ใช้ทุกคนจะถูกขอความยินยอมใหม่ */
export const PDPA_POLICY_VERSION = '2026-09-01'
export const PDPA_UPDATED_AT = '1 ก.ย. 2569'
