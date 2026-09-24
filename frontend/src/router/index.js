import { createRouter, createWebHistory } from 'vue-router'
import routes from './routes'
import { useAuthStore } from '@/store'
import { APP_NAME, PDPA_POLICY_VERSION } from '@/utils/constants'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: 'home' }
  }

  // ต้องยินยอม PDPA (เวอร์ชันปัจจุบัน) ก่อนใช้งานหน้าอื่น
  const consented = auth.user?.consent?.policyVersion === PDPA_POLICY_VERSION
  if (to.meta.requiresAuth && !to.meta.skipConsent && !consented) {
    return { name: 'onboarding' }
  }
})

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} | ${APP_NAME}` : APP_NAME
})

export default router
