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

  async function login(credentials) {
    const { token: newToken, user: newUser } = await authService.login(credentials)
    setSession(newToken, newUser)
    return newUser
  }

  /** โหลดข้อมูลผู้ใช้ล่าสุดจาก API */
  async function refreshUser() {
    const freshUser = await authService.me()
    setSession(token.value, freshUser)
    return freshUser
  }

  async function loginWithPin(credentials) {
    const { token: newToken, user: newUser } = await authService.loginWithPin(credentials)
    setSession(newToken, newUser)
    return newUser
  }

  async function updateProfile(payload) {
    const updated = await authService.updateProfile(payload)
    setSession(token.value, updated)
    rememberPinUser()
    return updated
  }

  /** ตั้ง PIN และจำบัญชีไว้บนเครื่องนี้ (หน้า login จะมีปุ่มเข้าด้วย PIN) */
  async function setPin(pin) {
    await authService.setPin({ pin })
    setSession(token.value, { ...user.value, hasPin: true })
    rememberPinUser()
  }

  async function removePin() {
    await authService.removePin()
    setSession(token.value, { ...user.value, hasPin: false })
    localStorage.removeItem(STORAGE_KEYS.PIN_USER)
  }

  function rememberPinUser() {
    if (!user.value?.hasPin) return
    const { employeeCode, name, avatarUrl } = user.value
    localStorage.setItem(STORAGE_KEYS.PIN_USER, JSON.stringify({ employeeCode, name, avatarUrl }))
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
    loginWithPin,
    refreshUser,
    updateProfile,
    setPin,
    removePin,
    logout,
    clearSession,
  }
})
