/**
 * Mock API ที่เก็บข้อมูลใน localStorage ใช้ระหว่างที่ยังไม่มี backend
 * รูปแบบข้อมูลที่คืนค่าเหมือนกับที่คาดหวังจาก API จริง
 * ล้างข้อมูลจำลองได้โดยลบ localStorage key 'cit_mock_db'
 */
import {
  LEAVE_PERIODS,
  LEAVE_STATUS,
  LEAVE_TYPES,
  NOTIFICATION_TYPES,
  OT_MIN_MINUTES,
  PAYDAY,
  REQUEST_TYPES,
  STORAGE_KEYS,
  TIME_FIX_MAX_DAYS_BACK,
  TIME_FIX_TYPES,
  WORK_END_TIME,
  WORK_START_TIME,
} from '@/utils/constants'
import { formatDayMonth, toDateKey, toMonthKey } from '@/utils/formatters'
import { countWeekdays, isWeekend, shiftMonth, toDate } from '@/utils/dates'

// เพิ่มเลขนี้เมื่อเปลี่ยนรูปแบบข้อมูล seed เพื่อให้สร้างข้อมูลใหม่อัตโนมัติ
const DB_VERSION = 5

const SEED_USERS = [
  {
    id: 1,
    employeeCode: 'EMP-2569001',
    email: 'somchai@checkin.local',
    password: '123456',
    name: 'นาย สมชาย ใจดี',
    position: 'พนักงานขาย',
    department: 'ฝ่ายขาย',
    phone: '081-234-5678',
    startDate: '2024-03-01',
    avatarUrl: null,
    address: '99/12 ถ.พหลโยธิน แขวงจตุจักร เขตจตุจักร กรุงเทพฯ 10900',
    emergencyContact: { name: 'นาง สมศรี ใจดี', relation: 'มารดา', phone: '089-765-4321' },
  },
]

/** โควตาวันลาต่อปี */
const LEAVE_QUOTAS = {
  [LEAVE_TYPES.VACATION]: 10,
  [LEAVE_TYPES.PERSONAL]: 10,
  [LEAVE_TYPES.SICK]: 30,
}

const delay = (ms = 350) => new Promise((resolve) => setTimeout(resolve, ms))
const clone = (value) => JSON.parse(JSON.stringify(value))
// eslint-disable-next-line no-unused-vars
const publicUser = ({ password, pin, ...user }) => ({ ...user, hasPin: !!pin })

function fail(status, message) {
  const error = new Error(message)
  error.status = status
  throw error
}

/** ตัวสุ่มที่กำหนด seed ได้ (mulberry32) ให้ข้อมูลตัวอย่างเหมือนเดิมทุกครั้ง */
function createRandom(seed) {
  return () => {
    seed = (seed + 0x6d2b79f5) | 0
    let t = Math.imul(seed ^ (seed >>> 15), 1 | seed)
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296
  }
}

/** จำนวนนาทีที่เกินจากเวลา HH:mm ของวันเดียวกัน (ติดลบ = ยังไม่ถึง) */
function minutesAfter(iso, hhmm) {
  const d = new Date(iso)
  const [h, m] = hhmm.split(':').map(Number)
  return d.getHours() * 60 + d.getMinutes() - (h * 60 + m)
}

function withCheckOut(record, checkOut, location = null) {
  const overtime = minutesAfter(checkOut, WORK_END_TIME)
  return {
    ...record,
    checkOut,
    checkOutLocation: location,
    workMinutes: Math.max(0, Math.floor((new Date(checkOut) - new Date(record.checkIn)) / 60000)),
    otMinutes: overtime >= OT_MIN_MINUTES ? overtime : 0,
  }
}

function buildRecord(id, userId, checkIn, location = null) {
  return {
    id,
    userId,
    date: toDateKey(checkIn),
    checkIn,
    checkOut: null,
    checkInLocation: location,
    checkOutLocation: null,
    lateMinutes: Math.max(0, minutesAfter(checkIn, WORK_START_TIME)),
    workMinutes: null,
    otMinutes: 0,
  }
}

function atMinutes(day, totalMinutes, rand) {
  const d = new Date(day)
  d.setHours(Math.floor(totalMinutes / 60), totalMinutes % 60, Math.floor(rand() * 60), 0)
  return d.toISOString()
}

/** วันทำงานถัดไป (ถ้า date เป็นเสาร์/อาทิตย์ จะเลื่อนไปวันจันทร์) */
function nextWeekday(date) {
  const d = new Date(date)
  while (isWeekend(d)) d.setDate(d.getDate() + 1)
  return d
}

function seed() {
  const rand = createRandom(2569)
  const userId = SEED_USERS[0].id
  const office = { lat: 13.7563, lng: 100.5018, accuracy: 15 }
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const daysFrom = (n) => {
    const d = new Date(today)
    d.setDate(d.getDate() + n)
    return nextWeekday(d)
  }

  let nextId = 1
  const leave = (leaveType, start, end, status, reason, extra = {}) => {
    const startDate = toDateKey(start)
    const endDate = toDateKey(end)
    const period = extra.period || LEAVE_PERIODS.FULL
    return {
      id: nextId++,
      userId,
      type: REQUEST_TYPES.LEAVE,
      leaveType,
      startDate,
      endDate,
      period,
      days: period === LEAVE_PERIODS.FULL ? countWeekdays(startDate, endDate) : 0.5,
      reason,
      status,
      reviewNote: extra.reviewNote || null,
      // ยื่นล่วงหน้า 5 วัน แต่ไม่เลยเวลาปัจจุบัน
      createdAt: new Date(Math.min(toDate(startDate).getTime() - 5 * 86400000, Date.now() - 3600000)).toISOString(),
    }
  }

  const vacationStart = daysFrom(-26)
  const vacationEnd = nextWeekday(new Date(vacationStart.getTime() + 86400000))
  const requests = [
    leave(LEAVE_TYPES.PERSONAL, daysFrom(6), daysFrom(6), LEAVE_STATUS.PENDING, 'ติดต่อราชการ ต่อบัตรประชาชน'),
    leave(LEAVE_TYPES.SICK, daysFrom(-9), daysFrom(-9), LEAVE_STATUS.APPROVED, 'มีไข้ ไปพบแพทย์'),
    leave(LEAVE_TYPES.PERSONAL, daysFrom(-15), daysFrom(-15), LEAVE_STATUS.REJECTED, 'ธุระส่วนตัว', {
      reviewNote: 'ช่วงปิดยอดขายสิ้นเดือน รบกวนเลื่อนวันลา',
    }),
    leave(LEAVE_TYPES.VACATION, vacationStart, vacationEnd, LEAVE_STATUS.APPROVED, 'พาครอบครัวไปเที่ยวต่างจังหวัด'),
    leave(LEAVE_TYPES.VACATION, daysFrom(-40), daysFrom(-40), LEAVE_STATUS.APPROVED, 'ธุระครอบครัว', {
      period: LEAVE_PERIODS.AFTERNOON,
    }),
  ]

  // วันที่ลาเต็มวัน (อนุมัติแล้ว) จะไม่มีการลงเวลา
  const leaveDays = new Set()
  for (const r of requests) {
    if (r.status !== LEAVE_STATUS.APPROVED || r.period !== LEAVE_PERIODS.FULL) continue
    for (let d = toDate(r.startDate); d <= toDate(r.endDate); d.setDate(d.getDate() + 1)) {
      leaveDays.add(toDateKey(d))
    }
  }

  // ตัวอย่างวันที่ลืมลงเวลา
  const forgotOutPending = toDateKey(daysFrom(-3)) // ลืมออกงาน + ส่งคำขอแล้ว (รออนุมัติ)
  const missingDay = toDateKey(daysFrom(-6)) // ไม่มีการลงเวลาเลย ยังไม่ได้ส่งคำขอ
  const fixedCheckIn = toDateKey(daysFrom(-12)) // ลืมเข้างาน คำขออนุมัติแล้ว
  const forgotOutRejected = toDateKey(daysFrom(-18)) // ลืมออกงาน คำขอไม่อนุมัติ

  const timeFix = (date, fixType, times, status, reason, extra = {}) => ({
    id: nextId++,
    userId,
    type: REQUEST_TYPES.TIME_FIX,
    date,
    fixType,
    checkIn: times.checkIn || null,
    checkOut: times.checkOut || null,
    reason,
    status,
    reviewNote: extra.reviewNote || null,
    createdAt: new Date(Math.min(toDate(date).getTime() + 33 * 3600000, Date.now() - 3600000)).toISOString(),
  })
  requests.push(
    timeFix(forgotOutPending, TIME_FIX_TYPES.CHECK_OUT, { checkOut: '18:10' }, LEAVE_STATUS.PENDING, 'ลืมกดลงเวลา'),
    timeFix(fixedCheckIn, TIME_FIX_TYPES.CHECK_IN, { checkIn: '08:52' }, LEAVE_STATUS.APPROVED, 'โทรศัพท์แบตหมด'),
    timeFix(forgotOutRejected, TIME_FIX_TYPES.CHECK_OUT, { checkOut: '19:30' }, LEAVE_STATUS.REJECTED, 'ออกไปพบลูกค้า', {
      reviewNote: 'ไม่พบหลักฐานการทำงานหลัง 18:00 กรุณาแนบเอกสารประกอบ',
    }),
  )

  // ประวัติลงเวลาย้อนหลัง ~2 เดือน (ไม่รวมวันนี้)
  const records = []
  for (let i = 62; i >= 1; i--) {
    const day = new Date(today)
    day.setDate(day.getDate() - i)
    const key = toDateKey(day)
    if (isWeekend(day) || leaveDays.has(key) || key === missingDay) continue

    const late = rand() < 0.15
    const inMinutes = late ? 9 * 60 + 1 + Math.floor(rand() * 25) : 8 * 60 + 30 + Math.floor(rand() * 29)
    const overtime = rand() < 0.2
    const outMinutes = overtime ? 18 * 60 + 30 + Math.floor(rand() * 90) : 18 * 60 + Math.floor(rand() * 15)

    const record = buildRecord(nextId++, userId, atMinutes(day, inMinutes, rand), office)
    const forgotOut = key === forgotOutPending || key === forgotOutRejected
    records.push(forgotOut ? record : withCheckOut(record, atMinutes(day, outMinutes, rand), office))
  }

  // การแจ้งเตือนตัวอย่าง (สอดคล้องกับคำขอด้านบน)
  const at = (date, hhmm) => {
    const d = toDate(typeof date === 'string' ? date : toDateKey(date))
    const [h, m] = hhmm.split(':').map(Number)
    d.setHours(h, m, 0, 0)
    return d.toISOString()
  }
  const hoursAgo = (h) => new Date(Date.now() - h * 3600000).toISOString()
  const notify = (type, title, body, createdAt, read, link = null) => ({
    id: nextId++,
    userId,
    type,
    title,
    body,
    createdAt,
    read,
    link,
  })
  const sick = requests.find((r) => r.leaveType === LEAVE_TYPES.SICK)
  const rejectedLeave = requests.find((r) => r.type === REQUEST_TYPES.LEAVE && r.status === LEAVE_STATUS.REJECTED)
  const firstOfMonth = new Date(today.getFullYear(), today.getMonth(), 1)
  const payslipMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1).toLocaleDateString('th-TH', {
    month: 'long',
    year: 'numeric',
  })
  const notifications = [
    notify(NOTIFICATION_TYPES.REMINDER, 'อย่าลืมลงเวลาเข้างาน', 'วันนี้เริ่มงาน 09:00 น. แตะเพื่อไปหน้าลงเวลา', hoursAgo(0.3), false, { name: 'home' }),
    notify(NOTIFICATION_TYPES.ANNOUNCEMENT, 'ประกาศวันหยุดชดเชย', 'บริษัทหยุดชดเชยวันจันทร์ที่ 13 ต.ค. 2569 ขอให้ทุกคนวางแผนงานล่วงหน้า', hoursAgo(3), false),
    notify(NOTIFICATION_TYPES.REMINDER, 'คุณยังไม่ได้ลงเวลาออกงาน', 'ถ้าลืมกดออกงาน ส่งคำขอลงเวลาย้อนหลังได้เลย', at(forgotOutPending, '19:00'), true, { name: 'time-fix' }),
    notify(NOTIFICATION_TYPES.PAYSLIP, `สลิปเงินเดือน ${payslipMonth} ออกแล้ว`, 'ดูรายละเอียดรายได้และรายการหักได้ในเมนูสลิป', at(firstOfMonth, '10:00'), false, { name: 'payslip' }),
    notify(NOTIFICATION_TYPES.LEAVE_APPROVED, 'ใบลาป่วยได้รับการอนุมัติ', `ลาป่วย ${sick.days} วัน (${formatDayMonth(sick.startDate, true)}) หัวหน้าอนุมัติแล้ว`, at(sick.startDate, '11:20'), true, { name: 'leave' }),
    notify(NOTIFICATION_TYPES.TIME_FIX_REJECTED, 'คำขอลงเวลาย้อนหลังไม่ได้รับอนุมัติ', 'ไม่พบหลักฐานการทำงานหลัง 18:00 กรุณาแนบเอกสารประกอบ', at(toDate(forgotOutRejected).getTime() + 86400000, '14:45'), false, { name: 'time-fix' }),
    notify(NOTIFICATION_TYPES.LEAVE_REJECTED, 'ใบลากิจไม่ได้รับการอนุมัติ', rejectedLeave.reviewNote, at(toDate(rejectedLeave.startDate).getTime() - 2 * 86400000, '16:30'), true, { name: 'leave' }),
    notify(NOTIFICATION_TYPES.TIME_FIX_APPROVED, 'คำขอลงเวลาย้อนหลังได้รับอนุมัติ', 'เวลาเข้างาน 08:52 น. ถูกบันทึกในประวัติแล้ว', at(toDate(fixedCheckIn).getTime() + 86400000, '10:15'), true, { name: 'history' }),
  ]

  return { version: DB_VERSION, users: SEED_USERS, records, requests, notifications, nextId }
}

function saveDb(db) {
  try {
    localStorage.setItem(STORAGE_KEYS.MOCK_DB, JSON.stringify(db))
  } catch {
    // localStorage เต็ม (ส่วนใหญ่มาจากไฟล์แนบ) — เฉพาะโหมดทดลอง
    fail(413, 'พื้นที่เก็บข้อมูลทดลองในเบราว์เซอร์เต็ม ลองแนบไฟล์ให้น้อยลง')
  }
}

const MAX_ATTACHMENTS = 3

/** เก็บเฉพาะฟิลด์ที่ต้องใช้ของไฟล์แนบ */
function sanitizeAttachments(attachments = []) {
  if (attachments.length > MAX_ATTACHMENTS) fail(400, `แนบไฟล์ได้ไม่เกิน ${MAX_ATTACHMENTS} ไฟล์`)
  return attachments.map(({ id, name, type, size, dataUrl }) => ({ id, name, type, size, dataUrl }))
}

function loadDb() {
  try {
    const saved = JSON.parse(localStorage.getItem(STORAGE_KEYS.MOCK_DB))
    // ผู้ใช้ = seed (ได้ฟิลด์ใหม่จากโค้ดทันที) + ค่าที่ผู้ใช้แก้ไขไว้ (userOverrides)
    if (saved?.version === DB_VERSION) {
      const overrides = saved.userOverrides || {}
      return { ...saved, userOverrides: overrides, users: SEED_USERS.map((u) => ({ ...u, ...overrides[u.id] })) }
    }
  } catch {
    // ข้อมูลเสีย สร้างใหม่ด้านล่าง
  }
  const db = seed()
  saveDb(db)
  return db
}

function currentUser(db) {
  const token = localStorage.getItem(STORAGE_KEYS.TOKEN) || ''
  const id = Number(token.replace('mock-token-', ''))
  const user = db.users.find((u) => u.id === id)
  if (!user) fail(401, 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่')
  return user
}

function findToday(db, userId) {
  const today = toDateKey()
  return db.records.find((r) => r.userId === userId && r.date === today) || null
}

function leaveRequestsOf(db, userId) {
  return db.requests.filter((r) => r.userId === userId && r.type === REQUEST_TYPES.LEAVE)
}

function timeFixRequestsOf(db, userId) {
  return db.requests.filter((r) => r.userId === userId && r.type === REQUEST_TYPES.TIME_FIX)
}

/** วันที่ลาเต็มวันที่อนุมัติแล้ว (Set ของ 'YYYY-MM-DD') */
function approvedLeaveDays(db, userId) {
  const days = new Set()
  for (const r of leaveRequestsOf(db, userId)) {
    if (r.status !== LEAVE_STATUS.APPROVED || r.period !== LEAVE_PERIODS.FULL) continue
    for (let d = toDate(r.startDate); d <= toDate(r.endDate); d.setDate(d.getDate() + 1)) days.add(toDateKey(d))
  }
  return days
}

/** โควตา/ใช้ไป/รออนุมัติ/คงเหลือ ของแต่ละประเภทการลา ในปีปัจจุบัน */
function leaveBalances(db, userId) {
  const year = String(new Date().getFullYear())
  const requests = leaveRequestsOf(db, userId).filter((r) => r.startDate.startsWith(year))
  const sum = (type, status) =>
    requests.filter((r) => r.leaveType === type && r.status === status).reduce((total, r) => total + r.days, 0)

  return Object.values(LEAVE_TYPES).map((type) => {
    const used = sum(type, LEAVE_STATUS.APPROVED)
    const pending = sum(type, LEAVE_STATUS.PENDING)
    return { type, quota: LEAVE_QUOTAS[type], used, pending, remaining: LEAVE_QUOTAS[type] - used }
  })
}

export const mockAuth = {
  async login({ username, password }) {
    await delay()
    const db = loadDb()
    const key = String(username).trim().toLowerCase()
    const user = db.users.find((u) => u.email.toLowerCase() === key || u.employeeCode.toLowerCase() === key)
    if (!user || user.password !== password) fail(401, 'รหัสพนักงาน/อีเมล หรือรหัสผ่านไม่ถูกต้อง')
    return { token: `mock-token-${user.id}`, user: publicUser(user) }
  },

  async me() {
    await delay(150)
    return publicUser(currentUser(loadDb()))
  },

  async logout() {
    await delay(100)
    return { success: true }
  },

  async updateProfile({ phone, email, address, emergencyContact, avatarUrl }) {
    await delay(500)
    const db = loadDb()
    const user = currentUser(db)
    if (!phone?.trim()) fail(400, 'กรุณากรอกเบอร์โทรศัพท์')
    saveUser(db, user.id, {
      phone: phone.trim(),
      email: email?.trim() || user.email,
      address: address?.trim() || '',
      emergencyContact,
      avatarUrl: avatarUrl ?? user.avatarUrl,
    })
    return publicUser(db.users.find((u) => u.id === user.id))
  },

  async changePassword({ currentPassword, newPassword }) {
    await delay(600)
    const db = loadDb()
    const user = currentUser(db)
    if (user.password !== currentPassword) fail(400, 'รหัสผ่านปัจจุบันไม่ถูกต้อง')
    if (newPassword === currentPassword) fail(400, 'รหัสผ่านใหม่ต้องไม่ซ้ำกับรหัสผ่านเดิม')
    saveUser(db, user.id, { password: newPassword })
    return { success: true }
  },

  /** ลืมรหัสผ่าน ขั้นที่ 1: ส่ง OTP ไปยังเบอร์ที่ลงทะเบียน (โหมดทดลอง OTP = 123456) */
  async requestPasswordReset({ username }) {
    await delay(700)
    const db = loadDb()
    const user = findUserByLogin(db, username)
    if (!user) fail(404, 'ไม่พบรหัสพนักงานหรืออีเมลนี้ในระบบ')
    const phone = user.phone || ''
    return { maskedPhone: `${phone.slice(0, 3)}-xxx-${phone.slice(-4)}`, refCode: 'K7QX', expiresIn: 300 }
  },

  /** ลืมรหัสผ่าน ขั้นที่ 2: ยืนยัน OTP ได้ resetToken */
  async verifyResetOtp({ username, otp }) {
    await delay(500)
    const user = findUserByLogin(loadDb(), username)
    if (!user || otp !== MOCK_OTP) fail(400, 'รหัส OTP ไม่ถูกต้อง')
    return { resetToken: `reset-${user.id}` }
  },

  /** ลืมรหัสผ่าน ขั้นที่ 3: ตั้งรหัสผ่านใหม่ */
  async resetPassword({ resetToken, newPassword }) {
    await delay(600)
    const db = loadDb()
    const id = Number(String(resetToken).replace('reset-', ''))
    if (!db.users.some((u) => u.id === id)) fail(400, 'ลิงก์รีเซ็ตรหัสผ่านหมดอายุ กรุณาเริ่มใหม่')
    saveUser(db, id, { password: newPassword })
    return { success: true }
  },

  /** ตั้ง PIN สำหรับเข้าสู่ระบบบนเครื่องนี้ (backend จริงต้องเก็บแบบ hash) */
  async setPin({ pin }) {
    await delay(400)
    const db = loadDb()
    const user = currentUser(db)
    if (!/^\d{6}$/.test(pin)) fail(400, 'PIN ต้องเป็นตัวเลข 6 หลัก')
    saveUser(db, user.id, { pin })
    return { success: true }
  },

  async removePin() {
    await delay(300)
    const db = loadDb()
    saveUser(db, currentUser(db).id, { pin: null })
    return { success: true }
  },

  /** บันทึกความยินยอมตาม PDPA (เก็บเวอร์ชันนโยบายและเวลาที่ยินยอม) */
  async acceptConsent({ policyVersion, location }) {
    await delay(400)
    const db = loadDb()
    const user = currentUser(db)
    if (!policyVersion) fail(400, 'ไม่พบเวอร์ชันนโยบาย')
    if (!location) fail(400, 'ต้องยินยอมให้ใช้ตำแหน่งขณะลงเวลา จึงจะใช้งานแอปได้')
    saveUser(db, user.id, {
      consent: { policyVersion, location: true, acceptedAt: new Date().toISOString() },
    })
    return publicUser(db.users.find((u) => u.id === user.id))
  },

  async loginWithPin({ employeeCode, pin }) {
    await delay(500)
    const user = findUserByLogin(loadDb(), employeeCode)
    if (!user?.pin) fail(400, 'ยังไม่ได้ตั้งค่า PIN สำหรับบัญชีนี้')
    if (user.pin !== pin) fail(401, 'PIN ไม่ถูกต้อง')
    return { token: `mock-token-${user.id}`, user: publicUser(user) }
  },
}

const MOCK_OTP = '123456'

function findUserByLogin(db, username) {
  const key = String(username || '').trim().toLowerCase()
  return db.users.find((u) => u.email.toLowerCase() === key || u.employeeCode.toLowerCase() === key)
}

/** บันทึกค่าที่ผู้ใช้แก้ไข (ทับค่าจาก seed) */
function saveUser(db, id, changes) {
  db.userOverrides = { ...db.userOverrides, [id]: { ...db.userOverrides?.[id], ...changes } }
  const index = db.users.findIndex((u) => u.id === id)
  db.users[index] = { ...db.users[index], ...changes }
  saveDb(db)
}

export const mockAttendance = {
  async getToday() {
    await delay(200)
    const db = loadDb()
    return clone(findToday(db, currentUser(db).id))
  },

  async checkIn({ location } = {}) {
    await delay()
    const db = loadDb()
    const user = currentUser(db)
    if (findToday(db, user.id)) fail(409, 'วันนี้คุณลงเวลาเข้างานไปแล้ว')

    const record = buildRecord(db.nextId++, user.id, new Date().toISOString(), location || null)
    db.records.push(record)
    saveDb(db)
    return clone(record)
  },

  async checkOut({ location } = {}) {
    await delay()
    const db = loadDb()
    const user = currentUser(db)
    const index = db.records.findIndex((r) => r.userId === user.id && r.date === toDateKey())
    if (index === -1) fail(400, 'กรุณาลงเวลาเข้างานก่อน')
    if (db.records[index].checkOut) fail(409, 'วันนี้คุณลงเวลาออกงานไปแล้ว')

    db.records[index] = withCheckOut(db.records[index], new Date().toISOString(), location || null)
    saveDb(db)
    return clone(db.records[index])
  },

  /** สรุปตัวเลขในหน้าหลัก ของเดือนที่ระบุ ('YYYY-MM') */
  async getSummary({ month = toMonthKey() } = {}) {
    await delay(250)
    const db = loadDb()
    const user = currentUser(db)
    const records = db.records.filter((r) => r.userId === user.id && r.date.startsWith(month))
    const lateRecords = records.filter((r) => r.lateMinutes > 0)
    // วันลาคงเหลือในหน้าหลัก = ลาพักร้อน + ลากิจ (ไม่นับลาป่วย)
    const leaveRemainingDays = leaveBalances(db, user.id)
      .filter((b) => b.type !== LEAVE_TYPES.SICK)
      .reduce((total, b) => total + b.remaining, 0)

    return {
      leaveRemainingDays,
      lateCount: lateRecords.length,
      lateMinutes: lateRecords.reduce((total, r) => total + r.lateMinutes, 0),
      otMinutes: records.reduce((total, r) => total + (r.otMinutes || 0), 0),
    }
  },

  /**
   * ประวัติรายวันของเดือน: รายการลงเวลา + วันทำงานที่ไม่มีการลงเวลา (missing: true)
   * แต่ละวันมี fixStatus = สถานะคำขอแก้ไขเวลาล่าสุดของวันนั้น (ถ้ามี)
   */
  async getHistory({ month = toMonthKey() } = {}) {
    await delay()
    const db = loadDb()
    const user = currentUser(db)
    const records = db.records.filter((r) => r.userId === user.id)
    const monthRecords = records.filter((r) => r.date.startsWith(month))

    // วันทำงานที่ผ่านมาแล้วแต่ไม่มีการลงเวลา (ไม่นับวันลาเต็มวันที่อนุมัติ และก่อนเริ่มมีข้อมูล)
    const recordDates = new Set(monthRecords.map((r) => r.date))
    const leaveDays = approvedLeaveDays(db, user.id)
    const firstTracked = records.reduce((min, r) => (r.date < min ? r.date : min), toDateKey())
    const [y, m] = month.split('-').map(Number)
    const missing = []
    for (let d = new Date(y, m - 1, 1); d.getMonth() === m - 1; d.setDate(d.getDate() + 1)) {
      const key = toDateKey(d)
      if (key >= toDateKey() || key < firstTracked) continue
      if (isWeekend(d) || recordDates.has(key) || leaveDays.has(key)) continue
      missing.push({ id: `missing-${key}`, date: key, missing: true, checkIn: null, checkOut: null, lateMinutes: 0, otMinutes: 0, workMinutes: null })
    }

    const fixStatusOf = (date) =>
      timeFixRequestsOf(db, user.id)
        .filter((r) => r.date === date)
        .sort((a, b) => b.createdAt.localeCompare(a.createdAt))[0]?.status ?? null

    return clone(
      [...monthRecords, ...missing]
        .map((r) => ({ ...r, fixStatus: fixStatusOf(r.date) }))
        .sort((a, b) => b.date.localeCompare(a.date)),
    )
  },
}

export const mockTimeFix = {
  async getRequests() {
    await delay()
    const db = loadDb()
    return clone(timeFixRequestsOf(db, currentUser(db).id).sort((a, b) => b.date.localeCompare(a.date)))
  },

  async createRequest({ date, fixType, checkIn, checkOut, reason, attachments }) {
    await delay(500)
    const db = loadDb()
    const user = currentUser(db)
    const today = toDateKey()
    const earliest = new Date()
    earliest.setDate(earliest.getDate() - TIME_FIX_MAX_DAYS_BACK)

    if (!date || date > today) fail(400, 'วันที่ไม่ถูกต้อง')
    if (date < toDateKey(earliest)) fail(400, `ขอลงเวลาย้อนหลังได้ไม่เกิน ${TIME_FIX_MAX_DAYS_BACK} วัน`)
    if (!Object.values(TIME_FIX_TYPES).includes(fixType)) fail(400, 'กรุณาเลือกประเภทคำขอ')
    const needIn = fixType !== TIME_FIX_TYPES.CHECK_OUT
    const needOut = fixType !== TIME_FIX_TYPES.CHECK_IN
    if ((needIn && !checkIn) || (needOut && !checkOut)) fail(400, 'กรุณาระบุเวลา')
    if (needIn && needOut && checkOut <= checkIn) fail(400, 'เวลาออกงานต้องหลังเวลาเข้างาน')
    if (!reason?.trim()) fail(400, 'กรุณาระบุเหตุผล')
    if (timeFixRequestsOf(db, user.id).some((r) => r.date === date && r.status === LEAVE_STATUS.PENDING)) {
      fail(409, 'วันนี้มีคำขอแก้ไขเวลารออนุมัติอยู่แล้ว')
    }

    const request = {
      id: db.nextId++,
      userId: user.id,
      type: REQUEST_TYPES.TIME_FIX,
      date,
      fixType,
      checkIn: needIn ? checkIn : null,
      checkOut: needOut ? checkOut : null,
      reason: reason.trim(),
      attachments: sanitizeAttachments(attachments),
      status: LEAVE_STATUS.PENDING,
      reviewNote: null,
      createdAt: new Date().toISOString(),
    }
    db.requests.push(request)
    saveDb(db)
    return clone(request)
  },

  async cancelRequest(id) {
    await delay()
    const db = loadDb()
    const user = currentUser(db)
    const request = db.requests.find((r) => r.id === id && r.userId === user.id && r.type === REQUEST_TYPES.TIME_FIX)
    if (!request) fail(404, 'ไม่พบคำขอ')
    if (request.status !== LEAVE_STATUS.PENDING) fail(409, 'ยกเลิกได้เฉพาะคำขอที่รออนุมัติ')

    request.status = LEAVE_STATUS.CANCELLED
    saveDb(db)
    return clone(request)
  },
}

export const mockLeave = {
  async getBalances() {
    await delay(250)
    const db = loadDb()
    return leaveBalances(db, currentUser(db).id)
  },

  async getRequests() {
    await delay()
    const db = loadDb()
    return clone(leaveRequestsOf(db, currentUser(db).id).sort((a, b) => b.startDate.localeCompare(a.startDate)))
  },

  async createRequest({ leaveType, startDate, endDate, period = LEAVE_PERIODS.FULL, reason, attachments }) {
    await delay(500)
    const db = loadDb()
    const user = currentUser(db)

    if (!LEAVE_QUOTAS[leaveType]) fail(400, 'กรุณาเลือกประเภทการลา')
    if (!startDate || !endDate || endDate < startDate) fail(400, 'ช่วงวันที่ไม่ถูกต้อง')
    if (!reason?.trim()) fail(400, 'กรุณาระบุเหตุผลการลา')

    const days = period === LEAVE_PERIODS.FULL ? countWeekdays(startDate, endDate) : 0.5
    if (days <= 0) fail(400, 'ช่วงวันที่เลือกเป็นวันหยุดทั้งหมด')

    const balance = leaveBalances(db, user.id).find((b) => b.type === leaveType)
    if (balance.remaining - balance.pending < days) fail(400, 'วันลาคงเหลือไม่เพียงพอ')

    const request = {
      id: db.nextId++,
      userId: user.id,
      type: REQUEST_TYPES.LEAVE,
      leaveType,
      startDate,
      endDate,
      period,
      days,
      reason: reason.trim(),
      attachments: sanitizeAttachments(attachments),
      status: LEAVE_STATUS.PENDING,
      reviewNote: null,
      createdAt: new Date().toISOString(),
    }
    db.requests.push(request)
    saveDb(db)
    return clone(request)
  },

  async cancelRequest(id) {
    await delay()
    const db = loadDb()
    const user = currentUser(db)
    const request = db.requests.find((r) => r.id === id && r.userId === user.id)
    if (!request) fail(404, 'ไม่พบคำขอลา')
    if (request.status !== LEAVE_STATUS.PENDING) fail(409, 'ยกเลิกได้เฉพาะคำขอที่รออนุมัติ')

    request.status = LEAVE_STATUS.CANCELLED
    saveDb(db)
    return clone(request)
  },
}

export const mockRequest = {
  /** จำนวนคำขอที่รออนุมัติ แยกตามประเภท */
  async getStatusSummary() {
    await delay(250)
    const db = loadDb()
    const user = currentUser(db)
    const pendingOf = (type) =>
      db.requests.filter((r) => r.userId === user.id && r.type === type && r.status === LEAVE_STATUS.PENDING).length

    return Object.values(REQUEST_TYPES).map((type) => ({ type, pendingCount: pendingOf(type) }))
  },
}

export const mockNotification = {
  async getAll() {
    await delay(300)
    const db = loadDb()
    const user = currentUser(db)
    return clone((db.notifications || []).filter((n) => n.userId === user.id).sort((a, b) => b.createdAt.localeCompare(a.createdAt)))
  },

  async markRead(id) {
    await delay(100)
    const db = loadDb()
    const user = currentUser(db)
    const item = (db.notifications || []).find((n) => n.id === id && n.userId === user.id)
    if (!item) fail(404, 'ไม่พบการแจ้งเตือน')
    item.read = true
    saveDb(db)
    return { success: true }
  },

  async markAllRead() {
    await delay(200)
    const db = loadDb()
    const user = currentUser(db)
    for (const n of db.notifications || []) if (n.userId === user.id) n.read = true
    saveDb(db)
    return { success: true }
  },
}

// ---------- สลิปเงินเดือน ----------

const PAYROLL = {
  baseSalary: 18000,
  travelAllowance: 1000,
  diligenceBonus: 500, // เบี้ยขยัน: ไม่สาย ไม่ขาดทั้งเดือน
  otMultiplier: 1.5,
  socialSecurityRate: 0.05,
  socialSecurityCap: 750,
  withholdingTax: 320,
  bank: { name: 'ธนาคารกสิกรไทย', account: 'xxx-x-x4821-x' },
}

const dailyRate = () => PAYROLL.baseSalary / 30
const minuteRate = () => dailyRate() / 8 / 60
const round2 = (n) => Math.round(n * 100) / 100

/** สลิปออกแล้วหรือยัง: เดือนก่อนหน้า หรือเดือนนี้ตั้งแต่วันจ่าย */
function isReleased(month) {
  const current = toMonthKey()
  return month < current || (month === current && new Date().getDate() >= PAYDAY)
}

/** สถิติการทำงานของเดือน (ใช้ข้อมูลจริง ถ้าเดือนนั้นอยู่นอกช่วงข้อมูลตัวอย่าง จะสุ่มแบบคงที่) */
function monthAttendance(db, userId, month) {
  const records = db.records.filter((r) => r.userId === userId)
  const firstTracked = records.reduce((min, r) => (r.date < min ? r.date : min), toDateKey())
  const [y, m] = month.split('-').map(Number)
  const lastDay = toDateKey(new Date(y, m, 0))

  if (lastDay < firstTracked) {
    const rand = createRandom(y * 100 + m)
    const weekdays = countWeekdays(`${month}-01`, lastDay)
    const leaveDays = rand() < 0.4 ? 1 : 0
    const lateCount = Math.floor(rand() * 3)
    return {
      workDays: weekdays - leaveDays,
      leaveDays,
      absentDays: 0,
      lateCount,
      lateMinutes: lateCount * (5 + Math.floor(rand() * 15)),
      otMinutes: Math.floor(rand() * 8) * 30,
    }
  }

  const monthRecords = records.filter((r) => r.date.startsWith(month))
  const leaveDays = approvedLeaveDays(db, userId)
  const approvedFixDates = new Set(
    timeFixRequestsOf(db, userId)
      .filter((r) => r.status === LEAVE_STATUS.APPROVED)
      .map((r) => r.date),
  )
  const recordDates = new Set(monthRecords.map((r) => r.date))
  let absentDays = 0
  let monthLeaveDays = 0
  for (let d = new Date(y, m - 1, 1); d.getMonth() === m - 1; d.setDate(d.getDate() + 1)) {
    const key = toDateKey(d)
    if (isWeekend(d) || key >= toDateKey() || key < firstTracked) continue
    if (leaveDays.has(key)) monthLeaveDays++
    else if (!recordDates.has(key) && !approvedFixDates.has(key)) absentDays++
  }
  const late = monthRecords.filter((r) => r.lateMinutes > 0)
  return {
    workDays: monthRecords.length,
    leaveDays: monthLeaveDays,
    absentDays,
    lateCount: late.length,
    lateMinutes: late.reduce((sum, r) => sum + r.lateMinutes, 0),
    otMinutes: monthRecords.reduce((sum, r) => sum + (r.otMinutes || 0), 0),
  }
}

function buildPayslip(db, user, month) {
  const att = monthAttendance(db, user.id, month)
  const otHours = att.otMinutes / 60
  const otPay = round2(otHours * (dailyRate() / 8) * PAYROLL.otMultiplier)
  const diligent = att.lateCount === 0 && att.absentDays === 0

  const earnings = [
    { label: 'เงินเดือน', amount: PAYROLL.baseSalary },
    otPay > 0 && { label: 'ค่าล่วงเวลา (OT)', amount: otPay, note: `${round2(otHours)} ชม. × ${PAYROLL.otMultiplier} เท่า` },
    { label: 'ค่าเดินทาง', amount: PAYROLL.travelAllowance },
    diligent && { label: 'เบี้ยขยัน', amount: PAYROLL.diligenceBonus, note: 'ไม่สาย ไม่ขาดงานทั้งเดือน' },
  ].filter(Boolean)

  const grossForSso = Math.min(PAYROLL.baseSalary, 15000)
  const deductions = [
    {
      label: 'ประกันสังคม',
      amount: Math.min(PAYROLL.socialSecurityCap, round2(grossForSso * PAYROLL.socialSecurityRate)),
      note: `${PAYROLL.socialSecurityRate * 100}% (สูงสุด ${PAYROLL.socialSecurityCap} บาท)`,
    },
    { label: 'ภาษีหัก ณ ที่จ่าย', amount: PAYROLL.withholdingTax },
    att.lateMinutes > 0 && {
      label: 'หักมาสาย',
      amount: round2(att.lateMinutes * minuteRate()),
      note: `${att.lateCount} ครั้ง รวม ${att.lateMinutes} นาที`,
    },
    att.absentDays > 0 && {
      label: 'หักขาดงาน',
      amount: round2(att.absentDays * dailyRate()),
      note: `${att.absentDays} วัน`,
    },
  ].filter(Boolean)

  const totalEarnings = round2(earnings.reduce((sum, e) => sum + e.amount, 0))
  const totalDeductions = round2(deductions.reduce((sum, d) => sum + d.amount, 0))
  const [y, m] = month.split('-').map(Number)

  return {
    month,
    payDate: toDateKey(new Date(y, m - 1, PAYDAY)),
    employee: {
      name: user.name,
      employeeCode: user.employeeCode,
      position: user.position,
      department: user.department,
    },
    bank: PAYROLL.bank,
    earnings,
    deductions,
    totalEarnings,
    totalDeductions,
    netPay: round2(totalEarnings - totalDeductions),
    attendance: att,
  }
}

export const mockPayslip = {
  /** รายการเดือนที่มีสลิป (ล่าสุดก่อน) สูงสุด 12 เดือน */
  async getList() {
    await delay(250)
    const db = loadDb()
    const user = currentUser(db)
    const months = []
    let month = toMonthKey()
    while (months.length < 12) {
      if (isReleased(month) && month >= user.startDate.slice(0, 7)) {
        months.push({ month, netPay: buildPayslip(db, user, month).netPay })
      }
      month = shiftMonth(month, -1)
      if (month < user.startDate.slice(0, 7)) break
    }
    return months
  },

  async getDetail(month) {
    await delay(400)
    const db = loadDb()
    const user = currentUser(db)
    if (!isReleased(month)) fail(404, `สลิปเดือนนี้จะออกวันที่ ${PAYDAY}`)
    return buildPayslip(db, user, month)
  },
}
