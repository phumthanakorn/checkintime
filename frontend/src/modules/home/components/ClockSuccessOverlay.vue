<template>
  <Teleport to="body">
    <Transition name="overlay-fade">
      <div
        v-if="open && config"
        class="success-overlay fixed inset-0 z-[3000] flex cursor-pointer flex-col items-center justify-center overflow-hidden px-6 text-center text-white"
        :class="config.bg"
        role="alertdialog"
        aria-live="assertive"
        :aria-label="config.title"
        @click="close"
      >
        <!-- กระดาษสีโปรย (เฉพาะเข้างานตรงเวลา) -->
        <div v-if="config.confetti" class="pointer-events-none absolute inset-0" aria-hidden="true">
          <span
            v-for="piece in confetti"
            :key="piece.id"
            class="confetti-piece absolute top-0"
            :style="piece.style"
          />
        </div>

        <!-- วงกลม + เครื่องหมายถูก -->
        <div class="relative mb-8 flex h-32 w-32 items-center justify-center">
          <span class="pulse-ring absolute inset-0 rounded-full bg-white/40" />
          <span class="pulse-ring pulse-ring--late absolute inset-0 rounded-full bg-white/30" />
          <span class="pop-circle relative flex h-32 w-32 items-center justify-center rounded-full bg-card shadow-2xl">
            <svg viewBox="0 0 52 52" class="h-16 w-16" :class="config.iconColor" aria-hidden="true">
              <path
                v-if="config.icon === 'check'"
                class="draw-path"
                d="M14 27 L23 36 L39 18"
                fill="none"
                stroke="currentColor"
                stroke-width="5"
                stroke-linecap="round"
                stroke-linejoin="round"
                pathLength="100"
              />
              <path
                v-else
                class="draw-path"
                d="M26 14 V28 M26 38 V38.5"
                fill="none"
                stroke="currentColor"
                stroke-width="5.5"
                stroke-linecap="round"
                pathLength="100"
              />
            </svg>
          </span>
        </div>

        <p class="fade-up text-2xl font-bold" style="--delay: 0.75s">{{ config.title }}</p>
        <p class="fade-up mt-1 font-display text-6xl font-extrabold tracking-tight tabular-nums" style="--delay: 0.85s">
          {{ formatClock(time) }}
        </p>
        <p class="fade-up mt-1 text-sm text-white/85" style="--delay: 0.95s">{{ formatThaiDate(time) }}</p>

        <p class="fade-up mt-6 rounded-full bg-white/15 px-4 py-2 text-sm font-medium" style="--delay: 1.1s">
          {{ config.message }}
        </p>

        <button
          type="button"
          class="fade-up mt-10 rounded-2xl bg-card px-10 py-3 text-sm font-semibold shadow-lg"
          :class="config.iconColor"
          style="--delay: 1.2s"
          @click.stop="close"
        >
          ตกลง
        </button>

        <!-- แถบนับถอยหลังปิดอัตโนมัติ -->
        <span
          class="countdown-bar absolute bottom-0 left-0 h-1 bg-white/60"
          :style="{ animationDuration: `${autoCloseMs}ms` }"
        />
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { formatClock, formatDuration, formatThaiDate } from '@/utils/formatters'

/**
 * variant:
 *  - 'checkin-ontime'  เข้างานตรงเวลา (สีเขียว + กระดาษสี)
 *  - 'checkin-late'    เข้างานสาย (สีส้ม)
 *  - 'checkout'        ออกงาน (สีเข้ม)
 */
const open = defineModel({ type: Boolean, default: false })

const props = defineProps({
  variant: { type: String, default: 'checkin-ontime' },
  time: { type: [String, Date], default: () => new Date() },
  lateMinutes: { type: Number, default: 0 },
  workMinutes: { type: Number, default: null },
  autoCloseMs: { type: Number, default: 3500 },
})

const config = computed(
  () =>
    ({
      'checkin-ontime': {
        bg: 'bg-status-checkin',
        iconColor: 'text-status-checkin',
        icon: 'check',
        title: 'เข้างานตรงเวลา',
        message: 'ขอให้เป็นวันที่ดีนะ ✨',
        confetti: true,
      },
      'checkin-late': {
        bg: 'bg-status-checkout',
        iconColor: 'text-status-checkout',
        icon: 'alert',
        title: `เข้างานสาย ${props.lateMinutes} นาที`,
        message: 'พรุ่งนี้มาให้ทันนะ 💪',
        confetti: false,
      },
      checkout: {
        bg: 'bg-status-done',
        iconColor: 'text-status-checkin',
        icon: 'check',
        title: 'ออกงานเรียบร้อย',
        message: props.workMinutes != null ? `วันนี้ทำงาน ${formatDuration(props.workMinutes)} 🌙` : 'พักผ่อนให้เต็มที่ 🌙',
        confetti: false,
      },
    })[props.variant],
)

const CONFETTI_COLORS = ['#FDE047', '#F59E0B', '#3B82F6', '#EC4899', '#A78BFA', '#FFFFFF', '#06B6D4']
const confetti = ref([])

function createConfetti(count = 44) {
  return Array.from({ length: count }, (_, id) => {
    const size = 6 + Math.random() * 6
    return {
      id,
      style: {
        left: `${Math.random() * 100}%`,
        width: `${size}px`,
        height: `${Math.random() < 0.3 ? size : size * 1.6}px`,
        borderRadius: Math.random() < 0.3 ? '9999px' : '2px',
        background: CONFETTI_COLORS[id % CONFETTI_COLORS.length],
        '--drift': `${(Math.random() - 0.5) * 160}px`,
        '--spin': `${360 + Math.random() * 720}deg`,
        animationDelay: `${0.45 + Math.random() * 0.7}s`,
        animationDuration: `${2.2 + Math.random() * 1.4}s`,
      },
    }
  })
}

let timer = null

function close() {
  open.value = false
}

watch(open, (isOpen) => {
  clearTimeout(timer)
  if (!isOpen) return
  confetti.value = config.value?.confetti ? createConfetti() : []
  try {
    navigator.vibrate?.(props.variant === 'checkin-ontime' ? [30, 60, 30] : 40)
  } catch {
    // ไม่รองรับการสั่นก็ข้ามไป
  }
  timer = setTimeout(close, props.autoCloseMs)
})

onBeforeUnmount(() => clearTimeout(timer))
</script>

<style scoped>
/* พื้นหลังขยายเป็นวงกลมจากกลางจอ */
.success-overlay {
  animation: reveal 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes reveal {
  from {
    clip-path: circle(0% at 50% 42%);
  }
  to {
    clip-path: circle(150% at 50% 42%);
  }
}

/* วงกลมสีขาวเด้งเข้ามา */
.pop-circle {
  animation: pop 0.55s cubic-bezier(0.34, 1.56, 0.64, 1) 0.25s both;
}

@keyframes pop {
  from {
    transform: scale(0);
  }
  to {
    transform: scale(1);
  }
}

/* วาดเครื่องหมายถูกทีละเส้น */
.draw-path {
  stroke-dasharray: 100;
  stroke-dashoffset: 100;
  animation: draw 0.45s ease-out 0.6s forwards;
}

@keyframes draw {
  to {
    stroke-dashoffset: 0;
  }
}

/* คลื่นวงกลมกระจายออก */
.pulse-ring {
  animation: pulse 1.4s ease-out 0.7s 2 both;
}

.pulse-ring--late {
  animation-delay: 1.1s;
}

@keyframes pulse {
  from {
    transform: scale(1);
    opacity: 0.8;
  }
  to {
    transform: scale(2);
    opacity: 0;
  }
}

/* ข้อความค่อย ๆ ลอยขึ้น */
.fade-up {
  animation: fade-up 0.5s ease-out var(--delay, 0s) both;
}

@keyframes fade-up {
  from {
    transform: translateY(14px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

/* กระดาษสีร่วงลงพร้อมหมุนและส่ายซ้ายขวา */
.confetti-piece {
  animation-name: confetti-fall;
  animation-timing-function: cubic-bezier(0.25, 0.46, 0.45, 0.94);
  animation-fill-mode: both;
}

@keyframes confetti-fall {
  from {
    transform: translate3d(0, -20px, 0) rotate(0deg);
    opacity: 1;
  }
  80% {
    opacity: 1;
  }
  to {
    transform: translate3d(var(--drift), 105vh, 0) rotate(var(--spin));
    opacity: 0;
  }
}

.countdown-bar {
  width: 100%;
  animation-name: countdown;
  animation-timing-function: linear;
  animation-fill-mode: forwards;
}

@keyframes countdown {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}

.overlay-fade-leave-active {
  transition: opacity 0.25s ease;
}

.overlay-fade-leave-to {
  opacity: 0;
}

/* ผู้ใช้ที่ตั้งค่าลดการเคลื่อนไหว: แสดงผลทันทีโดยไม่มีแอนิเมชัน */
@media (prefers-reduced-motion: reduce) {
  .success-overlay,
  .pop-circle,
  .draw-path,
  .fade-up {
    animation-duration: 0.01s !important;
    animation-delay: 0s !important;
  }

  .pulse-ring,
  .confetti-piece {
    display: none;
  }
}
</style>
