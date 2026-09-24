<template>
  <!-- ช่องวันที่เริ่ม | ถึงวันที่ แตะช่องไหนก็เปิดปฏิทินเลือกช่วงวันเดียวกัน -->
  <div class="grid grid-cols-2 gap-3">
    <div v-for="field in fields" :key="field.label">
      <span class="mb-2 block text-sm font-medium text-ink">{{ field.label }}</span>
      <button
        type="button"
        class="flex h-12 w-full items-center gap-2 rounded-xl border bg-app-bg px-3.5 text-left transition"
        :class="open ? 'border-status-checkin bg-card' : 'border-slate-200'"
        @click="openPicker"
      >
        <v-icon :icon="field.icon" size="18" class="text-ink-muted" />
        <span class="truncate text-sm" :class="field.value ? 'text-ink' : 'text-slate-400'">
          {{ field.value ? formatDayMonth(field.value, true) : 'เลือกวันที่' }}
        </span>
      </button>
    </div>
  </div>

  <AppBottomSheet v-model="open" :title="title">
    <!-- สรุปช่วงที่เลือก -->
    <div class="mb-4 grid grid-cols-[1fr_auto_1fr] items-center gap-2 rounded-2xl bg-app-bg p-3">
      <div class="text-center" :class="!draft[1] && 'rounded-xl bg-card py-1 shadow-sm'">
        <p class="text-[11px] text-ink-muted">วันที่เริ่ม</p>
        <p class="text-sm font-semibold text-ink">{{ draft[0] ? formatDayMonth(draft[0], true) : '-' }}</p>
      </div>
      <v-icon icon="mdi-arrow-right" size="18" class="text-ink-muted" />
      <div class="text-center" :class="draft[0] && !draft[1] && 'rounded-xl py-1 ring-2 ring-status-checkin/40'">
        <p class="text-[11px] text-ink-muted">ถึงวันที่</p>
        <p class="text-sm font-semibold" :class="draft[1] ? 'text-ink' : 'text-slate-400'">
          {{ draft[1] ? formatDayMonth(draft[1], true) : 'แตะวันสิ้นสุด' }}
        </p>
      </div>
    </div>

    <AppDatePicker v-model="draft" mode="range" :min="min" :max="max" />

    <p class="mt-3 text-center text-xs text-ink-muted">
      <template v-if="draft[0] && draft[1]">
        รวม <b class="font-display text-ink">{{ workdays }}</b> วันทำการ (ไม่นับเสาร์-อาทิตย์)
      </template>
      <template v-else-if="draft[0]">แตะวันเดิมอีกครั้งหากลาวันเดียว</template>
      <template v-else>แตะวันที่เริ่มต้น</template>
    </p>

    <template #footer>
      <button
        type="button"
        class="flex h-12 w-full items-center justify-center rounded-2xl bg-status-checkin font-semibold text-white shadow-lg shadow-status-checkin/30 disabled:opacity-50"
        :disabled="!draft[0] || !draft[1]"
        @click="confirm"
      >
        ยืนยันช่วงวันที่
      </button>
    </template>
  </AppBottomSheet>
</template>

<script setup>
import { computed, ref } from 'vue'
import AppBottomSheet from './AppBottomSheet.vue'
import AppDatePicker from './AppDatePicker.vue'
import { countWeekdays } from '@/utils/dates'
import { formatDayMonth } from '@/utils/formatters'

/** 'YYYY-MM-DD' */
const start = defineModel('start', { type: String, default: '' })
const end = defineModel('end', { type: String, default: '' })

defineProps({
  min: { type: String, default: null },
  max: { type: String, default: null },
  title: { type: String, default: 'เลือกช่วงวันที่' },
})

const open = ref(false)
const draft = ref([null, null])

const fields = computed(() => [
  { label: 'วันที่เริ่ม', value: start.value, icon: 'mdi-calendar-start-outline' },
  { label: 'ถึงวันที่', value: end.value, icon: 'mdi-calendar-end-outline' },
])

const workdays = computed(() => (draft.value[0] && draft.value[1] ? countWeekdays(draft.value[0], draft.value[1]) : 0))

function openPicker() {
  draft.value = [start.value || null, end.value || null]
  open.value = true
}

function confirm() {
  start.value = draft.value[0]
  end.value = draft.value[1]
  open.value = false
}
</script>
