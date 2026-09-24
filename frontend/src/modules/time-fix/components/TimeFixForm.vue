<template>
  <form id="time-fix-form" class="space-y-5" novalidate @submit.prevent="handleSubmit">
    <div>
      <span class="mb-2 block text-sm font-medium text-ink">วันที่ลืมลงเวลา</span>
      <DateField v-model="form.date" :min="minDate" :max="today" title="เลือกวันที่ลืมลงเวลา" />
    </div>

    <div>
      <span class="mb-2 block text-sm font-medium text-ink">ลืมลงเวลาอะไร</span>
      <SegmentedTabs v-model="form.fixType" :options="typeOptions" />
    </div>

    <div class="grid gap-3" :class="needIn && needOut ? 'grid-cols-2' : 'grid-cols-1'">
      <div v-if="needIn">
        <span class="mb-2 block text-sm font-medium text-ink">เวลาเข้างาน</span>
        <TimeField
          v-model="form.checkIn"
          title="เวลาเข้างาน"
          icon="sign-in"
          :default-time="WORK_START_TIME"
          :shortcuts="['08:00', '08:30', '09:00']"
        />
      </div>
      <div v-if="needOut">
        <span class="mb-2 block text-sm font-medium text-ink">เวลาออกงาน</span>
        <TimeField
          v-model="form.checkOut"
          title="เวลาออกงาน"
          icon="sign-out"
          :default-time="WORK_END_TIME"
          :shortcuts="['17:30', '18:00', '18:30', '19:00']"
        />
      </div>
    </div>

    <div>
      <span class="mb-2 block text-sm font-medium text-ink">เหตุผล <span class="text-status-outside">*</span></span>
      <div class="mb-2 flex flex-wrap gap-2">
        <button
          v-for="reason in TIME_FIX_REASONS"
          :key="reason"
          type="button"
          class="rounded-full border px-3 py-1.5 text-xs transition"
          :class="
            form.reason === reason
              ? 'border-status-checkin bg-status-checkin/10 font-semibold text-status-checkin'
              : 'border-slate-200 text-ink'
          "
          @click="form.reason = reason"
        >
          {{ reason }}
        </button>
      </div>
      <textarea
        v-model="form.reason"
        rows="2"
        maxlength="200"
        placeholder="หรือพิมพ์เหตุผลเพิ่มเติม"
        class="w-full resize-none rounded-xl border border-slate-200 bg-app-bg px-4 py-3 text-sm text-ink outline-none transition placeholder:text-slate-400 focus:border-status-checkin focus:bg-card"
      />
    </div>

    <AttachmentField
      v-model="form.attachments"
      hint="เช่น รูปหน้าจอแชทงาน ใบนัดลูกค้า หรือหลักฐานการทำงาน"
    />

    <p class="flex items-start gap-1.5 rounded-2xl bg-metric-blue/10 px-3 py-2.5 text-xs text-metric-blue">
      <AppIcon name="info" :size="16" />
      คำขอจะส่งให้หัวหน้าอนุมัติ เมื่ออนุมัติแล้วเวลาจะถูกบันทึกในประวัติของคุณ
    </p>

    <p v-if="error" class="flex items-center gap-1 text-xs text-status-outside">
      <AppIcon name="warning-circle" :size="16" />
      {{ error }}
    </p>
  </form>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import DateField from '@/components/common/DateField.vue'
import TimeField from '@/components/common/TimeField.vue'
import SegmentedTabs from '@/components/common/SegmentedTabs.vue'
import AttachmentField from '@/components/common/AttachmentField.vue'
import {
  TIME_FIX_MAX_DAYS_BACK,
  TIME_FIX_REASONS,
  TIME_FIX_TYPE_LABELS,
  TIME_FIX_TYPES,
  WORK_END_TIME,
  WORK_START_TIME,
} from '@/utils/constants'
import { toDateKey } from '@/utils/formatters'

const props = defineProps({
  /** ค่าเริ่มต้น เช่น { date, fixType } เมื่อเปิดจากหน้าประวัติ */
  initial: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['submit'])

const today = toDateKey()
const minDate = toDateKey(new Date(Date.now() - TIME_FIX_MAX_DAYS_BACK * 86400000))

const emptyForm = () => ({
  date: '',
  fixType: TIME_FIX_TYPES.CHECK_OUT,
  checkIn: '',
  checkOut: '',
  reason: '',
  attachments: [],
})
const form = reactive({ ...emptyForm(), ...props.initial })
const error = ref('')

const typeOptions = Object.values(TIME_FIX_TYPES).map((value) => ({ value, label: TIME_FIX_TYPE_LABELS[value] }))
const needIn = computed(() => form.fixType !== TIME_FIX_TYPES.CHECK_OUT)
const needOut = computed(() => form.fixType !== TIME_FIX_TYPES.CHECK_IN)

function validate() {
  if (!form.date) return 'กรุณาเลือกวันที่'
  if (needIn.value && !form.checkIn) return 'กรุณาเลือกเวลาเข้างาน'
  if (needOut.value && !form.checkOut) return 'กรุณาเลือกเวลาออกงาน'
  if (needIn.value && needOut.value && form.checkOut <= form.checkIn) return 'เวลาออกงานต้องหลังเวลาเข้างาน'
  if (!form.reason.trim()) return 'กรุณาระบุเหตุผล'
  return ''
}

function handleSubmit() {
  error.value = validate()
  if (error.value) return
  emit('submit', {
    date: form.date,
    fixType: form.fixType,
    checkIn: needIn.value ? form.checkIn : null,
    checkOut: needOut.value ? form.checkOut : null,
    reason: form.reason.trim(),
    attachments: form.attachments,
  })
}
</script>
