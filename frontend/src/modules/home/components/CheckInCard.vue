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
      </div>

      <button
        type="button"
        class="flex h-[104px] w-[104px] shrink-0 flex-col items-center justify-center gap-2 rounded-2xl bg-card px-2 shadow-md transition active:scale-95 disabled:active:scale-100"
        :disabled="!config.actionable || loading"
        @click="emit('action', state)"
      >
        <v-progress-circular v-if="loading" indeterminate :class="config.text" size="40" width="3" />
        <span
          v-else-if="config.iconBg"
          class="flex h-12 w-12 items-center justify-center rounded-full text-white"
          :class="config.iconBg"
        >
          <v-icon :icon="config.icon" size="26" />
        </span>
        <v-icon v-else :icon="config.icon" size="46" :class="config.text" />
        <span class="whitespace-pre-line text-center text-xs font-semibold leading-tight" :class="config.text">
          {{ config.label }}
        </span>
      </button>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
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
    icon: 'mdi-check',
    label: 'กดเข้างาน',
    actionable: true,
  },
  [CLOCK_STATE.WORKING]: {
    card: 'bg-status-checkout shadow-status-checkout/30',
    iconBg: 'bg-status-checkout',
    text: 'text-status-checkout',
    icon: 'mdi-logout',
    label: 'กดออกงาน',
    actionable: true,
  },
  [CLOCK_STATE.DONE]: {
    card: 'bg-status-done shadow-status-done/30',
    iconBg: null,
    text: 'text-status-checkin',
    icon: 'mdi-check-decagram',
    label: 'เช็คอินครบแล้ว\nสำหรับวันนี้',
    actionable: false,
  },
  [CLOCK_STATE.OUT_OF_AREA]: {
    card: 'bg-status-outside shadow-status-outside/30',
    iconBg: 'bg-status-outside',
    text: 'text-status-outside',
    icon: 'mdi-map-marker',
    label: 'อยู่นอกพื้นที่',
    actionable: false,
  },
}

const config = computed(() => STATE_CONFIG[props.state])
</script>
