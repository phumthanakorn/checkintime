import { onBeforeUnmount, onMounted, ref } from 'vue'

/** สถานะการเชื่อมต่ออินเทอร์เน็ต (อัปเดตทันทีเมื่อเน็ตหลุด/กลับมา) */
export function useOnline() {
  const online = ref(typeof navigator === 'undefined' ? true : navigator.onLine)
  const update = () => (online.value = navigator.onLine)

  onMounted(() => {
    window.addEventListener('online', update)
    window.addEventListener('offline', update)
  })
  onBeforeUnmount(() => {
    window.removeEventListener('online', update)
    window.removeEventListener('offline', update)
  })

  return online
}
