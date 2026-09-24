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
