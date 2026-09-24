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
    path: '/payslip',
    name: 'payslip',
    component: () => import('@/modules/payslip/views/PayslipView.vue'),
    meta: { layout: 'main', requiresAuth: true, hideHeader: true, tab: 'profile', title: 'สลิปเงินเดือน' },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/modules/system/views/NotFoundView.vue'),
    meta: { layout: 'blank', title: 'ไม่พบหน้า' },
  },
]

export default routes
