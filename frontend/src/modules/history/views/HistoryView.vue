<template>
  <div class="space-y-4">
    <PageHeader title="ประวัติการลงเวลา">
      <template #actions>
        <router-link
          :to="{ name: 'time-fix' }"
          class="flex h-10 items-center gap-1 rounded-full bg-card px-3.5 text-xs font-semibold text-ink no-underline shadow-sm"
        >
          <v-icon icon="mdi-clock-edit-outline" size="18" class="text-violet-500" />
          ขอลงเวลา
        </router-link>
      </template>
    </PageHeader>

    <MonthSwitcher v-model="month" />

    <!-- เตือนวันที่ลงเวลาไม่ครบ -->
    <button
      v-if="incompleteDays.length"
      type="button"
      class="flex w-full items-center gap-3 rounded-2xl bg-status-outside/10 p-3.5 text-left"
      @click="requestFix(incompleteDays[incompleteDays.length - 1])"
    >
      <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-status-outside text-white">
        <v-icon icon="mdi-alert-outline" size="20" />
      </span>
      <span class="min-w-0 flex-1">
        <span class="block text-sm font-semibold text-ink">ลงเวลาไม่ครบ {{ incompleteDays.length }} วัน</span>
        <span class="block text-xs text-ink-muted">แตะเพื่อขอลงเวลาย้อนหลัง ก่อนสรุปเงินเดือน</span>
      </span>
      <v-icon icon="mdi-chevron-right" class="text-status-outside" />
    </button>

    <HistorySummary :records="workedRecords" />

    <LoadingSpinner v-if="attendance.loadingHistory" text="กำลังโหลด..." />

    <EmptyState
      v-else-if="!attendance.history.length"
      icon="mdi-calendar-blank-outline"
      title="ไม่มีข้อมูลการลงเวลา"
      :description="`ยังไม่มีการลงเวลาในเดือน${formatMonth(month)}`"
    />

    <div v-else class="space-y-2.5">
      <HistoryItem v-for="record in attendance.history" :key="record.id" :record="record" @select="handleSelect" />
    </div>
  </div>

  <HistoryDetailSheet v-model="detailOpen" :record="selected" @request-fix="requestFix" />
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/layout/PageHeader.vue'
import MonthSwitcher from '@/components/common/MonthSwitcher.vue'
import LoadingSpinner from '@/components/feedback/LoadingSpinner.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import HistorySummary from '../components/HistorySummary.vue'
import HistoryItem from '../components/HistoryItem.vue'
import HistoryDetailSheet from '../components/HistoryDetailSheet.vue'
import { hasPendingFix, isIncomplete, suggestedFixType } from '../recordStatus'
import { useAttendanceStore } from '@/store'
import { useNotification } from '@/composables/useNotification'
import { formatMonth, toMonthKey } from '@/utils/formatters'

const attendance = useAttendanceStore()
const notify = useNotification()
const router = useRouter()

const month = ref(toMonthKey())
const selected = ref(null)
const detailOpen = ref(false)

// วันที่มาทำงานจริง (ไม่รวมวันที่ไม่มีการลงเวลา)
const workedRecords = computed(() => attendance.history.filter((r) => !r.missing))
// วันที่ลงเวลาไม่ครบและยังไม่ได้ส่งคำขอ
const incompleteDays = computed(() => attendance.history.filter((r) => isIncomplete(r) && !hasPendingFix(r)))

watch(
  month,
  async (value) => {
    try {
      await attendance.fetchHistory(value)
    } catch (error) {
      notify.error(error.message)
    }
  },
  { immediate: true },
)

function handleSelect(record) {
  // วันที่ไม่มีการลงเวลา: ไปขอลงเวลาย้อนหลังเลย
  if (record.missing) {
    requestFix(record)
    return
  }
  selected.value = record
  detailOpen.value = true
}

function requestFix(record) {
  detailOpen.value = false
  if (hasPendingFix(record)) {
    router.push({ name: 'time-fix' })
    return
  }
  router.push({ name: 'time-fix', query: { date: record.date, type: suggestedFixType(record) } })
}
</script>
