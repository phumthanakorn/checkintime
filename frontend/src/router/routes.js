/**
 * meta:
 *  - layout: 'main' (มีแถบเมนูด้านล่าง) | 'auth' | 'blank'
 *  - requiresAuth: ต้องเข้าสู่ระบบก่อน
 *  - guestOnly: เฉพาะผู้ที่ยังไม่เข้าสู่ระบบ
 *  - hideHeader: ซ่อนส่วนหัว (คำทักทาย) ของ MainLayout
 *  - tab: ชื่อ route ของแท็บด้านล่างที่ต้องไฮไลต์ (ใช้กับหน้าย่อย)
 */
const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/modules/auth/views/LoginView.vue'),
    meta: { layout: 'auth', guestOnly: true, title: 'เข้าสู่ระบบ' },
  },
  {
    path: '/login/pin',
    name: 'pin-login',
    component: () => import('@/modules/auth/views/PinLoginView.vue'),
    meta: { layout: 'auth', guestOnly: true, title: 'เข้าสู่ระบบด้วย PIN' },
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/modules/auth/views/ForgotPasswordView.vue'),
    meta: { layout: 'auth', guestOnly: true, title: 'ลืมรหัสผ่าน' },
  },
  {
    path: '/',
    name: 'home',
    component: () => import('@/modules/home/views/HomeView.vue'),
    meta: { layout: 'main', requiresAuth: true, title: 'หน้าหลัก' },
  },
  {
    path: '/history',
    name: 'history',
    component: () => import('@/modules/history/views/HistoryView.vue'),
    meta: { layout: 'main', requiresAuth: true, hideHeader: true, title: 'ประวัติ' },
  },
  {
    path: '/time-fix',
    name: 'time-fix',
    component: () => import('@/modules/time-fix/views/TimeFixView.vue'),
    meta: { layout: 'main', requiresAuth: true, hideHeader: true, tab: 'history', title: 'ขอลงเวลาย้อนหลัง' },
  },
  {
    path: '/leave',
    name: 'leave',
    component: () => import('@/modules/leave/views/LeaveView.vue'),
    meta: { layout: 'main', requiresAuth: true, hideHeader: true, title: 'การลา' },
  },
  {
    path: '/me',
    name: 'profile',
    component: () => import('@/modules/profile/views/ProfileView.vue'),
    meta: { layout: 'main', requiresAuth: true, hideHeader: true, title: 'ฉัน' },
  },
  {
    path: '/me/personal',
    name: 'profile-personal',
    component: () => import('@/modules/profile/views/PersonalInfoView.vue'),
    meta: { layout: 'main', requiresAuth: true, hideHeader: true, tab: 'profile', title: 'ข้อมูลส่วนตัว' },
  },
  {
    path: '/me/password',
    name: 'profile-password',
    component: () => import('@/modules/profile/views/ChangePasswordView.vue'),
    meta: { layout: 'main', requiresAuth: true, hideHeader: true, tab: 'profile', title: 'เปลี่ยนรหัสผ่าน' },
  },
  {
    path: '/me/pin',
    name: 'profile-pin',
    component: () => import('@/modules/profile/views/PinSettingsView.vue'),
    meta: { layout: 'main', requiresAuth: true, hideHeader: true, tab: 'profile', title: 'PIN / Biometric' },
  },
  {
    path: '/me/settings',
    name: 'settings',
    component: () => import('@/modules/profile/views/SettingsView.vue'),
    meta: { layout: 'main', requiresAuth: true, hideHeader: true, tab: 'profile', title: 'ตั้งค่า' },
  },
  {
    path: '/privacy',
    name: 'privacy',
    component: () => import('@/modules/profile/views/PrivacyView.vue'),
    meta: { layout: 'main', requiresAuth: true, hideHeader: true, tab: 'profile', title: 'นโยบายความเป็นส่วนตัว' },
  },
  {
    path: '/payslip',
    name: 'payslip',
    component: () => import('@/modules/payslip/views/PayslipView.vue'),
    meta: { layout: 'main', requiresAuth: true, hideHeader: true, tab: 'profile', title: 'สลิปเงินเดือน' },
  },
  {
    path: '/notifications',
    name: 'notifications',
    component: () => import('@/modules/notifications/views/NotificationsView.vue'),
    meta: { layout: 'main', requiresAuth: true, hideHeader: true, title: 'การแจ้งเตือน' },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/modules/system/views/NotFoundView.vue'),
    meta: { layout: 'blank', title: 'ไม่พบหน้า' },
  },
]

export default routes
