<template>
  <section
    class="relative overflow-hidden rounded-[28px] p-5 text-white shadow-lg transition-colors duration-300"
    :class="config.card"
  >
    <!-- วงกลมตกแต่งมุมขวาบน -->
    <span class="pointer-events-none absolute -right-10 -top-10 h-44 w-44 rounded-full bg-white/10" />

    <div class="relative flex items-center justify-between gap-4">
      <div class="min-w-0">
        <p class="text-[13px] text-white/80">เวลาปัจจุบัน</p>
        <!-- Clock Hero: Plus Jakarta Sans 48px / 800 -->
        <p class="font-display tabular-nums text-5xl font-extrabold leading-tight tracking-tight">
          {{ formatClock(now) }}
        </p>
        <p class="text-[13px] text-white/80">{{ formatThaiDate(now) }}</p>
        <p
          v-if="config.actionable && !loading"
          class="mt-2 inline-flex items-center gap-1 rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-medium"
        >
          <AppIcon name="hand-tap" :size="14" />
          แตะค้างไว้เพื่อลงเวลา
        </p>
      </div>

      <div class="relative shrink-0">
        <!-- วงแหวนความคืบหน้าขณะกดค้าง -->
        <svg
          v-if="config.actionable && !loading"
          class="pointer-events-none absolute -inset-1.5 h-[116px] w-[116px]"
          viewBox="0 0 116 116"
          aria-hidden="true"
        >
          <rect x="2" y="2" width="112" height="112" rx="21" fill="none" stroke="rgb(255 255 255 / 0.3)" stroke-width="3" />
          <rect
            x="2"
            y="2"
            width="112"
            height="112"
            rx="21"
            fill="none"
            stroke="white"
            stroke-width="4"
            stroke-linecap="round"
            pathLength="100"
            stroke-dasharray="100"
            :stroke-dashoffset="100 - progress * 100"
            :class="!holding && 'transition-[stroke-dashoffset] duration-300 ease-out'"
          />
        </svg>

        <button
          type="button"
          class="hold-button flex h-[104px] w-[104px] flex-col items-center justify-center gap-2 rounded-2xl bg-card px-2 shadow-md transition-transform duration-150"
          :class="holding && 'scale-95'"
          :disabled="!config.actionable || loading"
          :aria-label="`กดค้างเพื่อ${config.label}`"
          @pointerdown="onPointerDown"
          @pointerup="cancel"
          @pointercancel="cancel"
          @keydown.enter.space.prevent="onKeyDown"
          @keyup.enter.space.prevent="cancel"
          @contextmenu.prevent
        >
          <LoadingDots v-if="loading" size="lg" class="h-12" :class="config.text" />
          <span
            v-else-if="config.iconBg"
            class="flex h-12 w-12 items-center justify-center rounded-full text-white transition-transform duration-150"
            :class="[config.iconBg, holding && 'scale-110']"
          >
            <AppIcon :name="config.icon" :size="26" weight="bold" />
          </span>
          <AppIcon v-else :name="config.icon" :size="46" weight="fill" :class="config.text" />
          <span class="whitespace-pre-line text-center text-xs font-semibold leading-tight" :class="config.text">
            {{ loading ? 'กำลังบันทึก...' : holding ? 'ค้างไว้...' : config.label }}
          </span>
        </button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, watch } from 'vue'
import { useHoldPress } from '@/composables/useHoldPress'
import { CLOCK_STATE } from '@/utils/constants'
import { formatClock, formatThaiDate } from '@/utils/formatters'

const props = defineProps({
  state: { type: String, required: true, validator: (v) => Object.values(CLOCK_STATE).includes(v) },
  now: { type: Date, required: true },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['action'])

// สีตาม Card Status Palette (ดู tokens ใน assets/css/tailwind.css)
const STATE_CONFIG = {
  [CLOCK_STATE.READY]: {
    card: 'bg-status-checkin shadow-status-checkin/30',
    iconBg: 'bg-status-checkin',
    text: 'text-status-checkin',
    icon: 'check',
    label: 'กดเข้างาน',
    actionable: true,
  },
  [CLOCK_STATE.WORKING]: {
    card: 'bg-status-checkout shadow-status-checkout/30',
    iconBg: 'bg-status-checkout',
    text: 'text-status-checkout',
    icon: 'sign-out',
    label: 'กดออกงาน',
    actionable: true,
  },
  [CLOCK_STATE.DONE]: {
    card: 'bg-status-done shadow-status-done/30',
    iconBg: null,
    text: 'text-status-checkin',
    icon: 'seal-check',
    label: 'เช็คอินครบแล้ว\nสำหรับวันนี้',
    actionable: false,
  },
  [CLOCK_STATE.OUT_OF_AREA]: {
    card: 'bg-status-outside shadow-status-outside/30',
    iconBg: 'bg-status-outside',
    text: 'text-status-outside',
    icon: 'map-pin',
    label: 'อยู่นอกพื้นที่',
    actionable: false,
  },
}

const config = computed(() => STATE_CONFIG[props.state])

// กดค้างครบ 1 วินาทีจึงส่ง action (กันกดโดนโดยไม่ตั้งใจ)
const { progress, holding, begin, cancel, reset } = useHoldPress(() => {
  emit('action', props.state)
  // คืนวงแหวนหลังส่ง action (กรณีไม่ได้เปลี่ยนสถานะ เช่น อยู่นอกพื้นที่)
  setTimeout(reset, 600)
})

watch(
  () => [props.state, props.loading],
  () => reset(),
)

function onPointerDown(event) {
  if (event.button !== 0) return
  // จับ pointer ไว้ นิ้วเลื่อนเล็กน้อยก็ยังนับว่ากดค้างอยู่
  event.currentTarget.setPointerCapture?.(event.pointerId)
  begin()
}

function onKeyDown(event) {
  if (!event.repeat) begin()
}
</script>

<style scoped>
.hold-button {
  touch-action: none;
  user-select: none;
  -webkit-user-select: none;
  -webkit-touch-callout: none;
}
</style>
