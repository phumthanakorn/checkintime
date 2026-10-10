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
        <!-- Clock Hero: Plus Jakarta Sans 48px / 800 — วินาทีต่อท้ายเล็กกว่า+จางกว่า ไม่แย่งความสนใจจาก
             นาฬิกาหลัก แค่เพิ่มลูกเล่นว่า "ยังเดินอยู่จริง" เปลี่ยนคีย์ตาม transition ทุกวินาทีให้มันกระพริบเบาๆ -->
        <p class="font-display tabular-nums text-5xl font-extrabold leading-tight tracking-tight">
          {{ formatClock(now) }}<span class="align-baseline text-2xl font-bold text-white/55"
            >:<Transition name="tick" mode="out-in"><span :key="formatSeconds(now)">{{ formatSeconds(now) }}</span></Transition></span
          >
        </p>
        <p class="text-[13px] text-white/80">{{ formatThaiDate(now) }}</p>
        <!-- ป้ายสถานที่ลงเวลาที่ใกล้ตำแหน่งปัจจุบันที่สุด — แสดงไว้ให้ดูเฉยๆ ไม่ได้บังคับว่าต้องลงที่นี่
             (ดึงจาก useGeofence().nearest ซึ่งหาจากรายชื่อสถานที่จริงทั้งหมด ไม่ใช่ที่ fix ไว้ตายตัว)
             แตะได้ — เปิด popup แผนที่ดูตำแหน่งสถานที่ + ตำแหน่งตัวเองเทียบกัน -->
        <button
          v-if="nearestLocation"
          type="button"
          class="mt-2 flex w-full max-w-full items-center gap-1 rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-medium active:bg-white/25"
          @click="emit('location-click')"
        >
          <AppIcon name="map-pin" :size="14" class="shrink-0" />
          <!-- min-w-0 จำเป็นสำหรับ flex child ถึงจะยอม shrink ให้ truncate ทำงาน (ไม่งั้น span จะดันความกว้าง
               ปุ่มจนล้นแล้วตัวหนังสือตก 2 บรรทัดแทนที่จะตัด ... แบบที่ตั้งใจ) -->
          <span class="min-w-0 flex-1 truncate text-left">{{ nearestLocation.location.name }}</span>
          <span class="shrink-0 text-white/70">· {{ formatDistance(nearestLocation.distance) }}</span>
        </button>

        <p
          v-if="config.help"
          class="mt-2 inline-flex items-center gap-1 rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-medium"
        >
          <AppIcon name="map-pin" :size="14" />
          แตะปุ่มเพื่อดูแผนที่
        </p>
      </div>

      <div class="relative shrink-0">
        <button
          type="button"
          class="tap-button flex h-[104px] w-[104px] flex-col items-center justify-center gap-2 rounded-2xl bg-card px-2 shadow-md transition-transform duration-150 active:scale-95"
          :disabled="(!config.actionable && !config.help) || loading"
          :aria-label="config.help ? 'อยู่นอกพื้นที่ แตะเพื่อดูแผนที่' : config.label"
          @click="onClick"
        >
          <LoadingDots v-if="loading" size="lg" class="h-12" :class="config.text" />
          <span
            v-else-if="config.iconBg"
            class="flex h-12 w-12 items-center justify-center rounded-full text-white"
            :class="config.iconBg"
          >
            <AppIcon :name="config.icon" :size="26" weight="bold" />
          </span>
          <AppIcon v-else :name="config.icon" :size="46" weight="fill" :class="config.text" />
          <span class="whitespace-pre-line text-center text-xs font-semibold leading-tight" :class="config.text">
            {{ loading ? 'กำลังบันทึก...' : config.label }}
          </span>
        </button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { CLOCK_STATE } from '@/utils/constants'
import { formatClock, formatSeconds, formatThaiDate } from '@/utils/formatters'

const props = defineProps({
  state: { type: String, required: true, validator: (v) => Object.values(CLOCK_STATE).includes(v) },
  now: { type: Date, required: true },
  loading: { type: Boolean, default: false },
  /** { location: {name, ...}, distance: number } | null — จาก useGeofence().nearest */
  nearestLocation: { type: Object, default: null },
})

/** 85 -> '85 ม.', 1340 -> '1.3 กม.' */
function formatDistance(meters) {
  if (meters < 1000) return `${Math.round(meters)} ม.`
  return `${(meters / 1000).toFixed(1)} กม.`
}

const emit = defineEmits(['action', 'help', 'location-click'])

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
    // ใช้ action-checkout (กรมท่าเข้ม) ไม่ใช่ status-checkout (Amber) — status-checkout ถูกใช้เป็นสี
    // warning ทั่วแอปอยู่แล้ว สีมันใกล้กับ checkin (ส้ม) เกินไปจนแยกยากบนจอเล็ก (ตามที่ผู้ใช้ทักมา)
    card: 'bg-action-checkout shadow-action-checkout/30',
    iconBg: 'bg-action-checkout',
    text: 'text-action-checkout',
    icon: 'sign-out',
    label: 'กดออกงาน',
    actionable: true,
  },
  [CLOCK_STATE.DONE]: {
    // ใช้ action-done (เขียว) ไม่ใช่ status-done (เทาเข้ม) — status-done ใช้เป็นสีกลางๆ ทั่วแอปอยู่แล้ว แถม
    // สีใกล้เคียง action-checkout (กรมท่าเข้มทั้งคู่) มากจนแยกยากบนจอเล็ก (ตามที่ผู้ใช้ทักมา)
    card: 'bg-action-done shadow-action-done/30',
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
    help: true, // แตะเพื่อเปิดหน้าแผนที่ (ไม่ใช่ action ลงเวลา เลยแยก flag ไว้ต่างหากจาก actionable)
  },
}

const config = computed(() => STATE_CONFIG[props.state])

// แตะครั้งเดียวก็ส่ง action เลย — ไม่ต้องกดค้างแล้ว เพราะขั้นถ่ายภาพยืนยันตัวตน (CameraCaptureModal ฝั่ง
// HomeView.vue) ทำหน้าที่ "กันกดโดนโดยไม่ตั้งใจ" แทนอยู่แล้ว ถ่ายภาพเองก็ต้องตั้งใจกดอยู่แล้วในตัว
function onClick() {
  if (config.value.help) {
    emit('help')
    return
  }
  if (!config.value.actionable || props.loading) return
  emit('action', props.state)
}
</script>

<style scoped>
.tap-button {
  user-select: none;
  -webkit-user-select: none;
  -webkit-touch-callout: none;
}

/* วินาทีกระพริบเบาๆ ทุกครั้งที่เปลี่ยนเลข */
.tick-enter-active,
.tick-leave-active {
  transition: opacity 0.15s ease;
}
.tick-enter-from,
.tick-leave-to {
  opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
  .tick-enter-active,
  .tick-leave-active {
    transition-duration: 0.01s;
  }
}
</style>
