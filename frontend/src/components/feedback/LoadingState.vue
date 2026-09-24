<template>
  <!-- สถานะกำลังโหลดของทั้งส่วน/ทั้งหน้า -->
  <div class="flex flex-col items-center justify-center gap-4 py-12">
    <LoadingDots size="lg" class="text-status-checkin" />
    <span v-if="text" class="text-sm text-ink-muted">{{ text }}</span>
  </div>
</template>

<script setup>
import LoadingDots from './LoadingDots.vue'

defineProps({
  text: { type: String, default: 'กำลังโหลด...' },
})
</script>
