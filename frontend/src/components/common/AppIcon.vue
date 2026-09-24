<template>
  <component :is="icon" :size="size" :weight="weight" class="inline-block shrink-0" aria-hidden="true" />
</template>

<script setup>
/**
 * ไอคอนกลางของแอป (Phosphor Icons — https://phosphoricons.com)
 * ใช้ชื่อสั้นที่สื่อความหมาย เช่น <AppIcon name="calendar" :size="20" weight="duotone" />
 * สีตาม currentColor ใช้ class สีของ Tailwind ได้ เช่น class="text-status-checkin"
 *
 * weight: 'regular' (ทั่วไป) | 'fill' (แท็บที่เลือก/เน้น) | 'duotone' (ไอคอนในวงกลมสี) | 'bold' | 'light' | 'thin'
 * เพิ่มไอคอนใหม่: import จาก @phosphor-icons/vue แล้วใส่ใน ICONS (โหลดเฉพาะไอคอนที่อยู่ในรายการนี้)
 */
import { computed } from 'vue'
import {
  PhAlarm,
  PhArrowRight,
  PhBell,
  PhBriefcase,
  PhCalendarBlank,
  PhCalendarCheck,
  PhCalendarDots,
  PhCaretDown,
  PhCaretLeft,
  PhCaretRight,
  PhCaretUp,
  PhChatText,
  PhCheck,
  PhClock,
  PhClockCounterClockwise,
  PhClockUser,
  PhConfetti,
  PhEye,
  PhEyeSlash,
  PhFileText,
  PhFilePdf,
  PhFingerprint,
  PhFirstAidKit,
  PhGearSix,
  PhHandTap,
  PhHourglassMedium,
  PhHouse,
  PhIdentificationCard,
  PhInfo,
  PhIsland,
  PhLock,
  PhLockOpen,
  PhMapPin,
  PhPaperPlaneRight,
  PhPaperclip,
  PhPencilSimple,
  PhPhone,
  PhPlus,
  PhQuestion,
  PhSealCheck,
  PhShieldCheck,
  PhSignIn,
  PhSignOut,
  PhTimer,
  PhTray,
  PhUser,
  PhUserCircle,
  PhWallet,
  PhWarning,
  PhWarningCircle,
  PhX,
  PhXCircle,
} from '@phosphor-icons/vue'

const ICONS = {
  alarm: PhAlarm,
  'arrow-right': PhArrowRight,
  bell: PhBell,
  briefcase: PhBriefcase,
  calendar: PhCalendarBlank,
  'calendar-check': PhCalendarCheck,
  'calendar-dots': PhCalendarDots,
  'caret-down': PhCaretDown,
  'caret-left': PhCaretLeft,
  'caret-right': PhCaretRight,
  'caret-up': PhCaretUp,
  chat: PhChatText,
  check: PhCheck,
  clock: PhClock,
  'clock-edit': PhClockUser,
  confetti: PhConfetti,
  eye: PhEye,
  'eye-slash': PhEyeSlash,
  'file-pdf': PhFilePdf,
  'file-text': PhFileText,
  fingerprint: PhFingerprint,
  'first-aid': PhFirstAidKit,
  gear: PhGearSix,
  'hand-tap': PhHandTap,
  history: PhClockCounterClockwise,
  home: PhHouse,
  hourglass: PhHourglassMedium,
  'id-card': PhIdentificationCard,
  info: PhInfo,
  island: PhIsland,
  lock: PhLock,
  'lock-open': PhLockOpen,
  'map-pin': PhMapPin,
  paperclip: PhPaperclip,
  pencil: PhPencilSimple,
  phone: PhPhone,
  plus: PhPlus,
  'seal-check': PhSealCheck,
  send: PhPaperPlaneRight,
  'shield-check': PhShieldCheck,
  'sign-in': PhSignIn,
  'sign-out': PhSignOut,
  timer: PhTimer,
  tray: PhTray,
  user: PhUser,
  'user-circle': PhUserCircle,
  wallet: PhWallet,
  warning: PhWarning,
  'warning-circle': PhWarningCircle,
  x: PhX,
  'x-circle': PhXCircle,
}

const props = defineProps({
  name: { type: String, required: true },
  size: { type: [Number, String], default: 20 },
  weight: { type: String, default: 'regular' },
})

const icon = computed(() => {
  const found = ICONS[props.name]
  if (!found && import.meta.env.DEV) console.warn(`[AppIcon] ไม่พบไอคอน "${props.name}"`)
  return found || PhQuestion
})
</script>
