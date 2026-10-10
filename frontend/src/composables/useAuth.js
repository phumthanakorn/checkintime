import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAttendanceStore, useAuthStore, useLeaveStore, useNotificationStore, useTimeFixStore } from '@/store'

export function useAuth() {
  const auth = useAuthStore()
  const attendance = useAttendanceStore()
  const leave = useLeaveStore()
  const timeFix = useTimeFixStore()
  const notifications = useNotificationStore()
  const router = useRouter()
  const route = useRoute()

  const user = computed(() => auth.user)
  const isAuthenticated = computed(() => auth.isAuthenticated)

  // คืนค่า {needsSetup:true, empcode} กลับไปให้ผู้เรียก (LoginView) ถ้ายังไม่เคยตั้งรหัสผ่าน แทนที่จะพาไปหน้า
  // หลักเลย — ไม่ throw เพราะไม่ใช่ error กรอกถูกแล้ว แค่ยังไม่มีรหัสผ่านให้เช็คเท่านั้น
  async function login(credentials) {
    const result = await auth.login(credentials)
    if (result?.needsSetup) return result
    await goAfterLogin()
  }

  // เหมือน login() แต่ด้วย Google — คืน {needsSetup:true} กลับไปเหมือนกันถ้ายังไม่เคยตั้งรหัสผ่าน
  async function googleLogin(credential) {
    const result = await auth.googleLogin(credential)
    if (result?.needsSetup) return result
    await goAfterLogin()
  }

  // ตั้งรหัสผ่านใหม่สำเร็จ -> เข้าสู่ระบบอัตโนมัติเหมือน login ปกติ
  // onSuccess = callback ที่รอให้จบก่อนพาไปหน้าถัดไป (LoginView ใช้โชว์หน้า "ตั้งรหัสผ่านสำเร็จ" สั้นๆ ก่อนเข้าระบบ)
  async function completeSetup(payload, { onSuccess } = {}) {
    await auth.setupPassword(payload)
    if (onSuccess) await onSuccess()
    await goAfterLogin()
  }

  async function goAfterLogin() {
    const redirect = route.query.redirect
    // อนุญาตเฉพาะ path ภายในแอป กัน open redirect
    const target = typeof redirect === 'string' && redirect.startsWith('/') && !redirect.startsWith('//') ? redirect : '/'
    await router.replace(target)
  }

  async function logout() {
    await auth.logout()
    attendance.reset()
    leave.reset()
    timeFix.reset()
    notifications.reset()
    await router.replace({ name: 'login' })
  }

  return { user, isAuthenticated, login, googleLogin, completeSetup, logout }
}
