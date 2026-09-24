<template>
  <button
    type="button"
    role="switch"
    :aria-checked="model"
    :aria-label="label"
    :disabled="disabled"
    class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition-colors duration-200 disabled:opacity-40"
    :class="model ? 'bg-status-checkin' : 'bg-slate-200'"
    @click="model = !model"
  >
    <span
      class="absolute left-0.5 h-6 w-6 rounded-full bg-card shadow transition-transform duration-200"
      :class="model ? 'translate-x-5' : 'translate-x-0'"
    />
  </button>
</template>

<script setup>
const model = defineModel({ type: Boolean, default: false })

defineProps({
  label: { type: String, default: undefined },
  disabled: { type: Boolean, default: false },
})
</script>
