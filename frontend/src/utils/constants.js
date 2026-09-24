export const APP_NAME = 'CheckInTime'
export const APP_VERSION = '1.0.0'
export const HR_CONTACT_PHONE = '02-xxx-xxxx'

export const USE_MOCK = import.meta.env.VITE_USE_MOCK === 'true'

export const STORAGE_KEYS = {
  TOKEN: 'cit_token',
  USER: 'cit_user',
  REMEMBER_USERNAME: 'cit_remember_username',
  NOTIFICATIONS: 'cit_notifications',
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
    icon: 'mdi-beach',
    bg: 'bg-metric-blue/10',
    color: 'text-metric-blue',
    bar: 'bg-metric-blue',
  },
  [LEAVE_TYPES.PERSONAL]: {
    label: 'ลากิจ',
    icon: 'mdi-briefcase-outline',
    bg: 'bg-violet-50',
    color: 'text-violet-500',
    bar: 'bg-violet-500',
  },
  [LEAVE_TYPES.SICK]: {
    label: 'ลาป่วย',
    icon: 'mdi-medical-bag',
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
