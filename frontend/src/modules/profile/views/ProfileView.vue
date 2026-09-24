<template>
  <div class="space-y-5">
    <PageHeader title="ฉัน">
      <template #actions>
        <button
          type="button"
          class="flex h-10 w-10 items-center justify-center rounded-full bg-card text-ink shadow-sm"
          aria-label="ตั้งค่า"
          @click="comingSoon"
        >
          <v-icon icon="mdi-cog-outline" size="22" />
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
      <v-icon icon="mdi-logout" size="20" />
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
import { APP_NAME, APP_VERSION, HR_CONTACT_PHONE, STORAGE_KEYS } from '@/utils/constants'

const auth = useAuthStore()
const { user, logout } = useAuth()
const notify = useNotification()
const router = useRouter()

const confirmLogout = ref(false)
const loggingOut = ref(false)
const notificationsOn = ref(readNotificationSetting())

function readNotificationSetting() {
  try {
    return localStorage.getItem(STORAGE_KEYS.NOTIFICATIONS) !== 'off'
  } catch {
    return true
  }
}

const workMenu = [
  {
    key: 'payslip',
    label: 'สลิปเงินเดือน',
    description: 'ดูและดาวน์โหลดสลิปย้อนหลัง',
    icon: 'mdi-file-document-outline',
    bg: 'bg-metric-blue/10',
    color: 'text-metric-blue',
  },
  {
    key: 'history',
    label: 'ประวัติการลงเวลา',
    description: 'เวลาเข้า-ออกงานรายวัน',
    icon: 'mdi-history',
    bg: 'bg-violet-50',
    color: 'text-violet-500',
  },
  {
    key: 'advance',
    label: 'คำขอเบิกเงิน',
    description: 'ยื่นและติดตามสถานะการเบิก',
    icon: 'mdi-wallet-outline',
    bg: 'bg-metric-cyan/10',
    color: 'text-metric-cyan',
  },
]

const accountMenu = computed(() => [
  {
    key: 'personal',
    label: 'ข้อมูลส่วนตัว',
    icon: 'mdi-account-outline',
    bg: 'bg-status-checkin/10',
    color: 'text-status-checkin',
  },
  {
    key: 'password',
    label: 'เปลี่ยนรหัสผ่าน',
    icon: 'mdi-lock-outline',
    bg: 'bg-status-checkout/10',
    color: 'text-status-checkout',
  },
  {
    key: 'biometric',
    label: 'PIN / Biometric',
    description: 'เข้าสู่ระบบด้วยลายนิ้วมือหรือ PIN',
    icon: 'mdi-fingerprint',
    bg: 'bg-status-checkin/10',
    color: 'text-status-checkin',
  },
  {
    key: 'notifications',
    label: 'การแจ้งเตือน',
    description: 'เตือนเวลาเข้า-ออกงาน',
    icon: 'mdi-bell-outline',
    bg: 'bg-rose-50',
    color: 'text-rose-500',
    switch: notificationsOn.value,
  },
])

const helpMenu = [
  {
    key: 'contact-hr',
    label: 'ติดต่อฝ่ายบุคคล (HR)',
    icon: 'mdi-phone-outline',
    bg: 'bg-status-checkin/10',
    color: 'text-status-checkin',
    value: HR_CONTACT_PHONE,
  },
  {
    key: 'privacy',
    label: 'นโยบายความเป็นส่วนตัว (PDPA)',
    icon: 'mdi-shield-check-outline',
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

function comingSoon() {
  notify.info('เมนูนี้จะเปิดให้ใช้งานเร็ว ๆ นี้')
}

function handleSelect(key) {
  if (key === 'payslip') router.push({ name: 'payslip' })
  else if (key === 'history') router.push({ name: 'history' })
  else if (key === 'contact-hr') window.location.href = `tel:${HR_CONTACT_PHONE.replace(/[^\d+]/g, '')}`
  else comingSoon()
}

function handleToggle(key, value) {
  if (key !== 'notifications') return
  notificationsOn.value = value
  try {
    localStorage.setItem(STORAGE_KEYS.NOTIFICATIONS, value ? 'on' : 'off')
  } catch {
    // ไม่บันทึกได้ก็ไม่เป็นไร
  }
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
