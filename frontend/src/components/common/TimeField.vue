<template>
  <button
    type="button"
    class="flex h-12 w-full items-center gap-2 rounded-xl border bg-app-bg px-3.5 text-left transition"
    :class="open ? 'border-status-checkin bg-card' : 'border-slate-200'"
    :aria-label="ariaLabel"
    @click="openPicker"
  >
    <AppIcon :name="icon" :size="18" class="text-ink-muted" />
    <span class="font-display text-sm" :class="model ? 'font-semibold text-ink' : 'font-normal text-slate-400'">
      {{ model ? `${model} น.` : placeholder }}
    </span>
  </button>

  <AppBottomSheet v-model="open" :title="title">
    <p class="mb-2 text-center font-display text-4xl font-extrabold tracking-tight text-ink">{{ draft }}</p>

    <AppTimePicker ref="pickerRef" v-model="draft" :minute-step="minuteStep" />

    <div v-if="shortcuts.length" class="mt-3 flex flex-wrap justify-center gap-2">
      <button
        v-for="time in shortcuts"
        :key="time"
        type="button"
        class="rounded-full px-3 py-1.5 font-display text-xs font-semibold transition"
        :class="draft === time ? 'bg-status-checkin text-white' : 'bg-app-bg text-ink hover:bg-slate-100'"
        @click="applyShortcut(time)"
      >
        {{ time }}
      </button>
    </div>

    <template #footer>
      <button
        type="button"
        class="flex h-12 w-full items-center justify-center rounded-2xl bg-status-checkin font-semibold text-white shadow-lg shadow-status-checkin/30"
        @click="confirm"
      >
        เลือกเวลา {{ draft }} น.
      </button>
    </template>
  </AppBottomSheet>
</template>

<script setup>
import { nextTick, ref } from 'vue'
import AppBottomSheet from './AppBottomSheet.vue'
import AppTimePicker from './AppTimePicker.vue'

/** 'HH:mm' */
const model = defineModel({ type: String, default: '' })

const props = defineProps({
  title: { type: String, default: 'เลือกเวลา' },
  placeholder: { type: String, default: 'เลือกเวลา' },
  icon: { type: String, default: 'clock' },
  /** เวลาที่ใช้เมื่อยังไม่มีค่า */
  defaultTime: { type: String, default: '09:00' },
  /** ปุ่มลัด เช่น ['09:00', '18:00'] */
  shortcuts: { type: Array, default: () => [] },
  minuteStep: { type: Number, default: 1 },
  ariaLabel: { type: String, default: undefined },
})

const open = ref(false)
const draft = ref(props.defaultTime)
const pickerRef = ref()

async function openPicker() {
  draft.value = model.value || props.defaultTime
  open.value = true
  // รอ bottom sheet แสดงผลก่อน แล้วค่อยเลื่อนวงล้อไปที่ค่าปัจจุบัน
  await nextTick()
  setTimeout(() => pickerRef.value?.sync(), 50)
}

function applyShortcut(time) {
  draft.value = time // AppTimePicker จะเลื่อนวงล้อตามเอง
}

function confirm() {
  model.value = draft.value
  open.value = false
}
</script>
