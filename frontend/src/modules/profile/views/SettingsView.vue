<template>
  <div class="space-y-5">
    <PageHeader title="ตั้งค่า" :back-to="{ name: 'profile' }" />

    <!-- การแจ้งเตือน -->
    <section>
      <h2 class="mb-2 px-1 text-[13px] font-semibold text-ink-muted">การแจ้งเตือน</h2>
      <div class="divide-y divide-slate-100 overflow-hidden rounded-2xl bg-card shadow-sm">
        <div class="setting-row">
          <span class="icon bg-rose-50"><AppIcon name="bell" weight="duotone" class="text-rose-500" /></span>
          <span class="min-w-0 flex-1">
            <span class="block text-sm font-medium text-ink">เปิดการแจ้งเตือน</span>
            <span class="block text-xs text-ink-muted">ปิดแล้วจะไม่ได้รับการแจ้งเตือนทั้งหมด</span>
          </span>
          <ToggleSwitch v-model="settings.notifications" label="เปิดการแจ้งเตือน" />
        </div>

        <div v-for="reminder in reminders" :key="reminder.key" class="setting-row flex-wrap">
          <span class="icon" :class="reminder.bg"><AppIcon :name="reminder.icon" weight="duotone" :class="reminder.color" /></span>
          <span class="min-w-0 flex-1">
            <span class="block text-sm font-medium" :class="settings.notifications ? 'text-ink' : 'text-ink-muted'">
              {{ reminder.label }}
            </span>
            <span class="block text-xs text-ink-muted">{{ reminder.description }}</span>
          </span>
          <ToggleSwitch v-model="settings[reminder.key]" :label="reminder.label" :disabled="!settings.notifications" />
          <div v-if="reminder.timeKey && settings.notifications && settings[reminder.key]" class="w-full pl-[3.25rem]">
            <TimeField
              v-model="settings[reminder.timeKey]"
              :title="`เวลา${reminder.label}`"
              icon="alarm"
              :minute-step="5"
              :shortcuts="reminder.shortcuts"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- ความเป็นส่วนตัว -->
    <section>
      <h2 class="mb-2 px-1 text-[13px] font-semibold text-ink-muted">ความเป็นส่วนตัว</h2>
      <div class="overflow-hidden rounded-2xl bg-card shadow-sm">
        <div class="setting-row">
          <span class="icon bg-metric-blue/10"><AppIcon name="eye-slash" weight="duotone" class="text-metric-blue" /></span>
          <span class="min-w-0 flex-1">
            <span class="block text-sm font-medium text-ink">ซ่อนยอดเงินในสลิป</span>
            <span class="block text-xs text-ink-muted">แสดงเป็น •••••• จนกว่าจะกดดู</span>
          </span>
          <ToggleSwitch v-model="hideAmounts" label="ซ่อนยอดเงินในสลิป" />
        </div>
      </div>
    </section>

    <!-- เกี่ยวกับแอป -->
    <section>
      <h2 class="mb-2 px-1 text-[13px] font-semibold text-ink-muted">เกี่ยวกับแอป</h2>
      <div class="divide-y divide-slate-100 overflow-hidden rounded-2xl bg-card shadow-sm">
        <div class="setting-row">
          <span class="icon bg-slate-100"><AppIcon name="globe" weight="duotone" class="text-status-done" /></span>
          <span class="flex-1 text-sm font-medium text-ink">ภาษา</span>
          <span class="text-sm text-ink-muted">ไทย</span>
        </div>
        <div class="setting-row">
          <span class="icon bg-slate-100"><AppIcon name="mobile" weight="duotone" class="text-status-done" /></span>
          <span class="flex-1 text-sm font-medium text-ink">เวอร์ชัน</span>
          <span class="font-display text-sm text-ink-muted">{{ APP_VERSION }}</span>
        </div>
        <button v-if="USE_MOCK" type="button" class="setting-row w-full text-left" @click="confirmReset = true">
          <span class="icon bg-status-outside/10"><AppIcon name="reset" weight="duotone" class="text-status-outside" /></span>
          <span class="min-w-0 flex-1">
            <span class="block text-sm font-medium text-status-outside">รีเซ็ตข้อมูลทดลอง</span>
            <span class="block text-xs text-ink-muted">ล้างข้อมูลจำลองทั้งหมดแล้วเริ่มใหม่</span>
          </span>
        </button>
      </div>
    </section>
  </div>

  <ConfirmModal
    v-model="confirmReset"
    title="รีเซ็ตข้อมูลทดลอง"
    message="ข้อมูลการลงเวลา คำขอ และการตั้งค่าในโหมดทดลองจะถูกล้างทั้งหมด ต้องการรีเซ็ตใช่หรือไม่?"
    confirm-text="รีเซ็ต"
    color="error"
    @confirm="resetMock"
  />
</template>

<script setup>
import { ref, watch } from 'vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import ToggleSwitch from '@/components/common/ToggleSwitch.vue'
import TimeField from '@/components/common/TimeField.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import { useSettings } from '@/composables/useSettings'
import { useAuth } from '@/composables/useAuth'
import { APP_VERSION, STORAGE_KEYS, USE_MOCK } from '@/utils/constants'

const settings = useSettings()
const { logout } = useAuth()
const confirmReset = ref(false)

const reminders = [
  {
    key: 'checkInReminder',
    timeKey: 'checkInReminderTime',
    label: 'เตือนลงเวลาเข้างาน',
    description: 'ถ้ายังไม่ได้กดเข้างานตามเวลาที่ตั้ง',
    icon: 'sign-in',
    bg: 'bg-status-checkin/10',
    color: 'text-status-checkin',
    shortcuts: ['08:30', '08:45', '09:00'],
  },
  {
    key: 'checkOutReminder',
    timeKey: 'checkOutReminderTime',
    label: 'เตือนลงเวลาออกงาน',
    description: 'ถ้ายังไม่ได้กดออกงานหลังเลิกงาน',
    icon: 'sign-out',
    bg: 'bg-status-checkout/10',
    color: 'text-status-checkout',
    shortcuts: ['18:00', '18:05', '18:30'],
  },
  {
    key: 'requestUpdates',
    label: 'ผลการอนุมัติคำขอ',
    description: 'ใบลาและคำขอลงเวลาย้อนหลัง',
    icon: 'calendar-check',
    bg: 'bg-violet-50',
    color: 'text-violet-500',
  },
]

const hideAmounts = ref(readHide())
function readHide() {
  try {
    return localStorage.getItem(STORAGE_KEYS.HIDE_AMOUNTS) === '1'
  } catch {
    return false
  }
}
watch(hideAmounts, (value) => {
  try {
    localStorage.setItem(STORAGE_KEYS.HIDE_AMOUNTS, value ? '1' : '0')
  } catch {
    // ไม่เป็นไร
  }
})

async function resetMock() {
  localStorage.removeItem(STORAGE_KEYS.MOCK_DB)
  localStorage.removeItem(STORAGE_KEYS.PIN_USER)
  confirmReset.value = false
  await logout()
}
</script>

<style scoped>
.setting-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  row-gap: 0.625rem;
}

.icon {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 0.75rem;
}
</style>
