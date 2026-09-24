<template>
  <span
    class="inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold"
    :class="TONES[tone]"
  >
    <span class="h-1.5 w-1.5 rounded-full bg-current" />
    {{ label }}
  </span>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  tone: {
    type: String,
    default: 'neutral',
    validator: (v) => ['success', 'warning', 'danger', 'info', 'neutral'].includes(v),
  },
})

const TONES = {
  success: 'bg-status-checkin/10 text-status-checkin',
  warning: 'bg-status-checkout/10 text-amber-600',
  danger: 'bg-status-outside/10 text-status-outside',
  info: 'bg-metric-blue/10 text-metric-blue',
  neutral: 'bg-slate-100 text-ink-muted',
}
</script>
