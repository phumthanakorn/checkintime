<template>
  <div class="space-y-4">
    <PageHeader title="สลิปเงินเดือน" :back-to="{ name: 'profile' }" />

    <MonthSwitcher v-if="latestMonth" v-model="month" :max="latestMonth" />

    <LoadingState v-if="(store.loadingList && !store.list.length) || store.loadingDetail" />

    <EmptyState
      v-else-if="notReleased"
      icon="receipt"
      title="สลิปเดือนนี้ยังไม่ออก"
      :description="`สลิปเงินเดือนจะออกทุกวันที่ ${PAYDAY} ของเดือน`"
    />

    <template v-else-if="slip">
      <NetPayCard v-model:hidden="hidden" :slip="slip" />

      <!-- สรุปเวลาทำงานของเดือน (ที่มาของยอดเงิน) -->
      <section class="grid grid-cols-4 rounded-3xl bg-card px-2 py-3.5 shadow-sm">
        <div
          v-for="(stat, index) in attendanceStats"
          :key="stat.label"
          class="flex flex-col items-center gap-0.5 text-center"
          :class="index > 0 && 'border-l border-slate-100'"
        >
          <span class="flex items-baseline gap-0.5">
            <span class="font-display text-lg font-bold tabular-nums" :class="stat.color">{{ stat.value }}</span>
            <span class="text-[10px] text-ink-muted">{{ stat.unit }}</span>
          </span>
          <span class="text-[11px] text-ink-muted">{{ stat.label }}</span>
        </div>
      </section>

      <PayslipSection
        title="รายได้"
        icon="coins"
        tone="plus"
        :items="slip.earnings"
        :total="slip.totalEarnings"
        :hidden="hidden"
      />
      <PayslipSection
        title="รายการหัก"
        icon="receipt"
        tone="minus"
        :items="slip.deductions"
        :total="slip.totalDeductions"
        :hidden="hidden"
      />

      <button
        type="button"
        class="flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-card text-sm font-semibold text-ink shadow-sm transition active:scale-[0.99]"
        @click="download"
      >
        <AppIcon name="download" :size="20" class="text-status-checkin" />
        ดาวน์โหลดสลิป (PDF)
      </button>
      <p class="text-center text-[11px] text-ink-muted">
        หากมีข้อสงสัยเกี่ยวกับยอดเงิน ติดต่อฝ่ายบุคคล (HR) {{ HR_CONTACT_PHONE }}
      </p>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import MonthSwitcher from '@/components/common/MonthSwitcher.vue'
import LoadingState from '@/components/feedback/LoadingState.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import NetPayCard from '../components/NetPayCard.vue'
import PayslipSection from '../components/PayslipSection.vue'
import { printPayslip } from '../printPayslip'
import { usePayslipStore } from '@/store'
import { useNotification } from '@/composables/useNotification'
import { HR_CONTACT_PHONE, PAYDAY, STORAGE_KEYS } from '@/utils/constants'

const store = usePayslipStore()
const notify = useNotification()

const month = ref('')
const notReleased = ref(false)
const hidden = ref(readHidden())

function readHidden() {
  try {
    return localStorage.getItem(STORAGE_KEYS.HIDE_AMOUNTS) === '1'
  } catch {
    return false
  }
}

watch(hidden, (value) => {
  try {
    localStorage.setItem(STORAGE_KEYS.HIDE_AMOUNTS, value ? '1' : '0')
  } catch {
    // ไม่บันทึกได้ก็ไม่เป็นไร
  }
})

const latestMonth = computed(() => store.list[0]?.month ?? '')
const slip = computed(() => (store.current?.month === month.value ? store.current : null))

const attendanceStats = computed(() => {
  const a = slip.value?.attendance ?? {}
  return [
    { label: 'วันทำงาน', value: a.workDays ?? 0, unit: 'วัน', color: 'text-ink' },
    { label: 'ลา', value: a.leaveDays ?? 0, unit: 'วัน', color: 'text-metric-blue' },
    { label: 'มาสาย', value: a.lateCount ?? 0, unit: 'ครั้ง', color: 'text-status-checkout' },
    { label: 'OT', value: +((a.otMinutes ?? 0) / 60).toFixed(1), unit: 'ชม.', color: 'text-metric-cyan' },
  ]
})

watch(month, async (value) => {
  if (!value) return
  notReleased.value = false
  try {
    await store.fetchDetail(value)
  } catch (error) {
    if (error.status === 404) notReleased.value = true
    else notify.error(error.message)
  }
})

onMounted(async () => {
  try {
    await store.fetchList()
    month.value = latestMonth.value
    if (!month.value) notReleased.value = true
  } catch (error) {
    notify.error(error.message)
  }
})

function download() {
  if (!printPayslip(slip.value)) notify.warning('เบราว์เซอร์บล็อกหน้าต่างใหม่ กรุณาอนุญาต pop-up แล้วลองอีกครั้ง')
}
</script>
