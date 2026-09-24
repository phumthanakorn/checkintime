<template>
  <div class="space-y-5">
    <PageHeader title="ฉัน">
      <template #actions>
        <button
          type="button"
          class="flex h-10 w-10 items-center justify-center rounded-full bg-card text-ink shadow-sm"
          aria-label="ตั้งค่า"
          @click="router.push({ name: 'settings' })"
        >
          <AppIcon name="gear" :size="22" />
        </button>
      </template>
    </PageHeader>

    <ProfileCard :user="user" />

    <MenuGroup title="งานของฉัน" :items="workMenu" @select="handleSelect" />
    <MenuGroup title="บัญชีและความปลอดภัย" :items="accountMenu" @select="handleSelect" @toggle="handleToggle" />
    <MenuGroup title="ช่วยเหลือ" :items="helpMenu" @select="handleSelect" />

    <button
      type="button"
      class="flex w-full items-center justify-center gap-2 rounded-2xl bg-card py-3.5 text-sm font-semibold text-status-outside shadow-sm transition active:scale-[0.99]"
      @click="confirmLogout = true"
    >
      <AppIcon name="sign-out" :size="20" />
      ออกจากระบบ
    </button>

    <p class="text-center text-xs text-ink-muted">{{ APP_NAME }} v{{ APP_VERSION }}</p>
  </div>

  <ConfirmModal
    v-model="confirmLogout"
    title="ออกจากระบบ"
    message="ต้องการออกจากระบบใช่หรือไม่?"
    confirm-text="ออกจากระบบ"
    color="error"
    :loading="loggingOut"
    @confirm="handleLogout"
  />
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/layout/PageHeader.vue'
import ProfileCard from '../components/ProfileCard.vue'
import MenuGroup from '../components/MenuGroup.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useAuthStore } from '@/store'
import { useAuth } from '@/composables/useAuth'
import { useNotification } from '@/composables/useNotification'
import { useSettings } from '@/composables/useSettings'
import { APP_NAME, APP_VERSION, FEATURES, HR_CONTACT_PHONE } from '@/utils/constants'

const auth = useAuthStore()
const { user, logout } = useAuth()
const notify = useNotification()
const router = useRouter()

const confirmLogout = ref(false)
const loggingOut = ref(false)
// สวิตช์การแจ้งเตือนใช้ค่าเดียวกับหน้าตั้งค่า
const settings = useSettings()

const workMenu = [
  {
    key: 'payslip',
    label: 'สลิปเงินเดือน',
    description: 'ดูและดาวน์โหลดสลิปย้อนหลัง',
    icon: 'file-text',
    bg: 'bg-metric-blue/10',
    color: 'text-metric-blue',
  },
  {
    key: 'calendar',
    label: 'ปฏิทินของฉัน',
    description: 'วันหยุด วันลา และกะการทำงาน',
    icon: 'calendar-dots',
    bg: 'bg-metric-blue/10',
    color: 'text-metric-blue',
  },
  {
    key: 'history',
    label: 'ประวัติการลงเวลา',
    description: 'เวลาเข้า-ออกงานรายวัน',
    icon: 'history',
    bg: 'bg-violet-50',
    color: 'text-violet-500',
  },
  {
    key: 'time-fix',
    label: 'ขอลงเวลาย้อนหลัง',
    description: 'กรณีลืมลงเวลาเข้า-ออกงาน',
    icon: 'clock-edit',
    bg: 'bg-violet-50',
    color: 'text-violet-500',
  },
  {
    key: 'advance',
    label: 'คำขอเบิกเงิน',
    description: FEATURES.ADVANCE_REQUEST ? 'ยื่นและติดตามสถานะการเบิก' : 'ยังไม่เปิดให้บริการ',
    icon: 'wallet',
    bg: 'bg-metric-cyan/10',
    color: 'text-metric-cyan',
    disabled: !FEATURES.ADVANCE_REQUEST,
  },
]

const accountMenu = computed(() => [
  {
    key: 'personal',
    label: 'ข้อมูลส่วนตัว',
    description: 'เบอร์โทร ที่อยู่ ผู้ติดต่อฉุกเฉิน',
    icon: 'user',
    bg: 'bg-status-checkin/10',
    color: 'text-status-checkin',
  },
  {
    key: 'password',
    label: 'เปลี่ยนรหัสผ่าน',
    icon: 'lock',
    bg: 'bg-status-checkout/10',
    color: 'text-status-checkout',
  },
  {
    key: 'biometric',
    label: 'PIN / Biometric',
    description: user.value?.hasPin ? 'เปิดใช้ PIN แล้ว' : 'ตั้ง PIN เพื่อเข้าสู่ระบบได้เร็วขึ้น',
    icon: 'fingerprint',
    bg: 'bg-status-checkin/10',
    color: 'text-status-checkin',
  },
  {
    key: 'notifications',
    label: 'การแจ้งเตือน',
    description: 'ตั้งเวลาเตือนได้ในหน้าตั้งค่า',
    icon: 'bell',
    bg: 'bg-rose-50',
    color: 'text-rose-500',
    switch: settings.notifications,
  },
])

const helpMenu = [
  {
    key: 'contact-hr',
    label: 'ติดต่อฝ่ายบุคคล (HR)',
    icon: 'phone',
    bg: 'bg-status-checkin/10',
    color: 'text-status-checkin',
    value: HR_CONTACT_PHONE,
  },
  {
    key: 'privacy',
    label: 'นโยบายความเป็นส่วนตัว (PDPA)',
    icon: 'shield-check',
    bg: 'bg-slate-100',
    color: 'text-status-done',
  },
]

onMounted(async () => {
  try {
    await auth.refreshUser()
  } catch {
    // ใช้ข้อมูลที่เก็บไว้เดิมต่อไป
  }
})

// key ของเมนู -> ชื่อ route
const ROUTES = {
  payslip: 'payslip',
  calendar: 'calendar',
  history: 'history',
  'time-fix': 'time-fix',
  personal: 'profile-personal',
  password: 'profile-password',
  biometric: 'profile-pin',
  privacy: 'privacy',
}

function handleSelect(key) {
  if (ROUTES[key]) router.push({ name: ROUTES[key] })
  else if (key === 'contact-hr') window.location.href = `tel:${HR_CONTACT_PHONE.replace(/[^\d+]/g, '')}`
  else if (key === 'advance' && !FEATURES.ADVANCE_REQUEST) notify.info('บริการเบิกเงินยังไม่เปิดให้ใช้งาน')
}

function handleToggle(key, value) {
  if (key !== 'notifications') return
  settings.notifications = value
  notify.success(value ? 'เปิดการแจ้งเตือนแล้ว' : 'ปิดการแจ้งเตือนแล้ว')
}

async function handleLogout() {
  loggingOut.value = true
  try {
    await logout()
  } finally {
    loggingOut.value = false
    confirmLogout.value = false
  }
}
</script>
