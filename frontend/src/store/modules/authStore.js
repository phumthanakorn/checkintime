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

  async function logout() {
    try {
      await authService.logout()
    } catch {
      // ออกจากระบบฝั่ง client ต่อได้แม้เรียก API ไม่สำเร็จ
    } finally {
      clearSession()
    }
  }

  return { token, user, isAuthenticated, role, login, refreshUser, logout, clearSession }
})
