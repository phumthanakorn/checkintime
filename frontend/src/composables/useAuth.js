import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAttendanceStore, useAuthStore, useLeaveStore, useNotificationStore, usePayslipStore, useTimeFixStore } from '@/store'

export function useAuth() {
  const auth = useAuthStore()
  const attendance = useAttendanceStore()
  const leave = useLeaveStore()
  const timeFix = useTimeFixStore()
  const notifications = useNotificationStore()
  const payslip = usePayslipStore()
  const router = useRouter()
  const route = useRoute()

  const user = computed(() => auth.user)
  const isAuthenticated = computed(() => auth.isAuthenticated)

  async function login(credentials) {
    await auth.login(credentials)
    await goAfterLogin()
  }

  async function loginWithPin(credentials) {
    await auth.loginWithPin(credentials)
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
    payslip.reset()
    await router.replace({ name: 'login' })
  }

  return { user, isAuthenticated, login, loginWithPin, logout }
}
