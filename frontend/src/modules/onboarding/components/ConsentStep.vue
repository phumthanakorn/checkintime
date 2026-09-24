<template>
  <div>
    <div class="text-center">
      <span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-status-checkin/10">
        <AppIcon name="shield-check" :size="34" weight="duotone" class="text-status-checkin" />
      </span>
      <h1 class="text-2xl font-bold text-ink">ความเป็นส่วนตัวของคุณ</h1>
      <p class="mx-auto mt-1 max-w-[300px] text-[13px] text-ink-muted">
        ก่อนเริ่มใช้งาน โปรดอ่านและยินยอมการใช้ข้อมูลตาม พ.ร.บ.คุ้มครองข้อมูลส่วนบุคคล (PDPA)
      </p>
    </div>

    <!-- สรุปสั้น ๆ -->
    <ul class="mt-6 list-none space-y-3 rounded-3xl bg-card p-4 shadow-sm">
      <li v-for="item in SUMMARY" :key="item.title" class="flex items-start gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :class="item.bg">
          <AppIcon :name="item.icon" :size="20" weight="duotone" :class="item.color" />
        </span>
        <span class="min-w-0">
          <span class="block text-sm font-semibold text-ink">{{ item.title }}</span>
          <span class="block text-xs leading-relaxed text-ink-muted">{{ item.body }}</span>
        </span>
      </li>
      <li>
        <button
          type="button"
          class="flex w-full items-center justify-center gap-1 rounded-xl bg-app-bg py-2.5 text-xs font-semibold text-status-checkin"
          @click="policyOpen = true"
        >
          อ่านนโยบายความเป็นส่วนตัวฉบับเต็ม
          <AppIcon name="caret-right" :size="14" />
        </button>
      </li>
    </ul>

    <!-- ช่องยินยอม (ต้องติ๊กครบ) -->
    <div class="mt-4 space-y-2.5">
      <button
        v-for="item in consents"
        :key="item.key"
        type="button"
        role="checkbox"
        :aria-checked="accepted[item.key]"
        class="flex w-full items-start gap-3 rounded-2xl border-2 bg-card p-3.5 text-left transition"
        :class="accepted[item.key] ? 'border-status-checkin' : 'border-transparent shadow-sm'"
        @click="accepted[item.key] = !accepted[item.key]"
      >
        <AppIcon
          :name="accepted[item.key] ? 'check-square' : 'square'"
          :size="24"
          :weight="accepted[item.key] ? 'fill' : 'regular'"
          :class="accepted[item.key] ? 'text-status-checkin' : 'text-slate-300'"
        />
        <span class="text-sm leading-snug text-ink">
          {{ item.label }}
          <span class="text-status-outside">*</span>
        </span>
      </button>
    </div>

    <button
      type="button"
      class="mt-5 flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-status-checkin font-semibold text-white shadow-lg shadow-status-checkin/30 transition disabled:opacity-40 disabled:shadow-none"
      :disabled="!allAccepted || loading"
      @click="emit('accept', { location: accepted.location })"
    >
      <LoadingDots v-if="loading" />
      <template v-else>
        ยินยอมและดำเนินการต่อ
        <AppIcon name="arrow-right" :size="20" />
      </template>
    </button>
    <p class="mt-3 text-center text-[11px] text-ink-muted">
      ถอนความยินยอมได้ภายหลัง โดยติดต่อฝ่ายบุคคล (HR) {{ HR_CONTACT_PHONE }}
    </p>

    <!-- อยู่ใน root เดียวกัน เพราะ <Transition> ของหน้าแม่รองรับ element ชั้นนอกสุดตัวเดียว -->
    <AppBottomSheet v-model="policyOpen" title="นโยบายความเป็นส่วนตัว">
      <p class="mb-3 text-xs text-ink-muted">ปรับปรุงล่าสุด {{ PDPA_UPDATED_AT }}</p>
      <PrivacySections bordered />
      <template #footer>
        <button
          type="button"
          class="h-12 w-full rounded-2xl bg-status-checkin font-semibold text-white"
          @click="policyOpen = false"
        >
          อ่านแล้ว
        </button>
      </template>
    </AppBottomSheet>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import AppBottomSheet from '@/components/common/AppBottomSheet.vue'
import PrivacySections from '@/modules/profile/components/PrivacySections.vue'
import { HR_CONTACT_PHONE, PDPA_UPDATED_AT } from '@/utils/constants'

defineProps({
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['accept'])

const SUMMARY = [
  {
    title: 'ข้อมูลที่ใช้',
    body: 'ข้อมูลพนักงาน เวลาเข้า-ออกงาน การลา และเงินเดือน เท่าที่จำเป็นต่อการทำงาน',
    icon: 'id-card',
    bg: 'bg-metric-blue/10',
    color: 'text-metric-blue',
  },
  {
    title: 'ตำแหน่งที่ตั้ง',
    body: 'ใช้เฉพาะตอนกดเข้า-ออกงาน เพื่อยืนยันว่าอยู่ที่ทำงาน ไม่ติดตามตลอดเวลา',
    icon: 'map-pin',
    bg: 'bg-status-checkout/10',
    color: 'text-status-checkout',
  },
  {
    title: 'สิทธิของคุณ',
    body: 'ขอดู แก้ไข หรือขอลบข้อมูลได้ตามที่กฎหมายกำหนด',
    icon: 'handshake',
    bg: 'bg-status-checkin/10',
    color: 'text-status-checkin',
  },
]

const consents = [
  { key: 'policy', label: 'ข้าพเจ้าได้อ่านและยอมรับนโยบายความเป็นส่วนตัว' },
  { key: 'location', label: 'ยินยอมให้ใช้ตำแหน่งที่ตั้ง (GPS) ขณะกดลงเวลาเข้า-ออกงาน' },
]

const accepted = reactive({ policy: false, location: false })
const allAccepted = computed(() => accepted.policy && accepted.location)
const policyOpen = ref(false)
</script>
