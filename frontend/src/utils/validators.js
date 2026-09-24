// rule สำหรับ Vuetify: คืน true เมื่อผ่าน หรือข้อความ error เมื่อไม่ผ่าน

export const required =
  (message = 'กรุณากรอกข้อมูล') =>
  (value) =>
    (value !== null && value !== undefined && String(value).trim() !== '') || message

export const email =
  (message = 'รูปแบบอีเมลไม่ถูกต้อง') =>
  (value) =>
    !value || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || message

export const minLength = (length, message) => (value) =>
  !value || String(value).length >= length || message || `ต้องมีอย่างน้อย ${length} ตัวอักษร`

/** คะแนนความแข็งแรงรหัสผ่าน 0–4 */
export function passwordStrength(value = '') {
  let score = 0
  if (value.length >= 8) score++
  if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++
  if (/\d/.test(value)) score++
  if (/[^A-Za-z0-9]/.test(value) || value.length >= 12) score++
  return value.length < 6 ? Math.min(score, 1) : score
}

/** รหัสผ่านใหม่: อย่างน้อย 8 ตัว มีทั้งตัวอักษรและตัวเลข */
export function validateNewPassword(value = '') {
  if (value.length < 8) return 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร'
  if (!/[A-Za-z]/.test(value) || !/\d/.test(value)) return 'รหัสผ่านต้องมีทั้งตัวอักษรและตัวเลข'
  return ''
}

export const isPhone = (value = '') => /^0\d{1,2}-?\d{3}-?\d{4}$/.test(value.trim())
export const isEmail = (value = '') => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim())
