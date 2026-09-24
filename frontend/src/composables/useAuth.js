import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAttendanceStore, useAuthStore, useLeaveStore, useTimeFixStore } from '@/store'

export function useAuth() {
  const auth = useAuthStore()
  const attendance = useAttendanceStore()
  const leave = useLeaveStore()
  const timeFix = useTimeFixStore()
  const router = useRouter()
  const route = useRoute()

  const user = computed(() => auth.user)
  const isAuthenticated = computed(() => auth.isAuthenticated)

  async function login(credentials) {
    await auth.login(credentials)
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
    await router.replace({ name: 'login' })
  }

  return { user, isAuthenticated, login, logout }
}
