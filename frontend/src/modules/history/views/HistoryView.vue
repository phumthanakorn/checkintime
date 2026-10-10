<template>
  <div class="space-y-4">
    <PageHeader title="ปฏิทินการลงเวลา">
      <template #actions>
        <router-link
          :to="{ name: 'time-fix' }"
          class="flex h-10 items-center gap-1 rounded-full bg-card px-3.5 text-xs font-semibold text-ink no-underline shadow-sm"
        >
          <AppIcon name="clock-edit" :size="18" weight="duotone" class="text-violet-500" />
          ขอลงเวลา
        </router-link>
      </template>
    </PageHeader>

    <MonthSwitcher v-model="month" />

    <LoadingState v-if="loading && !data" />

    <!-- ปฏิทินแสดงวันที่ลงเวลา — แตะวันไหนก็ดูเวลาเข้า-ออกของวันนั้นได้ ถ้าวันไหนไม่มีข้อมูล จะมีปุ่ม
    "ขอลงเวลาย้อนหลัง" ให้กดจาก CalendarDayDetail ทันที (ดูเงื่อนไข needsFix ในไฟล์นั้น) -->
    <template v-else-if="data">
      <CalendarMonth :days="data.days" :selected="selected" @select="selected = $event" />
      <CalendarDayDetail :day="selectedDay" />
    </template>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import MonthSwitcher from '@/components/common/MonthSwitcher.vue'
import LoadingState from '@/components/feedback/LoadingState.vue'
import CalendarMonth from '@/modules/calendar/components/CalendarMonth.vue'
import CalendarDayDetail from '@/modules/calendar/components/CalendarDayDetail.vue'
import { calendarService } from '@/api/services/calendarService'
import { toDateKey, toMonthKey } from '@/utils/formatters'

const today = toDateKey()
const month = ref(toMonthKey())
const data = ref(null)
const loading = ref(false)
const selected = ref(today)

const selectedDay = computed(() => data.value?.days.find((d) => d.date === selected.value) ?? null)

// ปฏิทินเปล่าของเดือนนั้น (ไม่มีกะ/วันหยุด/วันลา/การลงเวลาเลย) — ใช้ตอนเรียก /calendar ไม่สำเร็จ (เช่น
// backend ยังไม่พร้อม) กันไม่ให้หน้าว่างเปล่า อย่างน้อยยังเห็นโครงปฏิทินของเดือนที่เลือกไว้รอได้
function emptyMonthDays(monthKey) {
  const [y, m] = monthKey.split('-').map(Number)
  const dayCount = new Date(y, m, 0).getDate()
  return Array.from({ length: dayCount }, (_, i) => ({
    date: `${monthKey}-${String(i + 1).padStart(2, '0')}`,
    shift: null,
    holiday: null,
    leave: null,
    attendance: null,
    checkIn: null,
    checkOut: null,
  }))
}

watch(
  month,
  async (value) => {
    loading.value = true
    try {
      data.value = await calendarService.getMonth({ month: value })
    } catch {
      // backend /calendar ยังไม่พร้อม — ไม่ต้อง toast error เพราะเป็นสถานะที่คาดไว้อยู่แล้ว (รอ backend)
      // ไม่ใช่ปัญหาเครือข่ายที่ผู้ใช้ควรต้องรู้ตอนนี้
      data.value = { month: value, days: emptyMonthDays(value) }
    } finally {
      // เลือกวันนี้ถ้าอยู่ในเดือนที่แสดง ไม่งั้นเลือกวันที่ 1 ของเดือนนั้น
      selected.value = value === toMonthKey() ? today : `${value}-01`
      loading.value = false
    }
  },
  { immediate: true },
)
</script>
