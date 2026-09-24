<template>
  <!-- อธิบายเมื่อกดลงเวลาขณะไม่มีอินเทอร์เน็ต -->
  <AppBottomSheet v-model="open" title="ไม่มีการเชื่อมต่ออินเทอร์เน็ต">
    <div class="text-center">
      <span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
        <AppIcon name="globe" :size="32" weight="duotone" class="text-status-done" />
      </span>
      <p class="text-sm text-ink">ต้องเชื่อมต่ออินเทอร์เน็ตจึงจะลงเวลาได้</p>
      <p class="mt-1 text-xs text-ink-muted">เพื่อให้เวลาที่บันทึกตรงกับเวลาจริงของระบบ</p>
    </div>

    <ol class="mt-5 space-y-2.5 rounded-2xl bg-app-bg p-4">
      <li v-for="(tip, index) in TIPS" :key="tip" class="flex gap-3 text-sm text-ink">
        <span
          class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-card font-display text-xs font-bold text-status-checkin shadow-sm"
        >
          {{ index + 1 }}
        </span>
        <span class="leading-relaxed">{{ tip }}</span>
      </li>
    </ol>

    <p class="mt-4 text-center text-xs text-ink-muted">
      ถ้าเน็ตใช้ไม่ได้ทั้งวัน กด “ขอลงเวลาย้อนหลัง” ในหน้าประวัติได้ภายหลัง
    </p>

    <template #footer>
      <button
        type="button"
        class="flex h-12 w-full items-center justify-center gap-2 rounded-2xl font-semibold text-white shadow-lg transition"
        :class="online ? 'bg-status-checkin shadow-status-checkin/30' : 'bg-ink shadow-ink/20'"
        @click="retry"
      >
        <AppIcon :name="online ? 'check' : 'reset'" :size="20" />
        {{ online ? 'เชื่อมต่อแล้ว กลับไปลงเวลา' : 'ลองอีกครั้ง' }}
      </button>
    </template>
  </AppBottomSheet>
</template>

<script setup>
import AppBottomSheet from '@/components/common/AppBottomSheet.vue'
import { useOnline } from '@/composables/useOnline'
import { useNotification } from '@/composables/useNotification'

const open = defineModel({ type: Boolean, default: false })

const online = useOnline()
const notify = useNotification()

const TIPS = [
  'ตรวจว่าเปิด Wi-Fi หรือข้อมูลมือถือ (Mobile Data) อยู่',
  'ปิดโหมดเครื่องบิน แล้วลองเปิดเว็บอื่นดูว่าใช้ได้ไหม',
  'ถ้าอยู่ที่ออฟฟิศ ลองเชื่อมต่อ Wi-Fi ของบริษัท',
]

function retry() {
  if (navigator.onLine) open.value = false
  else notify.warning('ยังไม่มีการเชื่อมต่ออินเทอร์เน็ต')
}
</script>
