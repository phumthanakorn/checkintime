import { onMounted, onUnmounted, ref } from 'vue'

/** เวลาปัจจุบันแบบ reactive อัปเดตทุก `interval` ms */
export function useNow(interval = 1000) {
  const now = ref(new Date())
  let timer

  onMounted(() => {
    timer = setInterval(() => {
      now.value = new Date()
    }, interval)
  })

  onUnmounted(() => clearInterval(timer))

  return now
}
