<template>
  <div class="flex flex-col items-center justify-center gap-3 py-10">
    <v-progress-circular indeterminate color="primary" :size="size" />
    <span v-if="text" class="text-sm text-slate-500">{{ text }}</span>
  </div>
</template>

<script setup>
defineProps({
  text: { type: String, default: '' },
  size: { type: Number, default: 40 },
})
</script>
