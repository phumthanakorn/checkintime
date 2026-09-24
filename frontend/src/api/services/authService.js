import axiosClient from '@/api/axiosClient'
import { mockAuth } from '@/api/mock/mockServer'
import { USE_MOCK } from '@/utils/constants'

const httpAuth = {
  /** @returns {Promise<{ token: string, user: object }>} */
  login: (payload) => axiosClient.post('/auth/login', payload),
  me: () => axiosClient.get('/auth/me'),
  logout: () => axiosClient.post('/auth/logout'),

  /** แก้ไขข้อมูลส่วนตัว { phone, email, address, emergencyContact, avatarUrl } -> user */
  updateProfile: (payload) => axiosClient.put('/me/profile', payload),
  changePassword: (payload) => axiosClient.post('/me/password', payload),

  /** ลืมรหัสผ่าน: ส่ง OTP -> ยืนยัน OTP -> ตั้งรหัสใหม่ */
  requestPasswordReset: (payload) => axiosClient.post('/auth/password/forgot', payload),
  verifyResetOtp: (payload) => axiosClient.post('/auth/password/verify-otp', payload),
  resetPassword: (payload) => axiosClient.post('/auth/password/reset', payload),

  /** PIN 6 หลักสำหรับเข้าสู่ระบบบนเครื่องนี้ */
  setPin: (payload) => axiosClient.post('/me/pin', payload),
  removePin: () => axiosClient.delete('/me/pin'),
  loginWithPin: (payload) => axiosClient.post('/auth/login/pin', payload),
}

export const authService = USE_MOCK ? mockAuth : httpAuth
