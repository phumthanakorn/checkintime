<template>
  <form id="leave-request-form" class="space-y-5" novalidate @submit.prevent="handleSubmit">
    <!-- ประเภทการลา -->
    <div role="radiogroup" aria-labelledby="leave-type-label">
      <p id="leave-type-label" class="mb-2 text-sm font-medium text-ink">ประเภทการลา</p>
      <div class="grid grid-cols-3 gap-2">
        <button
          v-for="option in typeOptions"
          :key="option.type"
          type="button"
          class="flex flex-col items-center gap-1 rounded-2xl border-2 p-3 transition"
          :class="form.leaveType === option.type ? 'border-status-checkin bg-status-checkin/5' : 'border-slate-100'"
          @click="form.leaveType = option.type"
        >
          <span class="flex h-9 w-9 items-center justify-center rounded-full" :class="option.meta.bg">
            <v-icon :icon="option.meta.icon" size="18" :class="option.meta.color" />
          </span>
          <span class="text-xs font-semibold text-ink">{{ option.meta.label }}</span>
          <span class="text-[10px] text-ink-muted">เหลือ {{ option.available }} วัน</span>
        </button>
      </div>
    </div>

    <!-- ช่วงวันที่ -->
    <DateRangeField v-model:start="form.startDate" v-model:end="form.endDate" title="เลือกวันลา" />

    <!-- ช่วงเวลา (เลือกครึ่งวันได้เมื่อลาวันเดียว) -->
    <div v-if="isSingleDay">
      <span class="mb-2 block text-sm font-medium text-ink">ช่วงเวลา</span>
      <SegmentedTabs v-model="form.period" :options="periodOptions" />
    </div>

    <div>
      <span class="mb-2 block text-sm font-medium text-ink">เหตุผลการลา <span class="text-status-outside">*</span></span>
      <textarea
        v-model="form.reason"
        rows="3"
        maxlength="200"
        placeholder="เช่น ไปพบแพทย์, ธุระครอบครัว"
        class="w-full resize-none rounded-xl border border-slate-200 bg-app-bg px-4 py-3 text-sm text-ink outline-none transition placeholder:text-slate-400 focus:border-status-checkin focus:bg-card"
      />
      <p class="text-right text-[11px] text-ink-muted">{{ form.reason.length }}/200</p>
    </div>

    <AttachmentField
      v-model="form.attachments"
      :hint="
        form.leaveType === LEAVE_TYPES.SICK
          ? 'ลาป่วยตั้งแต่ 3 วันขึ้นไป ควรแนบใบรับรองแพทย์'
          : 'เช่น ใบนัดหมาย หรือเอกสารประกอบการลา'
      "
    />

    <!-- สรุป -->
    <div class="flex items-center justify-between rounded-2xl bg-app-bg px-4 py-3">
      <span class="text-sm text-ink-muted">รวมวันลา</span>
      <span class="font-display text-lg font-bold text-ink">{{ days > 0 ? formatLeaveDays(days) : '-' }}</span>
    </div>

    <p v-if="error" class="flex items-center gap-1 text-xs text-status-outside">
      <v-icon icon="mdi-alert-circle-outline" size="16" />
      {{ error }}
    </p>
  </form>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import SegmentedTabs from '@/components/common/SegmentedTabs.vue'
import AttachmentField from '@/components/common/AttachmentField.vue'
import DateRangeField from '@/components/common/DateRangeField.vue'
import { LEAVE_PERIOD_LABELS, LEAVE_PERIODS, LEAVE_TYPE_META, LEAVE_TYPES } from '@/utils/constants'
import { countWeekdays } from '@/utils/dates'
import { formatLeaveDays, toDateKey } from '@/utils/formatters'

const props = defineProps({
  /** [{ type, remaining, pending }] */
  balances: { type: Array, default: () => [] },
})

const emit = defineEmits(['submit'])

const today = toDateKey()
const form = reactive({
  leaveType: LEAVE_TYPES.VACATION,
  startDate: today,
  endDate: today,
  period: LEAVE_PERIODS.FULL,
  reason: '',
  attachments: [],
})
const error = ref('')

const typeOptions = computed(() =>
  Object.values(LEAVE_TYPES).map((type) => {
    const balance = props.balances.find((b) => b.type === type)
    return { type, meta: LEAVE_TYPE_META[type], available: balance ? balance.remaining - balance.pending : 0 }
  }),
)

const periodOptions = Object.values(LEAVE_PERIODS).map((value) => ({ value, label: LEAVE_PERIOD_LABELS[value] }))

const isSingleDay = computed(() => form.startDate === form.endDate)

const days = computed(() => {
  if (!form.startDate || !form.endDate || form.endDate < form.startDate) return 0
  const weekdays = countWeekdays(form.startDate, form.endDate)
  return isSingleDay.value && form.period !== LEAVE_PERIODS.FULL ? weekdays * 0.5 : weekdays
})

// เลื่อนวันสิ้นสุดตามเมื่อเลือกวันเริ่มหลังวันสิ้นสุด และกลับเป็นเต็มวันเมื่อลาหลายวัน
watch(
  () => form.startDate,
  (start) => {
    if (form.endDate < start) form.endDate = start
  },
)
watch(isSingleDay, (single) => {
  if (!single) form.period = LEAVE_PERIODS.FULL
})

function validate() {
  const available = typeOptions.value.find((o) => o.type === form.leaveType)?.available ?? 0
  if (!form.startDate || !form.endDate) return 'กรุณาเลือกวันที่'
  if (form.endDate < form.startDate) return 'วันที่สิ้นสุดต้องไม่ก่อนวันที่เริ่ม'
  if (days.value <= 0) return 'ช่วงวันที่เลือกเป็นวันหยุดทั้งหมด'
  if (days.value > available) return `วันลาคงเหลือไม่พอ (เหลือ ${available} วัน)`
  if (!form.reason.trim()) return 'กรุณาระบุเหตุผลการลา'
  return ''
}

function handleSubmit() {
  error.value = validate()
  if (!error.value) emit('submit', { ...form, reason: form.reason.trim() })
}

function reset() {
  Object.assign(form, {
    leaveType: LEAVE_TYPES.VACATION,
    startDate: today,
    endDate: today,
    period: LEAVE_PERIODS.FULL,
    reason: '',
    attachments: [],
  })
  error.value = ''
}

defineExpose({ reset })
</script>

