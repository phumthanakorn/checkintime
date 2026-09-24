<template>
  <!-- จุด 3 จุดเด้งเป็นคลื่น ใช้สีตาม currentColor (ใส่ class สีได้ เช่น text-status-checkin) -->
  <span class="loading-dots inline-flex items-center" :style="{ gap: `${dot * 0.6}px` }" role="status" aria-label="กำลังโหลด">
    <span v-for="i in 3" :key="i" class="dot rounded-full bg-current" :style="{ width: `${dot}px`, height: `${dot}px`, animationDelay: `${(i - 1) * 0.16}s` }" />
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  /** sm = ในปุ่มเล็ก, md = ในปุ่มทั่วไป, lg = กลางหน้าจอ/การ์ด */
  size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg'].includes(v) },
})

const dot = computed(() => ({ sm: 6, md: 8, lg: 11 })[props.size])
</script>

<style scoped>
.dot {
  animation: dot-wave 1.1s ease-in-out infinite both;
}

@keyframes dot-wave {
  0%,
  70%,
  100% {
    transform: translateY(0) scale(0.75);
    opacity: 0.35;
  }
  35% {
    transform: translateY(-70%) scale(1);
    opacity: 1;
  }
}

/* ลดการเคลื่อนไหว: กะพริบอย่างเดียว ไม่เด้ง */
@media (prefers-reduced-motion: reduce) {
  .dot {
    animation-name: dot-fade;
  }

  @keyframes dot-fade {
    0%,
    100% {
      opacity: 0.35;
    }
    50% {
      opacity: 1;
    }
  }
}
</style>
