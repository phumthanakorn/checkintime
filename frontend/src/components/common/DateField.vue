<template>
  <button
    type="button"
    class="flex h-12 w-full items-center gap-2 rounded-xl border bg-app-bg px-3.5 text-left transition"
    :class="open ? 'border-status-checkin bg-card' : 'border-slate-200'"
    :aria-label="ariaLabel"
    @click="openPicker"
  >
    <AppIcon name="calendar" :size="18" class="text-ink-muted" />
    <span class="truncate text-sm" :class="model ? 'text-ink' : 'text-slate-400'">
      {{ model ? formatDayMonth(model, true) : placeholder }}
    </span>
  </button>

  <AppBottomSheet v-model="open" :title="title">
    <AppDatePicker ref="pickerRef" v-model="draft" :min="min" :max="max" />

    <div class="mt-3 flex gap-2">
      <button
        v-for="shortcut in shortcuts"
        :key="shortcut.label"
        type="button"
        class="rounded-full bg-app-bg px-3 py-1.5 text-xs font-medium text-ink transition hover:bg-slate-100"
        @click="applyShortcut(shortcut.value)"
      >
        {{ shortcut.label }}
      </button>
    </div>

    <template #footer>
      <button
        type="button"
        class="flex h-12 w-full items-center justify-center rounded-2xl bg-status-checkin font-semibold text-white shadow-lg shadow-status-checkin/30 disabled:opacity-50"
        :disabled="!draft"
        @click="confirm"
      >
        {{ draft ? `เลือก ${formatThaiDate(draft)}` : 'เลือกวันที่' }}
      </button>
    </template>
  </AppBottomSheet>
</template>

<script setup>
import { computed, ref } from 'vue'
import AppBottomSheet from './AppBottomSheet.vue'
import AppDatePicker from './AppDatePicker.vue'
import { formatDayMonth, formatThaiDate, toDateKey } from '@/utils/formatters'

/** 'YYYY-MM-DD' */
const model = defineModel({ type: String, default: '' })

const props = defineProps({
  min: { type: String, default: null },
  max: { type: String, default: null },
  title: { type: String, default: 'เลือกวันที่' },
  placeholder: { type: String, default: 'เลือกวันที่' },
  ariaLabel: { type: String, default: undefined },
})

const open = ref(false)
const draft = ref(null)
const pickerRef = ref()

const shortcuts = computed(() => {
  const today = new Date()
  const tomorrow = new Date(today)
  tomorrow.setDate(today.getDate() + 1)
  return [
    { label: 'วันนี้', value: toDateKey(today) },
    { label: 'พรุ่งนี้', value: toDateKey(tomorrow) },
  ].filter((s) => (!props.min || s.value >= props.min) && (!props.max || s.value <= props.max))
})

function openPicker() {
  draft.value = model.value || null
  open.value = true
}

function applyShortcut(value) {
  draft.value = value
}

function confirm() {
  model.value = draft.value
  open.value = false
}
</script>
