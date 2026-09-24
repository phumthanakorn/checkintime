<template>
  <div class="flex gap-1 rounded-2xl bg-slate-100 p-1">
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      class="flex flex-1 items-center justify-center gap-1 whitespace-nowrap rounded-xl px-2 py-2 text-xs transition"
      :class="model === option.value ? 'bg-card font-semibold text-ink shadow-sm' : 'text-ink-muted'"
      @click="model = option.value"
    >
      {{ option.label }}
      <span
        v-if="option.count"
        class="min-w-[18px] rounded-full px-1 font-display text-[10px] font-bold leading-[18px]"
        :class="model === option.value ? 'bg-ink text-white' : 'bg-slate-200 text-ink-muted'"
      >
        {{ option.count }}
      </span>
    </button>
  </div>
</template>

<script setup>
const model = defineModel({ type: [String, Number], required: true })

defineProps({
  /** [{ value, label, count? }] */
  options: { type: Array, required: true },
})
</script>
