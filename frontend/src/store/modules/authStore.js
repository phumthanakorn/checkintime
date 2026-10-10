import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { authService } from '@/api/services/authService'
import { STORAGE_KEYS } from '@/utils/constants'

function readStoredUser() {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEYS.USER))
  } catch {
    return null
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem(STORAGE_KEYS.TOKEN))
  const user = ref(readStoredUser())

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const role = computed(() => user.value?.role ?? null)

  function setSession(newToken, newUser) {
    token.value = newToken
    user.value = newUser
    localStorage.setItem(STORAGE_KEYS.TOKEN, newToken)
    localStorage.setItem(STORAGE_KEYS.USER, JSON.stringify(newUser))
  }

  function clearSession() {
    token.value = null
    user.value = null
    localStorage.removeItem(STORAGE_KEYS.TOKEN)
    localStorage.removeItem(STORAGE_KEYS.USER)
  }

  // login ครั้งแรก (ยังไม่เคยตั้งรหัสผ่าน หรือ HR รีเซ็ตให้) backend จะไม่ออก token/user ทันที แต่คืน
  // {needsSetup:true, empcode} แทน ให้ผู้เรียก (useAuth/LoginView) พาไปหน้าตั้งรหัสผ่านใหม่ต่อแทนที่จะ login
  // สำเร็จเลย — ไม่เรียก setSession() ในเคสนี้
  async function login(credentials) {
    const res = await authService.login(credentials)
    if (res?.needsSetup) return res
    setSession(res.token, res.user)
    return res.user
  }

  // ตั้งรหัสผ่านใหม่ (ครั้งแรก/HR รีเซ็ตให้) สำเร็จแล้ว backend ออก token/user ให้เข้าสู่ระบบอัตโนมัติเหมือน
  // login ปกติ — ไม่ต้องให้กรอก login ซ้ำอีกรอบ
  async function setupPassword(payload) {
    const { token: newToken, user: newUser } = await authService.setupPassword(payload)
    setSession(newToken, newUser)
    return newUser
  }

  // เข้าสู่ระบบด้วย Google — เหมือน login() ปกติ: backend อาจคืน {needsSetup:true} ถ้ายังไม่เคยตั้งรหัสผ่าน
  // (Google login ไม่ใช่ทางลัดข้ามการตั้งรหัสผ่านครั้งแรก ดู AuthController::googleLogin())
  async function googleLogin(credential) {
    const res = await authService.googleLogin({ credential })
    if (res?.needsSetup) return res
    setSession(res.token, res.user)
    return res.user
  }

  // ตั้งรหัสผ่านใหม่จากลิงก์อีเมล (token) สำเร็จแล้ว backend ออก token/user ให้เข้าสู่ระบบอัตโนมัติเหมือนกัน
  async function resetPassword(payload) {
    const { token: newToken, user: newUser } = await authService.resetPassword(payload)
    setSession(newToken, newUser)
    return newUser
  }

  /** โหลดข้อมูลผู้ใช้ล่าสุดจาก API */
  async function refreshUser() {
    const freshUser = await authService.me()
    setSession(token.value, freshUser)
    return freshUser
  }

  async function updateProfile(payload) {
    const updated = await authService.updateProfile(payload)
    setSession(token.value, updated)
    return updated
  }

  async function acceptConsent(payload) {
    const updated = await authService.acceptConsent(payload)
    setSession(token.value, updated)
    return updated
  }

  async function logout() {
    try {
      await authService.logout()
    } catch {
      // ออกจากระบบฝั่ง client ต่อได้แม้เรียก API ไม่สำเร็จ
    } finally {
      clearSession()
    }
  }

  return {
    token,
    user,
    isAuthenticated,
    role,
    login,
    googleLogin,
    setupPassword,
    resetPassword,
    refreshUser,
    updateProfile,
    acceptConsent,
    logout,
    clearSession,
  }
})
