<template>
  <!-- แถบสถานะอินเทอร์เน็ต: แดงเมื่อหลุด, เขียวชั่วครู่เมื่อกลับมา -->
  <Transition name="slide-down">
    <div
      v-if="!online || justReconnected"
      class="fixed inset-x-0 top-0 z-[2500] mx-auto flex max-w-md items-center justify-center gap-2 px-4 pb-2 pt-[calc(0.5rem+env(safe-area-inset-top))] text-xs font-semibold text-white shadow-md"
      :class="online ? 'bg-status-checkin' : 'bg-status-done'"
      role="status"
      aria-live="polite"
    >
      <AppIcon :name="online ? 'check' : 'warning-circle'" :size="16" weight="bold" />
      {{ online ? 'กลับมาออนไลน์แล้ว' : 'ไม่มีการเชื่อมต่ออินเทอร์เน็ต' }}
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useOnline } from '@/composables/useOnline'

const online = useOnline()
const justReconnected = ref(false)
let timer = null

watch(online, (value, previous) => {
  clearTimeout(timer)
  if (value && previous === false) {
    justReconnected.value = true
    timer = setTimeout(() => (justReconnected.value = false), 2500)
  } else {
    justReconnected.value = false
  }
})
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: transform 0.25s ease;
}

.slide-down-enter-from,
.slide-down-leave-to {
  transform: translateY(-100%);
}
</style>
