<template>
  <div class="space-y-4">
    <PageHeader title="ประวัติการลงเวลา" />

    <MonthSwitcher v-model="month" />

    <HistorySummary :records="attendance.history" />

    <LoadingSpinner v-if="attendance.loadingHistory" text="กำลังโหลด..." />

    <EmptyState
      v-else-if="!attendance.history.length"
      icon="mdi-calendar-blank-outline"
      title="ไม่มีข้อมูลการลงเวลา"
      :description="`ยังไม่มีการลงเวลาในเดือน${formatMonth(month)}`"
    />

    <div v-else class="space-y-2.5">
      <HistoryItem v-for="record in attendance.history" :key="record.id" :record="record" @select="openDetail" />
    </div>
  </div>

  <HistoryDetailSheet v-model="detailOpen" :record="selected" @request-fix="requestFix" />
</template>

<script setup>
import { ref, watch } from 'vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import MonthSwitcher from '@/components/common/MonthSwitcher.vue'
import LoadingSpinner from '@/components/feedback/LoadingSpinner.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import HistorySummary from '../components/HistorySummary.vue'
import HistoryItem from '../components/HistoryItem.vue'
import HistoryDetailSheet from '../components/HistoryDetailSheet.vue'
import { useAttendanceStore } from '@/store'
import { useNotification } from '@/composables/useNotification'
import { formatMonth, toMonthKey } from '@/utils/formatters'

const attendance = useAttendanceStore()
const notify = useNotification()

const month = ref(toMonthKey())
const selected = ref(null)
const detailOpen = ref(false)

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

function openDetail(record) {
  selected.value = record
  detailOpen.value = true
}

function requestFix() {
  notify.info('การแจ้งแก้ไขเวลาจะเปิดให้ใช้งานเร็ว ๆ นี้')
}
</script>
