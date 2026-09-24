<template>
  <div class="space-y-4">
    <PageHeader title="ปฏิทินของฉัน" subtitle="วันหยุด วันลา และกะการทำงาน" :back-to="{ name: backTo }" />

    <MonthSwitcher v-model="month" :max="maxMonth" />

    <LoadingState v-if="loading && !data" />

    <template v-else-if="data">
      <CalendarMonth :days="data.days" :selected="selected" @select="selected = $event" />

      <CalendarDayDetail :day="selectedDay" />

      <!-- วันหยุดของเดือน -->
      <section v-if="holidays.length" class="rounded-3xl bg-card p-4 shadow-sm">
        <h2 class="mb-2 text-base font-bold text-ink">วันหยุดเดือนนี้</h2>
        <ul class="list-none divide-y divide-slate-100">
          <li v-for="day in holidays" :key="day.date">
            <button type="button" class="flex w-full items-center gap-3 py-2.5 text-left" @click="selected = day.date">
              <span class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-xl bg-status-outside/10">
                <span class="text-[10px] text-status-outside">{{ formatWeekdayShort(day.date) }}</span>
                <span class="font-display text-base font-bold leading-none text-status-outside">
                  {{ Number(day.date.slice(8)) }}
                </span>
              </span>
              <span class="flex-1 text-sm text-ink">{{ day.holiday.name }}</span>
              <span v-if="day.date < today" class="text-[11px] text-ink-muted">ผ่านไปแล้ว</span>
            </button>
          </li>
        </ul>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import PageHeader from '@/components/layout/PageHeader.vue'
import MonthSwitcher from '@/components/common/MonthSwitcher.vue'
import LoadingState from '@/components/feedback/LoadingState.vue'
import CalendarMonth from '../components/CalendarMonth.vue'
import CalendarDayDetail from '../components/CalendarDayDetail.vue'
import { calendarService } from '@/api/services/calendarService'
import { useNotification } from '@/composables/useNotification'
import { shiftMonth } from '@/utils/dates'
import { formatWeekdayShort, toDateKey, toMonthKey } from '@/utils/formatters'

const route = useRoute()
const notify = useNotification()

// กลับไปหน้าที่เปิดมา (เช่น /calendar?from=leave)
const backTo = ['leave', 'profile', 'home'].includes(route.query.from) ? route.query.from : 'profile'

const today = toDateKey()
const month = ref(toMonthKey())
const maxMonth = shiftMonth(toMonthKey(), 12) // ดูล่วงหน้าได้ 1 ปี เพื่อวางแผนลา
const data = ref(null)
const loading = ref(false)
const selected = ref(today)

const selectedDay = computed(() => data.value?.days.find((d) => d.date === selected.value) ?? null)
const holidays = computed(() => data.value?.days.filter((d) => d.holiday) ?? [])

watch(
  month,
  async (value) => {
    loading.value = true
    try {
      data.value = await calendarService.getMonth({ month: value })
      // เลือกวันนี้ถ้าอยู่ในเดือนที่แสดง ไม่งั้นเลือกวันที่ 1
      selected.value = value === toMonthKey() ? today : `${value}-01`
    } catch (error) {
      notify.error(error.message)
    } finally {
      loading.value = false
    }
  },
  { immediate: true },
)
</script>
