<template>
  <div class="space-y-5">
    <PageHeader title="PIN / Biometric" :back-to="{ name: 'profile' }" />

    <!-- ขั้นตั้ง PIN -->
    <section v-if="step !== 'status'" class="rounded-3xl bg-card px-4 pb-6 pt-7 shadow-sm">
      <div class="mb-6 text-center">
        <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-status-checkin/10">
          <AppIcon name="password" :size="28" weight="duotone" class="text-status-checkin" />
        </span>
        <p class="text-lg font-bold text-ink">{{ step === 'enter' ? 'ตั้ง PIN 6 หลัก' : 'ยืนยัน PIN อีกครั้ง' }}</p>
        <p class="text-xs text-ink-muted">
          {{ step === 'enter' ? 'ใช้เข้าสู่ระบบบนเครื่องนี้แทนรหัสผ่าน' : 'กรอก PIN เดิมอีกครั้งเพื่อยืนยัน' }}
        </p>
      </div>

      <PinPad v-model="pin" :error="error" :disabled="saving" @complete="onComplete" />

      <button type="button" class="mx-auto mt-5 block text-sm font-medium text-ink-muted" @click="cancel">ยกเลิก</button>
    </section>

    <!-- สถานะปัจจุบัน -->
    <template v-else>
      <section class="rounded-3xl bg-card p-5 text-center shadow-sm">
        <span
          class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full"
          :class="hasPin ? 'bg-status-checkin/10' : 'bg-slate-100'"
        >
          <AppIcon name="password" :size="32" weight="duotone" :class="hasPin ? 'text-status-checkin' : 'text-slate-400'" />
        </span>
        <p class="text-base font-bold text-ink">{{ hasPin ? 'เปิดใช้ PIN แล้ว' : 'ยังไม่ได้ตั้ง PIN' }}</p>
        <p class="mt-1 text-xs text-ink-muted">
          {{
            hasPin
              ? 'เข้าสู่ระบบบนเครื่องนี้ด้วย PIN 6 หลักได้ทันที ไม่ต้องพิมพ์รหัสผ่าน'
              : 'ตั้ง PIN เพื่อเข้าสู่ระบบได้เร็วขึ้น ไม่ต้องพิมพ์รหัสผ่านทุกครั้ง'
          }}
        </p>

        <button
          type="button"
          class="mt-5 flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-status-checkin font-semibold text-white shadow-lg shadow-status-checkin/30"
          @click="startSetup"
        >
          <AppIcon name="key" :size="20" />
          {{ hasPin ? 'เปลี่ยน PIN' : 'ตั้ง PIN' }}
        </button>
        <button
          v-if="hasPin"
          type="button"
          class="mt-2 h-11 w-full rounded-2xl text-sm font-semibold text-status-outside"
          @click="confirmRemove = true"
        >
          ปิดการใช้ PIN
        </button>
      </section>

      <MenuGroup title="การยืนยันตัวตนด้วยชีวมิติ" :items="biometricItems" @select="biometricInfo" />
    </template>
  </div>

  <ConfirmModal
    v-model="confirmRemove"
    title="ปิดการใช้ PIN"
    message="หลังปิด ต้องเข้าสู่ระบบด้วยรหัสผ่านเท่านั้น ต้องการปิดใช่หรือไม่?"
    confirm-text="ปิดการใช้ PIN"
    color="error"
    :loading="saving"
    @confirm="removePin"
  />
</template>

<script setup>
import { computed, ref } from 'vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import PinPad from '@/components/common/PinPad.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
import MenuGroup from '../components/MenuGroup.vue'
import { useAuthStore } from '@/store'
import { useNotification } from '@/composables/useNotification'

const auth = useAuthStore()
const notify = useNotification()

const step = ref('status') // status | enter | confirm
const pin = ref('')
const firstPin = ref('')
const error = ref('')
const saving = ref(false)
const confirmRemove = ref(false)

const hasPin = computed(() => !!auth.user?.hasPin)

const biometricItems = [
  {
    key: 'biometric',
    label: 'ลายนิ้วมือ / Face ID',
    description: 'ใช้ได้เมื่อเปิดผ่านแอป LINE',
    icon: 'fingerprint',
    bg: 'bg-status-checkin/10',
    color: 'text-status-checkin',
    disabled: true,
  },
]

/** PIN ที่เดาง่าย: เลขซ้ำทั้งหมด หรือเรียงต่อกัน */
function isWeakPin(value) {
  if (/^(\d)\1{5}$/.test(value)) return true
  return '0123456789'.includes(value) || '9876543210'.includes(value)
}

function startSetup() {
  step.value = 'enter'
  pin.value = ''
  firstPin.value = ''
  error.value = ''
}

function cancel() {
  step.value = 'status'
  pin.value = ''
  error.value = ''
}

async function onComplete(value) {
  if (step.value === 'enter') {
    if (isWeakPin(value)) {
      error.value = 'PIN เดาง่ายเกินไป ลองใช้ตัวเลขอื่น'
      setTimeout(() => (pin.value = ''), 300)
      return
    }
    firstPin.value = value
    error.value = ''
    setTimeout(() => {
      pin.value = ''
      step.value = 'confirm'
    }, 200)
    return
  }

  if (value !== firstPin.value) {
    error.value = 'PIN ไม่ตรงกัน กรุณาตั้งใหม่'
    setTimeout(() => {
      pin.value = ''
      firstPin.value = ''
      step.value = 'enter'
    }, 700)
    return
  }

  saving.value = true
  try {
    await auth.setPin(value)
    notify.success('ตั้ง PIN เรียบร้อย')
    cancel()
  } catch (e) {
    error.value = e.message
    pin.value = ''
  } finally {
    saving.value = false
  }
}

async function removePin() {
  saving.value = true
  try {
    await auth.removePin()
    confirmRemove.value = false
    notify.success('ปิดการใช้ PIN แล้ว')
  } catch (e) {
    notify.error(e.message)
  } finally {
    saving.value = false
  }
}

function biometricInfo() {
  notify.info('การสแกนลายนิ้วมือ / Face ID จะใช้ได้เมื่อเปิดแอปผ่าน LINE')
}
</script>
