<template>
  <div>
    <!-- ภาพประกอบ: หมุดตำแหน่งพร้อมคลื่น -->
    <div class="relative mx-auto mb-5 flex h-28 w-28 items-center justify-center">
      <template v-if="state !== 'denied'">
        <span class="ripple absolute inset-0 rounded-full bg-status-checkin/20" />
        <span class="ripple ripple--late absolute inset-0 rounded-full bg-status-checkin/20" />
      </template>
      <span
        class="relative flex h-20 w-20 items-center justify-center rounded-full shadow-lg"
        :class="state === 'denied' ? 'bg-status-outside shadow-status-outside/30' : 'bg-status-checkin shadow-status-checkin/30'"
      >
        <AppIcon :name="state === 'granted' ? 'check' : 'map-pin'" :size="40" weight="fill" class="text-white" />
      </span>
    </div>

    <div class="text-center">
      <h1 class="text-2xl font-bold text-ink">{{ texts.title }}</h1>
      <p class="mx-auto mt-1 max-w-[300px] text-[13px] text-ink-muted">{{ texts.subtitle }}</p>
    </div>

    <!-- เหตุผลที่ต้องใช้ตำแหน่ง -->
    <ul v-if="state !== 'denied'" class="mt-6 space-y-3 rounded-3xl bg-card p-4 shadow-sm">
      <li v-for="reason in REASONS" :key="reason.text" class="flex items-center gap-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-status-checkin/10">
          <AppIcon :name="reason.icon" :size="18" weight="duotone" class="text-status-checkin" />
        </span>
        <span class="text-sm text-ink">{{ reason.text }}</span>
      </li>
    </ul>

    <!-- ถูกปฏิเสธ: วิธีเปิดตามเครื่อง -->
    <section v-else class="mt-6 rounded-3xl bg-card p-4 shadow-sm">
      <p class="mb-3 text-sm font-semibold text-ink">วิธีเปิดการเข้าถึงตำแหน่ง</p>
      <SegmentedTabs v-model="device" :options="DEVICE_OPTIONS" />
      <ol class="mt-4 space-y-2.5">
        <li v-for="(stepText, index) in GUIDES[device]" :key="stepText" class="flex gap-3 text-sm text-ink">
          <span
            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-status-checkin/10 font-display text-xs font-bold text-status-checkin"
          >
            {{ index + 1 }}
          </span>
          <span class="leading-relaxed">{{ stepText }}</span>
        </li>
      </ol>
    </section>

    <p
      v-if="state === 'idle'"
      class="mt-4 flex items-start gap-2 rounded-2xl bg-metric-blue/10 p-3 text-xs leading-relaxed text-metric-blue"
    >
      <AppIcon name="info" :size="16" />
      เมื่อมีหน้าต่างถามสิทธิ์ขึ้นมา ให้กด “อนุญาต” (Allow)
    </p>
    <p
      v-else-if="state === 'unavailable'"
      class="mt-4 flex items-start gap-2 rounded-2xl bg-status-checkout/10 p-3 text-xs leading-relaxed text-amber-700"
    >
      <AppIcon name="warning" :size="16" />
      หาตำแหน่งไม่ได้ ลองเปิด GPS / บริการหาตำแหน่งของโทรศัพท์ หรือไปที่โล่งแล้วลองอีกครั้ง
    </p>

    <button
      type="button"
      class="mt-5 flex h-12 w-full items-center justify-center gap-2 rounded-2xl font-semibold text-white shadow-lg transition disabled:opacity-60"
      :class="state === 'denied' ? 'bg-ink shadow-ink/20' : 'bg-status-checkin shadow-status-checkin/30'"
      :disabled="state === 'requesting'"
      @click="state === 'granted' ? emit('done', { granted: true }) : request()"
    >
      <LoadingDots v-if="state === 'requesting'" />
      <template v-else>
        <AppIcon :name="state === 'granted' ? 'arrow-right' : 'navigation'" :size="20" weight="fill" />
        {{ texts.button }}
      </template>
    </button>
    <button
      v-if="state !== 'granted'"
      type="button"
      class="mx-auto mt-3 block text-sm font-medium text-ink-muted"
      @click="emit('done', { granted: false })"
    >
      {{ skipLabel }}
    </button>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import SegmentedTabs from '@/components/common/SegmentedTabs.vue'
import { useGeolocation } from '@/composables/useGeolocation'

defineProps({
  skipLabel: { type: String, default: 'ข้ามไปก่อน' },
})

const emit = defineEmits(['done'])

const { requestPosition, getPermissionState } = useGeolocation()

/** idle | requesting | granted | denied | unavailable */
const state = ref('idle')

const REASONS = [
  { icon: 'crosshair', text: 'ยืนยันว่าคุณอยู่ในพื้นที่ทำงานตอนลงเวลา' },
  { icon: 'navigation', text: 'ใช้เฉพาะตอนกดเข้างาน-ออกงานเท่านั้น' },
  { icon: 'shield-check', text: 'ไม่มีการติดตามตำแหน่งตลอดเวลา' },
]

const DEVICE_OPTIONS = [
  { value: 'ios', label: 'iPhone' },
  { value: 'android', label: 'Android' },
  { value: 'line', label: 'ในแอป LINE' },
]

const GUIDES = {
  ios: [
    'เปิด “การตั้งค่า” > “ความเป็นส่วนตัวและความปลอดภัย” > “บริการหาตำแหน่ง” แล้วเปิดใช้งาน',
    'เลื่อนหา “Safari เว็บไซต์” (หรือเบราว์เซอร์ที่ใช้) แล้วเลือก “ขณะใช้แอป”',
    'กลับมาที่หน้านี้ แตะปุ่ม “aA” ในช่องที่อยู่เว็บ > “การตั้งค่าเว็บไซต์” > “ตำแหน่ง” > “อนุญาต”',
    'แตะ “ลองอีกครั้ง” ด้านล่าง',
  ],
  android: [
    'ปัดแถบด้านบนลงมา แล้วเปิด “ตำแหน่ง” (Location)',
    'แตะไอคอนแม่กุญแจ 🔒 หน้าช่องที่อยู่เว็บใน Chrome > “สิทธิ์” > “ตำแหน่ง” > “อนุญาต”',
    'แตะ “ลองอีกครั้ง” ด้านล่าง',
  ],
  line: [
    'เปิด “การตั้งค่า” ของโทรศัพท์ > “แอป” > “LINE” > “สิทธิ์” (Permissions)',
    'เลือก “ตำแหน่ง” > “อนุญาตขณะใช้แอป”',
    'กลับมาที่ LINE แล้วแตะ “ลองอีกครั้ง” ด้านล่าง',
  ],
}

/** เดาเครื่องจาก userAgent เพื่อเลือกแท็บวิธีเปิดให้ตรง */
function detectDevice() {
  const ua = navigator.userAgent
  if (/Line\//i.test(ua)) return 'line'
  if (/iPhone|iPad|iPod/i.test(ua)) return 'ios'
  return 'android'
}
const device = ref(detectDevice())

const texts = computed(
  () =>
    ({
      idle: {
        title: 'อนุญาตให้ใช้ตำแหน่ง',
        subtitle: 'แอปต้องใช้ตำแหน่งของคุณตอนลงเวลา เพื่อยืนยันว่าอยู่ที่ทำงานจริง',
        button: 'อนุญาตตำแหน่ง',
      },
      requesting: { title: 'อนุญาตให้ใช้ตำแหน่ง', subtitle: 'กำลังขอสิทธิ์...', button: '' },
      granted: {
        title: 'เปิดตำแหน่งเรียบร้อย',
        subtitle: 'พร้อมลงเวลาเข้า-ออกงานได้แล้ว',
        button: 'ถัดไป',
      },
      denied: {
        title: 'ตำแหน่งถูกปิดอยู่',
        subtitle: 'ถ้าไม่เปิดตำแหน่ง จะกดลงเวลาในแอปไม่ได้ ทำตามขั้นตอนด้านล่างเพื่อเปิด',
        button: 'ลองอีกครั้ง',
      },
      unavailable: {
        title: 'อนุญาตให้ใช้ตำแหน่ง',
        subtitle: 'แอปต้องใช้ตำแหน่งของคุณตอนลงเวลา เพื่อยืนยันว่าอยู่ที่ทำงานจริง',
        button: 'ลองอีกครั้ง',
      },
    })[state.value],
)

async function request() {
  state.value = 'requesting'
  const { status } = await requestPosition()
  state.value = status === 'granted' ? 'granted' : status === 'denied' ? 'denied' : 'unavailable'
}

// เคยอนุญาต/ปฏิเสธไว้แล้ว -> แสดงสถานะนั้นเลย ไม่ต้องให้กดขอใหม่
onMounted(async () => {
  const permission = await getPermissionState()
  if (permission === 'granted') state.value = 'granted'
  else if (permission === 'denied') state.value = 'denied'
})
</script>

<style scoped>
.ripple {
  animation: ripple 2s ease-out infinite;
}

.ripple--late {
  animation-delay: 1s;
}

@keyframes ripple {
  from {
    transform: scale(0.7);
    opacity: 1;
  }
  to {
    transform: scale(1.35);
    opacity: 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .ripple {
    animation: none;
    opacity: 0.4;
  }
}
</style>
