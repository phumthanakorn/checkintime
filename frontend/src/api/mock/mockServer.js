/**
 * Mock API ที่เก็บข้อมูลใน localStorage ใช้ระหว่างที่ยังไม่มี backend
 * รูปแบบข้อมูลที่คืนค่าเหมือนกับที่คาดหวังจาก API จริง
 * ล้างข้อมูลจำลองได้โดยลบ localStorage key 'cit_mock_db'
 */
import {
  LEAVE_PERIODS,
  LEAVE_STATUS,
  LEAVE_TYPES,
  OT_MIN_MINUTES,
  REQUEST_TYPES,
  STORAGE_KEYS,
  WORK_END_TIME,
  WORK_START_TIME,
} from '@/utils/constants'
import { toDateKey, toMonthKey } from '@/utils/formatters'
import { countWeekdays, isWeekend, toDate } from '@/utils/dates'

// เพิ่มเลขนี้เมื่อเปลี่ยนรูปแบบข้อมูล seed เพื่อให้สร้างข้อมูลใหม่อัตโนมัติ
const DB_VERSION = 3

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
const publicUser = ({ password, ...user }) => user

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

  // ประวัติลงเวลาย้อนหลัง ~2 เดือน (ไม่รวมวันนี้)
  const records = []
  for (let i = 62; i >= 1; i--) {
    const day = new Date(today)
    day.setDate(day.getDate() - i)
    if (isWeekend(day) || leaveDays.has(toDateKey(day))) continue

    const late = rand() < 0.15
    const inMinutes = late ? 9 * 60 + 1 + Math.floor(rand() * 25) : 8 * 60 + 30 + Math.floor(rand() * 29)
    const overtime = rand() < 0.2
    const outMinutes = overtime ? 18 * 60 + 30 + Math.floor(rand() * 90) : 18 * 60 + Math.floor(rand() * 15)

    const record = buildRecord(nextId++, userId, atMinutes(day, inMinutes, rand), office)
    records.push(withCheckOut(record, atMinutes(day, outMinutes, rand), office))
  }

  return { version: DB_VERSION, users: SEED_USERS, records, requests, nextId }
}

function saveDb(db) {
  localStorage.setItem(STORAGE_KEYS.MOCK_DB, JSON.stringify(db))
}

function loadDb() {
  try {
    const saved = JSON.parse(localStorage.getItem(STORAGE_KEYS.MOCK_DB))
    // ข้อมูลผู้ใช้ใช้จาก seed เสมอ เพื่อให้ฟิลด์ใหม่ที่เพิ่มในโค้ดมีผลทันที
    if (saved?.version === DB_VERSION) return { ...saved, users: SEED_USERS }
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

  async getHistory({ month } = {}) {
    await delay()
    const db = loadDb()
    const user = currentUser(db)
    return clone(
      db.records
        .filter((r) => r.userId === user.id && (!month || r.date.startsWith(month)))
        .sort((a, b) => b.date.localeCompare(a.date)),
    )
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

  async createRequest({ leaveType, startDate, endDate, period = LEAVE_PERIODS.FULL, reason }) {
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
