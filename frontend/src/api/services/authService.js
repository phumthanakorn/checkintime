import axiosClient from '@/api/axiosClient'

const httpAuth = {
  /** @returns {Promise<{ token: string, user: object } | { needsSetup: true, empcode: string }>} */
  login: (payload) => axiosClient.post('/auth/login', payload),
  /** เข้าสู่ระบบครั้งแรก/HR รีเซ็ตให้ -> ตั้งรหัสผ่านใหม่ { empcode, password: <temp = empcode>, newPassword }
   * @returns {Promise<{ token: string, user: object }>} */
  setupPassword: (payload) => axiosClient.post('/auth/setup-password', payload),
  /** เข้าสู่ระบบด้วย Google — payload = { credential } (ID token จาก Google Identity Services)
   * @returns {Promise<{ token: string, user: object } | { needsSetup: true, empcode: string }>} */
  googleLogin: (payload) => axiosClient.post('/auth/google', payload),
  me: () => axiosClient.get('/auth/me'),
  logout: () => axiosClient.post('/auth/logout'),

  /** ลืมรหัสผ่าน: ส่งลิงก์รีเซ็ตไปที่อีเมล -> ตั้งรหัสใหม่ด้วย token จากลิงก์นั้น */
  requestPasswordReset: (payload) => axiosClient.post('/auth/password/forgot', payload),
  /** @returns {Promise<{ token: string, user: object }>} */
  resetPassword: (payload) => axiosClient.post('/auth/password/reset', payload),

  /** แก้ไขข้อมูลส่วนตัว { phone, email, address, emergencyContact, avatarUrl } -> user */
  updateProfile: (payload) => axiosClient.put('/me/profile', payload),
  changePassword: (payload) => axiosClient.post('/me/password', payload),
  verifyCurrentPassword: (payload) => axiosClient.post('/me/password/verify', payload),

  /** ความยินยอมตาม PDPA { policyVersion, location: true } -> user (มี consent) */
  acceptConsent: (payload) => axiosClient.post('/me/consents', payload),
}

export const authService = httpAuth
